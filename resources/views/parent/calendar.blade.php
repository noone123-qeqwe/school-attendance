@extends('parent.layout')

@section('page-title', 'Attendance Calendar')

@section('content')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="color: #f3e7cd; font-weight: 800; margin: 0;">
            <i class="bi bi-calendar-event" style="color: #cfa46f; margin-right: 8px;"></i>Attendance Calendar
        </h2>
    </div>

    <div class="adm-card">
        <div class="adm-card-body">
            <div id='calendar'></div>
        </div>
    </div>
</div>

<style>
    /* FullCalendar Customizations for Dark Theme */
    .fc-theme-standard .fc-scrollgrid {
        border-color: rgba(255,255,255,0.1);
    }
    .fc-theme-standard td, .fc-theme-standard th {
        border-color: rgba(255,255,255,0.1);
    }
    .fc-col-header-cell {
        background-color: rgba(255,255,255,0.05);
        color: #f3e7cd;
        padding: 8px 0;
    }
    .fc-daygrid-day-number {
        color: #e7dcc8;
    }
    .fc .fc-button-primary {
        background-color: #cfa46f;
        border-color: #cfa46f;
    }
    .fc .fc-button-primary:hover {
        background-color: #b38c5b;
        border-color: #b38c5b;
    }
    .fc .fc-button-primary:disabled {
        background-color: #cfa46f;
        border-color: #cfa46f;
        opacity: 0.5;
    }
    .fc .fc-button-primary:not(:disabled):active, .fc .fc-button-primary:not(:disabled).fc-button-active {
        background-color: #a0784a;
        border-color: #a0784a;
    }
    .fc-event {
        border: none;
        padding: 2px 4px;
        border-radius: 4px;
        color: white !important;
        font-size: 0.8rem;
    }
    .fc-day-today {
        background-color: rgba(207, 164, 111, 0.1) !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            events: '{{ route("parent.calendar.data") }}',
            eventDidMount: function(info) {
                if(info.event.extendedProps.excused) {
                    var el = info.el;
                    el.style.border = '2px dashed #fff';
                }
            }
        });
        calendar.render();
    });
</script>
@endsection
