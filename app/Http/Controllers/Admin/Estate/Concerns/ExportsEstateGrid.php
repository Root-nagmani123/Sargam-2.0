<?php

namespace App\Http\Controllers\Admin\Estate\Concerns;

use App\Http\Controllers\Concerns\ExportsMasterGrid;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * CSV and PDF for the Estate grids, on the app's shared export layer.
 *
 * Every Estate listing already has an Excel download and a print view, each
 * driven by an App\Exports\Estate*Export class whose columnDefs() describe the
 * columns once. This concern renders the same columns and the same filtered
 * rows as CSV or PDF through ExportsMasterGrid (export audit log, PDF memory
 * guard, CSV formula-injection guard), so the four formats cannot drift apart.
 *
 * Usage, at the top of an existing export action, after its own authorisation
 * and payload (so CSV/PDF inherit exactly the rows and filters Excel uses):
 *
 *     if ($response = $this->estateCsvOrPdf($request, EstateFooExport::class, $payload, 'Foo', 'estate-foo')) {
 *         return $response;
 *     }
 *
 * Returns null for any other ?format=, leaving the caller's Excel path untouched.
 */
trait ExportsEstateGrid
{
    use ExportsMasterGrid;

    /**
     * @param  class-string  $exportClass  an Estate*Export with static columnDefs()
     * @param  array{rows: Collection|iterable, cols?: string[]|null, filterLine?: string|null}  $payload
     */
    protected function estateCsvOrPdf(
        Request $request,
        string $exportClass,
        array $payload,
        string $reportTitle,
        string $slug,
        string $orientation = 'landscape'
    ): ?Response {
        $format = strtolower(trim((string) $request->query('format', '')));
        if (! in_array($format, ['csv', 'pdf'], true)) {
            return null;
        }

        $defs = $exportClass::columnDefs();
        $keys = array_values(array_intersect($payload['cols'] ?? array_keys($defs), array_keys($defs)));
        if ($keys === []) {
            $keys = array_keys($defs);
        }

        // Excel widths are character units; the print/PDF layer wants percentages.
        $widths = array_map(fn ($k) => (float) ($defs[$k]['width'] ?? 12), $keys);
        $total = array_sum($widths) ?: 1;

        $columns = [];
        foreach ($keys as $i => $key) {
            $def = $defs[$key];
            $money = ! empty($def['money']);
            $columns[$key] = [
                'heading' => $def['heading'],
                'width' => round($widths[$i] / $total * 100, 2).'%',
                'align' => $money ? 'right' : (! empty($def['center']) ? 'center' : 'left'),
                // Export classes number rows from 1; the shared layer passes a 0-based index.
                'value' => function ($row, int $index) use ($def, $money) {
                    $value = ($def['value'])($row, $index + 1);

                    return $money && is_numeric($value) ? number_format((float) $value, 2, '.', '') : $value;
                },
            ];
        }

        $rows = $payload['rows'] instanceof Collection ? $payload['rows'] : collect($payload['rows']);

        return $this->renderMasterExport(
            $format,
            $rows->values(),
            $columns,
            $reportTitle,
            $slug,
            $payload['filterLine'] ?? null,
            'No records for the applied filters.',
            $orientation
        );
    }
}
