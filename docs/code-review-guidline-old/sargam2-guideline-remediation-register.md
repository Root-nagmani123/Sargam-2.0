# Sargam 2.0 - Developer Guideline Remediation Register

Line-level index of every location requiring investigation, grouped by guideline clause.
Each entry gives file, line number, enclosing function and the offending code.

Scanned: 643 PHP files under app/, 848 Blade views.

| Clause | Sites | Files |
| --- | ---: | ---: |
| G7 - manual transactions (14 files at risk) | 41 | 19 |
| G8-A - whereDate() / orWhereDate() | 304 | 37 |
| G8-B - whereYear() / whereMonth() / whereDay() | 3 | 2 |
| G8-C - SQL functions in raw predicates | 145 | 26 |
| G1-A - Model::all() | 52 | 25 |
| G1-B - DB::table(..)->get() | 2 | 2 |
| G2-A - controllers rendering views, no pagination | 42 | 42 |
| G2-B - client-side DataTables | 65 | 65 |
| G5-A - DB calls inside loops | 319 | 54 |
| G5-B - queries inside Blade views | 19 | 11 |

---

## G7 - Manual transactions

A transaction rolled back only under catch (\Exception) leaks an OPEN TRANSACTION when
an \Error (\TypeError, \ValueError, ...) is thrown, holding locks until the connection is
torn down. Files marked AT RISK contain zero catch (\Throwable).

Preferred remedy: replace with DB::transaction(function () { ... }).
Minimum remedy: change the catch to \Throwable.

### `app/Http/Controllers/Admin/UserController.php`

**AT RISK - no catch (\Throwable)** - begins: 4, commits: 4, rollbacks: 4, catch (\Exception): 13

```text
L4155    store()   DB::beginTransaction()
L4261    update()   DB::beginTransaction()
L4455    assignRoleSave()   DB::beginTransaction()
L4511    assignRoleSave()   DB::beginTransaction()
```

### `app/Http/Controllers/Admin/FacultyController.php`

**AT RISK - no catch (\Throwable)** - begins: 4, commits: 4, rollbacks: 5, catch (\Exception): 4

```text
L67      store()   DB::beginTransaction()
L318     store()   DB::beginTransaction()
L606     update()   DB::beginTransaction()
L1006    destroy()   DB::beginTransaction()
```

### `app/Http/Controllers/Admin/Registration/FormController.php`

**AT RISK - no catch (\Throwable)** - begins: 3, commits: 3, rollbacks: 3, catch (\Exception): 6

```text
L123     template_store()   DB::beginTransaction()
L245     cloneForm()   DB::beginTransaction()
L476     saveform()   DB::beginTransaction()
```

### `app/Http/Controllers/Admin/Registration/EnrollementController.php`

**AT RISK - no catch (\Throwable)** - begins: 3, commits: 3, rollbacks: 3, catch (\Exception): 9

```text
L122     store()   DB::beginTransaction()
L194     store()   DB::beginTransaction()
L969     update()   DB::beginTransaction()
```

### `app/Http/Controllers/Mess/KitchenIssueController.php`

**AT RISK - no catch (\Throwable)** - begins: 3, commits: 3, rollbacks: 9, catch (\Exception): 7

```text
L1361    store()   DB::beginTransaction()
L1772    update()   DB::beginTransaction()
L1988    updateReturn()   DB::beginTransaction()
```

### `app/Http/Controllers/Admin/Security/VisitorPassController.php`

**AT RISK - no catch (\Throwable)** - begins: 2, commits: 2, rollbacks: 2, catch (\Exception): 7

```text
L49      store()   DB::beginTransaction()
L155     update()   DB::beginTransaction()
```

### `app/Http/Controllers/Mess/KitchenIssueApprovalController.php`

**AT RISK - no catch (\Throwable)** - begins: 2, commits: 2, rollbacks: 2, catch (\Exception): 2

```text
L74      approve()   DB::beginTransaction()
L120     reject()   DB::beginTransaction()
```

### `app/Http/Controllers/Admin/Registration/FormEditController.php`

**AT RISK - no catch (\Throwable)** - begins: 2, commits: 2, rollbacks: 2, catch (\Exception): 2

```text
L79      fc_update()   DB::beginTransaction()
L189     fc_update()   DB::beginTransaction()
```

### `app/Http/Controllers/Admin/CourseController.php`

**AT RISK - no catch (\Throwable)** - begins: 2, commits: 2, rollbacks: 4, catch (\Exception): 7

```text
L183     store()   DB::beginTransaction()
L451     destroy()   DB::beginTransaction()
```

### `app/Http/Controllers/Admin/IssueManagement/IssueManagementController.php`

**AT RISK - no catch (\Throwable)** - begins: 2, commits: 2, rollbacks: 2, catch (\Exception): 12

```text
L1003    update()   DB::beginTransaction()
L1418    status_update()   DB::beginTransaction()
```

### `app/Http/Controllers/Admin/PeerEvaluationController.php`

**AT RISK - no catch (\Throwable)** - begins: 1, commits: 1, rollbacks: 1, catch (\Exception): 15

```text
L491     store()   DB::beginTransaction()
```

### `app/Imports/CourseWiseOTImport.php`

**AT RISK - no catch (\Throwable)** - begins: 1, commits: 1, rollbacks: 1, catch (\Exception): 3

```text
L36      collection()   DB::beginTransaction()
```

### `app/Http/Controllers/Admin/Registration/StudentImportController.php`

**AT RISK - no catch (\Throwable)** - begins: 1, commits: 1, rollbacks: 1, catch (\Exception): 7

```text
L137     migrate()   DB::beginTransaction()
```

### `app/Http/Controllers/Admin/IssueManagement/IssueEscalationMatrixController.php`

**AT RISK - no catch (\Throwable)** - begins: 1, commits: 1, rollbacks: 1, catch (\Exception): 1

```text
L216     store()   DB::beginTransaction()
```

### `app/Http/Controllers/Mess/SellingVoucherDateRangeController.php`

**has 1 catch (\Throwable)** - begins: 4, commits: 4, rollbacks: 13, catch (\Exception): 12

```text
L609     store()   DB::beginTransaction()
L1355    update()   DB::beginTransaction()
L1598    updateFilteredSellingVoucherDateRange()   DB::beginTransaction()
L1994    updateReturn()   DB::beginTransaction()
```

### `app/Http/Controllers/Admin/Registration/FrontPageController.php`

**has 1 catch (\Throwable)** - begins: 1, commits: 1, rollbacks: 1, catch (\Exception): 2

```text
L777     pathPageSave()   DB::beginTransaction()
```

### `app/Http/Controllers/Admin/MemoDisciplineController.php`

**has 1 catch (\Throwable)** - begins: 1, commits: 1, rollbacks: 1, catch (\Exception): 9

```text
L1050    memoDisciplineConversationStore()   DB::beginTransaction()
```

### `app/Http/Controllers/Admin/Security/EmployeeIDCardApprovalController.php`

**has 8 catch (\Throwable)** - begins: 3, commits: 3, rollbacks: 5, catch (\Exception): 7

```text
L1781    approve2()   DB::beginTransaction()
L2042    approve3()   DB::beginTransaction()
L2178    approve3()   DB::beginTransaction()
```

### `app/Http/Controllers/Mess/ProcessMessBillsEmployeeController.php`

**has 22 catch (\Throwable)** - begins: 1, commits: 1, rollbacks: 3, catch (\Exception): 1

```text
L4812    generatePayment()   DB::beginTransaction()
```

---

## G8-A - whereDate() / orWhereDate()

Emits DATE(col) = ? and prevents index use on the column.

Replace with an explicit datetime range, e.g.:

    ->where('issue_date', '>=', $from.' 00:00:00')
    ->where('issue_date', '<=', $to.' 23:59:59')

### `app/Http/Controllers/Admin/FeedbackController.php` - 59 site(s)

```text
L616     showFacultyAverage()             ->orWhereDate('end_date', '>=', $currentDate);
L620     showFacultyAverage()             $q->whereDate('end_date', '<', $currentDate);
L720     showFacultyAverage()             $query->whereDate('tf.created_date', '>=', $fromDate);
L724     showFacultyAverage()             $query->whereDate('tf.created_date', '<=', $toDate);
L729     showFacultyAverage()             $query->whereDate('cm.end_date', '<', Carbon::today());
L733     showFacultyAverage()             ->orWhereDate('cm.end_date', '>=', Carbon::today());
L873     exportExcel()                    ->orWhereDate('end_date', '>=', $currentDate);
L877     exportExcel()                    $q->whereDate('end_date', '<', $currentDate);
L939     exportExcel()                    $query->whereDate('tf.created_date', '>=', $fromDate);
L943     exportExcel()                    $query->whereDate('tf.created_date', '<=', $toDate);
L948     exportExcel()                    $query->whereDate('cm.end_date', '<', $currentDate);
L952     exportExcel()                    ->orWhereDate('cm.end_date', '>=', $currentDate);
L1046    exportPdf()                      ->orWhereDate('end_date', '>=', $currentDate);
L1050    exportPdf()                      $q->whereDate('end_date', '<', $currentDate);
L1128    exportPdf()                      $query->whereDate('tf.created_date', '>=', $fromDate);
L1132    exportPdf()                      $query->whereDate('tf.created_date', '<=', $toDate);
L1137    exportPdf()                      $query->whereDate('cm.end_date', '<', Carbon::today());
L1141    exportPdf()                      ->orWhereDate('cm.end_date', '>=', Carbon::today());
L1267    printFacultyAverage()            ->when($courseType === 'current', fn($q) => $q->where(fn($q2) => $q2->whereNull('end_date')->orWhereDate('end_date', '>=', $currentDate)))
L1268    printFacultyAverage()            ->when($courseType === 'archived', fn($q) => $q->whereDate('end_date', '<', $currentDate))
L1315    printFacultyAverage()            if ($fromDate) $query->whereDate('tf.created_date', '>=', $fromDate);
L1316    printFacultyAverage()            if ($toDate) $query->whereDate('tf.created_date', '<=', $toDate);
L1318    printFacultyAverage()            $query->whereDate('cm.end_date', '<', Carbon::today());
L1320    printFacultyAverage()            $query->where(fn($q) => $q->whereNull('cm.end_date')->orWhereDate('cm.end_date', '>=', Carbon::today()));
L1547    facultyView()                    ->whereDate('end_date', '>=', Carbon::today());
L1551    facultyView()                    ->orWhereDate('end_date', '<', Carbon::today());
L1652    facultyView()                    $query->whereDate('tt.START_DATE', '>=', $fromDate);
L1656    facultyView()                    $query->whereDate('tt.END_DATE', '<=', $toDate);
L1663    facultyView()                    ->orWhereDate('cm.end_date', '<', Carbon::today());
L1667    facultyView()                    ->whereDate('cm.end_date', '>=', Carbon::today());
L2027    exportFacultyFeedback()          $query->whereDate('tt.START_DATE', '>=', $fromDate);
L2031    exportFacultyFeedback()          $query->whereDate('tt.END_DATE', '<=', $toDate);
L2037    exportFacultyFeedback()          ->orWhereDate('cm.end_date', '<', Carbon::today());
L2041    exportFacultyFeedback()          ->whereDate('cm.end_date', '>=', Carbon::today());
L2311    printFacultyFeedback()           $query->whereDate('tt.START_DATE', '>=', $fromDate);
L2314    printFacultyFeedback()           $query->whereDate('tt.END_DATE', '<=', $toDate);
L2319    printFacultyFeedback()           ->orWhereDate('cm.end_date', '<', Carbon::today());
L2323    printFacultyFeedback()           ->whereDate('cm.end_date', '>=', Carbon::today());
L2799    feedbackDetails()                ->whereDate('end_date', '>=', Carbon::today());
L2803    feedbackDetails()                ->orWhereDate('end_date', '<', Carbon::today());
L2920    feedbackDetails()                $query->whereDate('tt.START_DATE', '>=', $fromDate);
L2924    feedbackDetails()                $query->whereDate('tt.END_DATE', '<=', $toDate);
L2931    feedbackDetails()                ->orWhereDate('cm.end_date', '<', Carbon::today());
L2935    feedbackDetails()                ->whereDate('cm.end_date', '>=', Carbon::today());
L3152    exportFeedbackDetails()          $query->whereDate('tt.START_DATE', '>=', $fromDate);
L3156    exportFeedbackDetails()          $query->whereDate('tt.END_DATE', '<=', $toDate);
L3163    exportFeedbackDetails()          ->orWhereDate('cm.end_date', '<', Carbon::today());
L3167    exportFeedbackDetails()          ->whereDate('cm.end_date', '>=', Carbon::today());
L3600    resolvePendingStudentsCourseLists() ->whereDate('end_date', '>=', now()->toDateString())
L3608    resolvePendingStudentsCourseLists() $q->whereDate('end_date', '<', now()->toDateString())
L3752    buildPendingStudentsGroupedBaseQuery() $q->whereDate('c.end_date', '<', now()->toDateString())
L3757    buildPendingStudentsGroupedBaseQuery() ->whereDate('c.end_date', '>=', now()->toDateString());
L3764    buildPendingStudentsGroupedBaseQuery() $query->whereDate('t.START_DATE', '>=', $request->from_date);
L3767    buildPendingStudentsGroupedBaseQuery() $query->whereDate('t.START_DATE', '<=', $request->to_date);
L4340    applyExportFilters()             $query->whereDate('t.START_DATE', '>=', $request->from_date);
L4344    applyExportFilters()             $query->whereDate('t.START_DATE', '<=', $request->to_date);
L4898    applySummaryExportFilters()      $query->whereDate('t.START_DATE', '>=', $request->filter_from_date);
L4902    applySummaryExportFilters()      $query->whereDate('t.START_DATE', '<=', $request->filter_to_date);
L5183    defaultProgramIdForCurrentCourseFeedbackList() ->whereDate('end_date', '>=', Carbon::today())
```

### `app/Http/Controllers/Admin/UserController.php` - 44 site(s)

