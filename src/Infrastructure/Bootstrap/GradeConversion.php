<?php

namespace Climb\Grades\Infrastructure\Bootstrap;

use Climb\Grades\Domain\Value\Grade;
use Climb\Grades\Infrastructure\Config\GradeServices;

/**
 * User-friendly façade for grade conversion.
 *
 * Usage:
 * GradeConversion::from('6c+', 'fr')->to(GradeSystem::YDS);
 */
final class GradeConversion
{
    /**
     * Starting point for conversions.
     *
     * @param string $value eg "6c+"
     * @param string $system eg "fr" (case-insensitive)
     * @return GradeConversionChain
     */
    public static function from(string $value, string $system): GradeConversionChain
    {
        $service = GradeServices::conversion();
        $grade = new Grade($value, $system);

        return new GradeConversionChain($service, $grade);
    }
}