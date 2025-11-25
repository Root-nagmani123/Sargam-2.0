@extends('admin.layouts.master')

@section('title', 'Dashboard - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')

<div class="container-fluid">

    <div class="row mb-3">
        <div class="col-9">
            <div class="container my-4">
                <div class="row g-3 mb-3">
                    <!-- Total Students -->
                    <div class="col-md-3">
                        <div class="p-3 rounded-4 shadow-sm h-100" style="background: #EFF2FF;">
                            <div class="row">
                                <div class="col-3 d-flex align-items-center justify-content-center">
                                    <!-- <i class="material-icons menu-icon material-symbols-rounded text-primary my-filled-icon"
                                        style="font-size: 30px;">groups_2</i> -->
                                    <img src="{{ asset('images/groups.svg') }}" alt="Total Students"
                                        style="width:30px;">
                                </div>
                                <div class="col-9">
                                    <h6 class="text-muted mb-1">Total Students</h6>
                                    <div class="d-flex align-items-center gap-2">

                                        <h3 class="m-0 fw-bold">1,247</h3>
                                    </div>
                                    <p class="text-primary small mt-2 fw-semibold">+12% from last month</p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Active Teachers -->
                    <div class="col-md-3">
                        <div class="p-3 rounded-4 shadow-sm h-100" style="background: #E8F7ED;">
                            <div class="row">
                                <div class="col-3 d-flex align-items-center justify-content-center">
                                    <!-- <i class="material-icons menu-icon material-symbols-rounded text-success my-filled-icon"
                                        style="font-size: 30px;">groups_2</i> -->
                                    <img src="{{ asset('images/teachers.svg') }}" alt="Total Students"
                                        style="width:30px;">
                                </div>
                                <div class="col-9">
                                    <h6 class="text-muted mb-1">Active Teachers</h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <h3 class="m-0 fw-bold">89</h3>
                                    </div>
                                    <p class="text-success small mt-2 fw-semibold">+3% from last month</p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Total Classes -->
                    <div class="col-md-3">
                        <div class="p-3 rounded-4 shadow-sm h-100" style="background: #FFF4E6;">
                            <div class="row">
                                <div class="col-3 d-flex align-items-center justify-content-center">
                                    <!-- <i class="material-icons menu-icon material-symbols-rounded text-warning"
                                                    style="font-size: 30px;">school</i> -->
                                    <img src="{{ asset('images/classes.svg') }}" alt="Total Classes"
                                        style="width:30px;">
                                </div>
                                <div class="col-9">
                                    <h6 class="text-muted mb-1">Total Classes</h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <h3 class="m-0 fw-bold">45</h3>
                                    </div>
                                    <p class="text-warning small mt-2 fw-semibold">+2 new classes</p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Attendance Rate -->
                    <div class="col-md-3">
                        <div class="p-3 rounded-4 shadow-sm h-100" style="background: #F0EDFF;">
                            <div class="row">
                                <div class="col-3 d-flex align-items-center justify-content-center">
                                    <!-- <i class="material-icons menu-icon material-symbols-rounded text-purple"
                                        style="font-size: 30px;">groups_2</i> -->
                                    <img src="{{ asset('images/attendance.svg') }}" alt="Attendance Rate"
                                        style="width:30px;">
                                </div>
                                <div class="col-9">
                                    <h6 class="text-muted mb-1">Attendance Rate</h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <h3 class="m-0 fw-bold">94.5%</h3>
                                    </div>
                                    <p class="text-primary small mt-2 fw-semibold">+2.1% improvement</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="col-3 text-end">
    <x-dropdown 
    label="Select Teacher"
    :items="['Amit', 'Rohan', 'Shreya']" 
/>
    <x-dropdown 
    label="Select Teacher"
    :items="['Amit', 'Rohan', 'Shreya']" 
/>
    <x-dropdown 
    label="Select Teacher"
    :items="['Amit', 'Rohan', 'Shreya']" 
/>

