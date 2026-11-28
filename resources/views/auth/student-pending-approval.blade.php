@extends('layouts.app')

@section('title', 'Student Registration Review')

@section('content')
<div style="max-width:560px;margin:40px auto;">
    <section class="sf-card" style="padding:32px 26px;text-align:center;">
        <div class="sf-bio-icon is-rose" style="width:52px;height:52px;margin:0 auto 16px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
        </div>
        <h1 style="margin:0 0 10px;font-size:20px;font-weight:700;color:var(--sf-text);letter-spacing:-0.02em;">
            Your school is reviewing your details
        </h1>
        <p style="margin:0 auto 22px;max-width:440px;font-size:13px;color:var(--sf-text-secondary);line-height:1.6;">
            A registrar must verify your Student ID before you can access student records and attendance. Your class schedule will appear after the school assigns your section. You can sign out and check again later.
        </p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sf-btn sf-btn-secondary">Sign out</button>
        </form>
    </section>
</div>
@endsection
