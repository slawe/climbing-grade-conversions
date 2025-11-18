<?php
declare(strict_types=1);

namespace Climb\Tests;

use Climb\Grades\Infrastructure\Bootstrap\GradeConversion;
use Climb\Grades\Domain\Service\PrimaryIndexPolicy;
use Climb\Grades\Domain\Service\TargetVariantPolicy;
use Climb\Grades\Domain\Value\GradeSystem;
use PHPUnit\Framework\TestCase;

final class GradeConversionSinglePolicyTest extends TestCase
{
    public function test_fr_7a_to_br_uses_target_variant_policy(): void
    {
        // in CSV: FR "7a" → BR "7c/8a"
        $first = GradeConversion::from('7a', 'fr')
            ->towards(GradeSystem::BR)
            ->single(PrimaryIndexPolicy::LOWEST, TargetVariantPolicy::FIRST);

        $last = GradeConversion::from('7a', 'fr')
            ->towards(GradeSystem::BR)
            ->single(PrimaryIndexPolicy::LOWEST, TargetVariantPolicy::LAST);

        $this->assertNotNull($first);
        $this->assertSame('7c', $first->value());

        $this->assertNotNull($last);
        $this->assertSame('8a', $last->value());
    }

    public function test_fr_7a_to_br_chain_all_matches_to_list(): void
    {
        $list = GradeConversion::from('7a', 'fr')->to(GradeSystem::BR);
        $chainList = GradeConversion::from('7a', 'fr')->towards(GradeSystem::BR)->all();

        $this->assertSameSize($list, $chainList);
        $this->assertSame(
            array_map(fn($g) => $g->value(), $list),
            array_map(fn($g) => $g->value(), $chainList)
        );
    }
}
