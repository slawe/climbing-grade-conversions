# Climbing Grade Conversions

Small PHP library for converting rock climbing grades between multiple grading systems.
It started as a DDD / clean-architecture learning playground and evolved into a reusable package.

## Supported Grading Systems

Currently supported (out of the box):

- French Sport Scale (FR) - "6a", "7c+", etc.
- UIAA Scale (UIAA) - "VI+", "IX-", etc.
- Yosemite Decimal System (YDS) - "5.10a", "5.12d", etc.
- British Technical (UK-tech) - "4a", "6c", etc.
- British Adjectival (UK-adj) - "VS", "E3", etc.
- Saxon Scale (SAXON) - "VIIa", "IXc", etc.
- Australian Ewbank (AU) - "18", "24", etc.
- South African Ewbank (SA) - "19", "25", etc.
- Finnish Scale (FIN) - "6-", "7+", etc.
- Norwegian Scale (NO) - "6", "7+", etc.
- Brazilian Technical (BR) - "VIsup", "8b", etc.
- Polish Kurtyka's Scale (PO) - "VI.1+", "VI.4", etc.
- American Hueco/V-Scale (V) - "V3", "V7", etc.
- French Fontainebleau (FONT) - "7A+", "6C", etc.
- ..._(You can add more by implementing a scale class and registering it.)_

---

## Installation

```bash
composer require slawe/climbing-grade-conversions
```
or
```text
git clone git@github.com:slawe/climbing-grade-conversions.git
cd climbing-grade-conversions
composer install
```

> The package ships with a CSV-backed repository by default.
> Configure the CSV path via `Climb\Grades\Infrastructure\Config\GradeConfig::csvPath()`.

---

## Basic Usage

Convert between any supported grading systems with a simple and fluent API:

```php
use Climb\Grades\Infrastructure\Bootstrap\GradeConversion;
use Climb\Grades\Domain\Value\GradeSystem;

// Convert a French 6c+ to YDS
$ydsGrades = GradeConversion::from('6c+', 'fr')->to(GradeSystem::YDS);
echo $ydsGrades[0]->value(); // "5.11b"

// Convert a French 7a to all other systems
$allGrades = GradeConversion::from('7a', 'fr')->toAll();

// Include the source system in the result
$allWithSource = GradeConversion::from('7a', 'fr')->toAll(includeSource: true);

// Get a single result with explicit policies (useful when multiple grade mappings exist)
use Climb\Grades\Domain\Service\PrimaryIndexPolicy;
use Climb\Grades\Domain\Service\TargetVariantPolicy;

$singleGrade = GradeConversion::from('7a', 'fr')
    ->towards(GradeSystem::BR)
    ->single(
        PrimaryIndexPolicy::LOWEST,
        TargetVariantPolicy::LAST
    );
```

---

## Advanced Usage

### Using a Different CSV Data Source

You can use a different CSV data source by providing your own path:

```php
use Climb\Grades\Infrastructure\Config\GradeServices;

// Global configuration (applies to all future conversions)
GradeServices::useCsv('/path/to/your/custom-grades.csv');

// Then use the normal API
$grades = GradeConversion::from('6c+', 'fr')->to(GradeSystem::YDS);
```

### Using a Custom Data Repository

For advanced use cases, you can implement your own `GradeScaleDataRepository` (e.g., database-backed):

```php
use Climb\Grades\Infrastructure\Config\GradeServices;

// Create your custom repository implementing GradeScaleDataRepository
$myRepo = new MyDatabaseRepository($dbConnection);

// Use it globally
GradeServices::useRepository($myRepo);

// Or just for one specific conversion service
$service = GradeServices::conversion($myRepo);
```

---

## CLI usage

A small helper lives at `bin/grades`.


Positional:
- `<gradeValue>` - e.g. `6c+`, `7a`
- `<gradeSystem>` - e.g. `FR`, `UIAA`, `YDS`
- `[targetSystem]` - if omitted, converts to **all** other systems (range)

Options:
- `--single` (or `-1`) - return **one** result (instead of a list)
- `--source-policy=lowest|middle|highest` - how to pick the **source** index if the grade spans multiple (default: `lowest`)
- `--target-policy=first|middle|last` - which **target** variant to pick if the cell has multiple values (default: `first`)
- `--include-source` - when converting to **all** systems, include the source too
- `--help` - show help

Examples:
```bash
php bin/grades 6c+ fr
php bin/grades 6c+ fr yds
php bin/grades 7a fr br --single --target-policy=last
php bin/grades 6c+ fr --include-source
```
When installed via Composer in another project, the command is also available as:
```text
vendor/bin/grades 6c+ fr
```
---

## Architecture

This library follows DDD and clean architecture principles:

* **Domain layer**: Contains core business logic (grade systems, conversion algorithms)
* **Infrastructure layer**: Provides data access and bootstrap utilities
* **Bootstrap facade**: Offers a convenient entry point for typical usage scenarios

Core components:

* [GradeConversionService](./src/Domain/Service/GradeConversionService.php): Main domain service for converting between systems
* [GradeScale](./src/Domain/Service/GradeScale.php): Interface for specific grading scales
* [GradeScaleDataRepository](./src/Domain/Repository/GradeScaleDataRepository.php): Interface for data access
* [GradeConversion](./src/Infrastructure/Bootstrap/GradeConversion.php) (bootstrap): User-friendly facade for common operations

---

## Extending with a new grading system

1. **Add a case** to `GradeSystem` enum.
2. **Create a scale class** extending `AbstractGradeScale` and implement `system(): GradeSystem`; its data will come from the repository’s column for that system.
3. **Register** the scale in your composition root (`GradeServices` or your own factory).

No other changes are needed: `GradeConversionService` will pick it up automatically.

---

## Development

Run the test suite:

```text
composer test
```
Run CLI locally:
```text
php bin/grades 6c+ FR
php bin/grades 6c+ FR YDS
```

---

## Changelog

See **[CHANGELOG.md](./CHANGELOG.md)** for release notes.

Latest: **v0.3.1** — Release: Architecture refactoring and performance improvements.

---

## License

Released under the [MIT License](https://opensource.org/licenses/MIT).

Copyright © 2025 [slawe](https://github.com/slawe)
