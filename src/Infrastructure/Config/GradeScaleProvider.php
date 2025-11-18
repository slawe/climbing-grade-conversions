<?php

namespace Climb\Grades\Infrastructure\Config;

use Climb\Grades\Domain\Repository\GradeScaleDataRepository;
use Climb\Grades\Domain\Service\GradeScale;
use Climb\Grades\Domain\Service\Scale\AmericanVScale;
use Climb\Grades\Domain\Service\Scale\BrazilianTechnicalScale;
use Climb\Grades\Domain\Service\Scale\EwbankAustralianScale;
use Climb\Grades\Domain\Service\Scale\EwbankSouthAfricaScale;
use Climb\Grades\Domain\Service\Scale\FinlandScale;
use Climb\Grades\Domain\Service\Scale\FrenchFontainebleauScale;
use Climb\Grades\Domain\Service\Scale\FrenchSportScale;
use Climb\Grades\Domain\Service\Scale\NorwayScale;
use Climb\Grades\Domain\Service\Scale\PolishKurtykasScale;
use Climb\Grades\Domain\Service\Scale\SaxonScale;
use Climb\Grades\Domain\Service\Scale\UiaaScale;
use Climb\Grades\Domain\Service\Scale\UkAdjectivalScale;
use Climb\Grades\Domain\Service\Scale\UkTechnicalScale;
use Climb\Grades\Domain\Service\Scale\AmericanYdsScale;
use SplObjectStorage;

/**
 * Provides functionality to manage and retrieve grade scales.
 *
 * This class is responsible for creating and caching grade scale instances
 * associated with a specific data repository. It ensures that a single
 * instance of grade scales is reused for each repository, optimizing
 * resource usage and performance.
 */
class GradeScaleProvider
{
    /**
     * @var SplObjectStorage<GradeScaleDataRepository, GradeScale[]>
     */
    private static ?SplObjectStorage $memo = null;

    /** @var string[] */
    private const SCALES = [
        UiaaScale::class,
        FrenchSportScale::class,
        AmericanYdsScale::class,
        UkTechnicalScale::class,
        UkAdjectivalScale::class,
        SaxonScale::class,
        EwbankAustralianScale::class,
        EwbankSouthAfricaScale::class,
        FinlandScale::class,
        NorwayScale::class,
        BrazilianTechnicalScale::class,
        PolishKurtykasScale::class,
        AmericanVScale::class,
        FrenchFontainebleauScale::class,
    ];


    /**
     * Retrieves all scale instances derived from the repository provided.
     *
     * @param GradeScaleDataRepository $repo An instance of the repository containing grade scale data.
     * @return GradeScale[]
     */
    public static function all(GradeScaleDataRepository $repo): array
    {
        if (self::$memo === null) {
            self::$memo = new SplObjectStorage();
        }

        if (isset(self::$memo[$repo])) {
            /** @var GradeScale[] $scales */
            $scales = self::$memo[$repo];
            return $scales;
        }

        $scales = [];

        foreach (self::SCALES as $scale) {
            $scales[] = new $scale($repo);
        }

        self::$memo[$repo] = $scales;

        return $scales;
    }

    /**
     * Resets the memoization data, either entirely or for a specific repository.
     *
     * @param GradeScaleDataRepository|null $repo An optional repository instance. If null, all memoization data is cleared.
     * @return void
     */
    public static function reset(?GradeScaleDataRepository $repo = null): void
    {
        if (self::$memo === null) {
            return;
        }

        if ($repo === null) {
            self::$memo = null;
            return;
        }

        if (isset(self::$memo[$repo])) {
            unset(self::$memo[$repo]);
        }
    }
}