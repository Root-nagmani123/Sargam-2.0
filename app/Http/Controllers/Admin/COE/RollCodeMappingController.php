<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Concerns\ExportsMasterGrid;
use App\Http\Controllers\Controller;
use App\Services\COE\RollCodeMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * COE → Examination Management → Roll No ↔ Unique Code Mapping.
 *
 * Read-only: which Unique Participant Code belongs to which roll number, per
 * course and batch, from the two existing lists (RollCodeMapping). Nothing is
 * stored. Confidential — the routes are gated by menu permission
 * coe_roll_code_mapping (COE; Super Admin always passes).
 */
class RollCodeMappingController extends Controller
{
    use ExportsMasterGrid;

    public function __construct(private RollCodeMapping $mapping)
    {
    }

    public function index(Request $request)
    {
        $scope = $this->scope($request);
        $rows = $this->mapping->rows($scope);

        return view('admin.coe.roll_code_mapping.index', [
            'scope'   => $scope,
            'rows'    => $rows,
            'courses' => $rows->pluck('course_name')->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'states'  => RollCodeMapping::STATES,
            'summary' => $this->summary($rows),
        ]);
    }

    /** CSV / Excel / PDF / Print in the Sargam master format, following the page's filters. */
    public function export(Request $request, string $format)
    {
        abort_unless(in_array($format, self::$exportFormats, true), 404);
        $scope = $this->scope($request);

        $course = trim((string) $request->query('course', ''));
        $state = trim((string) $request->query('mapping', ''));
        $term = mb_strtolower(trim((string) $request->query('q', '')));

        $rows = $this->mapping->rows($scope)
            ->when($course !== '', fn ($c) => $c->where('course_name', $course))
            ->when(in_array($state, RollCodeMapping::STATES, true), fn ($c) => $c->where('mapping', $state))
            ->when($term !== '', fn ($c) => $c->filter(fn ($r) => str_contains(mb_strtolower(
                implode(' ', [$r['course_name'], $r['batch'], $r['roll_number'], $r['unique_code'], $r['ot_name'], $r['service']])
            ), $term)))
            ->values();

        $dash = fn ($v) => $v === '' ? '—' : $v;
        $columns = [
            'sno'         => ['heading' => 'S. No.', 'width' => '5%', 'align' => 'center', 'value' => fn ($r, int $i) => $i + 1],
            'course'      => ['heading' => 'Course Name', 'width' => '19%', 'value' => fn ($r) => $r['course_name']],
            'batch'       => ['heading' => 'Batch', 'width' => '6%', 'align' => 'center', 'value' => fn ($r) => $r['batch']],
            'roll'        => ['heading' => 'Roll Number', 'width' => '10%', 'value' => fn ($r) => $dash($r['roll_number'])],
            'code'        => ['heading' => 'Unique Code', 'width' => '10%', 'value' => fn ($r) => $dash($r['unique_code'])],
            'ot'          => ['heading' => 'OT Name', 'width' => '17%', 'value' => fn ($r) => $r['ot_name']],
            'service'     => ['heading' => 'Service', 'width' => '7%', 'value' => fn ($r) => $r['service']],
            'roll_status' => ['heading' => 'Roll No. Status', 'width' => '8%', 'align' => 'center', 'value' => fn ($r) => $r['roll_status']],
            'code_status' => ['heading' => 'Code Status', 'width' => '8%', 'align' => 'center', 'value' => fn ($r) => $r['code_status']],
            'mapping'     => ['heading' => 'Mapping', 'width' => '10%', 'align' => 'center', 'value' => fn ($r) => $r['mapping']],
        ];

        $s = $this->summary($rows);
        $filters = implode('  |  ', array_filter([
            'CONFIDENTIAL — COE use only',
            $scope === 'archived' ? 'Archived courses' : 'Active courses',
            $course !== '' ? 'Course: ' . $course : null,
            in_array($state, RollCodeMapping::STATES, true) ? 'Mapping: ' . $state : null,
            $term !== '' ? 'Search: ' . $request->query('q') : null,
            "OTs: {$s['total']}  ·  Mapped: {$s['mapped']}  ·  Unique code missing: {$s['code_missing']}  ·  Roll number missing: {$s['roll_missing']}",
        ]));

        // renderMasterExport writes the audit line (actor, format, rows, filter) for every export.
        return $this->renderMasterExport(
            $format,
            $rows,
            $this->resolveExportColumns($request, $columns),
            'Roll Number - Unique Code Mapping',
            'RollNumberUniqueCodeMapping',
            $filters,
            'No roll numbers or unique codes to map',
            'landscape'
        );
    }

    private function scope(Request $request): string
    {
        return in_array($request->query('scope'), RollCodeMapping::SCOPES, true) ? $request->query('scope') : 'active';
    }

    /** @return array{total:int, mapped:int, code_missing:int, roll_missing:int} */
    private function summary(Collection $rows): array
    {
        $by = $rows->countBy('mapping');

        return [
            'total'        => $rows->count(),
            'mapped'       => (int) ($by[RollCodeMapping::MAPPED] ?? 0),
            'code_missing' => (int) ($by[RollCodeMapping::CODE_MISSING] ?? 0),
            'roll_missing' => (int) ($by[RollCodeMapping::ROLL_MISSING] ?? 0),
        ];
    }
}
