@extends('parent.layout')
@section('page-title', $child->name . ' — Warnings')

@section('content')
<div class="p-4">
    {{-- Back Link --}}
    <a href="{{ route('parent.dashboard') }}" class="student-back-link">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>

    {{-- Header --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <img src="{{ $child->profile_image ? (str_starts_with($child->profile_image, 'http') ? $child->profile_image : asset('storage/'.$child->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($child->name).'&background=800000&color=fff' }}"
             alt="{{ $child->name }}" class="rounded-circle"
             style="width: 52px; height: 52px; object-fit: cover; border: 2px solid rgba(207,164,111,0.4);">
        <div>
            <h3 style="color: #f3e7cd; font-weight: 800; margin: 0;">{{ $child->name }}</h3>
            <small style="color: #b39b82;">Warnings & Notices</small>
        </div>
    </div>

    {{-- Warnings List --}}
    <div class="adm-card">
        <div class="adm-card-head">
            <div class="adm-card-title">
                <div class="adm-card-icon" style="background: rgba(239,83,80,0.15); color: #ef5350;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                All Warnings
            </div>
            <span style="color: #b39b82; font-size: 0.82rem;">{{ $warnings->count() }} total</span>
        </div>
        <div class="adm-card-body" style="padding: 16px 20px;">
            @if($warnings->count() > 0)
                @foreach($warnings as $warning)
                <div style="background: rgba(239,83,80,0.06); border: 1px solid rgba(239,83,80,0.12); border-radius: 14px; padding: 18px; margin-bottom: 14px; transition: all 0.2s;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                @if($warning->type === 'warning_consecutive_3')
                                    <span style="background: rgba(239,83,80,0.2); color: #ef5350; padding: 3px 10px; border-radius: 999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">
                                        <i class="bi bi-exclamation-octagon-fill me-1"></i> OSAS Required
                                    </span>
                                @elseif($warning->type === 'warning')
                                    <span style="background: rgba(255,167,38,0.15); color: #ffa726; padding: 3px 10px; border-radius: 999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Warning
                                    </span>
                                @else
                                    <span style="background: rgba(66,165,245,0.15); color: #42a5f5; padding: 3px 10px; border-radius: 999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">
                                        <i class="bi bi-info-circle-fill me-1"></i> Notice
                                    </span>
                                @endif
                                <span style="color: #cfa46f; font-weight: 600; font-size: 0.85rem;">
                                    {{ $warning->subject->name ?? $warning->subject_code }}
                                </span>
                            </div>
                            <p style="color: #e7dcc8; font-size: 0.88rem; margin: 0 0 8px; line-height: 1.5;">
                                {{ $warning->message }}
                            </p>
                            <div style="display: flex; gap: 16px; color: #8f826f; font-size: 0.72rem;">
                                <span><i class="bi bi-calendar3 me-1"></i>{{ $warning->created_at->format('M d, Y — h:i A') }}</span>
                                @if($warning->sender)
                                    <span><i class="bi bi-person me-1"></i>{{ $warning->sender->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            @else
                <div class="empty-state" style="padding: 40px;">
                    <i class="bi bi-shield-check" style="font-size: 2.5rem; color: #66bb6a;"></i>
                    <span style="font-size: 0.95rem; color: #f3e7cd;">No warnings on record</span>
                    <small style="color: #8f826f;">Your child has no warnings. Keep up the good work!</small>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
