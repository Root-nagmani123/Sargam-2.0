{{-- PHP-derived values the page JS reads from window.PMBE_CFG. --}}
@php
    $__pmbeClientTypeOptions = [];
    foreach (($clientTypes ?? []) as $__pmbeK => $__pmbeLabel) {
        $__pmbeClientTypeOptions[$__pmbeK] = [];
        if (isset($clientTypeCategories[$__pmbeK])) {
            foreach ($clientTypeCategories[$__pmbeK] as $__pmbeCat) {
                $__pmbeClientTypeOptions[$__pmbeK][] = [
                    'value' => (string) $__pmbeCat->id,
                    'text' => (string) $__pmbeCat->client_name,
                    'dataClientName' => strtolower((string) ($__pmbeCat->client_name ?? '')),
                ];
            }
        }
    }
    $__pmbeOtCourseOptions = [];
    if (isset($otCourses)) {
        foreach ($otCourses as $__pmbeCourse) {
            $__pmbeOtCourseOptions[] = ['value' => (string) $__pmbeCourse->pk, 'text' => (string) $__pmbeCourse->course_name];
        }
    }
@endphp
<script>
    window.PMBE_CFG = {
        paymentDetailsUrlTemplate: @json(route('admin.mess.process-mess-bills-employee.payment-details', ['id' => '__ID__'])),
        printReceiptUrlTemplate: @json(route('admin.mess.process-mess-bills-employee.print-receipt', ['id' => '__ID__'])),
        generateInvoiceBaseUrl: @json(url('admin/mess/process-mess-bills-employee')),
        modalDataUrl: @json(route('admin.mess.process-mess-bills-employee.modal-data')),
        generateInvoiceExportUrl: @json(route('admin.mess.process-mess-bills-employee.generate-invoice-export')),
        studentsByCourseUrl: @json(url('/admin/mess/selling-voucher-date-range/students-by-course')),
        buyersForReportUrl: @json(route('admin.mess.reports.category-wise-print-slip.buyers')),
        courseBuyersByCourseUrl: @json(url('/admin/mess/reports/category-wise-print-slip/course-buyers')),
        indexFormAction: @json(route('admin.mess.process-mess-bills-employee.index')),
        defaultDateFrom: @json(now()->startOfMonth()->format('d-m-Y')),
        defaultDateTo: @json(now()->endOfMonth()->format('d-m-Y')),
        defaultInvoiceDate: @json(now()->format('d-m-Y')),
        clientTypeOptions: @json($__pmbeClientTypeOptions, JSON_UNESCAPED_UNICODE),
        otCourseOptions: @json($__pmbeOtCourseOptions, JSON_UNESCAPED_UNICODE),
        employeeNamesByStaffType: {
            'academy staff': @json($filterEmployeeBuyerOptions ?? [], JSON_UNESCAPED_UNICODE),
            'faculty': @json($filterFacultyBuyerOptions ?? [], JSON_UNESCAPED_UNICODE),
            'mess staff': @json($filterMessStaffBuyerOptions ?? [], JSON_UNESCAPED_UNICODE)
        },
        allBuyerNames: @json(($allBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE),
        otBuyerNames: @json(($otBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE),
        courseBuyerNames: @json(($courseBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE),
        otherBuyerNames: @json(($otherBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE),
        sectionBuyerNames: @json(($sectionBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE),
        preservedClientTypePk: @json((array) ($clientTypePks ?? request('client_type_pk', []))),
        preservedBuyerName: @json((array) ($buyerNames ?? request('buyer_name', []))),
        periodText: @json('Period: ' . ($dateFromDisplay ?? '') . ' to ' . ($dateToDisplay ?? ''))
    };
</script>
