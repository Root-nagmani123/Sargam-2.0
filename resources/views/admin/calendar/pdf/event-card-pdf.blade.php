@php
    use Carbon\Carbon;

    /** Safe date formatter — START_DATE may be a date or datetime string. */
    $fmtDate = function ($value) {
        if (empty($value)) return null;
        try { return Carbon::parse($value)->format('l, d F Y'); }
        catch (\Throwable $e) { return (string) $value; }
    };

    $brand        = '#004a93';
    $startLabel   = $fmtDate($event['start_date'] ?? null);
    $endLabel     = $fmtDate($event['end_date'] ?? null);
    $dateLabel    = $startLabel;
    if ($endLabel && $endLabel !== $startLabel) {
        $dateLabel = $startLabel . ' – ' . $endLabel;
    }
    $timeLabel    = trim((string) ($event['class_session'] ?? ''));
    $organizer    = trim((string) ($event['organizer'] ?? ''));
    $faculty      = trim((string) ($event['faculty_name'] ?? ''));
    $internal     = trim((string) ($event['internal_faculty'] ?? ''));
    $group        = trim((string) ($event['group_name'] ?? ''));
    $venue        = trim((string) ($event['venue_name'] ?? ''));
    $category     = trim((string) ($event['event_category'] ?? ''));
    $course       = trim((string) ($event['course_name'] ?? ''));
    $contact      = trim((string) ($event['contact_info'] ?? ''));
    $description  = trim((string) ($event['event_description'] ?? ''));
    $customFields = $event['custom_fields'] ?? [];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Event Card — {{ $event['topic'] ?? 'Event' }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            font-family: "DejaVu Sans", sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.5;
        }
        .page {
            padding: 26px 30px 70px 30px;
        }
        /* ---------- Branding header ---------- */
        .brand {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px solid {{ $brand }};
            padding-bottom: 6px;
        }
        .brand td { vertical-align: middle; }
        .brand .logo-cell { width: 64px; text-align: center; }
        .brand img { height: 52px; width: auto; }
        .brand .title-cell { text-align: center; padding: 0 8px; }
        .brand .academy {
            color: {{ $brand }};
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .3px;
            margin: 0;
        }
        .brand .academy-sub {
            color: #6b7280;
            font-size: 9.5px;
            margin: 2px 0 0 0;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        /* ---------- Category ribbon + title ---------- */
        .ribbon { margin-top: 18px; }
        .badge {
            display: inline-block;
            background: {{ $brand }};
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .6px;
            padding: 4px 12px;
            border-radius: 3px;
        }
        .event-title {
            color: #0f172a;
            font-size: 26px;
            font-weight: bold;
            line-height: 1.2;
            margin: 12px 0 4px 0;
        }
        .event-course {
            color: {{ $brand }};
            font-size: 12.5px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }
        /* ---------- Banner ---------- */
        .banner-wrap {
            margin: 16px 0 6px 0;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }
        .banner-wrap img {
            display: block;
            width: 100%;
            height: auto;
            max-height: 300px;
        }
        /* ---------- Key details grid ---------- */
        .section-title {
            color: {{ $brand }};
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .5px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
            margin: 18px 0 10px 0;
        }
        .details { width: 100%; border-collapse: separate; border-spacing: 0 6px; }
        .details td { vertical-align: top; padding: 2px 0; }
        .details .ico {
            width: 22px;
            color: {{ $brand }};
            font-weight: bold;
            font-size: 12px;
        }
        .details .lbl {
            width: 96px;
            color: #6b7280;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .details .val { color: #111827; font-size: 12.5px; }
        /* ---------- Description ---------- */
        .desc {
            color: #374151;
            font-size: 12px;
            text-align: justify;
            white-space: pre-line;
        }
        /* ---------- Footer (QR + contact) ---------- */
        .footer-block {
            margin-top: 20px;
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
            width: 100%;
            border-collapse: collapse;
        }
        .footer-block td { vertical-align: middle; }
        .qr-cell { width: 96px; text-align: center; }
        .qr-cell img { width: 84px; height: 84px; }
        .qr-cap { font-size: 8.5px; color: #6b7280; margin-top: 2px; }
        .contact-lbl {
            color: #6b7280;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin: 0 0 2px 0;
        }
        .contact-val { color: #111827; font-size: 12px; margin: 0; }
        /* ---------- Page footer band ---------- */
        .page-footer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: {{ $brand }};
            color: #ffffff;
            font-size: 9px;
            text-align: center;
            padding: 7px 30px;
            letter-spacing: .3px;
        }
    </style>
</head>
<body>
    <div class="page">
        {{-- Branding header --}}
        <table class="brand">
            <tr>
                <td class="logo-cell">
                    @if(!empty($emblemSrc))<img src="{{ $emblemSrc }}" alt="Emblem of India">@endif
                </td>
                <td class="title-cell">
                    <p class="academy">Lal Bahadur Shastri National Academy of Administration</p>
                    <p class="academy-sub">Mussoorie &middot; Event Card</p>
                </td>
                <td class="logo-cell">
                    @if(!empty($lbsnaaLogoSrc))<img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA Logo">@endif
                </td>
            </tr>
        </table>

        {{-- Category + Title --}}
        <div class="ribbon">
            @if($category !== '')
                <span class="badge">{{ $category }}</span>
            @endif
        </div>
        @if($course !== '')
            <p class="event-course" style="margin-top:12px;">{{ $course }}</p>
            <h1 class="event-title" style="margin-top:2px;">{{ $event['topic'] ?? 'Event' }}</h1>
        @else
            <h1 class="event-title">{{ $event['topic'] ?? 'Event' }}</h1>
        @endif

        {{-- Banner --}}
        @if(!empty($bannerSrc))
            <div class="banner-wrap">
                <img src="{{ $bannerSrc }}" alt="Event banner">
            </div>
        @endif

        {{-- Key details --}}
        <div class="section-title">Event Details</div>
        <table class="details">
            @if($dateLabel)
                <tr>
                    <td class="ico">&#9679;</td>
                    <td class="lbl">Date</td>
                    <td class="val">{{ $dateLabel }}</td>
                </tr>
            @endif
            @if($timeLabel !== '')
                <tr>
                    <td class="ico">&#9679;</td>
                    <td class="lbl">Time</td>
                    <td class="val">{{ $timeLabel }}</td>
                </tr>
            @endif
            @if($venue !== '')
                <tr>
                    <td class="ico">&#9679;</td>
                    <td class="lbl">Venue</td>
                    <td class="val">{{ $venue }}</td>
                </tr>
            @endif
            @if($organizer !== '')
                <tr>
                    <td class="ico">&#9679;</td>
                    <td class="lbl">Organizer</td>
                    <td class="val">{{ $organizer }}</td>
                </tr>
            @endif
            @if($faculty !== '')
                <tr>
                    <td class="ico">&#9679;</td>
                    <td class="lbl">Faculty</td>
                    <td class="val">{{ $faculty }}</td>
                </tr>
            @endif
            @if($internal !== '')
                <tr>
                    <td class="ico">&#9679;</td>
                    <td class="lbl">Internal Faculty</td>
                    <td class="val">{{ $internal }}</td>
                </tr>
            @endif
            @if($group !== '')
                <tr>
                    <td class="ico">&#9679;</td>
                    <td class="lbl">Group</td>
                    <td class="val">{{ $group }}</td>
                </tr>
            @endif
        </table>

        {{-- Description --}}
        @if($description !== '')
            <div class="section-title">Description</div>
            <div class="desc">{{ $description }}</div>
        @endif

        {{-- Custom fields --}}
        @if(!empty($customFields))
            <div class="section-title">Additional Information</div>
            <table class="details">
                @foreach($customFields as $cf)
                    @if(trim((string)($cf['label'] ?? '')) !== '' || trim((string)($cf['value'] ?? '')) !== '')
                        <tr>
                            <td class="ico">&#9679;</td>
                            <td class="lbl">{{ $cf['label'] ?? '' }}</td>
                            <td class="val">{{ $cf['value'] ?? '' }}</td>
                        </tr>
                    @endif
                @endforeach
            </table>
        @endif

        {{-- QR + Contact --}}
        @if(!empty($qrSrc) || $contact !== '')
            <table class="footer-block">
                <tr>
                    @if(!empty($qrSrc))
                        <td class="qr-cell">
                            <img src="{{ $qrSrc }}" alt="Event QR code">
                            <div class="qr-cap">Scan for details</div>
                        </td>
                    @endif
                    <td>
                        @if($contact !== '')
                            <p class="contact-lbl">Contact</p>
                            <p class="contact-val">{{ $contact }}</p>
                        @endif
                    </td>
                </tr>
            </table>
        @endif
    </div>

    <div class="page-footer">
        Lal Bahadur Shastri National Academy of Administration, Mussoorie &nbsp;&middot;&nbsp; Official Event Card
    </div>
</body>
</html>
