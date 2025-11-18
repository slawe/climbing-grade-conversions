<?php

use Climb\Grades\Domain\Service\PrimaryIndexPolicy;
use Climb\Grades\Domain\Service\TargetVariantPolicy;
use Climb\Grades\Domain\Value\GradeSystem;
use Climb\Grades\Infrastructure\Bootstrap\GradeConversion;

require '../vendor/autoload.php';

$yds = GradeConversion::from('6c+', 'fr')->to(GradeSystem::BR);
$br = GradeConversion::from('7a','fr')->towards(GradeSystem::BR)->single(PrimaryIndexPolicy::LOWEST, TargetVariantPolicy::LAST);
$all = GradeConversion::from('6c+', 'fr')->toAll(true);

echo '<pre>';

print_r($yds);
print_r($br);
print_r($all);