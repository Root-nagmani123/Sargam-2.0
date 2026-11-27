<?php

namespace Modules\GuestTaClaim\Services;

use Illuminate\Support\Collection;
use Modules\GuestTaClaim\Models\Claim;
use Modules\GuestTaClaim\Support\Format;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    /** @param iterable<int, array> $rows first row is the header */
    public function download(string $filename, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows ₹ and Hindi correctly
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => $this->safe($v), $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @param Collection<int, Claim> $claims */
    public function claimRows(Collection $claims): array
    {
        $rows = [[
            'Claim no.', 'Course', 'Guest faculty', 'Designation', 'Official address', 'Basic pay', 'Category',
            'From', 'To', 'Sessions taken', 'Honorarium', 'Fare admissible', 'Accommodation', 'Journey DA days',
            'Academy stay DA days', 'DA rate', 'DA percentage', 'DA amount', 'Total claimed', 'Status', 'Paid amount', 'Paid on', 'Payment remark',
        ]];
        foreach ($claims as $c) {
            $rows[] = [
                $c->claim_no, $c->course_name, $c->faculty_name, $c->faculty_designation, $c->faculty_official_address, $c->basic_pay, $c->faculty_category,
                Format::date($c->period_from), Format::date($c->period_to), $c->sessions_taken, $c->honorarium_amount,
                $c->travel_fare, $c->local_conveyance, $c->da_journey_days, $c->da_stay_days, $c->da_rate,
                $c->da_percentage, $c->da_amount, $c->total_amount,
                $c->status->label(), $c->paid_amount, Format::dateTime($c->paid_at), $c->paid_remark,
            ];
        }

        return $rows;
    }

    /** Neutralise spreadsheet formula injection. */
    protected function safe($value): string
    {
        $v = (string) ($value ?? '');

        return $v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true) && ! is_numeric($v) ? "'".$v : $v;
    }
}
