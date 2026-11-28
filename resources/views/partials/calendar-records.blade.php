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
