<?php
declare(strict_types=1);

namespace Climb\Tests;

use Climb\Grades\Infrastructure\Bootstrap\GradeConversion;
use Climb\Grades\Domain\Value\GradeSystem;
use PHPUnit\Framework\TestCase;

final class GradeConversionListTest extends TestCase
{
    public function test_fr_6c_plus_to_yds_and_uiaa_list(): void
    {
        $yds = GradeConversion::from('6c+', 'fr')->to(GradeSystem::YDS);
        $uiaa = GradeConversion::from('6c+', 'fr')->to(GradeSystem::UIAA);

        // expect one result in each of these two scales and specific values
        $this->assertCount(2, $yds);
        $this->assertSame('5.11b', $yds[0]->value());

        $this->assertCount(1, $uiaa);
        $this->assertSame('VIII-', $uiaa[0]->value());
    }

    public function test_toAll_excludes_source_by_default_and_can_include_it(): void
    {
        $all = GradeConversion::from('6c+', 'fr')->toAll();
        $this->assertArrayHasKey('UIAA', $all);
        $this->assertArrayHasKey('YDS', $all);
        $this->assertArrayNotHasKey('FR', $all);

        $withSource = GradeConversion::from('6c+', 'fr')->toAll(true);
        $this->assertArrayHasKey('FR', $withSource);
        $this->assertSame('6c+', $withSource['FR'][0]->value());
    }

    public function test_towards_all_is_equivalent_to_to(): void
    {
        $viaTo = GradeConversion::from('6c+', 'fr')->to(GradeSystem::YDS);
        $viaChain = GradeConversion::from('6c+', 'fr')->towards(GradeSystem::YDS)->all();

        $this->assertSameSize($viaTo, $viaChain);
        $this->assertSame($viaTo[0]->value(), $viaChain[0]->value());
    }
}
