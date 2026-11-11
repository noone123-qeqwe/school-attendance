@extends('layouts.app')

@section('title', 'Subjects')

@section('content')
@php($isReadOnlyAuditor = auth()->user()->admin_sub_role === 'auditor')
<div class="admin-page-heading">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Subjects & enrollment</h1>
        <p class="saas-text-muted" style="margin:0;">Choose a subject to manage its roster, assigned teacher or schedule.</p>
    </div>

    <div class="admin-page-actions">
        @unless($isReadOnlyAuditor)
        <a href="{{ route('admin.subjects.create') }}" class="saas-btn saas-btn-primary">
            <i class="bi bi-plus-lg"></i> Add Subject
        </a>
        @endunless
    </div>
</div>


<x-card type="section">
    <x-slot:title>Subjects List</x-slot:title>
    <x-slot:headerActions>
        <form class="admin-filter-toolbar" method="GET" action="{{ route('admin.subjects') }}" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <div class="saas-search" style="width:200px;">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="saas-search-input" placeholder="Search code..." value="{{ request('search') }}">
            </div>
            <select name="course" class="saas-input saas-select" style="width:120px; padding:6px 30px 6px 12px;">
                <option value="">Course (All)</option>
                <option value="BSCS" {{ request('course')=='BSCS'?'selected':'' }}>BSCS</option>
                <option value="BSIT" {{ request('course')=='BSIT'?'selected':'' }}>BSIT</option>
                <option value="BSIS" {{ request('course')=='BSIS'?'selected':'' }}>BSIS</option>
            </select>
            <select name="year" class="saas-input saas-select" style="width:110px; padding:6px 30px 6px 12px;">
                <option value="">Year (All)</option>
                @foreach([1,2,3,4,5] as $y)
                    <option value="{{ $y }}" {{ request('year')==$y?'selected':'' }}>Year {{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="ent-btn ent-btn-sm ent-btn-secondary"><i class="bi bi-funnel"></i> Filter</button>
            @if(request()->hasAny(['search','course','year']))
                <a href="{{ route('admin.subjects') }}" class="ent-btn ent-btn-sm ent-btn-ghost text-danger">Clear</a>
            @endif
        </form>
    </x-slot:headerActions>

    <div class="ent-scroll-x" style="margin: -20px;">
        <table class="ent-table" style="min-width: 800px; margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" style="accent-color:var(--ent-primary);"></th>
                    <th>Subject Code & Name</th>
                    <th>Course</th>
                    <th>Year/Sem</th>
                    <th>Instructor</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subjects as $subject)
                <tr>
                    <td><input type="checkbox" style="accent-color:var(--ent-primary);"></td>
                    <td data-label="Subject Code & Name">
                        <div style="font-weight:600; font-family:monospace; color:var(--ent-gold);">{{ $subject->code }}</div>
                        <div style="font-size:0.85rem; font-weight:500;">{{ $subject->name }}</div>
                        @if($subject->description)
                            <div class="ent-text-muted" style="font-size:0.75rem;">{{ Str::limit($subject->description, 40) }}</div>
                        @endif
                    </td>
                    <td data-label="Course">
                        <span class="ent-badge ent-badge-info">{{ $subject->course }}</span>
                    </td>
                    <td data-label="Year/Sem">
                        <span class="ent-badge ent-badge-neutral">Y{{ $subject->year }} - S{{ $subject->semester }}</span>
                    </td>
                    <td data-label="Instructor">
                        @if($subject->instructorUser)
                            <div style="display:flex;align-items:center;gap:6px;">
                                <i class="bi bi-person-workspace ent-text-muted"></i>
                                <span style="font-size:0.85rem;">{{ $subject->instructorUser->name }}</span>
                            </div>
                        @else
                            <span class="ent-text-muted" style="font-style:italic;">Unassigned</span>
                        @endif
                    </td>
                    <td data-label="Actions" style="text-align:right;">
                        <div class="admin-row-actions">
                        <a href="{{ route('admin.enrollments.index', $subject) }}" class="ent-btn ent-btn-xs ent-btn-ghost" style="color:var(--ent-primary);">
                            <i class="bi bi-people" aria-hidden="true"></i> Roster
                        </a>
                        <a href="{{ route('admin.class-schedules.index', ['subject_id' => $subject->id]) }}" class="ent-btn ent-btn-xs ent-btn-ghost" style="color:#D4AF37;" title="Assign / View Schedule">
                            <i class="bi bi-clock-fill" aria-hidden="true"></i> Schedule
                        </a>
                        @unless($isReadOnlyAuditor)
                        <a href="{{ route('admin.subjects.edit', $subject) }}" class="ent-btn ent-btn-xs ent-btn-ghost"><i class="bi bi-pencil" aria-hidden="true"></i> Edit</a>
                        <form action="{{ route('admin.subjects.destroy', $subject->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this subject?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="ent-btn ent-btn-xs ent-btn-ghost" style="color:var(--ent-danger);" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endunless
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-0 border-0">
                        <x-empty-state
                            icon="journal-bookmark"
                            title="No subjects found"
                            message="There are no subjects matching your filters. Get started by adding your first subject."
                        >
                            <x-slot:action>
                                @unless($isReadOnlyAuditor)
                                <a href="{{ route('admin.subjects.create') }}" class="btn btn-primary" style="background: linear-gradient(135deg, #7A1A1A, #9c2727); border: none; border-radius: 12px; padding: 10px 24px; font-weight: 600; font-size: 0.9rem; color: #fff;">
                                    <i class="bi bi-plus-lg me-1"></i> Add Subject
                                </a>
                                @endunless
                            </x-slot:action>
                        </x-empty-state>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($subjects->hasPages())
    <div style="border-top:1px solid var(--ent-border); padding-top:16px; margin-top:16px; display:flex; justify-content:space-between; align-items:center;">
        <div class="ent-text-muted" style="font-size:0.85rem;">
            Showing {{ $subjects->firstItem() ?? 0 }} to {{ $subjects->lastItem() ?? 0 }} of {{ $subjects->total() }} results
        </div>
        <div>
            {{ $subjects->links() }}
        </div>
    </div>
    @endif
</x-card>

@endsection
