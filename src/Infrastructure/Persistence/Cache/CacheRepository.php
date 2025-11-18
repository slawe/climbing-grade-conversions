<?php

namespace Climb\Grades\Infrastructure\Persistence\Cache;

use Climb\Grades\Domain\Repository\GradeScaleDataRepository;
use Climb\Grades\Domain\Value\GradeSystem;

/**
 * Caches the results of the repository's indexToGradeMap method to optimize performance.
 */
final class CacheRepository implements GradeScaleDataRepository
{
    /** @var array<string, array<int, string>> */
    private array $cache = [];


    /**
     * CacheRepository constructor.
     *
     * @param GradeScaleDataRepository $repository The repository instance for grade scale data.
     * @return void
     */
    public function __construct(private readonly GradeScaleDataRepository $repository) {}


    /**
     * Maps index values to their corresponding grades based on the provided grade system.
     *
     * @param GradeSystem $system The grade system to be used for mapping index to grades.
     * @return array An associative array where the keys are indexes and the values are the corresponding grades.
     */
    public function indexToGradeMap(GradeSystem $system): array
    {
        $k = $system->value;

        if (!isset($this->cache[$k])) {
            $this->cache[$k] = $this->repository->indexToGradeMap($system);
        }

        return $this->cache[$k];
    }
}