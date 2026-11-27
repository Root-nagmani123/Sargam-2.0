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

    public function print()
    {
        return view('privacy_policy.print', ['lastUpdated' => self::LAST_UPDATED, 'autoPrint' => true]);
    }

    public function download()
    {
        return Pdf::loadView('privacy_policy.print', ['lastUpdated' => self::LAST_UPDATED, 'autoPrint' => false])
            ->setPaper('a4')
            ->download('SARGAM-2.0-Privacy-Policy.pdf');
    }
}
