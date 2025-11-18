<?php

namespace Climb\Grades\Domain\Service;

use Climb\Grades\Domain\Exception\{GradeNotFound, IndexOutOfRange, InvalidScaleData};
use Climb\Grades\Domain\Repository\GradeScaleDataRepository;
use Climb\Grades\Domain\Value\DifficultyIndex;
use Climb\Grades\Domain\Value\Grade;
use Climb\Grades\Domain\Value\GradeSystem;
use Normalizer;

/**
 * Base class for concrete grade scales.
 *
 * - Loads index → grade map from a GradeScaleDataRepository (CSV, DB, ...)
 * - Supports multiple textual variants in a single cell (e.g. "5a/5a+")
 * - Supports the same grade value appearing on multiple indexes (e.g. V2)
 */
abstract class AbstractGradeScale implements GradeScale
{
    /**
     * Cell delimiter used for splitting variants (override in subclass if needed).
     * @var string
     */
    protected const CELL_DELIMITER = '/';

    /**
     * Index → full grade value
     * (possibly containing multiple variants separated by "/").
     *
     * @var array<int,string>
     */
    private array $indexToGrade;

    /**
     * Normalized grade value (lowercase, single variant like "5a") → list of indexes.
     *
     * Example:
     *  "5a" => [10],
     *  "2" => [10, 11, 12] // e.g., V2 covering multiple difficulty indexes
     *
     * @var array<string,int[]>
     */
    private array $gradeToIndexes = [];

    /**
     * @var bool|null
     */
    private static ?bool $isNormalizerAvailable = null;

    /**
     * AbstractGradeScale constructor.
     *
     * @param GradeScaleDataRepository $repo
     */
    public function __construct(GradeScaleDataRepository $repo)
    {
        $this->indexToGrade = $repo->indexToGradeMap($this->system());
        $this->guardContinuousIndexing($this->indexToGrade);

        foreach ($this->indexToGrade as $index => $cell) {
            foreach ($this->parseCell($cell) as $variant) {
                $key = $this->normalized($variant);
                $this->gradeToIndexes[$key] ??= [];

                if (!in_array($index, $this->gradeToIndexes[$key], true)) {
                    $this->gradeToIndexes[$key][] = $index;
                }
            }
        }

        // sorting indexes
        foreach ($this->gradeToIndexes as &$idx) {
            sort($idx);
        }
        unset($idx);
    }

    /**
     * Concrete scale must declare which GradeSystem it represents.
     *
     * @return GradeSystem
     */
    abstract public function system(): GradeSystem;

    /**
     * Same as toIndex() but with an explicit policy (LOWEST/MIDDLE/HIGHEST).
     *
     * @param Grade $grade
     * @param PrimaryIndexPolicy $policy
     * @return DifficultyIndex
     */
    public function toIndexWithPolicy(Grade $grade, PrimaryIndexPolicy $policy): DifficultyIndex
    {
        $key = $this->normalized($grade->value());

        if (!isset($this->gradeToIndexes[$key])) {
            throw new GradeNotFound("Unknown grade: {$grade->value()}");
        }

        $indexes = $this->gradeToIndexes[$key];
        $primary = $this->pickPrimaryIndex($indexes, $policy);

        return new DifficultyIndex($primary);
    }

    /**
     * Convert a Grade into a single canonical DifficultyIndex.
     *
     * @param Grade $grade
     * @return DifficultyIndex
     */
    public function toIndex(Grade $grade): DifficultyIndex
    {
        return $this->toIndexWithPolicy($grade, PrimaryIndexPolicy::LOWEST);
    }

    /**
     * Return all DifficultyIndex values for the given grade,
     * in cases where the same grade covers multiple levels of difficulty.
     *
     * @param Grade $grade
     * @return DifficultyIndex[]
     */
    public function toAllIndexes(Grade $grade): array
    {
        $key = $this->normalized($grade->value());

        if (!isset($this->gradeToIndexes[$key])) {
            throw new GradeNotFound("Unknown grade: {$grade->value()}");
        }

        return array_map(static fn(int $i) => new DifficultyIndex($i), $this->gradeToIndexes[$key]);
    }

    /**
     * Convert DifficultyIndex back into a Grade in this scale.
     * First variant from the grade cell (e.g., "7/7+" → "7").
     *
     * @param DifficultyIndex $index
     * @return Grade
     */
    public function fromIndex(DifficultyIndex $index): Grade
    {
        $variants = $this->variantsFromIndex($index);

        if ($variants === []) {
            throw new IndexOutOfRange("Index out of range: {$index->value()}");
        }

        return new Grade($variants[0], $this->system()->value);
    }

    /**
     * Return all textual variants of grades for given index (e.g., "7/7+" → ["7","7+"]).
     *
     * @param DifficultyIndex $index
     * @return string[]
     */
    public function variantsFromIndex(DifficultyIndex $index): array
    {
        $cell = $this->gradeCell($index->value());
        return $cell === null ? [] : $this->parseCell($cell);
    }

    /**
     * Policy hook – you can override it at a specific scale if you want it differently.
     *
     * @param array $sortedIndexes
     * @param PrimaryIndexPolicy $policy
     * @return int
     */
    protected function pickPrimaryIndex(array $sortedIndexes, PrimaryIndexPolicy $policy): int
    {
        return match ($policy) {
            PrimaryIndexPolicy::LOWEST => current($sortedIndexes),
            PrimaryIndexPolicy::HIGHEST => end($sortedIndexes),
            PrimaryIndexPolicy::MIDDLE => $sortedIndexes[(int) floor((count($sortedIndexes) - 1) / 2)],

        };
    }

    /**
     * Expose a single cell safely to subclass.
     *
     * @param int $i
     * @return string|null
     */
    protected function gradeCell(int $i): ?string
    {
        return $this->indexToGrade[$i] ?? null;
    }

    /**
     * Splits a cell into textual variants (override to change parsing rules).
     *
     * @param string $cell
     * @return array
     */
    protected function parseCell(string $cell): array
    {
        $parts = array_map('trim', explode(static::CELL_DELIMITER, $cell));
        return array_values(array_filter($parts, static fn($s) => $s !== ''));
    }


    /**
     * Normalizes the given string by trimming, converting to lowercase,
     * and applying Unicode normalization if available.
     *
     * @param string $v The input string to be normalized.
     * @return string The normalized string.
     */
    private function normalized(string $v): string
    {
        $v = trim($v);

        if (self::$isNormalizerAvailable === null) {
            self::$isNormalizerAvailable = class_exists(Normalizer::class);
        }

        if (self::$isNormalizerAvailable) {
            $v = Normalizer::normalize($v, Normalizer::FORM_C);
        }

        return mb_strtolower(trim($v));
    }

    /**
     * Ensure map keys are 1..N continuous integers (typical CSV rows).
     *
     * @param array $map
     * @return void
     */
    private function guardContinuousIndexing(array $map): void
    {
        $keys = array_keys($map);

        // an empty map is ok
        if ($keys === []) {
            return;
        }

        // all must be the same
        foreach ($keys as $k) {
            if (!is_int($k)) {
                throw new InvalidScaleData("Scale map must use integer keys starting from 1.");
            }
        }

        // continuous 1..N
        for ($i = 1; $i <= count($keys); $i++) {
            if (!array_key_exists($i, $map)) {
                throw new InvalidScaleData("Scale map must be continuous (missing index {$i}).");
            }
        }
    }
}