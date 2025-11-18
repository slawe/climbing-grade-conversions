<?php

namespace Climb\Grades\Domain\Service;

use Climb\Grades\Domain\Exception\InvalidScaleData;
use Climb\Grades\Domain\Value\Grade;
use Climb\Grades\Domain\Value\GradeSystem;

final class GradeConversionService
{
    /** @var array<string, GradeScale> $scales keyed by GradeSystem value (e.g. 'FR','UIAA',...) */
    private array $scales = [];

    /**
     * GradeConversionService constructor.
     *
     * @param GradeScale ...$scales
     */
    public function __construct(GradeScale ...$scales)
    {
        foreach ($scales as $scale) {
            $this->scales[$scale->system()->value] = $scale;
        }
    }

    /**
     * Converts from $from to target $to
     * and returns ALL variants (range) as a list of Grade objects.
     *
     * @param Grade $from
     * @param GradeSystem $to
     * @return Grade[]
     */
    public function convert(Grade $from, GradeSystem $to): array
    {
        $sourceSystem = GradeSystem::from(strtoupper($from->system()));
        $sourceScale  = $this->scaleOf($sourceSystem);
        $targetScale = $this->scaleOf($to);

        $indexes = $sourceScale->toAllIndexes($from);
        $seen = [];
        $out = [];

        foreach ($indexes as $index) {
            // all variants in the target scale on that index (e.g. "7/7+" → ["7","7+"])
            foreach ($targetScale->variantsFromIndex($index) as $val) {
                $key = mb_strtolower($val);

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;

                $out[] = new Grade($val, $to->value);
            }
        }

        return $out;
    }

    /**
     * Return ONE result, choosing the primary index in the original scale according to the policy
     * (LOWEST/MIDDLE/HIGHEST). If the target has no variants at that index, return null.
     *
     * @param Grade $from
     * @param GradeSystem $to
     * @param PrimaryIndexPolicy $sourcePolicy
     * @param TargetVariantPolicy $targetPolicy
     * @return Grade|null
     */
    public function convertOne(
        Grade $from,
        GradeSystem $to,
        PrimaryIndexPolicy $sourcePolicy = PrimaryIndexPolicy::LOWEST,
        TargetVariantPolicy $targetPolicy = TargetVariantPolicy::FIRST
    ): ?Grade {
        $sourceSystem = GradeSystem::from(strtoupper($from->system()));
        $sourceScale  = $this->scaleOf($sourceSystem);

        // choose one (primary) index in a source scale
        $index = $sourceScale->toIndexWithPolicy($from, $sourcePolicy);

        // convert that index into the target scale and take the first textual variant
        $targetScale = $this->scaleOf($to);
        $variants = $targetScale->variantsFromIndex($index);

        if ($variants === []) {
            return null;
        }

        $pick = match ($targetPolicy) {
            TargetVariantPolicy::FIRST => current($variants),
            TargetVariantPolicy::LAST => end($variants),
            TargetVariantPolicy::MIDDLE => $variants[(int) floor((count($variants) - 1) / 2)],
        };

        return new Grade($pick, $to->value);
    }

    /**
     * Convert given grade to all registered systems.
     *
     * @return array<string, Grade> associative: ['UIAA' => Grade(...), 'YDS' => Grade(...), ...]
     */
    public function convertToAll(Grade $from, bool $includeSource = false): array
    {
        $result = [];
        $sourceSystem = strtoupper($from->system());

        foreach ($this->scales as $system => $scale) {
            if ($system === $sourceSystem) {
                if ($includeSource) {
                    // return EXACTLY what the user entered (one Grade)
                    $result[$system] = [new Grade($from->value(), $system)];
                }
                continue;   // we don't call convert() for the source system
            }

            $result[$system] = $this->convert($from, GradeSystem::from($system));
        }

        return $result;
    }

    /**
     *
     *
     * @param GradeSystem $system
     * @return GradeScale
     */
    private function scaleOf(GradeSystem $system): GradeScale
    {
        $key = $system->value;

        if (!isset($this->scales[$key])) {
            throw new InvalidScaleData("Scale not registered: {$key}");
        }

        return $this->scales[$key];
    }
}
