<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Public Privacy Policy: the page, a printable copy and a PDF, all rendering the one text in
 * privacy_policy/_content. To revise the policy, edit that partial and LAST_UPDATED.
 */
class PrivacyPolicyController extends Controller
{
    public const LAST_UPDATED = '25 November 2026';

    public const SECTIONS = [
        'Introduction',
        'Information We Collect',
        'Purpose of Collection and Processing',
        'Data Sharing and Disclosure',
        'Integration with Other Government Applications',
        'Data Security',
        'Cookies and Session Management',
        'Data Retention',
        'User Responsibilities',
        'Third-Party Websites and Applications',
        'Rights of Users',
        'Privacy Grievances and Contact Information',
        'Changes to This Privacy Policy',
        'Applicable Laws',
    ];

    public function show()
    {
        return view('privacy_policy.index', ['lastUpdated' => self::LAST_UPDATED, 'sections' => self::SECTIONS]);
    }

    /** Branded printout, same chrome as every module's Print (admin.master.partials.export_print_*). */
    public function print()
    {
        return view('privacy_policy.print', $this->exportData());
    }

    /** Branded PDF, built like ExportsMasterGrid's: same options, page stamp and file-name pattern. */
    public function download()
    {
        $pdf = Pdf::loadView('privacy_policy.pdf', $this->exportData())
            ->setPaper('a4', 'portrait')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true, 'isPhpEnabled' => false]);

        // page_text() needs the final page count, so it runs after render(); download() reuses that render.
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $text = 'Page {PAGE_NUM} of {PAGE_COUNT}';
        $width = $dompdf->getFontMetrics()->getTextWidth($text, $font, 7);
        $canvas->page_text($canvas->get_width() - $width - 28, $canvas->get_height() - 24, $text, $font, 7, [0.42, 0.45, 0.5]);

        return $pdf->download('privacy_policy_'.now()->format('YmdHis').'.pdf');
    }

    private function exportData(): array
    {
        return ['lastUpdated' => self::LAST_UPDATED, 'exportDate' => now()->format('d-m-Y h:i A')];
    }
}
