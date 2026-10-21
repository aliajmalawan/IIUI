<?php

declare(strict_types=1);

namespace App\Core;

/** Builds a downloadable CSV response (UTF-8 with BOM so Excel shows Urdu/accents correctly). */
final class Csv
{
    /**
     * @param string[] $header
     * @param iterable<array<int,mixed>> $rows
     */
    public static function response(string $filename, array $header, iterable $rows): Response
    {
        $h = fopen('php://temp', 'r+');
        fputcsv($h, $header);
        foreach ($rows as $row) {
            fputcsv($h, array_map(self::safe(...), $row));
        }
        rewind($h);
        $body = chr(0xEF) . chr(0xBB) . chr(0xBF) . stream_get_contents($h);
        fclose($h);

        return Response::make($body, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '-', $filename) . '"',
        ]);
    }

    /** Stops spreadsheet formula injection: a cell starting with = + - @ is prefixed so it stays text. */
    private static function safe(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}
