<?php

namespace Climb\Grades\Infrastructure\Bootstrap;

use ArrayIterator;
use Climb\Grades\Domain\Service\ConversionChain;
use Climb\Grades\Domain\Service\GradeConversionService;
use Climb\Grades\Domain\Value\Grade;
use Climb\Grades\Domain\Value\GradeSystem;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * High-level chain for fluent conversions, wrapping the domain GradeConversionService.
 *
 * Methods:
 * ->to(GradeSystem $target): Grade[]
 * ->toAll(bool $includeSource = false): array<string, Grade[]>
 * ->towards(GradeSystem $target): ConversionChain (domain chain)
 *
 * Implements IteratorAggregate & JsonSerializable for convenience.
 */
class GradeConversionChain implements IteratorAggregate, JsonSerializable
{
    /**
     * The domain service that actually does the conversion.
     *
     * @var GradeConversionService
     */
    private GradeConversionService $service;

    /**
     * The original Grade to be converted.
     *
     * @var Grade
     */
    private Grade $grade;

    /**
     * GradeConversionChain constructor.
     *
     * @param GradeConversionService $service
     * @param Grade $grade
     */
    public function __construct(GradeConversionService $service, Grade $grade)
    {
        $this->service = $service;
        $this->grade   = $grade;
    }

    /**
     * Convert the original Grade to the target system.
     *
     * @param GradeSystem $target
     * @return Grade[]
     */
    public function to(GradeSystem $target): array
    {
        return $this->service->convert($this->grade, $target);
    }

    /**
     * Convert the original Grade to all possible target systems.
     *
     * @param bool $includeSource
     * @return array<string, Grade[]>
     */
    public function toAll(bool $includeSource = false): array
    {
        return $this->service->convertToAll($this->grade, $includeSource);
    }

    /**
     * Return a ConversionChain, so you can call ->all() or ->single(policy).
     *
     * @param GradeSystem $target
     * @return ConversionChain
     */
    public function towards(GradeSystem $target): ConversionChain
    {
        return new ConversionChain($this->service, $this->grade, $target);
    }

    /**
     * Allow foreach directly on the chain (iterates .all()).
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        foreach ($this->toAll(true) as $grades) {
            foreach ($grades as $grade) {
                yield $grade;
            }
        }
    }

    /**
     * Allow json_encode directly on the chain (returns .all()).
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->toAll(true);
    }
}