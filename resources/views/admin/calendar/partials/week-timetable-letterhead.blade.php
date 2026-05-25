{{-- Official "Revised Time Table" sheet header (matches academy PDF style) --}}
<div class="sheet-title-block">
    <div class="sheet-hi">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी</div>
    <div class="sheet-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>
    <div class="sheet-programme">{{ e($courseProgrammeTitle ?? $courseTitle ?? 'Academic timetable') }}</div>
    @if(!empty($coursePeriodParen))
        <div class="sheet-period">{{ e($coursePeriodParen) }}</div>
    @endif
    <div class="sheet-weekline">
        Time Table : Week-{{ (int) ($sheetWeekNumber ?? $weekNum) }}<span class="sheet-revised-tab">&nbsp;&nbsp;&nbsp;Revised</span>
    </div>
</div>
