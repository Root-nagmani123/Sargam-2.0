@extends('admin.layouts.master')
@section('title', 'Step 3 – Other Details')

@section('setup_content')
<div class="row justify-content-center">
<div class="col-12 col-xl-11">

    @include('partials.step-indicator', ['current' => 3])

    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h5 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-journal-text me-2"></i>Step 3: Other Details
            </h5>
            <small class="text-muted">Qualifications, Employment, Languages, Hobbies, Sports &amp; Module choice</small>
        </div>

        <div class="card-body p-0">
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs border-bottom px-3 pt-2" id="step3Tabs">
                @php
                    $tabs = [
                        'qual'      => ['icon'=>'bi-mortarboard','label'=>'Qualifications'],
                        'higher'    => ['icon'=>'bi-book','label'=>'Higher Education'],
                        'employ'    => ['icon'=>'bi-briefcase','label'=>'Employment'],
                        'spouse'    => ['icon'=>'bi-people','label'=>'Family/Spouse'],
                        'language'  => ['icon'=>'bi-translate','label'=>'Languages'],
                        'hobbies'   => ['icon'=>'bi-stars','label'=>'Hobbies/Skills'],
                        'distinc'   => ['icon'=>'bi-trophy','label'=>'Distinctions'],
                        'sports'    => ['icon'=>'bi-dribbble','label'=>'Sports'],
                        'module'    => ['icon'=>'bi-grid-3x3-gap','label'=>'Module Choice'],
                    ];
                @endphp
                @foreach($tabs as $id => $tab)
                    <li class="nav-item">
                        <a class="nav-link {{ $id === 'qual' ? 'active' : '' }} small py-2 px-3"
                           data-bs-toggle="tab" href="#tab-{{ $id }}">
                            <i class="bi {{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content p-4">

                <!-- ── TAB: Qualifications ─────────────────────────── -->
                <div class="tab-pane fade show active" id="tab-qual">
                    <form method="POST" action="{{ route('fc-reg.registration.step3.qualifications') }}">
                        @csrf
                        <div id="qualContainer">
                        @forelse($qualifications as $i => $q)
                            @include('partials.qualification-row', ['q'=>$q,'i'=>$i,'qualificationMasters'=>$qualificationMasters,'boardMasters'=>$boardMasters,'streamMasters'=>$streamMasters])
                        @empty
                            @include('partials.qualification-row', ['q'=>null,'i'=>0,'qualificationMasters'=>$qualificationMasters,'boardMasters'=>$boardMasters,'streamMasters'=>$streamMasters])
                        @endforelse
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addRow('qual')">
                            <i class="bi bi-plus-circle me-1"></i>Add Qualification
                        </button>
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save me-1"></i>Save Qualifications
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ── TAB: Higher Education ──────────────────────── -->
                <div class="tab-pane fade" id="tab-higher">
                    <form method="POST" action="{{ route('fc-reg.registration.step3.higher-education') }}">
                        @csrf
                        <p class="text-muted small mb-3">Add any post-graduation, PhD or diploma qualifications.</p>
                        <div id="higherContainer">
                        @forelse($higherEdus as $i => $h)
                            @include('partials.higher-edu-row', ['h'=>$h,'i'=>$i])
                        @empty
                            @include('partials.higher-edu-row', ['h'=>null,'i'=>0])
                        @endforelse
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addRow('higher')">
                            <i class="bi bi-plus-circle me-1"></i>Add Higher Education
                        </button>
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button>
                        </div>
                    </form>
                </div>

                <!-- ── TAB: Employment ────────────────────────────── -->
                <div class="tab-pane fade" id="tab-employ">
                    <form method="POST" action="{{ route('fc-reg.registration.step3.employment') }}">
                        @csrf
                        <p class="text-muted small mb-3">Prior employment (if any) before joining the service.</p>
                        <div id="employContainer">
                        @forelse($employments as $i => $e)
                            @include('partials.employment-row', ['e'=>$e,'i'=>$i,'jobTypes'=>$jobTypes])
                        @empty
                            @include('partials.employment-row', ['e'=>null,'i'=>0,'jobTypes'=>$jobTypes])
                        @endforelse
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addRow('employ')">
                            <i class="bi bi-plus-circle me-1"></i>Add Employment
                        </button>
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button>
                        </div>
                    </form>
                </div>

                <!-- ── TAB: Spouse / Family ───────────────────────── -->
                <div class="tab-pane fade" id="tab-spouse">
                    <form method="POST" action="{{ route('fc-reg.registration.step3.spouse') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Spouse Name</label>
                                <input type="text" name="spouse_name" class="form-control"
                                       value="{{ old('spouse_name', $spouse?->spouse_name) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Date of Birth</label>
                                <input type="date" name="spouse_dob" class="form-control"
                                       value="{{ old('spouse_dob', $spouse?->spouse_dob?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Occupation</label>
                                <input type="text" name="spouse_occupation" class="form-control"
                                       value="{{ old('spouse_occupation', $spouse?->spouse_occupation) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Organisation</label>
                                <input type="text" name="spouse_organisation" class="form-control"
                                       value="{{ old('spouse_organisation', $spouse?->spouse_organisation) }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">No. of Children</label>
                                <input type="text" name="no_of_children" class="form-control"
                                       value="{{ old('no_of_children', $spouse?->no_of_children) }}">
                            </div>
                            <div class="col-md-10">
                                <label class="form-label small fw-semibold">Children Details (Name, Age, Gender)</label>
                                <textarea name="children_details" class="form-control" rows="2">{{ old('children_details', $spouse?->children_details) }}</textarea>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button>
                        </div>
                    </form>
                </div>

                <!-- ── TAB: Languages ─────────────────────────────── -->
                <div class="tab-pane fade" id="tab-language">
                    <form method="POST" action="{{ route('fc-reg.registration.step3.languages') }}">
                        @csrf
                        <div id="langContainer">
                        @forelse($languages as $i => $l)
                            @include('partials.language-row', ['l'=>$l,'i'=>$i,'languageMasters'=>$languageMasters])
                        @empty
                            @include('partials.language-row', ['l'=>null,'i'=>0,'languageMasters'=>$languageMasters])
                        @endforelse
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addRow('lang')">
                            <i class="bi bi-plus-circle me-1"></i>Add Language
                        </button>
                        <hr>
                        <h6 class="fw-semibold small">Hindi Knowledge</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Medium of Study</label>
                                <input type="text" name="medium_of_study" class="form-control"
                                       value="{{ old('medium_of_study', $hindi?->medium_of_study) }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" name="hindi_medium_school" value="1" class="form-check-input"
                                           {{ old('hindi_medium_school', $hindi?->hindi_medium_school) ? 'checked' : '' }}>
                                    <label class="form-check-label small">Studied in Hindi Medium School</label>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" name="hindi_subject_studied" value="1" class="form-check-input"
                                           {{ old('hindi_subject_studied', $hindi?->hindi_subject_studied) ? 'checked' : '' }}>
                                    <label class="form-check-label small">Hindi as Subject</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Highest Hindi Exam Passed</label>
                                <input type="text" name="highest_hindi_exam" class="form-control"
                                       value="{{ old('highest_hindi_exam', $hindi?->highest_hindi_exam) }}">
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button>
                        </div>
                    </form>
                </div>

                <!-- ── TAB: Hobbies ───────────────────────────────── -->
                <div class="tab-pane fade" id="tab-hobbies">
                    <form method="POST" action="{{ route('fc-reg.registration.step3.hobbies') }}">
                        @csrf
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Hobbies</label>
                                <textarea name="hobbies" class="form-control" rows="3"
                                    placeholder="Reading, Photography, Trekking…">{{ old('hobbies', $hobbies?->hobbies) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Special Skills</label>
                                <textarea name="special_skills" class="form-control" rows="3">{{ old('special_skills', $hobbies?->special_skills) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Extra Curricular Activities</label>
                                <textarea name="extra_curricular" class="form-control" rows="3">{{ old('extra_curricular', $hobbies?->extra_curricular) }}</textarea>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button>
                        </div>
                    </form>
                </div>

                <!-- ── TAB: Distinctions ──────────────────────────── -->
                <div class="tab-pane fade" id="tab-distinc">
                    <form method="POST" action="{{ route('fc-reg.registration.step3.distinctions') }}">
                        @csrf
                        <div id="disinctContainer">
                        @forelse($distinctions as $i => $d)
                            @include('partials.distinction-row', ['d'=>$d,'i'=>$i])
                        @empty
                            @include('partials.distinction-row', ['d'=>null,'i'=>0])
                        @endforelse
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addRow('distc')">
                            <i class="bi bi-plus-circle me-1"></i>Add Distinction
                        </button>
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button>
                        </div>
                    </form>
                </div>

                <!-- ── TAB: Sports ────────────────────────────────── -->
                <div class="tab-pane fade" id="tab-sports">
                    <form method="POST" action="{{ route('fc-reg.registration.step3.sports') }}">
                        @csrf
                        <h6 class="fw-semibold small mb-2">Sports Played / Fitness</h6>
                        <div id="sportsPlayedContainer">
                        @forelse($sportsPlayed as $i => $sp)
                            @include('partials.sports-played-row', ['sp'=>$sp,'i'=>$i,'sportsMasters'=>$sportsMasters])
                        @empty
                            @include('partials.sports-played-row', ['sp'=>null,'i'=>0,'sportsMasters'=>$sportsMasters])
                        @endforelse
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addRow('sportsp')">
                            <i class="bi bi-plus-circle me-1"></i>Add Sport
                        </button>
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button>
                        </div>
                    </form>
                </div>

                <!-- ── TAB: Module Choice ─────────────────────────── -->
                <div class="tab-pane fade" id="tab-module">
                    <form method="POST" action="{{ route('fc-reg.registration.step3.module') }}">
                        @csrf
                        <p class="text-muted small">Select your preferred module for the Foundation Course programme.</p>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">First Choice Module <span class="text-danger">*</span></label>
                                <select name="chosen_module" class="form-select" required>
                                    <option value="">Select module…</option>
                                    @foreach(['Public Administration','Policy Studies','Economics','Law','Science & Technology','Rural Development'] as $m)
                                        <option value="{{ $m }}" {{ old('chosen_module', $moduleChoice?->chosen_module) == $m ? 'selected' : '' }}>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Second Choice Module</label>
                                <select name="second_module" class="form-select">
                                    <option value="">Select module…</option>
                                    @foreach(['Public Administration','Policy Studies','Economics','Law','Science & Technology','Rural Development'] as $m)
                                        <option value="{{ $m }}" {{ old('second_module', $moduleChoice?->second_module) == $m ? 'selected' : '' }}>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 d-flex justify-content-between align-items-center border-top pt-3">
                            <a href="{{ route('fc-reg.registration.step2') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                Complete Step 3 &amp; Continue <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>

            </div><!-- /tab-content -->
        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
// Generic dynamic row adder
function addRow(type) {
    // Each partial has a template stored in a hidden div
    const tmpl = document.getElementById('tmpl-' + type);
    if (!tmpl) return;
    const container = document.getElementById({
        'qual':'qualContainer','higher':'higherContainer','employ':'employContainer',
        'lang':'langContainer','distc':'disinctContainer','sportsp':'sportsPlayedContainer'
    }[type]);
    const count = container.querySelectorAll('.dynamic-row').length;
    const html  = tmpl.innerHTML.replace(/__INDEX__/g, count);
    container.insertAdjacentHTML('beforeend', html);
}
function removeRow(btn) { btn.closest('.dynamic-row').remove(); }
</script>
@endpush
