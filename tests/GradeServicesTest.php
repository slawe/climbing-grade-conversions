<?php

namespace Climb\Tests;

use Climb\Grades\Domain\Repository\GradeScaleDataRepository;
use Climb\Grades\Domain\Value\Grade;
use Climb\Grades\Domain\Value\GradeSystem;
use Climb\Grades\Infrastructure\Config\GradeConfig;
use Climb\Grades\Infrastructure\Config\GradeServices;
use PHPUnit\Framework\TestCase;

class GradeServicesTest extends TestCase
{
    protected function setUp(): void
    {
        GradeServices::reset();
    }

    public function test_default_conversion_uses_csv_path_from_config(): void
    {
        $service = GradeServices::conversion();

        $fr = new Grade('6c+', 'FR');

        $all = $service->convertToAll($fr);

        self::assertArrayHasKey('UIAA', $all);
        self::assertArrayHasKey('YDS', $all);
    }

    public function test_useCsv_overrides_default_path(): void
    {
        // ideally, you would have another test CSV here, but
        // at least you can check that it doesn't crash and returns the same keys
        GradeServices::useCsv(GradeConfig::csvPath());

        $service = GradeServices::conversion();
        $fr = new Grade('6c+', 'FR');
        $all = $service->convertToAll($fr);

        self::assertArrayHasKey('UIAA', $all);
        self::assertArrayHasKey('YDS', $all);
    }

    public function test_useRepository_can_inject_custom_repo(): void
    {
        $dummyRepo = new class implements GradeScaleDataRepository {
            public function indexToGradeMap(GradeSystem $system): array
            {
                // minimal “fake” – only one index for the requested system
                return [1 => 'X'];
            }
        };

        GradeServices::useRepository($dummyRepo);

        $service = GradeServices::conversion();
        $grade = new Grade('X', 'FR');

        $all = $service->convertToAll($grade, includeSource: true);

        self::assertArrayHasKey('FR', $all);
        self::assertSame('X', $all['FR'][0]->value());
    }
}