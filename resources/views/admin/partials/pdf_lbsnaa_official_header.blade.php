@php
    $emblemSrc = $emblemSrc ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
    foreach ([
        public_path('admin_assets/images/logos/ashoka.png'),
        public_path('images/ashoka.png'),
    ] as $emblemPath) {
        if (is_file($emblemPath) && is_readable($emblemPath)) {
            $raw = @file_get_contents($emblemPath);
            if ($raw !== false) {
                $emblemSrc = 'data:image/png;base64,' . base64_encode($raw);
                break;
            }
        }
    }

    $lbsnaaLogoSrc = $lbsnaaLogoSrc ?? 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
    foreach ([
        public_path('images/lbsnaa_logo.jpg'),
        public_path('images/lbsnaa_logo.png'),
        public_path('admin_assets/images/logos/logo_new.png'),
        public_path('admin_assets/images/logos/logo.png'),
        public_path('admin_assets/images/logos/logo.svg'),
    ] as $logoPath) {
        if (is_file($logoPath) && is_readable($logoPath)) {
            $raw = @file_get_contents($logoPath);
            if ($raw !== false) {
                $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'svg' => 'image/svg+xml',
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    default => 'image/png',
                };
                $lbsnaaLogoSrc = 'data:' . $mime . ';base64,' . base64_encode($raw);
                break;
            }
        }
    }
@endphp

<div class="pdf-header">
    <table>
        <tr>
            <td class="hdr-left">
                <img src="{{ $emblemSrc }}" alt="Emblem of India">
            </td>
            <td class="hdr-center">
                <div class="brand-1">Government of India</div>
                <div class="brand-2">LBSNAA MUSSOORIE</div>
                <div class="brand-3">Lal Bahadur Shastri National Academy of Administration</div>
            </td>
            <td class="hdr-right">
                <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA Logo">
            </td>
        </tr>
    </table>
</div>
