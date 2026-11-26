<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Protocol Request {{ $protocolRequest->request_number }}</title>
  <style>
    @page {
      size: A4 landscape;
      margin: 13mm 13mm 19mm;
    }

    * {
      box-sizing: border-box;
    }

    body {
      color: #243247;
      font-family: DejaVu Sans, sans-serif;
      font-size: 8.5px;
      line-height: 1.45;
    }

    .pdf-header {
      border-bottom: 2px solid #d7a640;
      margin-bottom: 12px;
      padding-bottom: 8px;
    }

    .pdf-request-number {
      color: #193653;
      font-size: 15px;
      font-weight: bold;
      letter-spacing: .4px;
    }

    .pr-card {
      border: 1px solid #dce3ec;
      margin-bottom: 9px;
      page-break-inside: auto;
    }

    .pr-card__body {
      padding: 9px 10px;
    }

    .pr-card__title {
      background: #f3f6fa;
      border-left: 3px solid #193653;
      color: #193653;
      font-size: 9px;
      font-weight: bold;
      letter-spacing: .45px;
      margin: 0 0 8px;
      padding: 6px 8px;
      page-break-after: avoid;
      text-transform: uppercase;
    }

    .pr-facts {
      line-height: 1.4;
    }

    .pr-facts > div {
      display: inline-block;
      margin: 0 0 6px;
      padding-right: 7px;
      vertical-align: top;
      width: 24%;
      word-wrap: break-word;
    }

    .pr-facts > div[style] {
      width: 100%;
    }

    .pr-fact__label {
      color: #6b7b90;
      display: block;
      font-size: 7px;
      margin-bottom: 2px;
    }

    .pr-fact__value {
      color: #26384d;
      font-size: 8px;
      font-weight: bold;
      word-wrap: break-word;
    }

    table {
      border-collapse: collapse;
      table-layout: fixed;
      width: 100%;
    }

    thead {
      display: table-header-group;
    }

    .programme-dt-table th,
    .programme-dt-table td {
      border: 1px solid #dce3ec;
      padding: 5px 6px;
      text-align: left;
      vertical-align: top;
      word-wrap: break-word;
    }

    .programme-dt-table th {
      background: #eaf0f6;
      color: #30455f;
      font-size: 7px;
      font-weight: bold;
    }

    .programme-dt-table td {
      color: #34445a;
      font-size: 7.5px;
    }

    .programme-dt-table tbody tr:nth-child(even) td {
      background: #f8fafc;
    }

    .programme-dt-table tr {
      page-break-inside: avoid;
    }

    .pr-guest-table th:nth-child(1) { width: 20%; }
    .pr-guest-table th:nth-child(2) { width: 6%; }
    .pr-guest-table th:nth-child(3) { width: 8%; }
    .pr-guest-table th:nth-child(4) { width: 14%; }
    .pr-guest-table th:nth-child(5) { width: 12%; }
    .pr-guest-table th:nth-child(6) { width: 16%; }
    .pr-guest-table th:nth-child(7) { width: 16%; }
    .pr-guest-table th:nth-child(8) { width: 8%; }

    .pr-vehicle-legs-table th:nth-child(1) { width: 17%; }
    .pr-vehicle-legs-table th:nth-child(2) { width: 17%; }
    .pr-vehicle-legs-table th:nth-child(3) { width: 18%; }
    .pr-vehicle-legs-table th:nth-child(4) { width: 18%; }
    .pr-vehicle-legs-table th:nth-child(5) { width: 8%; }
    .pr-vehicle-legs-table th:nth-child(6) { width: 22%; }

    .pr-journey {
      border: 1px solid #dce3ec;
      margin-bottom: 7px;
      padding: 7px;
      page-break-inside: avoid;
    }

    .badge-status {
      background: #e5f5ec;
      border: 1px solid #b7dfc7;
      color: #17613b;
      display: inline-block;
      padding: 1px 5px;
    }

    .empty-state {
      color: #6b7b90;
      padding: 8px;
    }

    .pdf-footer {
      border-top: 1px solid #dce3ec;
      bottom: -11mm;
      color: #7a8798;
      font-size: 7px;
      left: 0;
      padding-top: 5px;
      position: fixed;
      right: 0;
    }

    .pdf-footer table,
    .pdf-footer td {
      border: 0;
      padding: 0;
    }

    .pdf-footer .page-number {
      text-align: right;
    }

    .pdf-footer .page-number:before {
      content: "Page " counter(page) " of " counter(pages);
    }
  </style>
</head>
<body>
  <header class="pdf-header">
    <div class="pdf-request-number">{{ $protocolRequest->request_number }}</div>
  </header>

  @include('protocol::partials.request-detail')

  <footer class="pdf-footer">
    <table>
      <tr>
        <td>{{ config('app.name', 'Sargam') }} &nbsp; | &nbsp; Official Use</td>
        <td class="page-number"></td>
      </tr>
    </table>
  </footer>
</body>
</html>
