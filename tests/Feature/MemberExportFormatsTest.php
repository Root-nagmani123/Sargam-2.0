<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * The four member-listing exports must agree: same rows, same columns, same
 * filter description. These tests drive them through the HTTP stack and read the
 * bytes back, so a drift between formats fails here rather than on someone's desk.
 */
class MemberExportFormatsTest extends TestCase
{
    private function actor(): User
    {
        $user = User::query()->orderBy('pk')->first();
        if (! $user) {
            $this->markTestSkipped('no user available to authenticate as');
        }

        return $user;
    }

    private function fetch(string $url)
    {
        return $this->actingAs($this->actor())->get($url);
    }

    /**
     * Reads a download's bytes whatever shape the response is: CSV streams,
     * DomPDF buffers, and Maatwebsite hands back a BinaryFileResponse whose
     * body is empty because the bytes live in a temp file until it is sent.
     */
    private function bytes($response): string
    {
        $base = $response->baseResponse;

        if ($base instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            return (string) file_get_contents($base->getFile()->getPathname());
        }

        if ($base instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return $response->streamedContent();
        }

        return $response->getContent();
    }

    public function test_csv_carries_the_branded_band_and_the_grid_columns(): void
    {
        $response = $this->fetch('/member/export/csv');
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('.csv', $response->headers->get('content-disposition'));

        $body = $response->streamedContent();
        $lines = str_getcsv($body, "\n");

        $this->assertStringContainsString('LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION', $lines[0]);
        $this->assertStringContainsString('MEMBERS', $lines[1]);
        $this->assertStringContainsString('Generated:', $lines[2]);
        $this->assertStringContainsString('Total Records:', $lines[3]);

        // Heading row sits after the band + its blank spacer.
        $heading = str_getcsv($lines[5]);
        $this->assertSame(
            ['S. No.', 'Employee Name', 'Employee ID', 'Mobile No', 'Email', 'Status'],
            $heading
        );

        $first = str_getcsv($lines[6]);
        $this->assertSame('1', $first[0], 'S. No. must start at 1');
        $this->assertContains($first[5], ['Active', 'Inactive']);

        fwrite(STDERR, "\ncsv rows: " . (count($lines) - 6) . ", heading: " . implode(' | ', $heading) . "\n");
    }

    public function test_status_filter_and_search_reach_every_format(): void
    {
        $all = $this->fetch('/member/export/csv')->streamedContent();
        $active = $this->fetch('/member/export/csv?status_filter=active')->streamedContent();
        $searched = $this->fetch('/member/export/csv?q=kumar')->streamedContent();

        $count = fn (string $csv) => substr_count(trim($csv), "\n") - 5; // minus band + heading

        $this->assertGreaterThan($count($active), $count($all), 'Active must be a subset of All');
        $this->assertGreaterThan($count($searched), $count($all), 'a search must narrow the export');

        // The filter description is stated in the file, not just applied silently.
        $this->assertStringContainsString('Status: Active', $active);
        $this->assertStringContainsString('Search: kumar', $searched);

        // Print and PDF describe the same scope.
        $print = $this->fetch('/member/export/print?status_filter=active')->assertOk()->getContent();
        $this->assertStringContainsString('Status: Active', $print);
        $this->assertStringContainsString('window.print()', $print);

        fwrite(STDERR, sprintf(
            "all=%d active=%d search=%d\n",
            $count($all),
            $count($active),
            $count($searched)
        ));
    }

    public function test_hidden_columns_drop_from_every_format(): void
    {
        $csv = $this->fetch('/member/export/csv?cols=sno,employee_name,status')->streamedContent();
        $heading = str_getcsv(str_getcsv($csv, "\n")[5]);
        $this->assertSame(['S. No.', 'Employee Name', 'Status'], $heading);

        $print = $this->fetch('/member/export/print?cols=sno,employee_name,status')->assertOk()->getContent();
        $this->assertStringContainsString('>Employee Name<', $print);
        $this->assertStringNotContainsString('>Mobile No<', $print);

        // A hand-edited ?cols= can neither reorder nor inject a column.
        $tampered = $this->fetch('/member/export/csv?cols=status,employee_name,evil_column')->streamedContent();
        $this->assertSame(
            ['Employee Name', 'Status'],
            str_getcsv(str_getcsv($tampered, "\n")[5]),
            'canonical order must survive a reordered ?cols='
        );

        // All columns hidden falls back to the full set rather than an empty file.
        $empty = $this->fetch('/member/export/csv?cols=nothing_valid')->streamedContent();
        $this->assertCount(6, str_getcsv(str_getcsv($empty, "\n")[5]));
    }

    public function test_pdf_and_excel_download_as_real_files(): void
    {
        $pdf = $this->fetch('/member/export/pdf');
        $pdf->assertOk();
        $pdfBody = $this->bytes($pdf);
        $this->assertStringStartsWith('%PDF-', $pdfBody, 'not a PDF');
        $this->assertStringContainsString('.pdf', $pdf->headers->get('content-disposition'));

        $excel = $this->fetch('/member/export/excel');
        $excel->assertOk();
        $xlsx = $this->bytes($excel);
        // .xlsx is a zip container.
        $this->assertStringStartsWith('PK', $xlsx, 'not an xlsx');
        $this->assertStringContainsString('.xlsx', $excel->headers->get('content-disposition'));

        fwrite(STDERR, sprintf("pdf=%d bytes, xlsx=%d bytes\n", strlen($pdfBody), strlen($xlsx)));
    }

    /**
     * The PDF truncates past a measured row cap. It must say so on the page —
     * a silent cut would read as a complete report.
     */
    public function test_pdf_states_its_row_cap(): void
    {
        $note = 'Showing the first 1,000 of 1,831 records. Use the CSV or Excel download for the complete list.';

        $html = view('admin.exports.branded_pdf', [
            'title' => 'Members',
            'columns' => ['sno' => ['heading' => 'S. No.', 'class' => 'col-sno', 'value' => fn ($r, $i) => $i + 1]],
            'rows' => collect([(object) ['pk' => 1]]),
            'total' => 1831,
            'note' => $note,
            'filterLine' => null,
            'exportDate' => '01-01-2026 10:00 AM',
        ])->render();

        $this->assertStringContainsString($note, $html);
        $this->assertStringContainsString('Total Records: 1,831', $html, 'the header must state the FULL match count');

        // Without a note the block is absent entirely.
        $uncapped = view('admin.exports.branded_pdf', [
            'title' => 'Members',
            'columns' => ['sno' => ['heading' => 'S. No.', 'class' => 'col-sno', 'value' => fn ($r, $i) => $i + 1]],
            'rows' => collect([(object) ['pk' => 1]]),
            'total' => 1,
            'note' => null,
            'filterLine' => null,
            'exportDate' => '01-01-2026 10:00 AM',
        ])->render();

        $this->assertStringNotContainsString('class="note"', $uncapped);
    }

    public function test_unknown_format_is_rejected(): void
    {
        $this->actingAs($this->actor())->get('/member/export/exe')->assertNotFound();
    }

    public function test_legacy_full_dump_still_works(): void
    {
        $response = $this->fetch('/member/excel-export');
        $response->assertOk();
        $this->assertStringStartsWith('PK', $this->bytes($response));
    }
}
