@extends('admin.layouts.master')

@section('title', ($repository->course_repository_name ?? 'Repository Details') . ' | Course Repository')

@section('setup_content')
<div class="cru-page">
    <div class="container-fluid px-3 px-md-4 pt-3 pb-0">
        @php
            $crumbItems = [
                ['label' => 'Home', 'url' => route('admin.dashboard')],
                ['label' => 'Academic', 'url' => null],
                ['label' => 'Course Repository', 'url' => route('admin.course-repository.user.index')],
            ];
            if (!empty($ancestors)) {
                foreach ($ancestors as $ancestor) {
                    $crumbItems[] = [
                        'label' => $ancestor->course_repository_name,
                        'url' => route('admin.course-repository.user.show', $ancestor->pk),
                    ];
                }
            }
            $crumbItems[] = $repository->course_repository_name;
        @endphp

        <x-breadcrum
            :title="$repository->course_repository_name"
            :items="$crumbItems"
        />
    </div>

    <!-- Breadcrumb Navigation -->
    @if (!empty($ancestors) || $repository->parent)
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.course-repository.user.index') }}" class="text-decoration-none">Course
                    Repository</a>
            </li>
            @if (!empty($ancestors))
            @foreach ($ancestors as $ancestor)
            <li class="breadcrumb-item">
                <a href="{{ route('admin.course-repository.user.show', $ancestor->pk) }}" class="text-decoration-none">
                    {{ $ancestor->course_repository_name }}
                </a>
            </li>
            @endforeach
            @endif
            <li class="breadcrumb-item active" aria-current="page">{{ $repository->course_repository_name }}</li>
        </ol>
    </nav>
    @endif
</div>
<div class="d-flex" id="main-content">
    <!-- Left Sidebar -->
    <aside class="course-sidebar-wrapper">
        <x-course-sidebar />
    </aside>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="container-fluid px-4 py-4" id="main-content">
            <!-- Filter Card -->
            @if(isset($courses) && isset($subjects) && isset($faculties))
            @include('admin.course-repository.user.partials.filter-card', [
            'route' => route('admin.course-repository.user.show', $repository->pk),
            'courses' => $courses,
            'subjects' => $subjects,
            'faculties' => $faculties,
            'filters' => $filters ?? [],
            ])
            @endif

            @if($repository->children->count() == 0 && $documents->count() == 0)
            <!-- Empty State -->
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <span class="material-icons material-symbols-rounded"
                        style="font-size: 48px; color: #ccc;">inbox</span>
                    <p class="text-muted mt-3">No sub-categories or documents found in this repository.</p>
                </div>
            </div>
            @else
            <!-- Child Repositories Section -->
            @if($repository->children->count() > 0)
            <div class="course-cards-grid mb-4">
                <div class="row g-4">
                    @foreach ($repository->children as $child)
                    <div class="col-md-4 col-lg-3">
                        <div class="card course-card shadow-sm h-100"
                            onclick="window.location='{{ route('admin.course-repository.user.show', $child->pk) }}'">
                            <div class="card-img-wrapper">
                                @php
                                $imageUrl = null;
                                if($child->category_image && \Storage::disk('public')->exists($child->category_image)) {
                                $imageUrl = asset('storage/' . $child->category_image);
                                }
                                if(!$imageUrl) {
                                $imageUrl = 'https://via.placeholder.com/400x200/004a93/ffffff?text=' .
                                urlencode($child->course_repository_name);
                                }
                                @endphp
                                <img src="{{ $imageUrl }}" alt="{{ $child->course_repository_name }}"
                                    class="card-img-top" loading="lazy"
                                    onerror="this.src='https://via.placeholder.com/400x200/004a93/ffffff?text={{ urlencode($child->course_repository_name) }}'">
                            </div>
                            <div class="card-body d-flex flex-column" style="background-color: #F2F2F2;">
                                <h5 class="card-title text-center fw-bold mb-3">
                                    {{ Str::limit($child->course_repository_name, 50) }}</h5>
                                <div class="mt-auto">
                                    <a href="{{ route('admin.course-repository.user.show', $child->pk) }}"
                                        class="btn btn-outline-primary w-100 fw-semibold"
                                        onclick="event.stopPropagation();">
                                        Click Here
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

                    @if($documents->count() > 0)
                    @include('admin.course-repository.user.partials.documents-table', ['documents' => $documents])
                    @endif
                    @endif
                </div>
            </div>
        </main>
    </div>
</div>

@include('admin.course-repository.user.partials.assets')
@endsection
