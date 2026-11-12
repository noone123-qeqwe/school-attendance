@php
    $firstDate = \Carbon\Carbon::create($year, $month, 1);
    $today = now()->toDateString();
@endphp
<div class="scal-grid" id="scalGrid" aria-label="Calendar days" data-month="{{ $firstDate->format('Y-m') }}">
    @for($offset = 0; $offset < $firstDate->dayOfWeek; $offset++)
        <div class="scal-tile empty" aria-hidden="true"></div>
    @endfor
    @for($day = 1; $day <= $firstDate->daysInMonth; $day++)
        @php($date = $firstDate->copy()->day($day))
        <button type="button" @class(['scal-tile', 'sunday' => $date->isSunday(), 'today' => $date->toDateString() === $today])
                data-date="{{ $date->toDateString() }}" aria-label="{{ $date->format('F j, Y') }}"
                aria-haspopup="dialog" aria-controls="daySummaryModal">
            <span class="scal-num">{{ $day }}</span>
            <span class="scal-dots-row" aria-hidden="true"></span>
        </button>
    @endfor
</div>
<div class="scal-load-feedback">
    <p id="scalLoadStatus" role="status"></p>
    <button type="button" class="ent-btn ent-btn-ghost" id="scalRetryButton" hidden>Retry loading details</button>
</div>

<style>
    .scal-grid button[data-date] { min-width: 0; padding: 0; }
    .scal-load-feedback:has(#scalLoadStatus:empty) { display: none; }
    .scal-load-feedback { margin-top: 12px; color: #e0d6cc; font-size: .875rem; }
    .scal-load-feedback p { margin: 0; }
    .scal-load-feedback button { margin-top: 8px; }
    .scal-load-feedback button[hidden] { display: none !important; }
</style>
<script @cspNonce>
const calendarGrid = document.getElementById('scalGrid');
const calendarLoadStatus = document.getElementById('scalLoadStatus');
const calendarRetry = document.getElementById('scalRetryButton');
let calendarMonthRequest;

calendarGrid.addEventListener('click', event => {
    const day = event.target.closest('button[data-date]');
    if (day) scalDayClick(day.dataset.date);
});
calendarRetry.addEventListener('click', () => scalLoadAndRender('none'));

function calendarMonthKey(year, month) {
    return year + '-' + String(month).padStart(2, '0');
}

function renderCalendarDates(year, month, monthName) {
    const key = calendarMonthKey(year, month);
    if (calendarGrid.dataset.month === key) return;

    const firstDay = new Date(year, month - 1, 1).getDay();
    const dayCount = new Date(year, month, 0).getDate();
    const today = new Date();
    const todayDate = calendarMonthKey(today.getFullYear(), today.getMonth() + 1) + '-' + String(today.getDate()).padStart(2, '0');
    const tiles = document.createDocumentFragment();

    for (let offset = 0; offset < firstDay; offset++) {
        const spacer = document.createElement('div');
        spacer.className = 'scal-tile empty';
        spacer.setAttribute('aria-hidden', 'true');
        tiles.append(spacer);
    }
    for (let day = 1; day <= dayCount; day++) {
        const date = key + '-' + String(day).padStart(2, '0');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'scal-tile';
        button.classList.toggle('sunday', (firstDay + day - 1) % 7 === 0);
        button.classList.toggle('today', date === todayDate);
        button.dataset.date = date;
        button.setAttribute('aria-label', monthName + ' ' + day + ', ' + year);
        button.setAttribute('aria-haspopup', 'dialog');
        button.setAttribute('aria-controls', 'daySummaryModal');

        const number = document.createElement('span');
        number.className = 'scal-num';
        number.textContent = day;
        const dots = document.createElement('span');
        dots.className = 'scal-dots-row';
        dots.setAttribute('aria-hidden', 'true');
        button.append(number, dots);
        tiles.append(button);
    }
    calendarGrid.replaceChildren(tiles);
    calendarGrid.dataset.month = key;
}

async function loadCalendarMonth(endpoint, year, month, update, childId = null) {
    calendarMonthRequest?.abort();
    const request = new AbortController();
    calendarMonthRequest = request;
    const key = calendarMonthKey(year, month);
    const start = key + '-01';
    const end = key + '-' + new Date(year, month, 0).getDate();
    const url = new URL(endpoint, location.origin);
    url.search = new URLSearchParams({ start, end });
    if (childId !== null) url.searchParams.set('child_id', childId);

    calendarLoadStatus.textContent = 'Loading attendance and events…';
    calendarRetry.hidden = true;
    update('loading', {});
    const timer = setTimeout(() => request.abort(), 12000);
    let events;
    try {
        const response = await fetch(url, {
            signal: request.signal,
            cache: 'no-store',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) throw new Error('Calendar request failed');
        const data = await response.json();
        if (!Array.isArray(data)) throw new Error('Invalid calendar response');

        events = {};
        for (const event of data) {
            const date = typeof event?.start === 'string' ? event.start.split('T')[0] : '';
            if (date.length === 10 && date >= start && date <= end) {
                (events[date] ??= []).push(event);
            }
        }
    } catch (error) {
        if (request !== calendarMonthRequest) return;
        calendarLoadStatus.textContent = 'Attendance and events couldn’t load for this month.';
        calendarRetry.hidden = false;
        update('error', {});
        return;
    } finally {
        clearTimeout(timer);
    }

    if (request !== calendarMonthRequest) return;
    calendarLoadStatus.textContent = '';
    update('ready', events);
}
</script>