</div>

    </div>
    <div class="card">
        <div class="card-header my-2" style="background:transparent;">
            <h2 class="card-title fw-bold" style="font-size:24px;">Admin Summary</h2>
        </div>
        <hr class="my-2">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-8">
                    <!-- Content goes here -->
                    <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                        to various administrative functions.</p>
                    <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                        to various administrative functions.</p>
                    <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                        to various administrative functions.</p>
                    <p>Welcome to the Admin Dashboard! Here you can find a summary of key metrics and quick access
                        to various administrative functions.</p>
                </div>
                <div class="col-4">
                    <x-calendar :year="$year" :month="$month" :selected="now()->toDateString()" :events="$events" theme="gov-red" />
                </div>
            </div>
            <h3 class="fw-bold py-3">Today Birthdays</h3>
            <hr class="my-2">
            <div class="container my-4">
                <div class="row g-4">

                    <!-- Example Card (Repeat in Loop) -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card shadow-sm border-0 h-100" style="background:#E4F2E9; border-radius:20px;">
                            <div class="card-body d-flex align-items-start gap-3 p-4">

                                <!-- Profile Image -->
                                <img src="/mnt/data/Frame 1000002820.png" alt="Profile photo of Dalip Bisht"
                                    class="rounded-circle" style="width:60px; height:60px; object-fit:cover;">

                                <!-- Details -->
                                <div>
                                    <h5 class="fw-bold mb-1">Dalip Bisht</h5>
                                    <p class="text-muted small mb-2">Field or Office Assistant</p>
                                    <p class="mb-0 small">
                                        dalip.bisht12@gmail.com <br>
                                        7451992666
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- EXTRA SAMPLE CARDS — Change BG color to match your design -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card shadow-sm border-0 h-100" style="background:#FFF7DF; border-radius:20px;">
                            <div class="card-body d-flex align-items-start gap-3 p-4">
                                <img src="/mnt/data/Frame 1000002820.png" class="rounded-circle"
                                    style="width:60px; height:60px; object-fit:cover;" alt="Profile photo">
                                <div>
                                    <h5 class="fw-bold mb-1">Abhishek</h5>
                                    <p class="text-muted small mb-2">FC-100 2025</p>
                                    <p class="mb-0 small">abhisheksuper100@gmail.com<br>6386824515</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card shadow-sm border-0 h-100" style="background:#E8E8E9; border-radius:20px;">
                            <div class="card-body d-flex align-items-start gap-3 p-4">
                                <img src="/mnt/data/Frame 1000002820.png" class="rounded-circle"
                                    style="width:60px; height:60px; object-fit:cover;" alt="Profile photo">
                                <div>
                                    <h5 class="fw-bold mb-1">Abhishek</h5>
                                    <p class="text-muted small mb-2">FC-100 2025</p>
                                    <p class="mb-0 small">abhisheksuper100@gmail.com<br>6386824515</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Row -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card shadow-sm border-0 h-100" style="background:#ECEAF5; border-radius:20px;">
                            <div class="card-body d-flex align-items-start gap-3 p-4">
                                <img src="/mnt/data/Frame 1000002820.png" class="rounded-circle"
                                    style="width:60px; height:60px; object-fit:cover;" alt="Profile photo">
                                <div>
                                    <h5 class="fw-bold mb-1">Amitaj Pangtey</h5>
                                    <p class="text-muted small mb-2">FC-100 2025</p>
                                    <p class="mb-0 small">amitejpangteysah@gmail.com<br>9958114776</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card shadow-sm border-0 h-100" style="background:#F5DCE0; border-radius:20px;">
                            <div class="card-body d-flex align-items-start gap-3 p-4">
                                <img src="/mnt/data/Frame 1000002820.png" class="rounded-circle"
                                    style="width:60px; height:60px; object-fit:cover;" alt="Profile photo">
                                <div>
                                    <h5 class="fw-bold mb-1">Amitaj Pangtey</h5>
                                    <p class="text-muted small mb-2">FC-100 2025</p>
                                    <p class="mb-0 small">amitejpangteysah@gmail.com<br>9958114776</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card shadow-sm border-0 h-100" style="background:#D3E1EF; border-radius:20px;">
                            <div class="card-body d-flex align-items-start gap-3 p-4">
                                <img src="/mnt/data/Frame 1000002820.png" class="rounded-circle"
                                    style="width:60px; height:60px; object-fit:cover;" alt="Profile photo">
                                <div>
                                    <h5 class="fw-bold mb-1">Amitaj Pangtey</h5>
                                    <p class="text-muted small mb-2">FC-100 2025</p>
                                    <p class="mb-0 small">amitejpangteysah@gmail.com<br>9958114776</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>


        </div>
    </div>
</div>



@push('scripts')
<script>
    // Lightweight calendar interactions (vanilla JS)
document.addEventListener('DOMContentLoaded', function(){
document.querySelectorAll('.calendar-component').forEach(function(comp){
const yearSel = comp.querySelector('.calendar-year');
const monthSel = comp.querySelector('.calendar-month');
const cells = comp.querySelectorAll('.calendar-cell');


// Click a date -> emit custom event
comp.addEventListener('click', function(e){
const td = e.target.closest('.calendar-cell');
if(!td) return;
const prev = comp.querySelector('.calendar-cell.is-selected');
if(prev) prev.classList.remove('is-selected');
td.classList.add('is-selected');


const date = td.dataset.date;
comp.dispatchEvent(new CustomEvent('dateSelected', { detail: { date } }));
});


// keyboard support for cells
cells.forEach(function(cell){
cell.addEventListener('keydown', function(ev){
if(ev.key === 'Enter' || ev.key === ' ') {
ev.preventDefault();
cell.click();
}
// Arrow navigation (left/right/up/down)
const idx = Array.prototype.indexOf.call(cells, cell);
let targetIdx = null;
if(ev.key === 'ArrowLeft') targetIdx = idx - 1;
if(ev.key === 'ArrowRight') targetIdx = idx + 1;
if(ev.key === 'ArrowUp') targetIdx = idx - 7;
if(ev.key === 'ArrowDown') targetIdx = idx + 7;
if(targetIdx !== null && cells[targetIdx]) {
cells[targetIdx].focus();
ev.preventDefault();
}
});
});


// Change month/year -> navigate by query params (simple behavior)
yearSel.addEventListener('change', function(){
const y = this.value; const m = monthSel.value;
const url = new URL(window.location.href);
url.searchParams.set('year', y);
url.searchParams.set('month', m);
window.location.href = url.toString();
});
monthSel.addEventListener('change', function(){
const y = yearSel.value; const m = this.value;
const url = new URL(window.location.href);
url.searchParams.set('year', y);
url.searchParams.set('month', m);
window.location.href = url.toString();
});
});
});
</script>

@endpush
@endsection