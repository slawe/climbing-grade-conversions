<?php

namespace Climb\Grades\Infrastructure\Config;

use Climb\Grades\Domain\Repository\GradeScaleDataRepository;
use Climb\Grades\Domain\Service\GradeConversionService;
use Climb\Grades\Infrastructure\Persistence\Cache\CacheRepository;
use Climb\Grades\Infrastructure\Persistence\Csv\CsvGradeScaleDataRepository;

/**
 * Composition root for GradeConversionService.
 *
 * - Provides default configuration (CSV + cache + registration of all scales).
 * - Allows one-time override configuration of the repository.
 */
final class GradeServices
{
    /**
     * Optional global override for the data repository.
     *
     * @var GradeScaleDataRepository|null
     */
    private static ?GradeScaleDataRepository $repoOverride = null;

    /**
     * Lazily created, cached GradeConversionService instance.
     *
     * @var GradeConversionService|null
     */
    private static ?GradeConversionService $svc = null;

    /**
     * Main input to get a GradeConversionService instance.
     *
     * - Uses lazy initialization
     * - caches the instance during the process (except when passing adhoc $repo)
     *
     * @param GradeScaleDataRepository|null $repo custom repo (ignored if there is a global override)
     */
    public static function conversion(?GradeScaleDataRepository $repo = null): GradeConversionService
    {
        // If the user does not forward an adhoc repo, and we already have a cached service - return it
        if ($repo === null && self::$svc !== null) {
            return self::$svc;
        }

        $effectiveRepo = self::resolveRepository($repo);
        $service = new GradeConversionService(...GradeScaleProvider::all($effectiveRepo));

        // Cache the service for future calls with the same repo
        if ($repo === null) {
            self::$svc = $service;
        }

        return $service;
    }

    /**
     * One-time configuration (optional): override the default CSV + cache repo.
     * Useful for testing or when the default CSV path is not suitable.
     *
     * @param GradeScaleDataRepository $repo
     * @return void
     */
    public static function useRepository(GradeScaleDataRepository $repo): void
    {
        self::$repoOverride = $repo;
        self::$svc = null;

    }

    /**
     * One-time configuration (optional): uses CSV path instead of default.
     *
     * @param string $path
     * @return void
     */
    public static function useCsv(string $path): void
    {
        $csv = new CsvGradeScaleDataRepository($path);
        self::useRepository(new CacheRepository($csv));
    }

    /**
     * Test/helper: reset global overrides and cached service.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$repoOverride = null;
        self::$svc = null;
    }

    /**
     *  Determine which repo to use:
     *  1) global override, if it exists
     *  2) specific forwarded repo
     *  3) default CSV + cache
     *
     * @param GradeScaleDataRepository|null $repo
     * @return GradeScaleDataRepository
     */
    private static function resolveRepository(?GradeScaleDataRepository $repo): GradeScaleDataRepository
    {
        if (self::$repoOverride !== null) {
            return self::$repoOverride;
        }

        if ($repo !== null) {
            return $repo;
        }

        $csv = new CsvGradeScaleDataRepository(GradeConfig::csvPath());

        return new CacheRepository($csv);
    }
}