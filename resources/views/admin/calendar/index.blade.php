@extends('admin.layouts.master')

@section('title', 'Calendar - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Calendar</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="#">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    @lang('Calendar')
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body calender-sidebar app-calendar">
            <div id="calendar" class="fc fc-media-screen fc-direction-ltr fc-theme-standard" style="height: 1052px;">
                <div class="fc-header-toolbar fc-toolbar fc-toolbar-ltr">
                    <div class="fc-toolbar-chunk"><button type="button" title="Previous month" aria-pressed="false"
                            class="fc-prev-button fc-button fc-button-primary"><span
                                class="fc-icon fc-icon-chevron-left"></span></button><button type="button"
                            title="Next month" aria-pressed="false"
                            class="fc-next-button fc-button fc-button-primary"><span
                                class="fc-icon fc-icon-chevron-right"></span></button><button type="button"
                            title="Add Calendar Event" aria-pressed="false"
                            class="fc-addEventButton-button fc-button fc-button-primary">Add Calendar Event</button>
                    </div>
                    <div class="fc-toolbar-chunk">
                        <h2 class="fc-toolbar-title" id="fc-dom-1">{{ date('F Y') }}</h2>
                    </div>
                    <div class="fc-toolbar-chunk">
                        <div class="fc-button-group"><button type="button" title="month view" aria-pressed="true"
                                class="fc-dayGridMonth-button fc-button fc-button-primary fc-button-active">month</button><button
                                type="button" title="week view" aria-pressed="false"
                                class="fc-timeGridWeek-button fc-button fc-button-primary">week</button><button
                                type="button" title="day view" aria-pressed="false"
                                class="fc-timeGridDay-button fc-button fc-button-primary">day</button></div>
                    </div>
                </div>
                <div aria-labelledby="fc-dom-1" class="fc-view-harness fc-view-harness-active">
                    <div class="fc-dayGridMonth-view fc-view fc-daygrid">
                        <table role="grid" class="fc-scrollgrid  fc-scrollgrid-liquid">
                            <thead role="rowgroup">
                                <tr role="presentation" class="fc-scrollgrid-section fc-scrollgrid-section-header ">
                                    <th role="presentation">
                                        <div class="fc-scroller-harness">
                                            <div class="fc-scroller" style="overflow: hidden;">
                                                <table role="presentation" class="fc-col-header "
                                                    style="width: 1065px;">
                                                    <colgroup></colgroup>
                                                    <thead role="presentation">
                                                        <tr role="row">
                                                            <th role="columnheader"
                                                                class="fc-col-header-cell fc-day fc-day-sun">
                                                                <div class="fc-scrollgrid-sync-inner"><a
                                                                        aria-label="Sunday"
                                                                        class="fc-col-header-cell-cushion">Sun</a></div>
                                                            </th>
                                                            <th role="columnheader"
                                                                class="fc-col-header-cell fc-day fc-day-mon">
                                                                <div class="fc-scrollgrid-sync-inner"><a
                                                                        aria-label="Monday"
                                                                        class="fc-col-header-cell-cushion">Mon</a></div>
                                                            </th>
                                                            <th role="columnheader"
                                                                class="fc-col-header-cell fc-day fc-day-tue">
                                                                <div class="fc-scrollgrid-sync-inner"><a
                                                                        aria-label="Tuesday"
                                                                        class="fc-col-header-cell-cushion">Tue</a></div>
                                                            </th>
                                                            <th role="columnheader"
                                                                class="fc-col-header-cell fc-day fc-day-wed">
                                                                <div class="fc-scrollgrid-sync-inner"><a
                                                                        aria-label="Wednesday"
                                                                        class="fc-col-header-cell-cushion">Wed</a></div>
                                                            </th>
                                                            <th role="columnheader"
                                                                class="fc-col-header-cell fc-day fc-day-thu">
                                                                <div class="fc-scrollgrid-sync-inner"><a
                                                                        aria-label="Thursday"
                                                                        class="fc-col-header-cell-cushion">Thu</a></div>
                                                            </th>
                                                            <th role="columnheader"
                                                                class="fc-col-header-cell fc-day fc-day-fri">
                                                                <div class="fc-scrollgrid-sync-inner"><a
                                                                        aria-label="Friday"
                                                                        class="fc-col-header-cell-cushion">Fri</a></div>
                                                            </th>
                                                            <th role="columnheader"
                                                                class="fc-col-header-cell fc-day fc-day-sat">
                                                                <div class="fc-scrollgrid-sync-inner"><a
                                                                        aria-label="Saturday"
                                                                        class="fc-col-header-cell-cushion">Sat</a></div>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody role="rowgroup">
                                <tr role="presentation"
                                    class="fc-scrollgrid-section fc-scrollgrid-section-body  fc-scrollgrid-section-liquid">
                                    <td role="presentation">
                                        <div class="fc-scroller-harness fc-scroller-harness-liquid">
                                            <div class="fc-scroller fc-scroller-liquid-absolute"
                                                style="overflow: hidden auto;">
                                                <div class="fc-daygrid-body fc-daygrid-body-unbalanced "
                                                    style="width: 1064px;">
                                                    <table role="presentation" class="fc-scrollgrid-sync-table"
                                                        style="width: 1064px; height: 935px;">
                                                        <colgroup></colgroup>
                                                        <tbody role="presentation">
                                                            <tr role="row">
                                                                <td aria-labelledby="fc-dom-142" role="gridcell"
                                                                    data-date="2025-03-30"
                                                                    class="fc-day fc-day-sun fc-day-past fc-day-other fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="March 30, 2025"
                                                                                id="fc-dom-142"
                                                                                class="fc-daygrid-day-number">30</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-144" role="gridcell"
                                                                    data-date="2025-03-31"
                                                                    class="fc-day fc-day-mon fc-day-past fc-day-other fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="March 31, 2025"
                                                                                id="fc-dom-144"
                                                                                class="fc-daygrid-day-number">31</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-146" role="gridcell"
                                                                    data-date="2025-04-01"
                                                                    class="fc-day fc-day-tue fc-day-past fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 1, 2025"
                                                                                id="fc-dom-146"
                                                                                class="fc-daygrid-day-number">1</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-event-harness"
                                                                                style="margin-top: 0px;"><a tabindex="0"
                                                                                    class="fc-event fc-event-start fc-event-end fc-event-past fc-daygrid-event fc-daygrid-block-event fc-h-event event-fc-color fc-bg-danger">
                                                                                    <div class="fc-event-main">
                                                                                        <div
                                                                                            class="fc-event-main-frame">
                                                                                            <div
                                                                                                class="fc-event-title-container">
                                                                                                <div
                                                                                                    class="fc-event-title fc-sticky">
                                                                                                    Event Conf.</div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </a></div>
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-148" role="gridcell"
                                                                    data-date="2025-04-02"
                                                                    class="fc-day fc-day-wed fc-day-past fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 2, 2025"
                                                                                id="fc-dom-148"
                                                                                class="fc-daygrid-day-number">2</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-150" role="gridcell"
                                                                    data-date="2025-04-03"
                                                                    class="fc-day fc-day-thu fc-day-past fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 3, 2025"
                                                                                id="fc-dom-150"
                                                                                class="fc-daygrid-day-number">3</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-152" role="gridcell"
                                                                    data-date="2025-04-04"
                                                                    class="fc-day fc-day-fri fc-day-past fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 4, 2025"
                                                                                id="fc-dom-152"
                                                                                class="fc-daygrid-day-number">4</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-154" role="gridcell"
                                                                    data-date="2025-04-05"
                                                                    class="fc-day fc-day-sat fc-day-past fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 5, 2025"
                                                                                id="fc-dom-154"
                                                                                class="fc-daygrid-day-number">5</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr role="row">
                                                                <td aria-labelledby="fc-dom-156" role="gridcell"
                                                                    data-date="2025-04-06"
                                                                    class="fc-day fc-day-sun fc-day-past fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 6, 2025"
                                                                                id="fc-dom-156"
                                                                                class="fc-daygrid-day-number">6</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-158" role="gridcell"
                                                                    data-date="2025-04-07"
                                                                    class="fc-day fc-day-mon fc-day-past fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 7, 2025"
                                                                                id="fc-dom-158"
                                                                                class="fc-daygrid-day-number">7</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-event-harness fc-daygrid-event-harness-abs"
                                                                                style="top: 0px; left: 0px; right: -301.05px;">
                                                                                <a tabindex="0"
                                                                                    class="fc-event fc-event-start fc-event-end fc-daygrid-event fc-daygrid-block-event fc-h-event event-fc-color fc-bg-success">
                                                                                    <div class="fc-event-main">
                                                                                        <div
                                                                                            class="fc-event-main-frame">
                                                                                            <div
                                                                                                class="fc-event-title-container">
                                                                                                <div
                                                                                                    class="fc-event-title fc-sticky">
                                                                                                    Seminar #4</div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </a>
                                                                            </div>
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 40px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-160" role="gridcell"
                                                                    data-date="2025-04-08"
                                                                    class="fc-day fc-day-tue fc-day-past fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 8, 2025"
                                                                                id="fc-dom-160"
                                                                                class="fc-daygrid-day-number">8</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 40px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-162" role="gridcell"
                                                                    data-date="2025-04-09"
                                                                    class="fc-day fc-day-wed fc-day-today fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 9, 2025"
                                                                                id="fc-dom-162"
                                                                                class="fc-daygrid-day-number">9</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-event-harness"
                                                                                style="margin-top: 40px;"><a
                                                                                    tabindex="0"
                                                                                    class="fc-event fc-event-start fc-event-end fc-event-today fc-daygrid-event fc-daygrid-dot-event event-fc-color fc-bg-primary">
                                                                                    <div class="fc-daygrid-event-dot">
                                                                                    </div>
                                                                                    <div class="fc-event-time">4p</div>
                                                                                    <div class="fc-event-title">Meeting
                                                                                        #5</div>
                                                                                </a></div>
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-164" role="gridcell"
                                                                    data-date="2025-04-10"
                                                                    class="fc-day fc-day-thu fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 10, 2025"
                                                                                id="fc-dom-164"
                                                                                class="fc-daygrid-day-number">10</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-166" role="gridcell"
                                                                    data-date="2025-04-11"
                                                                    class="fc-day fc-day-fri fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 11, 2025"
                                                                                id="fc-dom-166"
                                                                                class="fc-daygrid-day-number">11</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-event-harness fc-daygrid-event-harness-abs"
                                                                                style="top: 0px; left: 0px; right: -152.45px;">
                                                                                <a tabindex="0"
                                                                                    class="fc-event fc-event-start fc-event-end fc-event-future fc-daygrid-event fc-daygrid-block-event fc-h-event event-fc-color fc-bg-danger">
                                                                                    <div class="fc-event-main">
                                                                                        <div
                                                                                            class="fc-event-main-frame">
                                                                                            <div
                                                                                                class="fc-event-title-container">
                                                                                                <div
                                                                                                    class="fc-event-title fc-sticky">
                                                                                                    Seminar #6</div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </a>
                                                                            </div>
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 40px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-168" role="gridcell"
                                                                    data-date="2025-04-12"
                                                                    class="fc-day fc-day-sat fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 12, 2025"
                                                                                id="fc-dom-168"
                                                                                class="fc-daygrid-day-number">12</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-event-harness"
                                                                                style="margin-top: 40px;"><a
                                                                                    tabindex="0"
                                                                                    class="fc-event fc-event-start fc-event-end fc-event-future fc-daygrid-event fc-daygrid-dot-event event-fc-color fc-bg-success">
                                                                                    <div class="fc-daygrid-event-dot">
                                                                                    </div>
                                                                                    <div class="fc-event-time">10:30a
                                                                                    </div>
                                                                                    <div class="fc-event-title">Meeting
                                                                                        3</div>
                                                                                </a></div>
                                                                            <div class="fc-daygrid-event-harness"
                                                                                style="margin-top: 0px;"><a tabindex="0"
                                                                                    class="fc-event fc-event-start fc-event-end fc-event-future fc-daygrid-event fc-daygrid-dot-event event-fc-color fc-bg-primary">
                                                                                    <div class="fc-daygrid-event-dot">
                                                                                    </div>
                                                                                    <div class="fc-event-time">12p</div>
                                                                                    <div class="fc-event-title">Meetup #
                                                                                    </div>
                                                                                </a></div>
                                                                            <div class="fc-daygrid-event-harness"
                                                                                style="margin-top: 0px;"><a tabindex="0"
                                                                                    class="fc-event fc-event-start fc-event-end fc-event-future fc-daygrid-event fc-daygrid-dot-event event-fc-color fc-bg-warning">
                                                                                    <div class="fc-daygrid-event-dot">
                                                                                    </div>
                                                                                    <div class="fc-event-time">2:30p
                                                                                    </div>
                                                                                    <div class="fc-event-title">
                                                                                        Submission</div>
                                                                                </a></div>
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr role="row">
                                                                <td aria-labelledby="fc-dom-170" role="gridcell"
                                                                    data-date="2025-04-13"
                                                                    class="fc-day fc-day-sun fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 13, 2025"
                                                                                id="fc-dom-170"
                                                                                class="fc-daygrid-day-number">13</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-event-harness"
                                                                                style="margin-top: 0px;"><a tabindex="0"
                                                                                    class="fc-event fc-event-start fc-event-end fc-event-future fc-daygrid-event fc-daygrid-dot-event event-fc-color fc-bg-success">
                                                                                    <div class="fc-daygrid-event-dot">
                                                                                    </div>
                                                                                    <div class="fc-event-time">7a</div>
                                                                                    <div class="fc-event-title">Attend
                                                                                        event</div>
                                                                                </a></div>
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-172" role="gridcell"
                                                                    data-date="2025-04-14"
                                                                    class="fc-day fc-day-mon fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 14, 2025"
                                                                                id="fc-dom-172"
                                                                                class="fc-daygrid-day-number">14</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-174" role="gridcell"
                                                                    data-date="2025-04-15"
                                                                    class="fc-day fc-day-tue fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 15, 2025"
                                                                                id="fc-dom-174"
                                                                                class="fc-daygrid-day-number">15</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-176" role="gridcell"
                                                                    data-date="2025-04-16"
                                                                    class="fc-day fc-day-wed fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 16, 2025"
                                                                                id="fc-dom-176"
                                                                                class="fc-daygrid-day-number">16</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-event-harness"
                                                                                style="margin-top: 0px;"><a tabindex="0"
                                                                                    class="fc-event fc-event-start fc-event-end fc-event-future fc-daygrid-event fc-daygrid-dot-event event-fc-color fc-bg-warning">
                                                                                    <div class="fc-daygrid-event-dot">
                                                                                    </div>
                                                                                    <div class="fc-event-time">4p</div>
                                                                                    <div class="fc-event-title">
                                                                                        Submission #1</div>
                                                                                </a></div>
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-178" role="gridcell"
                                                                    data-date="2025-04-17"
                                                                    class="fc-day fc-day-thu fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 17, 2025"
                                                                                id="fc-dom-178"
                                                                                class="fc-daygrid-day-number">17</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-180" role="gridcell"
                                                                    data-date="2025-04-18"
                                                                    class="fc-day fc-day-fri fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 18, 2025"
                                                                                id="fc-dom-180"
                                                                                class="fc-daygrid-day-number">18</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-182" role="gridcell"
                                                                    data-date="2025-04-19"
                                                                    class="fc-day fc-day-sat fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 19, 2025"
                                                                                id="fc-dom-182"
                                                                                class="fc-daygrid-day-number">19</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr role="row">
                                                                <td aria-labelledby="fc-dom-184" role="gridcell"
                                                                    data-date="2025-04-20"
                                                                    class="fc-day fc-day-sun fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 20, 2025"
                                                                                id="fc-dom-184"
                                                                                class="fc-daygrid-day-number">20</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-186" role="gridcell"
                                                                    data-date="2025-04-21"
                                                                    class="fc-day fc-day-mon fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 21, 2025"
                                                                                id="fc-dom-186"
                                                                                class="fc-daygrid-day-number">21</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-188" role="gridcell"
                                                                    data-date="2025-04-22"
                                                                    class="fc-day fc-day-tue fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 22, 2025"
                                                                                id="fc-dom-188"
                                                                                class="fc-daygrid-day-number">22</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-190" role="gridcell"
                                                                    data-date="2025-04-23"
                                                                    class="fc-day fc-day-wed fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 23, 2025"
                                                                                id="fc-dom-190"
                                                                                class="fc-daygrid-day-number">23</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-192" role="gridcell"
                                                                    data-date="2025-04-24"
                                                                    class="fc-day fc-day-thu fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 24, 2025"
                                                                                id="fc-dom-192"
                                                                                class="fc-daygrid-day-number">24</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-194" role="gridcell"
                                                                    data-date="2025-04-25"
                                                                    class="fc-day fc-day-fri fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 25, 2025"
                                                                                id="fc-dom-194"
                                                                                class="fc-daygrid-day-number">25</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-196" role="gridcell"
                                                                    data-date="2025-04-26"
                                                                    class="fc-day fc-day-sat fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 26, 2025"
                                                                                id="fc-dom-196"
                                                                                class="fc-daygrid-day-number">26</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr role="row">
                                                                <td aria-labelledby="fc-dom-58" role="gridcell"
                                                                    data-date="2025-04-27"
                                                                    class="fc-day fc-day-sun fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 27, 2025"
                                                                                id="fc-dom-58"
                                                                                class="fc-daygrid-day-number">27</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-60" role="gridcell"
                                                                    data-date="2025-04-28"
                                                                    class="fc-day fc-day-mon fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 28, 2025"
                                                                                id="fc-dom-60"
                                                                                class="fc-daygrid-day-number">28</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-event-harness"
                                                                                style="margin-top: 0px;"><a tabindex="0"
                                                                                    class="fc-event fc-event-start fc-event-end fc-event-future fc-daygrid-event fc-daygrid-block-event fc-h-event event-fc-color fc-bg-primary">
                                                                                    <div class="fc-event-main">
                                                                                        <div
                                                                                            class="fc-event-main-frame">
                                                                                            <div
                                                                                                class="fc-event-title-container">
                                                                                                <div
                                                                                                    class="fc-event-title fc-sticky">
                                                                                                    Project submission
                                                                                                    #2</div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </a></div>
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-62" role="gridcell"
                                                                    data-date="2025-04-29"
                                                                    class="fc-day fc-day-tue fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 29, 2025"
                                                                                id="fc-dom-62"
                                                                                class="fc-daygrid-day-number">29</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-64" role="gridcell"
                                                                    data-date="2025-04-30"
                                                                    class="fc-day fc-day-wed fc-day-future fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="April 30, 2025"
                                                                                id="fc-dom-64"
                                                                                class="fc-daygrid-day-number">30</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-66" role="gridcell"
                                                                    data-date="2025-05-01"
                                                                    class="fc-day fc-day-thu fc-day-future fc-day-other fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="May 1, 2025" id="fc-dom-66"
                                                                                class="fc-daygrid-day-number">1</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-68" role="gridcell"
                                                                    data-date="2025-05-02"
                                                                    class="fc-day fc-day-fri fc-day-future fc-day-other fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="May 2, 2025" id="fc-dom-68"
                                                                                class="fc-daygrid-day-number">2</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-70" role="gridcell"
                                                                    data-date="2025-05-03"
                                                                    class="fc-day fc-day-sat fc-day-future fc-day-other fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="May 3, 2025" id="fc-dom-70"
                                                                                class="fc-daygrid-day-number">3</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr role="row">
                                                                <td aria-labelledby="fc-dom-72" role="gridcell"
                                                                    data-date="2025-05-04"
                                                                    class="fc-day fc-day-sun fc-day-future fc-day-other fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="May 4, 2025" id="fc-dom-72"
                                                                                class="fc-daygrid-day-number">4</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-74" role="gridcell"
                                                                    data-date="2025-05-05"
                                                                    class="fc-day fc-day-mon fc-day-future fc-day-other fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="May 5, 2025" id="fc-dom-74"
                                                                                class="fc-daygrid-day-number">5</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-76" role="gridcell"
                                                                    data-date="2025-05-06"
                                                                    class="fc-day fc-day-tue fc-day-future fc-day-other fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="May 6, 2025" id="fc-dom-76"
                                                                                class="fc-daygrid-day-number">6</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-78" role="gridcell"
                                                                    data-date="2025-05-07"
                                                                    class="fc-day fc-day-wed fc-day-future fc-day-other fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="May 7, 2025" id="fc-dom-78"
                                                                                class="fc-daygrid-day-number">7</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-80" role="gridcell"
                                                                    data-date="2025-05-08"
                                                                    class="fc-day fc-day-thu fc-day-future fc-day-other fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="May 8, 2025" id="fc-dom-80"
                                                                                class="fc-daygrid-day-number">8</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-82" role="gridcell"
                                                                    data-date="2025-05-09"
                                                                    class="fc-day fc-day-fri fc-day-future fc-day-other fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="May 9, 2025" id="fc-dom-82"
                                                                                class="fc-daygrid-day-number">9</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                                <td aria-labelledby="fc-dom-84" role="gridcell"
                                                                    data-date="2025-05-10"
                                                                    class="fc-day fc-day-sat fc-day-future fc-day-other fc-daygrid-day">
                                                                    <div
                                                                        class="fc-daygrid-day-frame fc-scrollgrid-sync-inner">
                                                                        <div class="fc-daygrid-day-top"><a
                                                                                aria-label="May 10, 2025" id="fc-dom-84"
                                                                                class="fc-daygrid-day-number">10</a>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-events">
                                                                            <div class="fc-daygrid-day-bottom"
                                                                                style="margin-top: 0px;"></div>
                                                                        </div>
                                                                        <div class="fc-daygrid-day-bg"></div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- BEGIN MODAL -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true"
        style="display: none;">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">
                        {{ $modalTitle ?? __('Add / Edit Calendar Event') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Course name</label>
                                <select name="Course_name" id="Course_name" class="form-control">
                            <option value="">Select Course</option>
                            @foreach($courseMaster as $course)
                                <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                            @endforeach
                        </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Subject Name</label>
                                <select name="subject_name" id="subject_name" class="form-control">
                                        <option value="">Select Subject Name</option>
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->pk }}">{{ $subject->subject_name }}</option>
                                        @endforeach
                                    </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Subject Module</label>
                                <select name="subject_module" id="subject_module" class="form-control">
                                    <option value="">Select subject Module</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Topic</label>
                                <textarea name="topic" id="topic" class="form-control" row="5"></textarea>
                               
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div>
                                <label class="form-label">Group Type</label>
                            </div>
                            <div class="d-flex">
                                <div class="n-chk">
                                    <div class="form-check form-check-primary form-check-inline">
                                        <input class="form-check-input" type="radio" name="event-level" value="Danger"
                                            id="modalDanger-{{ uniqid() }}">
                                        <label class="form-check-label" for="modalDanger-{{ uniqid() }}">Lecture</label>
                                    </div>
                                </div>
                                <div class="n-chk">
                                    <div class="form-check form-check-warning form-check-inline">
                                        <input class="form-check-input" type="radio" name="event-level" value="Success"
                                            id="modalSuccess-{{ uniqid() }}">
                                        <label class="form-check-label"
                                            for="modalSuccess-{{ uniqid() }}">Language</label>
                                    </div>
                                </div>
                                <div class="n-chk">
                                    <div class="form-check form-check-success form-check-inline">
                                        <input class="form-check-input" type="radio" name="event-level" value="Primary"
                                            id="modalPrimary-{{ uniqid() }}">
                                        <label class="form-check-label"
                                            for="modalPrimary-{{ uniqid() }}">Counsellar</label>
                                    </div>
                                </div>
                                <div class="n-chk">
                                    <div class="form-check form-check-danger form-check-inline">
                                        <input class="form-check-input" type="radio" name="event-level" value="Warning"
                                            id="modalWarning-{{ uniqid() }}">
                                        <label class="form-check-label" for="modalWarning-{{ uniqid() }}">Module</label>
                                    </div>
                                </div>
                                <div class="n-chk">
                                    <div class="form-check form-check-danger form-check-inline">
                                        <input class="form-check-input" type="radio" name="event-level" value="Warning"
                                            id="modalWarning-{{ uniqid() }}">
                                        <label class="form-check-label" for="modalWarning-{{ uniqid() }}">Custom
                                            Group</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Faculty</label>
                                <select name="faculty" id="faculty" class="form-control">
                                    <option value="">Select Faculty</option>
                                    @foreach($facultyMaster as $faculty)
                                        <option value="{{ $faculty->pk }}">{{ $faculty->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Faculty Type</label>
                                <select name="faculty_type" id="faculty_type" class="form-control">
                                    <option value="">Select Faculty Type</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <select name="vanue" id="vanue" class="form-control">
                                        <option value="">Select Location</option>
                                        @foreach($venueMaster as $loc)
                                            <option value="{{ $loc->venue_id }}">{{ $loc->venue_name }}</option>
                                        @endforeach
                                    </select>

                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Shift</label>
                                <select name="shift" id="shift" class="form-control">
                                    <option value="">Select Shift</option>
                                    @foreach($classSessionMaster as $shift)
                                        <option value="{{ $shift->pk }}">{{ $shift->shift_name }}</option>
                                    @endforeach
                                </select>

                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
                                        <label class="form-check-label" for="flexCheckDefault">
                                            Full Day
                                        </label>
                                    </div>
                                </label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input type="time" name="start" id="start" class="form-control"
                                                    placeholder="Start Time">

                                            </div>
                                            <div class="col-md-6">
                                                <input type="date" name="start" id="start" class="form-control"
                                                    placeholder="Start Date">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input type="time" name="start" id="start" class="form-control"
                                                    placeholder="Start Time">

                                            </div>
                                            <div class="col-md-6">
                                                <input type="date" name="start" id="start" class="form-control"
                                                    placeholder="Start Date">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div>
                                <label class="form-label">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
                                        <label class="form-check-label" for="flexCheckDefault">
                                            Feedback
                                        </label>
                                    </div>
                                </label>
                                <textarea name="feedback" id="feedback" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Feedback Date</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input type="time" name="start" id="start" class="form-control"
                                                    placeholder="Start Time">

                                            </div>
                                            <div class="col-md-6">
                                                <input type="date" name="start" id="start" class="form-control"
                                                    placeholder="Start Date">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input type="time" name="start" id="start" class="form-control"
                                                    placeholder="Start Time">

                                            </div>
                                            <div class="col-md-6">
                                                <input type="date" name="start" id="start" class="form-control"
                                                    placeholder="Start Date">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Break Type</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <input type="text" name="break" id="break" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input type="time" name="start" id="start" class="form-control"
                                                    placeholder="Start Time">

                                            </div>
                                            <div class="col-md-6">
                                                <input type="date" name="start" id="start" class="form-control"
                                                    placeholder="Start Date">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="button" class="btn btn-success btn-update-event"
                        data-fc-event-public-id="{{ $event->public_id ?? '' }}" style="display: none;">
                        Update changes
                    </button>
                    <button type="button" class="btn btn-primary btn-add-event" id="addEventButton"
                        style="display: none;">
                        Add Calendar Event
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- END MODAL -->
</div>

<script src="{{asset('admin_assets/libs/fullcalendar/index.global.min.js')}}"></script>
<script src="{{asset('admin_assets/js/apps/calendar-init.js')}}"></script>
@endsection