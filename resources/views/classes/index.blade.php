@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold text-dark">My Class Schedule</h4>
            <p class="text-muted">Year Level: <span class="badge bg-danger">{{ Auth::user()->year_level }}</span></p>
        <p class="text-muted">
    Semester:
<span class="badge bg-primary">
    {{ Auth::user()->semester ?? 'Not Set' }}
</span>
</p>
        
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-secondary small fw-bold">
                            <th class="ps-4 py-3">SECTION</th>
                            <th class="py-3">SUBJECT CODE</th>
                            <th class="py-3">DESCRIPTIVE TITLE</th>
                            <th class="py-3 text-center">UNITS</th>
                            <th class="py-3">INSTRUCTOR</th>
                            <th class="py-3">DAYS</th>
                            <th class="py-3">TIME</th>
                        </tr>
                    </thead>
                    <tbody>
@forelse($subjects as $subject)
<tr>

    <td class="ps-4 fw-bold text-maroon">
        {{ $subject->section ?? 'N/A' }}
    </td>

    <td>
        <span class="badge bg-secondary-subtle text-dark border">
            {{ $subject->code }}
        </span>
    </td>

    <td>{{ $subject->name }}</td>

    <td class="text-center">
        {{ $subject->units ?? 3 }}
    </td>

    <td>
        {{ $subject->instructorUser->name ?? 'TBA' }}
    </td>

    <td>
        {{ $subject->days ?? 'TBA' }}
    </td>

    <td>
        @if($subject->start_time && $subject->end_time)
            {{ date('h:i A', strtotime($subject->start_time)) }}
            -
            {{ date('h:i A', strtotime($subject->end_time)) }}
        @else
            TBA
        @endif
    </td>

</tr>
@empty
<tr>
    <td colspan="7" class="text-center py-5 text-muted">
        No subjects found 😢
    </td>
</tr>
@endforelse
</tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection