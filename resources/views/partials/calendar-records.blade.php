<style>
    #attendance-records {
        scroll-margin-top: calc(var(--header-height, 72px) + env(safe-area-inset-top) + 24px);
    }
    .calendar-record-filters {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 160px), 1fr));
        gap: 12px;
        align-items: end;
        padding: 16px;
    }
    .calendar-record-filters > div { min-width: 0; }
    .calendar-record-filters .ent-input { width: 100%; min-height: 44px; }
    .calendar-record-filter-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .calendar-record-filter-actions .ent-btn { min-height: 44px; }
</style>
<script @cspNonce>
function syncCalendarRecordsMonth(year, month) {
    const values = { year, month };
    document.querySelectorAll('[data-calendar-month]').forEach(input => {
        input.value = values[input.dataset.calendarMonth];
    });
    document.querySelectorAll('[data-calendar-month-link], [data-calendar-month-pagination] a[href]').forEach(link => {
        const url = new URL(link.href);
        url.searchParams.set('year', year);
        url.searchParams.set('month', month);
        link.href = url.href;
    });
}
</script>