```text
L176     dashboard()                      ->whereDate('created_at', today())
L186     dashboard()                      ->whereDate('created_at', today())
L492     buildDashboardFeedData()         ->whereDate('created_at', today())
L732     getTodayPendingFamilyApprovalsCount() $q->whereDate('created_date', Carbon::today());
L792     getTodayPendingVehicleApprovalsCount() $twQ->whereDate('created_date', $today);
L793     getTodayPendingVehicleApprovalsCount() $fwQ->whereDate('created_date', $today);
L1063    studentList()                    ->whereDate('t.START_DATE', $snapshotDate)
L1461    otParticipantsRowMeta()          ->when($fromDate, fn ($q) => $q->whereDate('from_date', '>=', $fromDate))
L1462    otParticipantsRowMeta()          ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
L1482    otParticipantsRowMeta()          ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
L1547    otParticipantsRowMeta()          ->when($fromDate, fn ($q) => $q->whereDate('d.mdo_date', '>=', $fromDate))
L1548    otParticipantsRowMeta()          ->when($toDate, fn ($q) => $q->whereDate('d.mdo_date', '<=', $toDate))
L1571    otParticipantsRowMeta()          ->when($fromDate, fn ($q) => $q->whereDate('date', '>=', $fromDate))
L1572    otParticipantsRowMeta()          ->when($toDate, fn ($q) => $q->whereDate('date', '<=', $toDate))
L1585    otParticipantsRowMeta()          ->when($fromDate, fn ($q) => $q->whereDate('date', '>=', $fromDate))
L1586    otParticipantsRowMeta()          ->when($toDate, fn ($q) => $q->whereDate('date', '<=', $toDate))
L2132    resolveDashboardStudentListPayload() ->when($fromDate, fn ($q) => $q->whereDate('mdo_date', '>=', $fromDate))
L2133    resolveDashboardStudentListPayload() ->when($toDate, fn ($q) => $q->whereDate('mdo_date', '<=', $toDate))
L2142    resolveDashboardStudentListPayload() ->when($fromDate, fn ($q) => $q->whereDate('from_date', '>=', $fromDate))
L2143    resolveDashboardStudentListPayload() ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
L2152    resolveDashboardStudentListPayload() ->when($fromDate, fn ($q) => $q->whereDate('from_date', '>=', $fromDate))
L2153    resolveDashboardStudentListPayload() ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
L2373    augmentStudentListEntries()      ->when($fromDate, fn ($q) => $q->whereDate('mdo_date', '>=', $fromDate))
L2374    augmentStudentListEntries()      ->when($toDate, fn ($q) => $q->whereDate('mdo_date', '<=', $toDate))
L2383    augmentStudentListEntries()      ->when($fromDate, fn ($q) => $q->whereDate('from_date', '>=', $fromDate))
L2384    augmentStudentListEntries()      ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
L2393    augmentStudentListEntries()      ->when($fromDate, fn ($q) => $q->whereDate('from_date', '>=', $fromDate))
L2394    augmentStudentListEntries()      ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
L2481    resolveScopedTimetableOptions()  ->when($fromDate, fn ($q) => $q->whereDate('t.START_DATE', '>=', $fromDate))
L2482    resolveScopedTimetableOptions()  ->when($toDate, fn ($q) => $q->whereDate('t.START_DATE', '<=', $toDate))
L2521    resolveStudentAttendanceSessions() ->when($fromDate, fn ($q) => $q->whereDate('t.START_DATE', '>=', $fromDate))
L2522    resolveStudentAttendanceSessions() ->when($toDate, fn ($q) => $q->whereDate('t.START_DATE', '<=', $toDate))
L3120    leaveBasedAbsentees()            ->whereDate('from_date', '<=', $to)
L3122    leaveBasedAbsentees()            $q->whereNull('to_date')->orWhereDate('to_date', '>=', $from);
L3714    studentDetail()                  ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
L3724    studentDetail()                  ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
L3737    studentDetail()                  ->when($toDate, fn ($q) => $q->whereDate('from_date', '<=', $toDate))
L3813    studentDetail()                  ->when($fromDate, fn ($q) => $q->whereDate('mdo_date', '>=', $fromDate))
L3814    studentDetail()                  ->when($toDate, fn ($q) => $q->whereDate('mdo_date', '<=', $toDate))
L3887    studentDetail()                  ->whereDate('t.START_DATE', '<=', now()->toDateString())
L4688    getTodayTimetableForFaculty()    ->whereDate('START_DATE', '<=', $today)
L4689    getTodayTimetableForFaculty()    ->whereDate('END_DATE', '>=', $today)
L4784    getTodayTimetableForStudent()    ->whereDate('START_DATE', '<=', $today)
L4785    getTodayTimetableForStudent()    ->whereDate('END_DATE', '>=', $today)
```

### `app/Http/Controllers/Mess/ReportController.php` - 40 site(s)

```text
L587     buildStockPurchaseDetailsQuery() ->whereDate('po_date', '>=', $fromDate)
L588     buildStockPurchaseDetailsQuery() ->whereDate('po_date', '<=', $toDate);
L1228    sellingVoucherPrintSlipExcel()   $q->whereDate('issue_date', '>=', $from)
L1229    sellingVoucherPrintSlipExcel()   ->orWhereDate('date_from', '>=', $from);
L1235    sellingVoucherPrintSlipExcel()   $q->whereDate('issue_date', '<=', $to)
L1236    sellingVoucherPrintSlipExcel()   ->orWhereDate('date_to', '<=', $to);
L1409    weightedPurchaseUnitRateForItemUpToDate() ->whereDate('po_date', '<=', $untilDate);
L1434    weightedAllocationUnitRateForItemUpToDate() ->whereDate('sa.allocation_date', '<=', $untilDate);
L1704    getCourseBuyerNamesByCourse()    $itemQ->whereDate('issue_date', '>=', $fromDate);
L1708    getCourseBuyerNamesByCourse()    $itemQ->whereDate('issue_date', '<=', $toDate);
L1726    getCourseBuyerNamesByCourse()    $kiQuery->whereDate('issue_date', '>=', $fromDate);
L1729    getCourseBuyerNamesByCourse()    $kiQuery->whereDate('issue_date', '<=', $toDate);
L1785    getBuyerNamesForReportFilters()  $itemQ->whereDate('issue_date', '>=', $fromDate);
L1789    getBuyerNamesForReportFilters()  $itemQ->whereDate('issue_date', '<=', $toDate);
L1822    getBuyerNamesForReportFilters()  $kiQuery->whereDate('issue_date', '>=', $fromDate);
L1825    getBuyerNamesForReportFilters()  $kiQuery->whereDate('issue_date', '<=', $toDate);
L1893    buildCategoryWisePrintSlipReportData() $itemQ->whereDate('issue_date', '>=', $fromDate);
L1895    buildCategoryWisePrintSlipReportData() $itemQ->whereDate('issue_date', '<=', $toDate);
L1928    buildCategoryWisePrintSlipReportData() $itemQ->whereDate('issue_date', '>=', $fromDate);
L1932    buildCategoryWisePrintSlipReportData() $itemQ->whereDate('issue_date', '<=', $toDate);
L1947    buildCategoryWisePrintSlipReportData() $kiQuery->whereDate('issue_date', '>=', $request->from_date);
L1950    buildCategoryWisePrintSlipReportData() $kiQuery->whereDate('issue_date', '<=', $request->to_date);
L2373    buildStockBalanceTillDateData()  ->whereDate('po.po_date', '<=', $tillDate)
L2389    buildStockBalanceTillDateData()  ->whereDate('kim.issue_date', '<=', $tillDate)
L2403    buildStockBalanceTillDateData()  ->whereDate('svi.issue_date', '<=', $tillDate)
L2544    getLowStockAlertItems()          ->whereDate('po.po_date', '<=', $tillDate)
L2556    getLowStockAlertItems()          ->whereDate('kim.issue_date', '<=', $tillDate)
L2567    getLowStockAlertItems()          ->whereDate('svi.issue_date', '<=', $tillDate)
L2720    sellingVoucherPrintSlip()        $q->whereDate('issue_date', '>=', $from)
L2721    sellingVoucherPrintSlip()        ->orWhereDate('date_from', '>=', $from);
L2728    sellingVoucherPrintSlip()        $q->whereDate('issue_date', '<=', $to)
L2729    sellingVoucherPrintSlip()        ->orWhereDate('date_to', '<=', $to);
L2917    buildPurchaseSaleQuantityData()  ->whereDate('po.po_date', '>=', $fromDate)
L2918    buildPurchaseSaleQuantityData()  ->whereDate('po.po_date', '<=', $toDate)
L2935    buildPurchaseSaleQuantityData()  ->whereDate('kim.issue_date', '>=', $fromDate)
L2936    buildPurchaseSaleQuantityData()  ->whereDate('kim.issue_date', '<=', $toDate)
L2952    buildPurchaseSaleQuantityData()  ->whereDate('svi.issue_date', '>=', $fromDate)
L2953    buildPurchaseSaleQuantityData()  ->whereDate('svi.issue_date', '<=', $toDate)
L3006    stockIssueDetailReport()         $query->whereDate('issue_date', '>=', $request->from_date);
L3009    stockIssueDetailReport()         $query->whereDate('issue_date', '<=', $request->to_date);
```

### `app/Http/Controllers/Admin/AttendanceController.php` - 17 site(s)

```text
L167     index()                          ->whereDate('course_master.end_date', '<', $currentDate)
L176     index()                          ->orWhereDate('course_master.end_date', '>=', $currentDate);
L245     getAttendanceList()              $q->whereDate('START_DATE', '>=', $fromDate);
L248     getAttendanceList()              $q->whereDate('END_DATE', '<=', $toDate);
L644     buildAttendanceQuery()           $q->whereDate('START_DATE', '>=', $fromDate);
L647     buildAttendanceQuery()           $q->whereDate('END_DATE', '<=', $toDate);
L1024    OTmarkAttendanceView()           ->whereDate('course_master.end_date', '<', Carbon::today())
L1075    OTmarkAttendanceView()           $q->whereDate('START_DATE', '=', $filterDate);
L1177    OTmarkAttendanceView()           ])->whereDate('mdo_date', '=', $timetableDate)->first();
L1216    OTmarkAttendanceView()           ])->whereDate('mdo_date', '=', $timetableDate)->first();
L1237    OTmarkAttendanceView()           ])->whereDate('mdo_date', '=', $timetableDate)->first();
L1258    OTmarkAttendanceView()           ])->whereDate('mdo_date', '=', $timetableDate)->first();
L1371    getOTAttendanceData()            $q->whereDate('START_DATE', $filterDate);
L1502    getOTAttendanceData()            ])->whereDate('mdo_date', '=', $timetableDate)->first();
L1541    getOTAttendanceData()            ])->whereDate('mdo_date', '=', $timetableDate)->first();
L1562    getOTAttendanceData()            ])->whereDate('mdo_date', '=', $timetableDate)->first();
L1583    getOTAttendanceData()            ])->whereDate('mdo_date', '=', $timetableDate)->first();
```

### `app/Http/Controllers/Admin/CourseAttendanceNoticeMapController.php` - 17 site(s)

```text
L115     index()                          $q->whereDate('t.START_DATE', '>=', $fromDateFilter)
L118     index()                          ->whereDate('sns.date_', '>=', $fromDateFilter);
L124     index()                          $q->whereDate('t.START_DATE', '<=', $toDateFilter)
L127     index()                          ->whereDate('sns.date_', '<=', $toDateFilter);
L188     index()                          $memoQuery->whereDate('t.START_DATE', '>=', $fromDateFilter);
L191     index()                          $memoQuery->whereDate('t.START_DATE', '<=', $toDateFilter);
L507     noticeMemoExportData()           $noticesQuery->whereDate('t.START_DATE', '>=', $fromDateFilter);
L510     noticeMemoExportData()           $noticesQuery->whereDate('t.START_DATE', '<=', $toDateFilter);
L571     noticeMemoExportData()           $memoQuery->whereDate('t.START_DATE', '>=', $fromDateFilter);
L574     noticeMemoExportData()           $memoQuery->whereDate('t.START_DATE', '<=', $toDateFilter);
L592     noticeMemoExportData()           $memoDataQuery->whereDate('t.START_DATE', '>=', $fromDateFilter);
L595     noticeMemoExportData()           $memoDataQuery->whereDate('t.START_DATE', '<=', $toDateFilter);
L834     getSubjectByCourse()             $query->whereDate('t.START_DATE', $date);
L861     getTopicBysubject()              $query->whereDate('t.START_DATE', $date);
L917     getSessionsByCourse()            $query->whereDate('t.START_DATE', $date);
L956     getVenuesBySession()             $query->whereDate('t.START_DATE', $date);
L989     getTimetableDetailsBySessionVenue() $q->whereDate('t.START_DATE', $date);
```

### `app/Http/Controllers/Admin/CalendarController.php` - 13 site(s)

```text
L93      index()                          ->whereDate('end_date', '>=', $today)
L100     index()                          ->whereDate('end_date', '<', $today)
L160     createEvent()                    ->whereDate('end_date', '>=', today());
L671     fullCalendarDetails()            ->whereDate('START_DATE', '>=', $request->start)
L672     fullCalendarDetails()            ->whereDate('END_DATE', '<=', $request->end)
L945     otFullCalendarDetails()          ->whereDate('START_DATE', '>=', $request->start)
L946     otFullCalendarDetails()          ->whereDate('END_DATE', '<=', $request->end)
L1296    eventCardPdf()                   ->whereDate('timetable.START_DATE', '>=', $start)
L1297    eventCardPdf()                   ->whereDate('timetable.END_DATE', '<=', $end);
L1455    downloadTimetablePdf()           ->whereDate('timetable.START_DATE', '>=', $rangeStartDate->toDateString())
L1456    downloadTimetablePdf()           ->whereDate('timetable.START_DATE', '<=', $rangeEndDate->toDateString())
L1571    otDownloadPdf()                  ->whereDate('timetable.START_DATE', '>=', $rangeStartDate->toDateString())
L1572    otDownloadPdf()                  ->whereDate('timetable.START_DATE', '<=', $rangeEndDate->toDateString())
```

### `app/Http/Controllers/Admin/ExemptionMasterController.php` - 12 site(s)

```text
L70      create()                         ->whereDate('effective_from', $effectiveFrom)
L153     courseHasConflictingExemption()  ->whereDate('effective_from', $effectiveFrom)
L167     saveExemptionRow()               ->whereDate('effective_from', $effectiveFrom)
L208     status()                         ->whereDate('effective_from', $record->effective_from)
L236     destroy()                        ->whereDate('effective_from', $record->effective_from)
L254     datatable()                      ->whereDate('effective_from', $record->effective_from)
L342     baseListQuery()                  $query->whereHas('course', fn ($q) => $q->whereDate('end_date', '<', $today));
L345     baseListQuery()                  $query->whereHas('course', fn ($q) => $q->whereDate('end_date', '>=', $today));
L353     baseListQuery()                  $query->whereDate('effective_from', '>=', $request->input('from_date'));
L357     baseListQuery()                  $query->whereDate('effective_from', '<=', $request->input('to_date'));
L385     getConfiguredCourses()           ->when($status === 'active', fn ($q) => $q->whereDate('end_date', '>=', $today))
L386     getConfiguredCourses()           ->when($status === 'archive', fn ($q) => $q->whereDate('end_date', '<', $today))
```

### `app/Http/Controllers/Admin/StationedLeaveMasterController.php` - 9 site(s)

```text
L79      create()                         ->whereDate('effective_from', $effectiveFrom)
L155     store()                          ->whereDate('effective_from', $validated['effective_from'])
L214     courseHasConflictingConfig()     ->whereDate('effective_from', $effectiveFrom)
L318     baseListQuery()                  $query->whereDate('effective_from', '>=', $request->input('from_date'));
L322     baseListQuery()                  $query->whereDate('effective_from', '<=', $request->input('to_date'));
L428     applyCourseStatusFilter()        ->orWhereDate('end_date', '<', $today);
L435     applyCourseStatusFilter()        ->orWhereDate('end_date', '>=', $today);
L467     getConfiguredCoursesByStatus()   ->orWhereDate('end_date', '<', $today);
L473     getConfiguredCoursesByStatus()   ->orWhereDate('end_date', '>=', $today);
```

### `app/Http/Controllers/Admin/GroupMappingController.php` - 8 site(s)

