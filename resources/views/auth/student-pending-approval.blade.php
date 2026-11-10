@extends('layouts.app')

@section('title', 'Student Registration Review')

@section('content')
<div class="container py-5" style="max-width:760px;">
    <section class="p-4 p-md-5 text-center" style="border:1px solid rgba(207,164,111,.3);border-radius:24px;background:rgba(25,15,13,.94);box-shadow:0 18px 48px rgba(0,0,0,.28);">
        <div class="mx-auto mb-4 d-flex align-items-center justify-content-center" style="width:76px;height:76px;border-radius:50%;background:rgba(207,164,111,.14);color:#f3d48d;font-size:2rem;">
            <i class="bi bi-shield-check"></i>
        </div>
        <p class="text-uppercase mb-2" style="color:#cfa46f;font-size:.78rem;font-weight:800;letter-spacing:.14em;">Registration received</p>
        <h1 class="h2 mb-3" style="color:#f3e7cd;font-weight:800;">Your school is reviewing your details</h1>
        <p class="mx-auto mb-4" style="max-width:560px;color:#cbbba7;line-height:1.7;">
            A registrar must confirm your Student ID and class section before student records and attendance features become available. You can sign out and check again later.
        </p>
        <div class="d-flex justify-content-center flex-wrap gap-2">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-light px-4 py-2">Sign out</button>
            </form>
        </div>
    </section>
</div>
@endsection
