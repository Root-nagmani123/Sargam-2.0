<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

<div class="mb-3">
    {{-- Filters --}}
    @if(!empty($filters))
        <form id="{{ $id }}-filters" class="row g-2">
            <input type="hidden" name="lead_type" id="{{ $id }}-lead-type">
            <input type="hidden" name="sub_type"  id="{{ $id }}-sub-type">
            @foreach($filters as $filter)
                <div class="col-auto">
                    <select name="{{ $filter['name'] }}" class="form-control filter-input">
                        <option value="">{{ $filter['label'] }}</option>
                        @foreach($filter['options'] as $key => $val)
                            <option value="{{ $key }}">{{ $val }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <div class="col-auto">
                <button type="button" class="btn btn-primary" id="{{ $id }}-filter-btn">Filter</button>
                <button type="button" class="btn btn-secondary" id="{{ $id }}-reset-btn">Reset</button>
            </div>
        </form>
    @endif
</div>

{{-- Buttons --}}
@if(!empty($buttons))
    <div id="filter-buttons" class="row mx-1">
        @foreach($buttons as $btn)
            <button class="btn btn-sm btn-outline-secondary filter-button mr-2 mb-2" data-filter="{{ $btn['filter'] }}"> {{ $btn['label'] }} <span>({{ $btn['today_count'] ?? 0 }} / {{ $btn['count'] ?? 0 }})</span></button>
        @endforeach
    </div>
@endif



<div class="row my-2">
    <div class="col-3">
        <div id="commonDateWrapper" class="d-none">
            <input type="text" id="commonDateRange" class="form-control" style="height: calc(1.5em + 0.45rem + 2px) !important;" placeholder="Select Date Range">
            <span></span>   
        </div>
        <input type="hidden" id="from_date">
        <input type="hidden" id="to_date">
    </div>
    <div class="col-9">
        @if(!empty($buttons))
            @foreach($buttons as $btn)
                @if(!empty($btn['subs']))
                    <div class="sub-button-group d-none"
                        data-parent="{{ $btn['filter'] }}">
                        @foreach($btn['subs'] as $sub)
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary me-2 mb-2 sub-filter"
                                data-value="{{ $sub['value'] }}"
                            >
                                {{ $sub['label'] }} <span>({{isset($sub['sub_count']) ? $sub['sub_count'] : ''}} @if(isset($sub['sub_count'])) / @endif {{ $sub['count'] ?? 0 }})</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            @endforeach
        @endif
    </div>
    <div class="col-12 my-2">
        <div id="activeFilters" class="py-2 px-3 d-none text-muted" style="background: lightyellow;">
            <strong>Filters Applied:</strong>
            <span id="activeFiltersText"></span>
        </div>
    </div>
</div>  




<table class="table table-bordered" id="{{ $id }}">
    <thead>
        <tr class="table-default-primary">
            @foreach($columns as $col)
                <th>{{ $col['title'] }}</th>
            @endforeach
        </tr>
    </thead>
</table>

@push('scripts')
<!-- Popper.js (SECOND) -->
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<!-- DataTables JS -->
<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    $(function () {
        function applyDate(start = null, end = null) {
            if (!start || !end) {
                start = moment();
                end   = moment();
            }

            $('#commonDateRange').val(
                start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY')
            );

            $('#from_date').val(start.format('YYYY-MM-DD'));
            $('#to_date').val(end.format('YYYY-MM-DD'));

            table.draw();
        }

        $('#commonDateRange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear'
            },
            ranges: {
                'All': [moment().subtract(10, 'years'), moment().add(10, 'years')],
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [
                    moment().subtract(1, 'month').startOf('month'),
                    moment().subtract(1, 'month').endOf('month')
                ]
            }
        });

        // Apply
        $('#commonDateRange').on('apply.daterangepicker', function (ev, picker) {
            applyDate(picker.startDate, picker.endDate);
        });

        // Clear
        $('#commonDateRange').on('cancel.daterangepicker', function () {
            $(this).val('');
            $('#from_date').val('');
            $('#to_date').val('');
            $('#{{ $id }}').DataTable().draw();
        });

        $('#{{ $id }}').DataTable().destroy();

        var table = $('#{{ $id }}').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ $ajaxRoute }}',
                data: function(d) {
                    @if(!empty($filters))
                        $('#{{ $id }}-filters .filter-input').each(function(){
                            d[$(this).attr('name')] = $(this).val();
                        });
                    @endif

                    // button filter
                    d.lead_type = $('#{{ $id }}-lead-type').val();
                    d.sub_type  = $('#{{ $id }}-sub-type').val();
                    // date filter
                    d.from_date = $('#from_date').val();
                    d.to_date   = $('#to_date').val();  
                }
            },
            drawCallback: function (settings) {
                let rows = $('#{{ $id }} tbody tr');
                rows.hide().fadeIn(500);
                updateActiveFilters(settings.json);
            },
            columns: [
                @foreach($columns as $col)
                {
                    data: '{{ $col['data'] }}',
                    name: '{{ $col['name'] ?? $col['data'] }}',
                    orderable: {{ isset($col['orderable']) ? ($col['orderable'] ? 'true' : 'false') : 'true' }},
                    searchable: {{ isset($col['searchable']) ? ($col['searchable'] ? 'true' : 'false') : 'true' }}
                },
                @endforeach
            ]
        });

        @if(!empty($filters))
            $('#{{ $id }}-filter-btn').click(function() {
                table.draw();
            });

            $('#{{ $id }}-reset-btn').click(function() {
                $('#{{ $id }}-filters')[0].reset();
                table.draw();
            });
        @endif

        // MAIN BUTTON CLICK
        $('.filter-button').on('click', function () {

            let filter = $(this).data('filter');

            $('.filter-button')
                .removeClass('btn-primary')
                .addClass('btn-outline-secondary');

            $(this)
                .addClass('btn-primary')
                .removeClass('btn-outline-secondary');

            $('#{{ $id }}-lead-type').val(filter);
            $('#{{ $id }}-sub-type').val('');

            $('.sub-button-group').addClass('d-none');
            $('#commonDateWrapper').removeClass('d-none');
            $('.sub-filter').removeClass('btn-dark text-light');
            $('#from_date, #to_date').val('');
            applyDate();
            $('.sub-button-group[data-parent="' + filter + '"]').removeClass('d-none');

            // table.draw();
        });

        // SUB BUTTON CLICK
        $('.sub-filter').on('click', function () {

            $('.sub-filter').removeClass('btn-dark text-light');
            $(this).addClass('btn-dark text-light');
            $('#{{ $id }}-sub-type').val($(this).data('value'));
            table.draw();
        });

        // RESET
        $('#{{ $id }}-reset-btn').on('click', function () {
            $('#{{ $id }}-filters')[0].reset();
            $('#{{ $id }}-lead-type').val('');
            $('#{{ $id }}-sub-type').val('');
            $('#from_date, #to_date').val('');
            $('.filter-button').removeClass('btn-primary').addClass('btn-outline-secondary');     
            $('.sub-filter').removeClass('btn-dark text-light');
            $('.sub-button-group').addClass('d-none');
            table.draw();
        });


        function updateActiveFilters(json) {

            let parts = [];

            let leadType = $('#{{ $id }}-lead-type').val();
            let subType  = $('#{{ $id }}-sub-type').val();
            let fromDate = $('#from_date').val();
            let toDate   = $('#to_date').val();

            let count = json?.recordsFiltered ?? 0;

            if (leadType) {
                parts.push(`<strong>${leadType}</strong>`);
            }

            if (subType) {
                parts.push(`<strong>${subType}</strong>`);
            }

            if (fromDate && toDate) {
                parts.push(
                    `<strong>Date:</strong> ${moment(fromDate).format('DD-MM-YYYY')} → ${moment(toDate).format('DD-MM-YYYY')}`
                );
            }

            if (parts.length) {
                let message = parts.join(', ') + ` <strong>(${count} records)</strong>`;
                $('#activeFiltersText').html(message);
                $('#activeFilters').removeClass('d-none');
            } else {
                $('#activeFilters').addClass('d-none');
                $('#activeFiltersText').html('');
            }
        }
    });
</script>
@endpush