```text
L86      index()                          ->orWhereDate('end_date', '>=', $today);
L134     getFilterCoursesForList()        ->orWhereDate('end_date', '>=', $currentDate);
L141     getFilterCoursesForList()        ->whereDate('end_date', '<', $currentDate);
L177     getFilterFacultiesForList()      ->orWhereDate('end_date', '>=', $currentDate);
L184     getFilterFacultiesForList()      ->whereDate('end_date', '<', $currentDate);
L231     create()                         ->orWhereDate('end_date', '>=', $today);
L827     buildExportQuery()               ->orWhereDate('end_date', '>=', $currentDate);
L834     buildExportQuery()               ->whereDate('end_date', '<', $currentDate);
```

### `app/Services/FacultyFeedbackReportService.php` - 7 site(s)

```text
L77      getPrograms()                    ->whereDate('end_date', '>=', Carbon::today());
L81      getPrograms()                    ->orWhereDate('end_date', '<', Carbon::today());
L99      getDefaultProgramId()            ->whereDate('end_date', '>=', Carbon::today())
L185     getAllProcessedItems()           $query->whereDate('tt.START_DATE', '>=', $fromDate);
L189     getAllProcessedItems()           $query->whereDate('tt.END_DATE', '<=', $toDate);
L195     getAllProcessedItems()           ->orWhereDate('cm.end_date', '<', Carbon::today());
L199     getAllProcessedItems()           ->whereDate('cm.end_date', '>=', Carbon::today());
```

### `app/Services/LeaveApplicationService.php` - 7 site(s)

```text
L77      getActivePtExemptionConfig()     ->whereDate('effective_from', '<=', $asOfDate)
L104     getUpcomingPtExemptionConfig()   ->whereDate('effective_from', '>', $asOfDate)
L155     getActiveStationedLeaveConfig()  ->whereDate('effective_from', '<=', $asOfDate)
L193     getUpcomingStationedLeaveConfig() ->whereDate('effective_from', '>', $asOfDate)
L217     findOverlappingApplication()     $inner->whereDate('from_date', '<=', $fromDate)
L218     findOverlappingApplication()     ->whereDate('to_date', '>=', $toDate);
L337     findStudentCourse()              ->orWhereDate('course_master.end_date', '>=', $today);
```

### `app/Http/Controllers/Admin/Registration/EnrollementController.php` - 6 site(s)

```text
L387     studentCourses()                 ->orWhereDate('end_date', '>=', $currentDate);
L391     studentCourses()                 $q->whereDate('end_date', '<', $currentDate);
L546     myCourseParticipant()            ->whereDate('end_date', '>=', now()))
L548     myCourseParticipant()            ->orWhereDate('end_date', '<', now())))
L682     myCourseParticipantExport()      ->whereDate('end_date', '>=', now()))
L684     myCourseParticipantExport()      ->orWhereDate('end_date', '<', now())))
```

### `app/Http/Controllers/Admin/TimetableReportController.php` - 6 site(s)

```text
L68      index()                          ->orWhereDate('end_date', '>=', $currentDate);
L75      index()                          ->whereDate('end_date', '<', $currentDate)
L135     data()                           ->orWhereDate('c.end_date', '>=', $currentDate);
L139     data()                           ->whereDate('c.end_date', '<', $currentDate);
L320     buildExportData()                ->orWhereDate('c.end_date', '>=', $currentDate);
L324     buildExportData()                ->whereDate('c.end_date', '<', $currentDate);
```

### `app/Http/Controllers/Admin/MedicalExemptionReportController.php` - 6 site(s)

```text
L87      summaryQuery()                   ->when($status === 'active', fn ($q) => $q->whereDate('c.end_date', '>=', $currentDate))
L88      summaryQuery()                   ->when($status === 'archive', fn ($q) => $q->whereDate('c.end_date', '<', $currentDate))
L93      summaryQuery()                   fn ($q) => $q->whereDate('sme.from_date', '>=', $request->from_date))
L95      summaryQuery()                   fn ($q) => $q->whereDate('sme.from_date', '<=', $request->to_date))
L173     detailQuery()                    fn ($q) => $q->whereDate('from_date', '>=', $request->from_date))
L175     detailQuery()                    fn ($q) => $q->whereDate('from_date', '<=', $request->to_date))
```

### `app/Exports/AttendanceDataExport.php` - 5 site(s)

```text
L90      array()                          ])->whereDate('mdo_date', '=', $this->timetableDate)->first();
L103     array()                          ])->whereDate('mdo_date', '=', $this->timetableDate)->first();
L116     array()                          ])->whereDate('mdo_date', '=', $this->timetableDate)->first();
L130     array()                          $query->whereDate('from_date', '<=', $this->timetableDate)
L133     array()                          ->orWhereDate('to_date', '>=', $this->timetableDate);
```

### `app/Exports/MedicalExemptionReportSummaryExport.php` - 4 site(s)

```text
L150     records()                        ->when($this->status === 'active', fn ($q) => $q->whereDate('c.end_date', '>=', $currentDate))
L151     records()                        ->when($this->status === 'archive', fn ($q) => $q->whereDate('c.end_date', '<', $currentDate))
L156     records()                        fn ($q) => $q->whereDate('sme.from_date', '>=', $this->fromDateFilter))
L158     records()                        fn ($q) => $q->whereDate('sme.from_date', '<=', $this->toDateFilter))
```

### `app/DataTables/FC/PendingFeedbackDataTable.php` - 4 site(s)

```text
L85      dataTable()                      $query->whereDate('t.START_DATE', 'LIKE', "%{$keyword}%");
L88      dataTable()                      $query->whereDate('t.END_DATE', 'LIKE', "%{$keyword}%");
L110     dataTable()                      $query->whereDate('t.START_DATE', '>=', $request->filter_from_date);
L114     dataTable()                      $query->whereDate('t.START_DATE', '<=', $request->filter_to_date);
```

### `app/Http/Controllers/Admin/StudentMedicalExemptionController.php` - 4 site(s)

```text
L121     index()                          $query->whereDate('from_date', '>=', $request->from_date);
L123     index()                          $query->whereDate('from_date', '<=', $request->to_date);
L132     index()                          $q->whereDate('end_date', '>=', $currentDate);
L136     index()                          $q->whereDate('end_date', '<', $currentDate);
```

### `app/Services/Attendance/OtExemptionResolver.php` - 3 site(s)

```text
L108     hasDuty()                        ])->whereDate('mdo_date', '=', $timetable->START_DATE)->first();
L128     resolveMedical()                 ->whereDate('from_date', '<=', $date)
L131     resolveMedical()                 $q->whereNull('to_date')->orWhereDate('to_date', '>=', $date);
```

### `app/Http/Controllers/Mess/KitchenIssueController.php` - 3 site(s)

```text
L681     sellingVoucherItemRowsBaseQuery() $q->whereDate('kim.issue_date', '>=', now()->subDays(30)->toDateString());
L686     sellingVoucherItemRowsBaseQuery() $q->whereDate('kim.issue_date', '>=', $request->start_date);
L688     sellingVoucherItemRowsBaseQuery() $q->whereDate('kim.issue_date', '<=', $request->end_date);
```

### `app/Http/Controllers/Admin/Concerns/ScopesSessionFeedbackReports.php` - 3 site(s)

```text
L95      coursesForFeedbackDatabase()     ->orWhereDate('end_date', '>=', $currentDate);
L99      coursesForFeedbackDatabase()     $q->whereDate('end_date', '<', $currentDate);
L168     defaultProgramIdForFacultyReport() ->orWhereDate('end_date', '<', Carbon::today());
```

### `app/Http/Controllers/Mess/ProcessMessBillsEmployeeController.php` - 2 site(s)

```text
L5102    applySvDateRangeReportItemsIssueDateConstraint() $itemQuery->whereDate('issue_date', '>=', $dateFromYmd);
L5107    applySvDateRangeReportItemsIssueDateConstraint() $itemQuery->whereDate('issue_date', '<=', $dateToYmd);
```

### `app/Http/Controllers/Mess/PurchaseOrderController.php` - 2 site(s)

```text
L98      purchaseOrderFilteredQuery()     $query->whereDate('po_date', '>=', $request->date_from);
L101     purchaseOrderFilteredQuery()     $query->whereDate('po_date', '<=', $request->date_to);
```

### `app/DataTables/GroupMappingDataTable.php` - 2 site(s)

```text
L185     query()                          ->orWhereDate('end_date', '>=', $currentDate); // ya abhi ya future me active
L193     query()                          ->whereDate('end_date', '<', $currentDate); }); }) ->when(!empty($data_course_id), function ($query) use
```

### `app/Http/Controllers/Admin/LeaveApplicationController.php` - 2 site(s)

```text
L425     baseMyLeaveQuery()               $query->whereDate('from_date', '>=', $request->input('from_date'));
L429     baseMyLeaveQuery()               $query->whereDate('from_date', '<=', $request->input('to_date'));
```

### `app/Exports/MedicalExemptionReportDetailExport.php` - 2 site(s)

```text
L143     records()                        fn ($q) => $q->whereDate('from_date', '>=', $this->fromDateFilter))
L145     records()                        fn ($q) => $q->whereDate('from_date', '<=', $this->toDateFilter))
```

### `app/DataTables/MDOEscrotExemptionDataTable.php` - 2 site(s)

```text
L205     query()                          $query->whereDate('mdo_date', '>=', $fromDateFilter);
L209     query()                          $query->whereDate('mdo_date', '<=', $toDateFilter);
```

### `app/DataTables/FC/PendingFeedbackSummaryDataTable.php` - 2 site(s)

```text
L82      dataTable()                      $query->whereDate('t.START_DATE', '>=', $request->filter_from_date);
L86      dataTable()                      $query->whereDate('t.START_DATE', '<=', $request->filter_to_date);
```

### `app/Services/FC/FcTravelPlanReportService.php` - 2 site(s)

```text
L78      applyFilters()                   ->orWhereDate('tp.joining_date', '>=', $request->date_from);
L84      applyFilters()                   ->orWhereDate('tp.joining_date', '<=', $request->date_to);
```

### `app/Http/Controllers/Admin/CourseMemoDecisionMappController.php` - 2 site(s)

```text
L179     applyCourseStatusScope()         ->whereDate('end_date', '<', $today);
L183     applyCourseStatusScope()         ->orWhereDate('end_date', '>=', $today);
```

### `app/Http/Controllers/Admin/CourseRepositoryController.php` - 2 site(s)

```text
L1235    userIndex()                      $detailQuery->whereDate('session_date', $date);
L1740    applyUserDetailFilters()         $detailQuery->whereDate('session_date', $filters['date']);
```

### `app/Http/Controllers/Admin/FacultyLeaveApprovalController.php` - 2 site(s)

```text
L127     baseQuery()                      $query->whereDate('from_date', '>=', $request->input('from_date'));
L131     baseQuery()                      $query->whereDate('from_date', '<=', $request->input('to_date'));
```

### `app/Console/Commands/SendStockAlertCommand.php` - 1 site(s)

```text
L98      sendLowStockForStore()           ->whereDate('created_at', $now->toDateString())
```

### `app/Http/Controllers/Faculty/SessionFeedbackReportController.php` - 1 site(s)

```text
L163     defaultArchivedProgramId()       ->orWhereDate('end_date', '<', now());
```

### `app/Http/Controllers/Admin/MDOEscrotExemptionController.php` - 1 site(s)

```text
L434     getStudentListAccordingToCourse() ->whereDate('mdo_date', $request->selectedDate);
```

### `app/DataTables/StudentAttendanceListDataTable.php` - 1 site(s)

```text
L169     renderMdoCell()                  ])->whereDate('mdo_date', '=', $timetable->START_DATE)->exists();
```

### `app/Http/Controllers/Admin/MedicalExceptionFacultyViewController.php` - 1 site(s)

```text
L82      facultyLoginView()               $query->whereDate('sme.from_date', '>=', $dateFromFilter);
```

---

## G8-B - whereYear() / whereMonth() / whereDay()

Same defect as G8-A - the function wraps the column. Replace with a range covering the period.

### `app/Http/Controllers/Admin/EstateController.php` - 2 site(s)

```text
L8978    applyEstateGenerateBillMonthFilter() ->whereYear('emrd.to_date', $y)
L8979    applyEstateGenerateBillMonthFilter() ->whereMonth('emrd.to_date', $m);
```

### `app/DataTables/MDOEscrotExemptionDataTable.php` - 1 site(s)

```text
L182     query()                          $query->whereYear('mdo_date', $yearFilter);
```

---

## G8-C - SQL functions inside raw predicates

