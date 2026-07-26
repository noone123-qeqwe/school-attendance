@extends('layouts.pdf')

@section('title', 'Subjects List Report')
@section('report-title', 'Subjects Directory Report')
@section('footer-title', 'Subjects Directory Report')

@section('footer-details')
This document contains {{ $subjects->count() }} {{ $subjects->count() !== 1 ? 'subjects' : 'subject' }}
@endsection

@section('content')
    @include('admin.subjects._report', ['forPdf' => true])
@endsection
