@extends('layouts.pdf')

@section('title', 'My Students List Report')
@section('report-title', 'My Students Directory')
@section('footer-title', 'My Students Report')

@section('footer-details')
This document contains {{ $students->count() }} {{ $students->count() !== 1 ? 'students' : 'student' }} from {{ $teacherSubjects->count() }} {{ $teacherSubjects->count() !== 1 ? 'subjects' : 'subject' }} taught by {{ $teacher->name }}
@endsection

@section('additional-styles')
.teacher-info {
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    border: 1px solid #bbf7d0;
    border-left: 4px solid #059669;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(5, 150, 105, 0.1);
}

.teacher-info-title {
    font-weight: 700;
    color: #059669;
    font-size: 13px;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.teacher-detail {
    margin: 6px 0;
    font-size: 11px;
    color: #374151;
}

.teacher-detail strong {
    color: #059669;
    font-weight: 600;
}

.subjects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 12px;
}

.subject-item {
    background: white;
    border: 1px solid #e5e7eb;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 9px;
    color: #374151;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.subject-code {
    font-weight: 700;
    color: #059669;
    font-family: 'Courier New', monospace;
}

.progress-bar {
    width: 50px;
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
    display: inline-block;
    vertical-align: middle;
    margin-right: 8px;
}

.progress-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.rate-excellent { background: linear-gradient(90deg, #10b981, #059669); }
.rate-good { background: linear-gradient(90deg, #f59e0b, #d97706); }
.rate-poor { background: linear-gradient(90deg, #ef4444, #dc2626); }
@endsection

@section('content')
    @include('teacher.students._report', ['forPdf' => true])
@endsection