Case-sensitive matches for LOWER( UPPER( CAST( CONVERT( DATE( appearing inside raw SQL,
join conditions or COALESCE expressions. Each wraps a column and blocks index usage.

Where the root cause is a collation or column-type mismatch - notably fc_registration_master
(latin1) compared against user_credentials (utf8mb4) - fix the schema rather than the query.
That removes a whole class of these at once.

### `app/Http/Controllers/Admin/EstateController.php` - 27 site(s)

```text
L1618    requestAndChangeRequestDetails() DB::raw("CASE WHEN epd.allotment_date <= '1900-01-01' THEN NULL ELSE DATE(epd.allotment_date) END as allotment_date"),
L1619    requestAndChangeRequestDetails() DB::raw("CASE WHEN epd.possession_date <= '1900-01-01' THEN NULL ELSE DATE(epd.possession_date) END as possession_date")
L4493    updateDefineHouse()              ->whereRaw('UPPER(REPLACE(REGEXP_REPLACE(house_no, "-+", "-"), " ", "")) = ?', [strtoupper(preg_replace('/-+/', '-', $houseNo))])
L4969    possessionDetailsCreate()        DB::raw("CASE WHEN epd.allotment_date <= '1900-01-01' THEN NULL ELSE DATE(epd.allotment_date) END as allotment_date"),
L4970    possessionDetailsCreate()        DB::raw("CASE WHEN epd.possession_date <= '1900-01-01' THEN NULL ELSE DATE(epd.possession_date) END as possession_date"),
L5025    possessionDetailsCreate()        DB::raw("CASE WHEN epd.allotment_date <= '1900-01-01' THEN NULL ELSE DATE(epd.allotment_date) END as allotment_date"),
L5026    possessionDetailsCreate()        DB::raw("CASE WHEN epd.possession_date <= '1900-01-01' THEN NULL ELSE DATE(epd.possession_date) END as possession_date"),
L7258    computeUpdateMeterNoListPayload() WHERE LOWER(TRIM(COALESCE(type, \'\'))) = \'l\'
L7265    computeUpdateMeterNoListPayload() WHERE LOWER(TRIM(COALESCE(type, \'\'))) = \'o\'
L8144    applyMeterReadingExcludeReadingDateInUiMonth() ->orWhereRaw('DATE(' . $coalesce . ') < ?', [$startS])
L8145    applyMeterReadingExcludeReadingDateInUiMonth() ->orWhereRaw('DATE(' . $coalesce . ') > ?', [$endS]);
L8177    applyMeterReadingExcludePossessionIfAnyReadingDateInUiMonth() ->whereRaw('DATE(COALESCE(emro_any.to_date, emro_any.from_date)) BETWEEN ? AND ?', [$startS, $endS]);
L8187    applyMeterReadingExcludePossessionIfAnyReadingDateInUiMonth() ->whereRaw('DATE(COALESCE(emrd_any.to_date, emrd_any.from_date)) BETWEEN ? AND ?', [$startS, $endS]);
L10444   listMeterReading()               ->orderByRaw('CAST(bill_year AS UNSIGNED) DESC, CAST(bill_month AS UNSIGNED) DESC')
L11142   computeBillReportGridCachedPayload() ->whereRaw('CONVERT(LOWER(TRIM(COALESCE(ehm_bn.house_no, \'\'))) USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(LOWER(TRIM(COALESCE(emrd.house_no ...
L11158   computeBillReportGridCachedPayload() $q->whereRaw('LOWER(TRIM(emrd.bill_month)) = ?', [strtolower($v)]);
L11161   computeBillReportGridCachedPayload() $q->orWhereRaw('LOWER(TRIM(emrd.bill_month)) = ?', [strtolower($v)]);
L11165   computeBillReportGridCachedPayload() ->whereRaw('TRIM(CAST(emrd.bill_year AS CHAR)) = ?', [$billYearStr])
L11204   computeBillReportGridCachedPayload() ->whereRaw('CONVERT(LOWER(TRIM(COALESCE(ehm_o_bn.house_no, \'\'))) USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(LOWER(TRIM(COALESCE(emro.house_ ...
L11211   computeBillReportGridCachedPayload() $q->whereRaw('LOWER(TRIM(emro.bill_month)) = ?', [strtolower($v)]);
L11214   computeBillReportGridCachedPayload() $q->orWhereRaw('LOWER(TRIM(emro.bill_month)) = ?', [strtolower($v)]);
L11218   computeBillReportGridCachedPayload() ->whereRaw('TRIM(CAST(emro.bill_year AS CHAR)) = ?', [$billYearStr])
L11343   computeBillReportGridCachedPayload() ? DB::raw('LOWER(TRIM(' . $orderColName . '))')
L11349   computeBillReportGridCachedPayload() ->orderBy(DB::raw('LOWER(TRIM(u.name))'), 'asc')
L11350   computeBillReportGridCachedPayload() ->orderBy(DB::raw('LOWER(TRIM(u.house_no))'), 'asc')
L12041   computePendingMeterReadingRows() ->orderByRaw('CAST(emrd.bill_year AS UNSIGNED) DESC, ' . $monthOrderSql . ' DESC')
L12071   computePendingMeterReadingRows() ->orderByRaw('CAST(emro.bill_year AS UNSIGNED) DESC, ' . $monthOrderSqlEmro . ' DESC')
```

### `app/DataTables/EstateReturnHouseDataTable.php` - 26 site(s)

```text
L236     query()                          ->orWhereRaw('LOWER(ehrd.emp_name) LIKE ?', [$like])
L238     query()                          ->orWhereRaw('LOWER(ec.campus_name) LIKE ?', [$like])
L240     query()                          ->orWhereRaw('LOWER(eut.unit_type) LIKE ?', [$like])
L242     query()                          ->orWhereRaw('LOWER(eb.block_name) LIKE ?', [$like])
L244     query()                          ->orWhereRaw('LOWER(ehm.house_no) LIKE ?', [$like])
L246     query()                          ->orWhereRaw('LOWER(eust.unit_sub_type) LIKE ?', [$like])
L248     query()                          ->orWhereRaw('LOWER(CAST(epd.allotment_date AS CHAR)) LIKE ?', [$like])
L250     query()                          ->orWhereRaw('LOWER(CAST(epd.possession_date AS CHAR)) LIKE ?', [$like])
L252     query()                          ->orWhereRaw('LOWER(CAST(epd.current_meter_reading_date AS CHAR)) LIKE ?', [$like])
L255     query()                          $inner->orWhereRaw('LOWER(epd.remarks) LIKE ?', [$like]);
L299     query()                          ->orWhereRaw('LOWER(eor.emp_name) LIKE ?', [$like])
L301     query()                          ->orWhereRaw('LOWER(eor.section) LIKE ?', [$like])
L303     query()                          ->orWhereRaw('LOWER(ec.campus_name) LIKE ?', [$like])
L305     query()                          ->orWhereRaw('LOWER(eut.unit_type) LIKE ?', [$like])
L307     query()                          ->orWhereRaw('LOWER(eb.block_name) LIKE ?', [$like])
L309     query()                          ->orWhereRaw("LOWER(COALESCE(NULLIF(TRIM(epo.house_no), ''), ehm.house_no)) LIKE ?", [$like])
L311     query()                          ->orWhereRaw('LOWER(eust.unit_sub_type) LIKE ?', [$like])
L313     query()                          ->orWhereRaw('LOWER(CAST(epo.allotment_date AS CHAR)) LIKE ?', [$like])
L315     query()                          ->orWhereRaw('LOWER(CAST(epo.possession_date_oth AS CHAR)) LIKE ?', [$like])
L317     query()                          ->orWhereRaw('LOWER(CAST(epo.current_meter_reading_date AS CHAR)) LIKE ?', [$like])
L321     query()                          $inner->orWhereRaw('LOWER(COALESCE(epo.upload_document, epo.noc_document)) LIKE ?', [$like]);
L323     query()                          $inner->orWhereRaw('LOWER(epo.upload_document) LIKE ?', [$like]);
L325     query()                          $inner->orWhereRaw('LOWER(epo.noc_document) LIKE ?', [$like]);
L330     query()                          $inner->orWhereRaw('LOWER(epo.remarks) LIKE ?', [$like]);
L344     query()                          CONVERT(COALESCE(NULLIF(TRIM(epo.house_no),''), ehm.house_no) USING utf8mb4) COLLATE utf8mb4_unicode_ci as house_no,
L351     query()                          ? 'CONVERT(COALESCE(epo.upload_document, epo.noc_document) USING utf8mb4) COLLATE utf8mb4_unicode_ci'
```

### `app/Http/Controllers/Mess/ProcessMessBillsEmployeeController.php` - 24 site(s)

```text
L101     index()                          DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
L103     index()                          DB::raw("CONVERT(client_type_slug USING utf8mb4) COLLATE {$unionCollation} as client_type_slug"),
L109     index()                          DB::raw("CONVERT('date_range' USING utf8mb4) COLLATE {$unionCollation} as source_type")
L132     index()                          DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
L134     index()                          DB::raw("CONVERT((CASE client_type WHEN 1 THEN 'employee' WHEN 2 THEN 'ot' WHEN 3 THEN 'course' WHEN 4 THEN 'other' END) USING utf8mb4) COLLATE {$unio ...
L140     index()                          DB::raw("CONVERT('kitchen_issue' USING utf8mb4) COLLATE {$unionCollation} as source_type")
L1239    myBillsIndex()                   DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
L1241    myBillsIndex()                   DB::raw("CONVERT(client_type_slug USING utf8mb4) COLLATE {$unionCollation} as client_type_slug"),
L1247    myBillsIndex()                   DB::raw("CONVERT('date_range' USING utf8mb4) COLLATE {$unionCollation} as source_type"),
L1257    myBillsIndex()                   DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
L1259    myBillsIndex()                   DB::raw("CONVERT((CASE client_type WHEN 1 THEN 'employee' WHEN 2 THEN 'ot' WHEN 3 THEN 'course' WHEN 4 THEN 'other' END) USING utf8mb4) COLLATE {$unio ...
L1265    myBillsIndex()                   DB::raw("CONVERT('kitchen_issue' USING utf8mb4) COLLATE {$unionCollation} as source_type"),
L2887    export()                         DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
L2890    export()                         DB::raw("CONVERT(client_type_slug USING utf8mb4) COLLATE {$unionCollation} as client_type_slug"),
L2896    export()                         DB::raw("CONVERT('date_range' USING utf8mb4) COLLATE {$unionCollation} as source_type")
L2910    export()                         DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
L2913    export()                         DB::raw("CONVERT((CASE client_type WHEN 1 THEN 'employee' WHEN 2 THEN 'ot' WHEN 3 THEN 'course' WHEN 4 THEN 'other' END) USING utf8mb4) COLLATE {$unio ...
L2919    export()                         DB::raw("CONVERT('kitchen_issue' USING utf8mb4) COLLATE {$unionCollation} as source_type")
L3655    modalData()                      DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
L3657    modalData()                      DB::raw("CONVERT(client_type_slug USING utf8mb4) COLLATE {$unionCollation} as client_type_slug"),
L3661    modalData()                      DB::raw("CONVERT('date_range' USING utf8mb4) COLLATE {$unionCollation} as source_type"),
L3679    modalData()                      DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
L3681    modalData()                      DB::raw("CONVERT((CASE client_type WHEN 1 THEN 'employee' WHEN 2 THEN 'ot' WHEN 3 THEN 'course' WHEN 4 THEN 'other' WHEN 5 THEN 'section' END) USING u ...
L3685    modalData()                      DB::raw("CONVERT('kitchen_issue' USING utf8mb4) COLLATE {$unionCollation} as source_type"),
```

### `app/Http/Controllers/Admin/UserController.php` - 10 site(s)

```text
L1481    otParticipantsRowMeta()          ->when($fromDate, fn ($q) => $q->whereRaw('DATE(COALESCE(to_date, from_date)) >= ?', [$fromDate]))
L3713    studentDetail()                  ->when($fromDate, fn ($q) => $q->whereRaw('DATE(COALESCE(to_date, from_date)) >= ?', [$fromDate]))
L3723    studentDetail()                  ->when($fromDate, fn ($q) => $q->whereRaw('DATE(COALESCE(to_date, from_date)) >= ?', [$fromDate]))
L3736    studentDetail()                  ->when($fromDate, fn ($q) => $q->whereRaw('DATE(COALESCE(to_date, from_date)) >= ?', [$fromDate]))
L4039    adminUsersBaseQuery()            $q->whereRaw("LOWER(TRIM(COALESCE(uc.user_name, ''))) LIKE ?", [$like])
L4040    adminUsersBaseQuery()            ->orWhereRaw("LOWER(TRIM(uc.first_name)) LIKE ?", [$like])
L4041    adminUsersBaseQuery()            ->orWhereRaw("LOWER(TRIM(uc.last_name)) LIKE ?", [$like])
L4042    adminUsersBaseQuery()            ->orWhereRaw("LOWER(TRIM(uc.email_id)) LIKE ?", [$like])
L4043    adminUsersBaseQuery()            ->orWhereRaw("LOWER(CONCAT_WS(' ', TRIM(uc.first_name), TRIM(uc.last_name))) LIKE ?", [$like])
L4044    adminUsersBaseQuery()            ->orWhereRaw("LOWER(CONCAT_WS(' ', TRIM(uc.last_name), TRIM(uc.first_name))) LIKE ?", [$like]);
```

### `app/DataTables/UserCredentialsDataTable.php` - 8 site(s)

```text
L27      dataTable()                      $query->whereRaw("LOWER(user_credentials.email_id) like ?", ["%".strtolower($keyword)."%"]);
L31      dataTable()                      $query->whereRaw("LOWER(user_credentials.user_name) like ?", ["%".strtolower($keyword)."%"]);
L35      dataTable()                      $query->whereRaw("LOWER(user_credentials.first_name) like ?", ["%".strtolower($keyword)."%"]);
L39      dataTable()                      $query->whereRaw("LOWER(user_credentials.last_name) like ?", ["%".strtolower($keyword)."%"]);
L59      dataTable()                      $subQuery->whereRaw("LOWER(user_credentials.user_name) like ?", ["%".strtolower($searchValue)."%"])
L60      dataTable()                      ->orWhereRaw("LOWER(user_credentials.first_name) like ?", ["%".strtolower($searchValue)."%"])
L61      dataTable()                      ->orWhereRaw("LOWER(user_credentials.last_name) like ?", ["%".strtolower($searchValue)."%"])
L62      dataTable()                      ->orWhereRaw("LOWER(user_credentials.email_id) like ?", ["%".strtolower($searchValue)."%"]);
```

### `app/DataTables/EstateRequestForEstateDataTable.php` - 8 site(s)

```text
L309     dataTable()                      "CONVERT(COALESCE($column, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci";
L330     dataTable()                      $query->whereRaw("CONVERT(COALESCE(estate_home_request_details.req_id, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ?", [$like]);
L334     dataTable()                      $query->whereRaw("CONVERT(COALESCE(estate_home_request_details.current_alot, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ?", [$like]);
L339     dataTable()                      $q->whereRaw("CONVERT(COALESCE(estate_home_request_details.emp_name, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ?", [$like])
L340     dataTable()                      ->orWhereRaw("CONVERT(COALESCE(estate_home_request_details.employee_id, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ?", [$like])
L342     dataTable()                      "CONCAT(TRIM(CONVERT(COALESCE(estate_home_request_details.emp_name, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci), \" / \", TRIM(CONVERT(COALESCE(est ...
L348     dataTable()                      ->orderColumn('req_id', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(estate_home_request_details.req_id, "")) ' . $order))
L352     dataTable()                      ->orderColumn('name_id', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(estate_home_request_details.emp_name, "")) ' . $order)-> ...
```

### `app/Http/Controllers/Admin/FeedbackController.php` - 5 site(s)

```text
L115     baseDatabaseQuery()              DB::raw('DATE(t.START_DATE) as session_date'),
L3963    pendingStudentsGroupedData()     $q->whereRaw('LOWER(agg_students.student_name) LIKE ?', [$raw])
L3964    pendingStudentsGroupedData()     ->orWhereRaw('LOWER(agg_students.email) LIKE ?', [$raw])
L3965    pendingStudentsGroupedData()     ->orWhereRaw('LOWER(agg_students.generated_OT_code) LIKE ?', [$raw])
L3966    pendingStudentsGroupedData()     ->orWhereRaw('LOWER(COALESCE(agg_students.course_summary_build, "")) LIKE ?', [$raw]);
```

### `app/DataTables/EstateHacApprovedDataTable.php` - 5 site(s)

```text
L190     dataTable()                      ->orderColumn('request_type', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(request_type, "")) ' . $order))
L191     dataTable()                      ->orderColumn('request_id', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(request_id, "")) ' . $order))
L192     dataTable()                      ->orderColumn('emp_name', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(emp_name, "")) ' . $order))
L193     dataTable()                      ->orderColumn('emp_designation', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(emp_designation, "")) ' . $order))
L194     dataTable()                      ->orderColumn('pay_scale', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(pay_scale, "")) ' . $order))
```

### `app/Http/Controllers/FC/ReportController.php` - 5 site(s)

```text
L698     byService()                      ->leftJoin('service_master as svc_frm', DB::raw('CAST(frm.service_master_pk AS UNSIGNED)'), '=', 'svc_frm.pk');
L812     byState()                        ->leftJoin('state_masters as st_frm', DB::raw('CAST(frm.state_master_pk AS UNSIGNED)'), '=', 'st_frm.id');
L827     byState()                        ->orWhereIn(DB::raw('CAST(frm.state_master_pk AS UNSIGNED)'), $stateIds);
L1033    bankDetails()                    ->leftJoin('service_master as svc_frm', DB::raw('CAST(frm.service_master_pk AS UNSIGNED)'), '=', 'svc_frm.pk');
L1183    exportBankCsv()                  ->leftJoin('service_master as svc_frm', DB::raw('CAST(frm.service_master_pk AS UNSIGNED)'), '=', 'svc_frm.pk');
```

### `app/Services/FC/FcMigrateStudentsExportService.php` - 3 site(s)

```text
L241     applyUserCredentialsMatchExists() ->whereRaw('TRIM(CAST(uc.user_name AS CHAR)) = TRIM(CAST(r.user_id AS CHAR))');
L243     applyUserCredentialsMatchExists() ->whereRaw('TRIM(CAST(uc.mobile_no AS CHAR)) = TRIM(CAST(r.contact_no AS CHAR))');
L245     applyUserCredentialsMatchExists() ->whereRaw('LOWER(TRIM(uc.email_id)) = LOWER(TRIM(r.email))');
```

### `app/Services/FC/FcActivityStudentResolver.php` - 3 site(s)

```text
L41      findByOtCode()                   ->whereRaw('UPPER(TRIM(generated_OT_code)) = ?', [strtoupper($otCode)]);
L344     findRosterByOtCode()             ->whereRaw('UPPER(TRIM(generated_OT_code)) = ?', [strtoupper($otCode)]);
L384     resolveStudentForRoster()        ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower($email)])
```

### `app/Models/MDOEscotDutyMap.php` - 3 site(s)

```text
L33      getMdoDutyTypes()                'mdo' => optional(MDODutyTypeMaster::whereRaw('LOWER(mdo_duty_type_name) = ?', ['mdo'])->first())->pk,
L34      getMdoDutyTypes()                'escort' => optional(MDODutyTypeMaster::whereRaw('LOWER(mdo_duty_type_name) = ?', ['escort'])->first())->pk,
L35      getMdoDutyTypes()                'other' => optional(MDODutyTypeMaster::whereRaw('LOWER(mdo_duty_type_name) = ?', ['other'])->first())->pk,
```

### `app/Http/Controllers/Admin/TimetableReportController.php` - 2 site(s)

```text
L149     data()                           $q->whereRaw("JSON_CONTAINS(COALESCE(NULLIF(t.faculty_master, ''), '[]'), CAST(? AS JSON))", [$facultyPk])
L333     buildExportData()                $q->whereRaw("JSON_CONTAINS(COALESCE(NULLIF(t.faculty_master, ''), '[]'), CAST(? AS JSON))", [$facultyPk])
```

### `app/Http/Controllers/Admin/FamilyIDCardRequestController.php` - 2 site(s)

```text
L591     store()                          ->whereRaw('LOWER(TRIM(family_name)) = ?', [strtolower(trim($name))])
L593     store()                          $q->whereRaw('LOWER(TRIM(family_relation)) = ?', [strtolower(trim($member['relation']))]);
```

### `app/DataTables/FC/FcFormOverviewDataTable.php` - 2 site(s)

```text
L66      query()                          $query->leftJoin('service_master as svc_frm', DB::raw('CAST(frm.service_master_pk AS UNSIGNED)'), '=', 'svc_frm.pk')
L67      query()                          ->leftJoin('state_masters as st_frm', DB::raw('CAST(frm.state_master_pk AS UNSIGNED)'), '=', 'st_frm.id');
```

### `app/Http/Controllers/Admin/DashboardController.php` - 2 site(s)

```text
L61      getFacultyWithMetrics()          DB::raw('ROUND(AVG(CAST(tf.content AS DECIMAL(10,2))) * 20, 2) as avg_content'),
L62      getFacultyWithMetrics()          DB::raw('ROUND(AVG(CAST(tf.presentation AS DECIMAL(10,2))) * 20, 2) as avg_presentation')
```

### `app/Support/IdCardSecurityMapper.php` - 1 site(s)

```text
L98      resolveContractualBeneficiaryEmployee() "UPPER(TRIM(CONCAT(COALESCE(TRIM(first_name),''),' ',COALESCE(TRIM(last_name),'')))) = ?",
```

### `app/Services/FC/FcTravelPlanReportService.php` - 1 site(s)

```text
L30      baseQuery()                      DB::raw("COALESCE(NULLIF(TRIM(uc.user_name),''), CAST(tp.{$tpCol} AS CHAR)) as login_username"),
```

### `app/Services/FC/FcOptionalSubjectService.php` - 1 site(s)

```text
L93      ifosServiceIds()                 ->orWhereRaw("UPPER(TRIM(service_name)) = 'INDIAN FOREST SERVICES'");
```

### `app/Http/Requests/FacultyRequest.php` - 1 site(s)

```text
L30      rules()                          $otherCityPks = City::whereRaw('LOWER(city_name) = ?', ['other'])->pluck('pk')->toArray();
```

### `app/Http/Controllers/Admin/CourseAttendanceNoticeMapController.php` - 1 site(s)

```text
L1272    getStudentAttendanceBytopic()    ->whereRaw("CAST(a.status AS CHAR) IN ('2', '3')")
```

### `app/DataTables/FeedbackDatabaseDataTable.php` - 1 site(s)

```text
L111     query()                          DB::raw('DATE(t.START_DATE) as session_date'),
```

### `app/Console/Commands/SendStockAlertCommand.php` - 1 site(s)

```text
L146     resolveMessReceiverUserIds()     $q->whereRaw('LOWER(name) = ?', ['mess staff']);
```

### `app/Http/Controllers/Admin/GroupMappingController.php` - 1 site(s)

```text
L670     addSingleStudent()               $studentMaster = StudentMaster::whereRaw('LOWER(generated_OT_code) = ?', [strtolower($data['otcode'])])
```

### `app/helpers.php` - 1 site(s)

```text
L987     provision_faculty_profile_from_employee_user() ->orderByRaw("CAST(SUBSTRING_INDEX(faculty_code, '-', -1) AS UNSIGNED) DESC")
```

### `app/Http/Controllers/Admin/FacultyController.php` - 1 site(s)

```text
L845     generateFacultyCode()            ->orderByRaw('CAST(SUBSTRING_INDEX(faculty_code, \'-\', -1) AS UNSIGNED) DESC')
```

---

## G1-A - Model::all()

Loads every row and every column. Restrict columns and add filters, or paginate.
Small reference tables (Country/State/District) are lower risk than transactional tables
(User/Invoice/Inventory/Vendor) - triage accordingly.

### `app/Http/Controllers/Admin/LocationController.php` - 12 site(s)

```text
L88      stateCreate()                    $countries = Country::all();
L113     stateEdit()                      $countries = Country::all();
L157     districtCreate()                 $countries = Country::all();
L158     districtCreate()                 $states = State::all();
L184     districtEdit()                   $countries = Country::all();
L187     districtEdit()                   $states = State::all();
L227     cityCreate()                     $countries = Country::all();
L229     cityCreate()                     $states = State::all(); // Fetch all states
L230     cityCreate()                     $districts = District::all();
L258     cityEdit()                       $countries = Country::all();
L260     cityEdit()                       $states = State::all(); // Get all states
L261     cityEdit()                       $districts = District::all(); // Get all districts
```

### `app/Http/Controllers/Admin/CourseMemoDecisionMappController.php` - 4 site(s)

```text
L17      index()                          $mappings = CourseMemoDecisionMapp::all();
L206     create()                         $mappings = CourseMemoDecisionMapp::all();
L282     edit()                           $MemoTypeMaster = MemoTypeMaster::all();
L284     edit()                           $MemoConclusionMaster = MemoConclusionMaster::all();
```

### `app/Http/Controllers/FC/RegistrationStep3Controller.php` - 4 site(s)

```text
L129     showStep3Legacy()                $qualificationMasters = QualificationMaster::all();
L130     showStep3Legacy()                $boardMasters = BoardNameMaster::all();
L131     showStep3Legacy()                $streamMasters = HighestStreamMaster::all();
L132     showStep3Legacy()                $jobTypes = JobTypeMaster::all();
```

### `app/Http/Controllers/FC/RegistrationStep2Controller.php` - 3 site(s)

```text
L44      showStep2()                      $categories = CategoryMaster::all();
L45      showStep2()                      $religions = ReligionMaster::all();
L48      showStep2()                      $fatherProfessions = FatherProfession::all();
```

### `app/Http/Controllers/Admin/CourseRepositoryController.php` - 3 site(s)

```text
L195     show()                           'topics' => CourseRepositorySubtopic::all(),
L214     create()                         $subtopics = CourseRepositorySubtopic::all();
L315     edit()                           $subtopics = CourseRepositorySubtopic::all();
```

### `app/Support/FC/DocumentFormTemplates.php` - 2 site(s)

```text
L64      get()                            return self::all()[$key] ?? null;
L76      options()                        foreach (self::all() as $key => $tpl) {
```

### `app/Http/Controllers/Mess/PurchaseOrderController.php` - 2 site(s)

```text
L235     create()                         $vendors = Vendor::all();
L237     create()                         $inventories = Inventory::all();
```

### `app/Http/Controllers/Mess/CreditLimitController.php` - 2 site(s)

```text
L20      create()                         $users = User::all();
L52      edit()                           $users = User::all();
```

### `app/Http/Controllers/Mess/KitchenIssueController.php` - 2 site(s)

```text
L1684    edit()                           $items = Inventory::all();
L2126    billReport()                     $stores = Store::all();
```

### `app/Http/Controllers/Mess/InboundTransactionController.php` - 2 site(s)

```text
L26      create()                         $vendors = Vendor::all();
L28      create()                         $inventories = Inventory::all();
```

### `app/Http/Controllers/Admin/UserController.php` - 2 site(s)

```text
L4246    edit()                           $roles = Role::all();
L4442    getAllRoles()                    $roles = Role::all();
```

### `app/Http/Controllers/Mess/MaterialRequestController.php` - 1 site(s)

```text
L24      create()                         $inventories = Inventory::all();
```

### `app/Http/Controllers/Mess/InvoiceController.php` - 1 site(s)

```text
L19      create()                         $vendors = Vendor::all();
```

### `app/Exports/OTHostelRoomDetailsExport.php` - 1 site(s)

```text
L22      collection()                     return OTHostelRoomDetails::all();
```

### `app/Http/Controllers/Mess/MonthlyBillController.php` - 1 site(s)

```text
L184     generateBills()                  $users = User::all();
```

### `app/Exports/BuildingMasterExport.php` - 1 site(s)

```text
L23      collection()                     return BuildingMaster::all();
```

### `app/Exports/FloorMasterExport.php` - 1 site(s)

```text
L24      collection()                     return FloorMaster::all();
```

### `app/Http/Controllers/Mess/NumberConfigController.php` - 1 site(s)

```text
L13      index()                          $configs = NumberConfig::all();
```

### `app/Http/Controllers/Admin/PermissionController.php` - 1 site(s)

```text
L20      create()                         'all_permissions' => Permission::all(),
```

### `app/Http/Controllers/Admin/Registration/FrontPageController.php` - 1 site(s)

```text
L837     allFaqs()                        $faqs = PathPageFaq::all(); // Fetch all FAQs
```

### `app/Http/Controllers/FC/RegistrationStep1Controller.php` - 1 site(s)

```text
L47      showStep1()                      $services = ServiceMaster::all();
```

### `app/Http/Controllers/Mess/EventController.php` - 1 site(s)

```text
L11      index()                          $events = Event::all();
```

### `app/Http/Controllers/Mess/InventoryController.php` - 1 site(s)

```text
L11      index()                          $inventories = Inventory::all();
```

### `app/Http/Controllers/Admin/MemoDisciplineController.php` - 1 site(s)

```text
L155     index()                          $sessions = \App\Models\ClassSessionMaster::all();
```

### `app/Http/Controllers/Mess/FinanceBookingController.php` - 1 site(s)

```text
L90      edit()                           $invoices = Invoice::all();
```

---

## G1-B - DB::table(..)->get() with no select()

Unrestricted SELECT *. Add an explicit column list.

### `app/Http/Controllers/Admin/Registration/FcJoiningDocumentController.php` - 1 site(s)

```text
L175     fc_report_index()                $allUploads = DB::table('fc_joining_documents_user_uploads')->get()->keyBy('user_id');
```

### `app/Http/Controllers/Admin/PeerEvaluationController.php` - 1 site(s)

```text
L1057    exportSubmissions()              $columns = DB::table('peer_columns')->get();
```

---

## G5-A - DB calls inside loops (N+1)

Heuristic: brace-tracked scan forward from each foreach/for/while header.
[loop@Lnnn] identifies the loop header line. Results de-duplicated across nested loops.

NOTE: expect some false positives where the call is a deliberate hoisted pre-load outside
the hot path. Every site still warrants a look. Fix with eager loading (->with()), a single
whereIn() pre-fetch, or a keyed lookup array built before the loop.

### `app/Http/Controllers/Admin/UserController.php` - 37 site(s)

```text
L197     dashboard()                      [loop@L195]  $upcoming = EmployeeMaster::where('status', 1)
L208     dashboard()                      [loop@L195]  ->get()
L502     buildDashboardFeedData()         [loop@L500]  $upcoming = EmployeeMaster::where('status', 1)
L516     buildDashboardFeedData()         [loop@L500]  ->get()
L749     getTodayPendingFamilyApprovalsCount() [loop@L748]  $first = $rowsInGroup->sortBy('fml_id_apply')->first();
L2125    resolveDashboardStudentListPayload() [loop@L2102]  ->first();
L2130    resolveDashboardStudentListPayload() [loop@L2102]  $studentMap->total_duty_count = MDOEscotDutyMap::where('selected_student_list', $studentPk)
L2134    resolveDashboardStudentListPayload() [loop@L2102]  ->count();
L2135    resolveDashboardStudentListPayload() [loop@L2102]  $studentMap->total_medical_exception_count = StudentMedicalExemption::where('student_master_pk', $studentPk)
L2137    resolveDashboardStudentListPayload() [loop@L2102]  ->count();
L2138    resolveDashboardStudentListPayload() [loop@L2102]  $studentMap->total_pt_exemption_count = LeaveApplication::where('student_master_pk', $studentPk)
L2144    resolveDashboardStudentListPayload() [loop@L2102]  ->count();
L2145    resolveDashboardStudentListPayload() [loop@L2102]  $studentMap->total_stationed_leave_count = LeaveApplication::where('student_master_pk', $studentPk)
L2154    resolveDashboardStudentListPayload() [loop@L2102]  ->count();
L2158    resolveDashboardStudentListPayload() [loop@L2102]  $studentMap->total_notice_count = $notices->count();
L2159    resolveDashboardStudentListPayload() [loop@L2102]  $studentMap->total_memo_count = $memos->count();
L2303    appendStudentsWithMemos()        [loop@L2289]  ->first();
L2366    augmentStudentListEntries()      [loop@L2351]  ->first();
L2371    augmentStudentListEntries()      [loop@L2351]  $studentMap->total_duty_count = MDOEscotDutyMap::where('selected_student_list', $studentPk)
L2375    augmentStudentListEntries()      [loop@L2351]  ->count();
L2376    augmentStudentListEntries()      [loop@L2351]  $studentMap->total_medical_exception_count = StudentMedicalExemption::where('student_master_pk', $studentPk)
L2378    augmentStudentListEntries()      [loop@L2351]  ->count();
L2379    augmentStudentListEntries()      [loop@L2351]  $studentMap->total_pt_exemption_count = LeaveApplication::where('student_master_pk', $studentPk)
L2385    augmentStudentListEntries()      [loop@L2351]  ->count();
L2386    augmentStudentListEntries()      [loop@L2351]  $studentMap->total_stationed_leave_count = LeaveApplication::where('student_master_pk', $studentPk)
L2395    augmentStudentListEntries()      [loop@L2351]  ->count();
L2399    augmentStudentListEntries()      [loop@L2351]  $studentMap->total_notice_count = $notices->count();
L2400    augmentStudentListEntries()      [loop@L2351]  $studentMap->total_memo_count = $memos->count();
L3589    myCounselee()                    [loop@L3582]  $course = CourseMaster::find($coursePk);
L3595    myCounselee()                    [loop@L3582]  $att = CourseStudentAttendance::where('Student_master_pk', $studentPk)
L3601    myCounselee()                    [loop@L3582]  ->first();
L3607    myCounselee()                    [loop@L3582]  $exemptionsCount = StudentMedicalExemption::where('student_master_pk', $studentPk)
L3609    myCounselee()                    [loop@L3582]  ->count();
L3610    myCounselee()                    [loop@L3582]  $memosCount = $noticeMemoService->getDisciplineMemos($studentPk)->count();
L3616    myCounselee()                    [loop@L3582]  ->get()
L4187    store()                          [loop@L4177]  $role = UserRoleMaster::find($roleId);
L4302    update()                         [loop@L4293]  $role = UserRoleMaster::find($roleId);
```

### `app/Http/Controllers/Mess/ProcessMessBillsEmployeeController.php` - 33 site(s)

```text
L1578    batchLineItemKeysByDrAndKiIds()  [loop@L1577]  foreach (DB::table('sv_date_range_report_items')
L1581    batchLineItemKeysByDrAndKiIds()  [loop@L1577]  ->get() as $row) {
L1591    batchLineItemKeysByDrAndKiIds()  [loop@L1590]  $itemsByMaster = DB::table('kitchen_issue_items')
L1594    batchLineItemKeysByDrAndKiIds()  [loop@L1590]  ->get()
L1723    fetchProcessIndexSvNetTotalsByReportIds() [loop@L1722]  $query = DB::table('sv_date_range_report_items')
L1729    fetchProcessIndexSvNetTotalsByReportIds() [loop@L1722]  foreach ($query->get() as $row) {
L1750    fetchProcessIndexSvItemDateExtentsByReportIds() [loop@L1749]  $query = DB::table('sv_date_range_report_items')
L1760    fetchProcessIndexSvItemDateExtentsByReportIds() [loop@L1749]  foreach ($query->get() as $row) {
L1786    fetchProcessIndexKitchenNetTotalsByPk() [loop@L1785]  foreach (DB::table('kitchen_issue_items')
L1790    fetchProcessIndexKitchenNetTotalsByPk() [loop@L1785]  ->get() as $row) {
L1811    fetchProcessIndexKitchenPaidTotalsByPk() [loop@L1810]  foreach (DB::table('kitchen_issue_payment_details')
L1815    fetchProcessIndexKitchenPaidTotalsByPk() [loop@L1811]  ->get() as $row) {
L2325    collectMessBillLineItemKeysFromProcessIndexStub() [loop@L2323]  ->pluck('id') as $itemId) {
L2432    collectMessBillLineItemKeysIssuedOnOrBefore() [loop@L2363]  foreach (DB::table('sv_date_range_report_items')
L2435    collectMessBillLineItemKeysIssuedOnOrBefore() [loop@L2363]  ->pluck('id') as $itemId) {
L4072    resolveReceiverUserIdByClientName() [loop@L4069]  $row = DB::table('user_credentials as uc')
L4076    resolveReceiverUserIdByClientName() [loop@L4069]  ->value('uc.user_id');
L4082    resolveReceiverUserIdByClientName() [loop@L4069]  $row = DB::table('user_credentials as uc')
L4086    resolveReceiverUserIdByClientName() [loop@L4069]  ->value('uc.user_id');
L4091    resolveReceiverUserIdByClientName() [loop@L4069]  $row = DB::table('user_credentials as uc')
L4095    resolveReceiverUserIdByClientName() [loop@L4069]  ->value('uc.user_id');
L4101    resolveReceiverUserIdByClientName() [loop@L4069]  $row = DB::table('user_credentials')
L4104    resolveReceiverUserIdByClientName() [loop@L4069]  ->value('user_id');
L4134    resolveReceiverUserIdByStudentName() [loop@L4131]  $row = DB::table('user_credentials as uc')
L4138    resolveReceiverUserIdByStudentName() [loop@L4131]  ->value('uc.user_id');
L4144    resolveReceiverUserIdByStudentName() [loop@L4131]  $row = DB::table('user_credentials as uc')
L4148    resolveReceiverUserIdByStudentName() [loop@L4131]  ->value('uc.user_id');
L4153    resolveReceiverUserIdByStudentName() [loop@L4131]  $row = DB::table('user_credentials as uc')
L4157    resolveReceiverUserIdByStudentName() [loop@L4131]  ->value('uc.user_id');
L4163    resolveReceiverUserIdByStudentName() [loop@L4131]  $row = DB::table('user_credentials as uc')
L4167    resolveReceiverUserIdByStudentName() [loop@L4131]  ->value('uc.user_id');
L4842    generatePayment()                [loop@L4814]  $bill->save();
L4866    generatePayment()                [loop@L4814]  $bill->save();
```

### `app/Http/Controllers/Admin/Registration/FormController.php` - 29 site(s)

```text
L224     cloneForm()                      [loop@L220]  $form = DB::table('local_form')->where('id', $formId)->first();
L229     cloneForm()                      [loop@L220]  $children = DB::table('local_form')->where('parent_id', $formId)->pluck('id');
L269     cloneForm()                      [loop@L252]  $newFormId = DB::table('local_form')->insertGetId([
L283     cloneForm()                      [loop@L252]  $sections = DB::table('form_sections')->where('formid', $formId)->get();
L286     cloneForm()                      [loop@L285]  $newSectionId = DB::table('form_sections')->insertGetId([
L296     cloneForm()                      [loop@L252]  $fields = DB::table('form_data')->where('formid', $formId)->get();
L298     cloneForm()                      [loop@L252]  DB::table('form_data')->insert([
L529     saveform()                       [loop@L513]  DB::table('form_data')->insert([
L556     saveform()                       [loop@L553]  DB::table('form_data')->insert([
L792     submit()                         [loop@L759]  DB::table('form_submission_tabledata')->insert([
L963     courseList()                     [loop@L961]  $ids = $records->pluck($field)->filter()->unique()->values();
L968     courseList()                     [loop@L961]  $pairs = DB::table($map['table'])
L970     courseList()                     [loop@L961]  ->pluck($map['name'], $map['id'])
L1209    exportfcformList()               [loop@L1205]  $lookupValues[$field] = DB::table($map['table'])
L1210    exportfcformList()               [loop@L1205]  ->pluck($map['name'], $map['id']); // [id => name]
L1591    generatePdf()                    [loop@L1587]  $pairs = DB::table($map['table'])
L1593    generatePdf()                    [loop@L1587]  ->pluck($map['name'], $map['id'])
L1617    generatePdf()                    [loop@L1601]  $tableData = DB::table('form_submission_tabledata')
L1621    generatePdf()                    [loop@L1601]  ->get();
L1636    generatePdf()                    [loop@L1628]  $name = DB::table('language_master')
L1638    generatePdf()                    [loop@L1601]  ->value('language_name');
L1643    generatePdf()                    [loop@L1630]  $name = DB::table('university_board_name_master')
L1645    generatePdf()                    [loop@L1601]  ->value('board_name');
L1650    generatePdf()                    [loop@L1601]  $name = DB::table('degree_master')
L1652    generatePdf()                    [loop@L1601]  ->value('degree_name'); // use degree_full_name for clarity
L1655    generatePdf()                    [loop@L1628]  $name = DB::table('institute_type_master')
L1657    generatePdf()                    [loop@L1630]  ->value('type_name');
L1663    generatePdf()                    [loop@L1601]  $name = DB::table($map['table'])
L1665    generatePdf()                    [loop@L1630]  ->value($map['name']);
```

### `app/Http/Controllers/Admin/Registration/StudentImportController.php` - 15 site(s)

```text
L393     migrate()                        [loop@L391]  $studentId = DB::table('student_master')->insertGetId($studentData);
L418     migrate()                        [loop@L416]  DB::table('student_master')
L420     migrate()                        [loop@L416]  ->update($updateData);
L444     migrate()                        [loop@L438]  DB::table('user_credentials')->insertOrIgnore($credentialData);
L454     migrate()                        [loop@L452]  DB::table('user_credentials')
L456     migrate()                        [loop@L452]  ->update($updateData);
L469     migrate()                        [loop@L463]  DB::table('user_credentials')
L471     migrate()                        [loop@L463]  ->update(['user_id' => (int) $studentPk]);
L485     migrate()                        [loop@L463]  DB::table('user_credentials')
L487     migrate()                        [loop@L463]  ->update(['user_id' => (int) $studentPk]);
L514     migrate()                        [loop@L498]  $freshCred = DB::table('user_credentials')
L546     migrate()                        [loop@L537]  $exists = DB::table('student_master_course__map')
L549     migrate()                        [loop@L537]  ->exists();
L564     migrate()                        [loop@L561]  DB::table('student_master_course__map')->insertOrIgnore($mapData);
L695     rekeyFcFormUserIdFromRosterPk()  [loop@L689]  DB::table($table)->where('user_id', $rosterPk)->update(['user_id' => $credentialsPk]);
```

### `app/Services/FC/DynamicFormService.php` - 13 site(s)

```text
L129     buildValidationRules()           [loop@L127]  $allowed = collect($field->decoded_options)->pluck('value')->map(fn ($v) => (string) $v)->values()->all();
L263     buildGroupValidationRules()      [loop@L260]  $allowed = collect($field->decoded_options)->pluck('value')->map(fn ($v) => (string) $v)->values()->all();
L279     buildGroupValidationRules()      [loop@L260]  $existingPath = $existingRows->first()->{$col} ?? null;
L304     getLookupData()                  [loop@L294]  $query = DB::table($table);
L316     getLookupData()                  [loop@L294]  $lookups[$field->field_name] = $query->get();
L366     getGroupLookupData()             [loop@L354]  $query = DB::table($table);
L376     getGroupLookupData()             [loop@L354]  $lookups[$field->field_name] = $query->get();
L478     getExistingData()                [loop@L470]  $row = DB::table($tbl)->where($this->userCol($tbl), $uVal)->first();
L787     saveStepDataForStep()            [loop@L782]  $existing = DB::table($table)->where($uCol, $uVal)->first();
L803     saveStepDataForStep()            [loop@L782]  DB::table($table)->updateOrInsert(
L808     saveStepDataForStep()            [loop@L782]  $existing = DB::table($table)->where($uCol, $uVal)->first();
L810     saveStepDataForStep()            [loop@L782]  DB::table($table)->where($uCol, $uVal)->update(['created_at' => now()]);
L908     saveGroupData()                  [loop@L902]  DB::table($gt)->insert($data);
```

### `app/Http/Controllers/Admin/TimetableReportController.php` - 12 site(s)

```text
L231     data()                           [loop@L212]  $facultyRows = DB::table('faculty_master')
L234     data()                           [loop@L212]  ->get();
L237     data()                           [loop@L212]  $facultyNames = $facultyRows->pluck('full_name')->implode(', ');
L238     data()                           [loop@L212]  $facultyCodes = $facultyRows->pluck('faculty_code')->implode(', ');
L258     data()                           [loop@L212]  $groupRows = DB::table('group_type_master_course_master_map')
L260     data()                           [loop@L212]  ->pluck('group_name');
L383     buildExportData()                [loop@L367]  $facultyRows = DB::table('faculty_master')
L386     buildExportData()                [loop@L367]  ->get();
L388     buildExportData()                [loop@L367]  $facultyNames = $facultyRows->pluck('full_name')->implode(', ');
L389     buildExportData()                [loop@L367]  $facultyCodes = $facultyRows->pluck('faculty_code')->implode(', ');
L404     buildExportData()                [loop@L367]  $groupRows = DB::table('group_type_master_course_master_map')
L406     buildExportData()                [loop@L367]  ->pluck('group_name');
```

### `app/Http/Controllers/Admin/Registration/RegistrationImportController.php` - 11 site(s)

```text
L255     importConfirmed()                [loop@L245]  $existingSameService = FcRegistrationMaster::where('email', $email)
L257     importConfirmed()                [loop@L245]  ->first();
L261     importConfirmed()                [loop@L245]  $existingSameService->update($this->mapTemplate1ImportAttributes($row, $contactNo, $serviceMaster));
L266     importConfirmed()                [loop@L245]  $existingDifferentService = FcRegistrationMaster::where('email', $email)
L269     importConfirmed()                [loop@L245]  ->first();
L281     importConfirmed()                [loop@L245]  $existing = FcRegistrationMaster::where('email', $email)
L283     importConfirmed()                [loop@L245]  ->first();
L287     importConfirmed()                [loop@L245]  $existing->update($this->mapTemplate1ImportAttributes($row, $contactNo, $serviceMaster));
L759     confirmUpload()                  [loop@L748]  $existing = FcRegistrationMaster::where([
L763     confirmUpload()                  [loop@L748]  ])->first();
L766     confirmUpload()                  [loop@L748]  FcRegistrationMaster::where('pk', $existing->pk)->update([
```

### `app/Http/Controllers/Admin/EstateController.php` - 11 site(s)

```text
L3319    storePossession()                [loop@L3315]  DB::table('estate_change_home_req_details')
L3321    storePossession()                [loop@L3315]  ->update([
L6857    storeMeterReadings()             [loop@L6809]  $row = DB::table('estate_month_reading_details as emrd')
L6864    storeMeterReadings()             [loop@L6812]  ->first();
L6870    storeMeterReadings()             [loop@L6812]  $rh = DB::table('estate_possession_details as epd')
L6873    storeMeterReadings()             [loop@L6809]  ->value('epd.return_home_status');
L8550    storeMeterReadingsOther()        [loop@L8538]  $row = EstateMonthReadingDetailsOther::where('pk', $resolvePk)->first();
L8558    storeMeterReadingsOther()        [loop@L8549]  $otherEpoQuery = DB::table('estate_possession_other as epo')
L8578    storeMeterReadingsOther()        [loop@L8549]  ->first();
L8587    storeMeterReadingsOther()        [loop@L8538]  $possReturned = DB::table('estate_possession_other')
L8591    storeMeterReadingsOther()        [loop@L8549]  ->exists();
```

### `app/Http/Controllers/Admin/CalendarController.php` - 10 site(s)

```text
L551     syncSupportingFacultyFeedback()  [loop@L548]  $reactivated = DB::table('supporting_faculty_feedback')
L557     syncSupportingFacultyFeedback()  [loop@L549]  ->update(['active_inactive' => 1, 'modified_date' => $now]);
L561     syncSupportingFacultyFeedback()  [loop@L549]  DB::table('supporting_faculty_feedback')->insertOrIgnore([
L3251    submitFeedback()                 [loop@L3233]  $existingFeedback = DB::table('topic_feedback')
L3256    submitFeedback()                 [loop@L3233]  ->first();
L3302    submitFeedback()                 [loop@L3233]  DB::table('topic_feedback')->insert([
L3636    submitFacultyInternalFeedback()  [loop@L3612]  $row = DB::table('supporting_faculty_feedback')
L3641    submitFacultyInternalFeedback()  [loop@L3612]  ->first();
L3657    submitFacultyInternalFeedback()  [loop@L3612]  DB::table('supporting_faculty_feedback')
L3659    submitFacultyInternalFeedback()  [loop@L3612]  ->update([
```

### `app/Exports/AttendanceDataExport.php` - 9 site(s)

```text
L72      array()                          [loop@L61]  $attendance = is_iterable($record->attendance) ? $record->attendance->first() : $record->attendance;
L86      array()                          [loop@L61]  $mdoDuty = MDOEscotDutyMap::where([
L90      array()                          [loop@L61]  ])->whereDate('mdo_date', '=', $this->timetableDate)->first();
L99      array()                          [loop@L61]  $escortDuty = MDOEscotDutyMap::where([
L103     array()                          [loop@L61]  ])->whereDate('mdo_date', '=', $this->timetableDate)->first();
L112     array()                          [loop@L61]  $otherExemption = MDOEscotDutyMap::where([
L116     array()                          [loop@L61]  ])->whereDate('mdo_date', '=', $this->timetableDate)->first();
L124     array()                          [loop@L61]  $medicalExemption = StudentMedicalExemption::where([
L135     array()                          [loop@L61]  })->first();
```

### `app/Http/Controllers/Admin/CourseRepositoryController.php` - 9 site(s)

```text
L50      index()                          [loop@L48]  $documents_count = CourseRepositoryDetail::where(
L53      index()                          [loop@L48]  )->count();
L80      index()                          [loop@L78]  $documents_count = CourseRepositoryDetail::where(
L83      index()                          [loop@L78]  )->count();
L127     show()                           [loop@L126]  $documents_count = CourseRepositoryDetail::where(
L130     show()                           [loop@L126]  )->count();
L1191    buildFolderPath()                [loop@L1180]  $current = CourseRepositoryMaster::find($current->parent_type);
L1521    userShow()                       [loop@L1520]  $documents_count = CourseRepositoryDetail::where(
L1524    userShow()                       [loop@L1520]  )->count();
```

### `app/Http/Controllers/Admin/Security/FamilyIDCardApprovalController.php` - 8 site(s)

```text
L470     approveGroup()                   [loop@L465]  $baseApproval = SecurityFamilyIdApplyApproval::where('security_fm_id_apply_pk', $row->fml_id_apply)
L473     approveGroup()                   [loop@L465]  ->first();
L490     approveGroup()                   [loop@L465]  $baseApproval->save();
L508     approveGroup()                   [loop@L465]  $pendingApproval = SecurityFamilyIdApplyApproval::where('security_fm_id_apply_pk', $row->fml_id_apply)
L511     approveGroup()                   [loop@L465]  ->first();
L522     approveGroup()                   [loop@L465]  $pendingApproval->save();
L525     approveGroup()                   [loop@L465]  $row->save();
L567     rejectGroup()                    [loop@L565]  $row->save();
```

### `app/Http/Controllers/Admin/AttendanceController.php` - 8 site(s)

```text
L1094    OTmarkAttendanceView()           [loop@L1085]  $attendance = CourseStudentAttendance::where([
L1099    OTmarkAttendanceView()           [loop@L1085]  ])->first();
L1147    OTmarkAttendanceView()           [loop@L1085]  $medicalExemption = StudentMedicalExemption::where([
L1158    OTmarkAttendanceView()           [loop@L1085]  })->first();
L1173    OTmarkAttendanceView()           [loop@L1085]  $otherExemption = MDOEscotDutyMap::where([
L1419    getOTAttendanceData()            [loop@L1389]  $attendance = CourseStudentAttendance::where([
L1424    getOTAttendanceData()            [loop@L1389]  ])->first();
L1472    getOTAttendanceData()            [loop@L1389]  $medicalExemption = StudentMedicalExemption::where([
```

### `app/Services/FC/RegistrationService.php` - 7 site(s)

```text
L325     additionalDynamicFlatFieldsForDisplay() [loop@L313]  $tableRows[$table] = DB::table($table)->where(fc_user_col($table), fc_user_val($table, $userId))->first();
L351     additionalDynamicFlatFieldsForDisplay() [loop@L302]  $lookupCache[$lk] = DB::table($field->lookup_table)
L353     additionalDynamicFlatFieldsForDisplay() [loop@L313]  ->value($field->lookup_label_column);
L429     buildPdfSectionsFromFormDefinition() [loop@L415]  $tableRows[$table] = DB::table($table)->where(fc_user_col($table), fc_user_val($table, $userId))->first();
L583     resolveStudentPhotoPath()        [loop@L556]  $row = DB::table($table)->where(fc_user_col($table), fc_user_val($table, $userId))->first();
L856     resolveLookupLabelSafely()       [loop@L850]  $value = DB::table($resolvedTable)->where($valueColumn, $raw)->value($labelColumn);
L880     resolveLookupLabelSafely()       [loop@L872]  $value = DB::table($resolvedTable)->where($col, $raw)->value($labelColumn);
```

### `app/Http/Controllers/Admin/Registration/FormEditController.php` - 6 site(s)

```text
L33      fc_edit()                        [loop@L31]  DB::table('form_data')->where('section_id', $sectionId)->delete();
L36      fc_edit()                        [loop@L31]  DB::table('form_sections')->where('id', $sectionId)->delete();
L203     fc_update()                      [loop@L196]  $newId = DB::table('form_sections')->insertGetId([
L215     fc_update()                      [loop@L196]  DB::table('form_sections')->where('id', $section_id)->update([
L279     fc_update()                      [loop@L228]  DB::table('form_data')->insert($field_data);
L281     fc_update()                      [loop@L228]  DB::table('form_data')->where('id', $field_id)->update($field_data);
```

### `app/Imports/GroupMapping/GroupMappingImport.php` - 6 site(s)

```text
L60      collection()                     [loop@L40]  $groupData = DB::table('group_type_master_course_master_map as gtm')
L72      collection()                     [loop@L40]  ->first();
L80      collection()                     [loop@L40]  $studentMaster = StudentMaster::where('generated_OT_code', $data['otcode'])
L90      collection()                     [loop@L40]  ->first();
L110     collection()                     [loop@L40]  StudentCourseGroupMap::where('student_master_pk', $studentMaster->pk)
L112     collection()                     [loop@L40]  ->exists()
```

### `app/Services/FacultyNoticeMemoService.php` - 5 site(s)

```text
L106     getNotices()                     [loop@L105]  $conversations = DB::table('notice_message_student_decip_incharge')
L109     getNotices()                     [loop@L105]  ->get();
L168     getMemos()                       [loop@L167]  $conversations = DB::table('memo_message_student_decip_incharge')
L171     getMemos()                       [loop@L167]  ->get();
L218     prepareViewData()                [loop@L213]  $student = StudentMaster::where('pk', $studentPk)->first();
```

### `app/Http/Controllers/FC/FormManagementController.php` - 5 site(s)

```text
L164     store()                          [loop@L160]  $newStep->save();
L170     store()                          [loop@L160]  $newField->save();
L177     store()                          [loop@L174]  $newGroup->save();
L182     store()                          [loop@L174]  $newGf->save();
L312     reorderSteps()                   [loop@L311]  FcFormStep::where('id', $id)->update(['step_number' => $position + 1]);
```

### `app/Http/Controllers/Admin/MedicalExceptionOTViewController.php` - 5 site(s)

```text
L71      index()                          [loop@L67]  $course = CourseMaster::where('pk', $courseMasterPk)
L74      index()                          [loop@L67]  ->first();
L128     adminView()                      [loop@L126]  $exemptionQuery = StudentMedicalExemption::where('student_master_pk', $student->pk)
L136     adminView()                      [loop@L126]  $exemptionCount = $exemptionQuery->count();
L141     adminView()                      [loop@L126]  ->get();
```

### `app/Http/Controllers/Admin/PeerEvaluationController.php` - 5 site(s)

```text
L495     store()                          [loop@L494]  DB::table('peer_scores')->updateOrInsert(
L512     store()                          [loop@L511]  DB::table('reflection_responses')->updateOrInsert(
L683     addMembersToGroup()              [loop@L682]  $exists = DB::table('peer_group_members')
L686     addMembersToGroup()              [loop@L682]  ->exists();
L689     addMembersToGroup()              [loop@L682]  DB::table('peer_group_members')->insert([
```

### `app/Console/Commands/FcSyncStepGroupFieldsCommand.php` - 4 site(s)

```text
L67      handle()                         [loop@L59]  $existingNames = $targetGroup->groupFields->pluck('field_name')->flip();
L79      handle()                         [loop@L69]  $newField->save();
L116     listSteps()                      [loop@L109]  $step->fieldGroups()->pluck('id')
L117     listSteps()                      [loop@L109]  )->count();
```

### `app/Http/Controllers/Admin/CourseAttendanceNoticeMapController.php` - 4 site(s)

```text
L582     noticeMemoExportData()           [loop@L580]  $memoDataQuery = DB::table('student_memo_status')
L625     noticeMemoExportData()           [loop@L580]  ->first();
L1442    store_memo_notice_bkp()          [loop@L1441]  $student_id = DB::table('course_student_attendance as a')
L1448    store_memo_notice_bkp()          [loop@L1441]  ->get();
```

### `app/Services/OTNoticeMemoService.php` - 4 site(s)

```text
L66      getNotices()                     [loop@L65]  $conversations = DB::table('notice_message_student_decip_incharge')
L69      getNotices()                     [loop@L65]  ->get();
L110     getMemos()                       [loop@L109]  $conversations = DB::table('memo_message_student_decip_incharge')
L113     getMemos()                       [loop@L109]  ->get();
```

### `app/Support/IdCardSecurityMapper.php` - 4 site(s)

```text
L1262    resolvedDisplayIdCardNumberForEmployee() [loop@L1258]  $rows = DB::table($tbl)
L1344    resolveCanonicalFromPrintedIdCardNumber() [loop@L1324]  $row = DB::table($table)
L1347    resolveCanonicalFromPrintedIdCardNumber() [loop@L1324]  ->first();
L1353    resolveCanonicalFromPrintedIdCardNumber() [loop@L1324]  $em2 = DB::table('employee_master')
```

### `app/Http/Controllers/Mess/SellingVoucherDateRangeController.php` - 4 site(s)

```text
L1791    updateFilteredSellingVoucherDateRange() [loop@L1780]  $reportModel->update(['total_amount' => $grandTotal]);
L2035    updateReturn()                   [loop@L1995]  $item->update([
L2416    findEmployeePkByDisplayName()    [loop@L2413]  ->get()
L2425    findEmployeePkByDisplayName()    [loop@L2413]  ->value('department_name'));
```

### `app/Imports/CourseWiseOTImport.php` - 3 site(s)

```text
L266     processBatch()                   [loop@L265]  CourseWiseOTList::where('pk', $id)->update($data);
L292     updateStudentMaster()            [loop@L290]  $updated = StudentMaster::where('pk', $studentId)
L293     updateStudentMaster()            [loop@L290]  ->update([
```

### `app/Http/Controllers/FC/FormBuilderController.php` - 3 site(s)

```text
L117     reorderFields()                  [loop@L116]  FcFormField::where('id', $id)->update(['display_order' => $position + 1]);
L207     reorderGroupFields()             [loop@L206]  FcFormGroupField::where('id', $id)->update(['display_order' => $position + 1]);
L379     reorderDocMasters()              [loop@L378]  FcJoiningRelatedDocumentsMaster::where('id', $id)->update(['display_order' => $position + 1]);
```

### `app/Http/Controllers/FC/ReportController.php` - 3 site(s)

```text
L161     formOverview()                   [loop@L159]  ->where($step->tracker_column, 1)->count();
L606     fcResolveLookupLabel()           [loop@L598]  $label = DB::table($table)->where($valueColumn, $raw)->value($labelColumn);
L1236    formExportCsv()                  [loop@L1234]  $totalDone = $steps->filter(fn ($s) => ($r->{$s->tracker_column} ?? 0))->count();
```

### `app/Console/Commands/FcReconcileRosterIds.php` - 3 site(s)

```text
L106     handle()                         [loop@L105]  $rosterCount = DB::table($table)->where($col, $p->roster_pk)->count();
L111     handle()                         [loop@L105]  $credExists = DB::table($table)->where($col, $p->cred_pk)->exists();
L119     handle()                         [loop@L105]  DB::table($table)->where($col, $p->roster_pk)->update([$col => $p->cred_pk]);
```

### `app/Http/Controllers/Admin/MemoDisciplineController.php` - 3 site(s)

```text
L522     exportPdfZip()                   [loop@L508]  $conclusionTypeName = DB::table('memo_conclusion_master')
L523     exportPdfZip()                   [loop@L508]  ->where('pk', $memo->conclusion_type_pk)->value('discussion_name');
L900     discipline_generate_memo_store() [loop@L898]  $memoPk = DB::table('discipline_memo_status')->insertGetId([
```

### `app/Http/Controllers/Admin/MedicalExceptionFacultyViewController.php` - 3 site(s)

```text
L254     adminView()                      [loop@L236]  $courseStudentIds = $studentMappings->get($courseId, collect())->pluck('student_master_pk')->toArray();
L271     adminView()                      [loop@L236]  'total_students' => $courseStudents->count(),
L369     adminView()                      [loop@L368]  if ($course['students']->count() > 0) {
```

### `app/Http/Controllers/Admin/FamilyIDCardRequestController.php` - 3 site(s)

```text
L590     store()                          [loop@L583]  $existingDuplicate = SecurityFamilyIdApply::where('emp_id_apply', $employeeId)
L595     store()                          [loop@L583]  ->exists();
L868     update()                         [loop@L840]  $memberRow->save();
```

### `app/Http/Controllers/Admin/OTMDOEscrotExemptionController.php` - 2 site(s)

```text
L281     buildDutyMaps()                  [loop@L276]  $courseCache[$courseMasterPk] = CourseMaster::where('pk', $courseMasterPk)
L284     buildDutyMaps()                  [loop@L276]  ->first();
```

### `app/Http/Controllers/Admin/Security/VisitorPassController.php` - 2 site(s)

```text
L88      store()                          [loop@L82]  $visitorName->save();
L190     update()                         [loop@L184]  $visitorName->save();
```

### `app/Http/Controllers/Mess/StoreAllocationController.php` - 2 site(s)

```text
L235     store()                          [loop@L231]  $sub = ItemSubcategory::find($item['item_subcategory_id']);
L296     update()                         [loop@L292]  $sub = ItemSubcategory::find($item['item_subcategory_id']);
```

### `app/Http/Controllers/Admin/FeedbackController.php` - 2 site(s)

```text
L4959    getPendingStudentsOptimized()    [loop@L4958]  $query = DB::table('timetable as t')
L5006    getPendingStudentsOptimized()    [loop@L4958]  $chunkResults = $query->get();
```

### `app/Exports/PeerEvaluationExport.php` - 2 site(s)

```text
L133     collection()                     [loop@L131]  $evaluators = $this->scores->where('member_id', $member->id)->pluck('evaluator_id')->unique();
L151     collection()                     [loop@L146]  ->first();
```

### `app/Imports/AssignHostelToStudent.php` - 2 site(s)

```text
L52      collection()                     [loop@L32]  $room = BuildingFloorRoomMapping::where('room_name', $data['hostel_room_name'] ?? '')->first();
L54      collection()                     [loop@L32]  $allocatedCount = OTHostelRoomDetails::where('hostel_room_name', $data['hostel_room_name'])->count();
```

### `app/Http/Controllers/Mess/MonthlyBillController.php` - 2 site(s)

```text
L189     generateBills()                  [loop@L187]  $exists = MonthlyBill::where('user_id', $user->pk)
L192     generateBills()                  [loop@L187]  ->exists();
```

### `app/Http/Controllers/Admin/WhosWhoController.php` - 2 site(s)

```text
L451     buildStudentsResponsePayload()   [loop@L426]  ->first();
L471     buildStudentsResponsePayload()   [loop@L426]  ->get();
```

### `app/Http/Controllers/Mess/PurchaseOrderController.php` - 2 site(s)

```text
L320     store()                          [loop@L314]  $sub = ItemSubcategory::find($itemSubcategoryId);
L486     update()                         [loop@L480]  $sub = ItemSubcategory::find($itemSubcategoryId);
```

### `app/Http/Controllers/Admin/Setup/QuickLinksSetupController.php` - 2 site(s)

```text
L157     reorder()                        [loop@L155]  QuickLink::query()->where('id', $link->id)->update(['position' => $i]);
L187     bulkReorder()                    [loop@L184]  ->update([
```

### `app/Http/Controllers/Admin/Registration/EnrollementController.php` - 2 site(s)

```text
L177     store()                          [loop@L172]  $student = StudentMaster::find($studentId);
L220     store()                          [loop@L205]  $enrollment->save();
```

### `app/Http/Controllers/Mess/KitchenIssueController.php` - 2 site(s)

```text
L1994    updateReturn()                   [loop@L1989]  $item = KitchenIssueItem::find($itemPk);
L2025    updateReturn()                   [loop@L1989]  $item->update([
```

### `app/Http/Controllers/Admin/Setup/UsefulLinksSetupController.php` - 1 site(s)

```text
L201     bulkReorder()                    [loop@L198]  ->update([
```

### `app/Exports/Mess/CategoryWisePrintSlipExport.php` - 1 site(s)

```text
L52      collection()                     [loop@L51]  $first = $sectionVouchers->first();
```

### `app/Services/FC/FcRegistrationFlowService.php` - 1 site(s)

```text
L113     buildStepCompletionByStepId()    [loop@L108]  $row = DB::table($t)->where(fc_user_col($t), fc_user_val($t, $userId))->first();
```

### `app/Imports/PeerGroupMembersImport.php` - 1 site(s)

```text
L130     collection()                     [loop@L121]  DB::table('peer_group_members')->insert([
```

### `app/Http/Controllers/SidebarController.php` - 1 site(s)

```text
L132     sidebarMenus()                   [loop@L117]  $hasChild = $children->count() > 0;
```

### `app/Http/Controllers/Mess/InboundTransactionController.php` - 1 site(s)

```text
L81      store()                          [loop@L67]  $inventory = Inventory::find($item['inventory_id']);
```

### `app/Http/Controllers/Mess/MaterialRequestController.php` - 1 site(s)

```text
L98      processApproval()                [loop@L96]  $item->update([
```

### `app/Http/Controllers/Admin/IssueManagement/IssueEscalationMatrixController.php` - 1 site(s)

```text
L132     matrixFromCacheArray()           [loop@L125]  $category = IssueCategoryMaster::hydrate([$row['category']])->first();
```

### `app/Http/Controllers/Admin/Registration/FrontPageController.php` - 1 site(s)

```text
L618     fcFormSubmit()                   [loop@L591]  DB::table('form_submission_tabledata')->insert([
```

### `app/Http/Controllers/Admin/Registration/FcJoiningDocumentController.php` - 1 site(s)

```text
L187     fc_report_index()                [loop@L185]  $uploadedCount = $upload ? collect($fieldsKeys)->filter(fn($key) => !empty($upload->$key))->count() : 0;
```

---

## G5-B - Queries executed inside Blade views

Query logic in a template executes once per rendered row and cannot be eager-loaded.
Move to the controller and pass pre-grouped data to the view.

### `resources/views/admin/member/edit_steps/step4.blade.php` - 3 site(s)

```text
L27      (blade)                          $stateOptions = App\Models\State::where('country_master_pk', $member->country_master_pk)->get()->pluck('state_name', 'pk');
L35      (blade)                          $districtOptions = App\Models\District::where('state_master_pk', $member->state_master_pk)->get()->pluck('district_name', 'pk');
L43      (blade)                          $cityOptions = App\Models\City::where(['district_master_pk' => $member->state_district_mapping_pk, 'state_master_pk' => $member->s ...
```

### `resources/views/admin/member/show.blade.php` - 3 site(s)

```text
L31      (blade)                          'City' => App\Models\City::find($member->city)->city_name ?? '',
L32      (blade)                          'State' => App\Models\State::find($member->state_master_pk)->state_name ?? '',
L33      (blade)                          'Country' => App\Models\Country::find($member->country_master_pk)->country_name ?? '',
```

### `resources/views/admin/issue_management/show.blade.php` - 3 site(s)

```text
L125     (blade)                          $assignedEmployee = \DB::table('employee_master')->where('pk', $issue->assigned_to)->first();
L189     (blade)                          $hostelRow = \DB::table('hostel_building_master')->where('pk', $issue->hostelMapping->hostel_building_master_pk)->first();
L472     (blade)                          $assignedEmployee = DB::table('employee_master')->where('pk', $issue->assigned_to)->first();
```

### `resources/views/admin/faculty/show.blade.php` - 2 site(s)

```text
L277     (blade)                          $sectorName = \Illuminate\Support\Facades\DB::table('faculty_sector_master')
L289     (blade)                          $serviceName = \Illuminate\Support\Facades\DB::table('service_master')
```

### `resources/views/admin/forms/field-types.blade.php` - 2 site(s)

```text
L145     (blade)                          $options = DB::table($tableName)->get();
L242     (blade)                          $options = DB::table($tableName)->get();
```

### `resources/views/fc/registration/partials/document-checklist.blade.php` - 1 site(s)

```text
L34      (blade)                          $sampleDocs = \App\Models\FC\FcJoiningSampleDocument::where('is_active', 1)->get()->keyBy('field_name');
```

### `resources/views/components/menu/setup_estate_management.blade.php` - 1 site(s)

```text
L28      (blade)                          $empQuery = \Illuminate\Support\Facades\DB::table('employee_master');
```

### `resources/views/components/menu/setup_activities.blade.php` - 1 site(s)

```text
L17      (blade)                          $empQuery = \Illuminate\Support\Facades\DB::table('employee_master');
```

### `resources/views/admin/forms/peer_evaluation/group_members.blade.php` - 1 site(s)

```text
L113     (blade)                          $members = DB::table('peer_group_members')
```

### `resources/views/admin/layouts/sidebar/home.blade.php` - 1 site(s)

```text
L7       (blade)                          $emp = \Illuminate\Support\Facades\DB::table('employee_master')
```

### `resources/views/admin/layouts/sidebar/setup.blade.php` - 1 site(s)

```text
L8       (blade)                          $emp = \Illuminate\Support\Facades\DB::table('employee_master')
```

---

## G2-A - Controllers rendering views with no pagination

These render a view, call ->get() three or more times, and contain zero
paginate() / simplePaginate() / cursorPaginate() anywhere in the file.

```text
 gets    lines  file
   77    12663  app/Http/Controllers/Admin/EstateController.php
   32     3015  app/Http/Controllers/Admin/Security/EmployeeIDCardApprovalController.php
   31     5190  app/Http/Controllers/Admin/FeedbackController.php
   30     1775  app/Http/Controllers/Admin/CourseRepositoryController.php
   27     1831  app/Http/Controllers/Admin/Registration/FormController.php
   22     1538  app/Http/Controllers/Admin/IssueManagement/IssueManagementController.php
   20     2307  app/Http/Controllers/Mess/KitchenIssueController.php
   20     2859  app/Http/Controllers/Mess/SellingVoucherDateRangeController.php
   20     5524  app/Http/Controllers/Mess/ProcessMessBillsEmployeeController.php
   16     1061  app/Http/Controllers/Admin/StudentMedicalExemptionController.php
   15     1928  app/Http/Controllers/Admin/AttendanceController.php
   12      452  app/Http/Controllers/FC/RegistrationStep3Controller.php
   11      331  app/Http/Controllers/Admin/CourseMemoDecisionMappController.php
    9      549  app/Http/Controllers/Admin/TimetableReportController.php
    8      168  app/Http/Controllers/Mess/VendorItemMappingController.php
    7      666  app/Http/Controllers/Admin/Security/VehiclePassApprovalController.php
    6     1248  app/Http/Controllers/Admin/FamilyIDCardRequestController.php
    6      665  app/Http/Controllers/Mess/PurchaseOrderController.php
    5     2020  app/Http/Controllers/Admin/EmployeeIDCardRequestController.php
    5      291  app/Http/Controllers/Admin/Registration/FcExemptionMasterController.php
    5      192  app/Http/Controllers/Admin/DashboardController.php
    5      671  app/Http/Controllers/FC/FcActivityMedicalController.php
    4      408  app/Http/Controllers/FC/FormBuilderController.php
    4      353  app/Http/Controllers/Mess/ItemSubcategoryController.php
    4      482  app/Http/Controllers/Admin/CourseController.php
    4      374  app/Http/Controllers/FC/FormManagementController.php
    4      564  app/Http/Controllers/FC/GenericFormController.php
    4      559  app/Http/Controllers/Admin/StationedLeaveMasterController.php
    4       82  app/Http/Controllers/Admin/Master/DisciplineMasterController.php
    4      769  app/Http/Controllers/Admin/WhosWhoController.php
    4      206  app/Http/Controllers/Admin/HostelBuildingFloorMappingController.php
    4     1145  app/Http/Controllers/Admin/Security/VehiclePassController.php
    4      414  app/Http/Controllers/Admin/MedicalExceptionFacultyViewController.php
    4      403  app/Http/Controllers/Admin/Registration/FormEditController.php
    3      320  app/Http/Controllers/Mess/StoreAllocationController.php
    3      188  app/Http/Controllers/FC/FcActivityHomeController.php
    3      272  app/Http/Controllers/Admin/IssueManagement/IssueEscalationMatrixController.php
    3      195  app/Http/Controllers/FC/RegistrationStep2Controller.php
    3      795  app/Http/Controllers/Admin/Registration/RegistrationImportController.php
    3      474  app/Http/Controllers/Admin/ExemptionMasterController.php
    3      105  app/Http/Controllers/Mess/InboundTransactionController.php
    3      162  app/Http/Controllers/FC/TravelPlanReportController.php
```

---

## G2-B - Client-side DataTables

These views initialise a DataTable without serverSide: true, so the controller ships the
entire result set into the HTML and the browser pages it. Convert to a Yajra server-side
DataTable - PR #246 established the pattern in FcEnrollmentStudentsDataTable.

```text
L489     resources/views/admin/attendance/mark-attendance.blade.php
L347     resources/views/admin/building_floor_mapping/assign_student.blade.php
L1301    resources/views/admin/courseAttendanceNoticeMap/index.blade.php   [paging:false]
L176     resources/views/admin/courseAttendanceNoticeMap/memo_notice_index.blade.php   [paging:false]
L163     resources/views/admin/courseAttendanceNoticeMap/notice_list.blade.php
L385     resources/views/admin/course-repository/index.blade.php
L4010    resources/views/admin/course-repository/show.blade.php
L212     resources/views/admin/course-repository/user/show.blade.php
L55      resources/views/admin/dashboard/active_course.blade.php
L51      resources/views/admin/dashboard/incoming_course.blade.php
L277     resources/views/admin/dashboard/partials/faculty_table.blade.php
L77      resources/views/admin/dashboard/sessions.blade.php
L421     resources/views/admin/directory/lbsnaa.blade.php
L404     resources/views/admin/estate/change_request_hac_approved.blade.php
L55      resources/views/admin/estate/define_block_building/index.blade.php
L61      resources/views/admin/estate/define_campus/index.blade.php
L57      resources/views/admin/estate/define_unit_sub_type/index.blade.php
L64      resources/views/admin/estate/define_unit_type/index.blade.php
L125     resources/views/admin/estate/eligibility_criteria/index.blade.php
L183     resources/views/admin/estate/estate_possession_for_others.blade.php
L214     resources/views/admin/estate/estate_request_for_others.blade.php
L209     resources/views/admin/estate/generate_estate_bill_for_other.blade.php
L116     resources/views/admin/estate/hac_forward.blade.php
L48      resources/views/admin/estate/house_status.blade.php
L70      resources/views/admin/estate/pending_meter_reading.blade.php
L205     resources/views/admin/estate/possession_details.blade.php
L180     resources/views/admin/estate/put_in_hac.blade.php
L333     resources/views/admin/estate/request_for_estate.blade.php
L614     resources/views/admin/estate/request_for_house.blade.php
L725     resources/views/admin/estate/return_house.blade.php
L153     resources/views/admin/faculty/index.blade.php
L222     resources/views/admin/fc-activities/home/index.blade.php
L1022    resources/views/admin/forms/peer_evaluation/admin.blade.php
L240     resources/views/admin/forms/peer_evaluation/view_submissions.blade.php
L642     resources/views/admin/group_mapping/index.blade.php
L586     resources/views/admin/issue_management/categories/index.blade.php
L181     resources/views/admin/issue_management/index.blade.php
L117     resources/views/admin/layouts/footer.blade.php
L302     resources/views/admin/master/hostel_building/index.blade.php
L275     resources/views/admin/master/hostel_floor/index.blade.php
L165     resources/views/admin/master/mdo_duty_type/index.blade.php
L161     resources/views/admin/master/memo_conclusion_master/index.blade.php
L167     resources/views/admin/master/memo_type/index.blade.php
L392     resources/views/admin/mdo_escrot_exemption/index.blade.php
L108     resources/views/admin/member/index.blade.php
L347     resources/views/admin/memo_discipline/index.blade.php   [paging:false]
L60      resources/views/admin/mess/partials/column-manager-auto-init.blade.php
L117     resources/views/admin/mess/partials/smooth-scroll.blade.php
L410     resources/views/admin/mess/process-mess-bills-employee/index.blade.php
L314     resources/views/admin/programme/index.blade.php
L330     resources/views/admin/registration/enrollement.blade.php   [paging:false]
L321     resources/views/admin/registration/fcregistrationmaster_list.blade.php
L152     resources/views/admin/security/vehicle_pass_approval/all.blade.php
L194     resources/views/admin/subject/index.blade.php   [paging:false]
L194     resources/views/admin/subject_module/index.blade.php   [paging:false]
L151     resources/views/admin/travel/index.blade.php
L163     resources/views/admin/user_management/roles/index.blade.php
L2231    resources/views/mess/kitchen-issues/index.blade.php
L596     resources/views/mess/purchaseorders/index.blade.php
L220     resources/views/mess/selling-voucher-date-range/index.blade.php
L533     resources/views/roles-permissions/assign-dashboard.blade.php
L261     resources/views/roles-permissions/assign-permission.blade.php
L344     resources/views/SidebarMenu/categories/index.blade.php
L556     resources/views/SidebarMenu/menu_groups/index.blade.php
L535     resources/views/SidebarMenu/menus/index.blade.php
```

---

_Line numbers valid at time of scan and will shift as fixes are applied._
