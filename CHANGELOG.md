# Changelog

## [v0.3.0] - 2025-11-14
### Added
- Fluent chain helper: `GradeConversion::towards($target)` → `.all()` / `.single(...)`.
- Two selection policies:
  - `PrimaryIndexPolicy` (LOWEST/MIDDLE/HIGHEST) – choose **source** index when a grade spans multiple indices.
  - `TargetVariantPolicy` (FIRST/MIDDLE/LAST) – choose **target** variant when a cell contains multiple values (e.g. `7c/8a`).
- Domain exceptions: `GradeNotFound`, `IndexOutOfRange`, `AmbiguousGrade`, `InvalidScaleData`.
- CLI options:
  - `--single|-1`, `--source-policy=lowest|middle|highest`,
  - `--target-policy=first|middle|last`, `--include-source`.

### Changed
- `AbstractGradeScale` validates repository data (contiguous integer keys, string cell values); throws `InvalidScaleData` when invalid.
- `convertToAll(..., includeSource: true)` now returns the **exact source grade** as a single `Grade` for the source system (no range).
- README expanded with chain API, policies, and updated CLI docs.
- Comprehensive test suite added: range/single conversions, both policies, ambiguity handling, and exceptions.

### Notes
- Backward compatible: `->to()` still returns a **list (range)**. For a single value, use `->towards(...)->single(...)`.

---

## [v0.2.0] - 2025-11-13
### Added
- CSV data source: `data/grades.csv` with a consolidated table across multiple systems.
- Repository layer:
  - `GradeScaleDataRepository` (interface)
  - `CsvGradeScaleDataRepository` (implementation)
- New scales (Domain/Service/Scale):
  `FrenchSportScale`, `UiaaScale`, `AmericanYdsScale`, `UkTechnicalScale`, `UkAdjectivalScale`,
  `SaxonScale`, `EwbankAustralianScale`, `EwbankSouthAfricaScale`, `FinlandScale`, `NorwayScale`,
  `BrazilianTechnicalScale`, `PolishKurtykasScale`, `AmericanVScale`, `FrenchFontainebleauScale`.
- Configuration/wiring helpers: `GradeConfig`, `GradeScaleProvider`, `GradeScaleRegistry`, `GradeServices`.
- Simple CLI (`bin/grades`) and demo script (`public/index.php`).
- Initial tests (`tests/GradeConversionServiceTest.php`).

### Changed
- `AbstractGradeScale` and `GradeConversionService` return **lists (ranges)** when a cell has multiple variants or when a source grade spans multiple indices.
- README significantly expanded (supported systems, usage, CLI).

### Removed
- n/a

### Notes
- Introduces CSV as the default storage and broadens the supported scales; the API naturally returns **multiple values** whenever the underlying table dictates it.
