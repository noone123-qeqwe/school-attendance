@extends('layouts.app')

@section('portal-title', 'Admin Panel')
@section('profile-role', 'Admin')
@section('profile-detail', Auth::user()->email)

@section('role-sidebar')
    @include('layouts.sidebars.admin')
@endsection

@section('scripts')
    @include('partials.scripts.admin')
@endsection
