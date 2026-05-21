@php $snoThClass = trim('mess-report-sno-th text-center text-nowrap ' . ($class ?? '')); @endphp
<th class="{{ $snoThClass }}" scope="col"@if(!empty($rowspan)) rowspan="{{ (int) $rowspan }}"@endif>S.&nbsp;No.</th>
