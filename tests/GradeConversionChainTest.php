<?php

namespace Climb\Tests;

use Climb\Grades\Domain\Service\PrimaryIndexPolicy;
use Climb\Grades\Domain\Service\TargetVariantPolicy;
use Climb\Grades\Domain\Value\GradeSystem;
use Climb\Grades\Infrastructure\Bootstrap\GradeConversion;
use Climb\Grades\Infrastructure\Config\GradeServices;
use PHPUnit\Framework\TestCase;

class GradeConversionChainTest extends TestCase
{
    protected function setUp(): void
    {
        // ensure a clean global state before each test
        GradeServices::reset();
    }

    public function test_single_uses_domain_chain_and_respects_policies(): void
    {
        $chain = GradeConversion::from('7a', 'fr');

        $single = $chain->towards(GradeSystem::BR)->single(
            PrimaryIndexPolicy::LOWEST,
            TargetVariantPolicy::LAST
        );

        self::assertNotNull($single);
        self::assertSame('BR', $single->system());
        self::assertSame('8a', $single->value());
    }

    public function test_jsonSerialize_returns_toAll_includeSource_true(): void
    {
        $chain = GradeConversion::from('6c+', 'fr');

        $serialized = $chain->jsonSerialize();

        self::assertIsArray($serialized);
        self::assertArrayHasKey('FR', $serialized);
        self::assertArrayHasKey('UIAA', $serialized);
        self::assertArrayHasKey('YDS', $serialized);
    }
}