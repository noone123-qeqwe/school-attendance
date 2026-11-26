@extends('layouts.app')

@section('title', 'Student Registration Review')

@section('content')
<div class="container py-4 py-md-5" style="max-width:680px;">
    <section class="p-4 p-md-5 text-center" style="border:1px solid rgba(212,175,55,0.2);border-top:2px solid #7A1A1A;border-radius:14px;background:#1E1515;">
        <div class="mx-auto mb-4 d-flex align-items-center justify-content-center" style="width:64px;height:64px;border-radius:14px;background:#261B1B;border:1px solid rgba(212,175,55,0.3);color:#D4AF37;font-size:1.75rem;">
            <i class="bi bi-shield-check" aria-hidden="true"></i>
        </div>
        <p class="text-uppercase mb-2" style="color:#D4AF37;font-size:.74rem;font-weight:700;letter-spacing:.12em;">Registration received</p>
        <h1 class="h3 mb-3" style="color:#FAF8F5;font-weight:700;letter-spacing:-0.02em;">Your school is reviewing your details</h1>
        <p class="mx-auto mb-4" style="max-width:520px;color:#D6CCC6;font-size:.9rem;line-height:1.65;">
            A registrar must verify your Student ID before you can access student records and attendance. Your class schedule will appear after the school assigns your section. You can sign out and check again later.
        </p>
        <div class="d-flex justify-content-center flex-wrap gap-2">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn px-4" style="min-height:44px;background:#261B1B;border:1px solid rgba(255,255,255,0.18);color:#FAF8F5;border-radius:10px;font-weight:600;">Sign out</button>
            </form>
        </div>
    </section>
</div>
@endsection
