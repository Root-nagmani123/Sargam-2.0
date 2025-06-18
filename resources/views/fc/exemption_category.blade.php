@extends('fc.layouts.master')

@section('title', 'Exemption Category - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <main style="flex: 1;">
        <div class="container mt-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active" style="font-size: 20px;">Home</li>
                    <li class="breadcrumb-item" aria-current="page" style="font-size: 20px;">Exemption Category</li>
                </ol>
            </nav>

            <div class="text-center mt-5">
                <h4 style="color: #004a93; font-size: 30px; font-weight: 700;">Select Exemption Category</h4>
                <div class="col-8 mx-auto">
                    <p class="text-muted" style="font-size: 16px;">Choose the appropriate exemption category based on your
                        circumstances. Each category has specific requirements and documentation needs.</p>
                </div>
            </div>

            <div class="row mt-5 g-4">
                @foreach ($exemptions as $index => $item)
                    @php
                        // Assign background/icon colors based on index
                        $colors = [
                            ['bg' => '#e5f2ff', 'icon' => '#2563eb', 'iconName' => 'school'],
                            ['bg' => '#dcfce7', 'icon' => '#16a32a', 'iconName' => 'article'],
                            ['bg' => '#fee2e2', 'icon' => '#dc2626', 'iconName' => 'medical_services'],
                            ['bg' => '#ffedd5', 'icon' => '#ea580c', 'iconName' => 'person_remove'],
                        ];
                        $color = $colors[$index % count($colors)];
                    @endphp

                    <div class="col-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="icon-circle" style="background-color: {{ $color['bg'] }};">
                                    <i class="material-icons menu-icon fs-2" style="color: {{ $color['icon'] }};">
                                        {{ $color['iconName'] }}
                                    </i>
                                </div>
                                <h5 class="fw-bold text-center" style="color: #004a93; font-size: 20px;">
                                    {{ $item->Exemption_name }}
                                </h5>
                                <span class="text-muted">{!! $item->description !!}</span>
                                
                            </div>
                            <div class="card-footer">
                                <a href="{{ route('fc.exemption_application', $item->pk) }}"
                                    class="btn btn-success custom-btn mt-2"
                                    style="background-color: {{ $color['icon'] }}; border: {{ $color['icon'] }};">
                                    Apply for Exemption
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Important Notice -->
            @if (!empty($notice?->description))
                <div class="notice-box mt-5">
                    <div class="text-muted" style="font-size: 14px;">
                        {!! $notice->description !!}
                    </div>
                </div>
            @endif
        </div>
    </main>
@endsection
