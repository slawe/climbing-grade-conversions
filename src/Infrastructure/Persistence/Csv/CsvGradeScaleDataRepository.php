<?php

namespace Climb\Grades\Infrastructure\Persistence\Csv;

use Climb\Grades\Domain\Repository\GradeScaleDataRepository;
use Climb\Grades\Domain\Value\GradeSystem;
use RuntimeException;
use SplFileObject;

final class CsvGradeScaleDataRepository implements GradeScaleDataRepository
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @inheritDoc
     */
    public function indexToGradeMap(GradeSystem $system): array
    {
        $key = $system->value;

        if (!is_file($this->path)) {
            throw new RuntimeException("Grades CSV file not found: $this->path");
        }

        $file = new SplFileObject($this->path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $header = null;
        $indexCol = null;
        $gradeCol = null;
        $map = [];

        foreach ($file as $row) {
            if ($row === [null] || $row === false) {
                continue;
            }

            if ($header === null) {
                $header = $row;
                $indexCol = array_search('INDEX', $header, true);
                $gradeCol = array_search($key, $header, true);

                if ($indexCol === false || $gradeCol === false) {
                    throw new RuntimeException("Missing 'index' or '{$key}' column in CSV.");
                }

                continue;
            }

            $idx = $row[$indexCol] ?? null;
            $val = $row[$gradeCol] ?? null;

            if ($idx === null || $val === null || $val === '') {
                continue;
            }

            $map[(int)$idx] = (string)$val;
        }

        ksort($map);

        return $map;
    }
}