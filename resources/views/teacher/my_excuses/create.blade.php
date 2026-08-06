@extends('layouts.app')
@section('page-title', 'Submit Excuse')

@section('content')
<div class="ent-section ent-fade-up" style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <a href="{{ route('teacher.excuses') }}" style="color: #b39b82; text-decoration: none; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 4px; transition: color 0.2s;" onmouseover="this.style.color='#f3e7cd'" onmouseout="this.style.color='#b39b82'">
            <i class="bi bi-arrow-left"></i> Back to My Excuses
        </a>
    </div>

    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.2);">
        <div style="padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(0,0,0,0.2);">
            <h1 style="font-size: 1.5rem; font-weight: 800; color: #f3e7cd; margin-bottom: 4px;">Submit New Excuse</h1>
            <p style="color: #b39b82; font-size: 0.9rem; margin: 0;">Provide details about your absence for a specific class.</p>
        </div>

        <div style="padding: 24px;">
            <form action="{{ route('teacher.excuses.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label style="color: #d6b67b; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Select Subject</label>
                    <select name="subject_code" class="form-select @error('subject_code') is-invalid @enderror" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(207,164,111,0.2); color: #f3e7cd; border-radius: 8px; padding: 12px; appearance: auto;" required>
                        <option value="">-- Choose Subject --</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->code }}" {{ old('subject_code') == $subject->code ? 'selected' : '' }}>
                                {{ $subject->name }} ({{ $subject->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('subject_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label style="color: #d6b67b; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Date of Absence</label>
                    <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', date('Y-m-d')) }}" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(207,164,111,0.2); color: #f3e7cd; border-radius: 8px; padding: 12px;" required max="{{ date('Y-m-d') }}">
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label style="color: #d6b67b; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Reason</label>
                    <select name="reason" class="form-select @error('reason') is-invalid @enderror" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(207,164,111,0.2); color: #f3e7cd; border-radius: 8px; padding: 12px; appearance: auto;" required>
                        <option value="">-- Select Reason --</option>
                        <option value="Medical / Illness" {{ old('reason') == 'Medical / Illness' ? 'selected' : '' }}>Medical / Illness</option>
                        <option value="Emergency" {{ old('reason') == 'Emergency' ? 'selected' : '' }}>Emergency</option>
                        <option value="Official Business" {{ old('reason') == 'Official Business' ? 'selected' : '' }}>Official Business</option>
                        <option value="Other" {{ old('reason') == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label style="color: #d6b67b; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Detailed Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="Provide more context about your absence..." style="background: rgba(0,0,0,0.2); border: 1px solid rgba(207,164,111,0.2); color: #f3e7cd; border-radius: 8px; padding: 12px; resize: vertical;" required>{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label style="color: #d6b67b; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Attachments (Optional)</label>
                    <input type="file" name="attachments[]" multiple class="form-control" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(207,164,111,0.2); color: #f3e7cd; border-radius: 8px; padding: 12px;">
                    <div class="form-text" style="color: #b39b82; font-size: 0.8rem; margin-top: 6px;">You can upload multiple files (JPG, PNG, PDF, DOCX) up to 5MB each.</div>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <a href="{{ route('teacher.excuses') }}" class="btn" style="background: rgba(255,255,255,0.05); color: #f3e7cd; border: 1px solid rgba(255,255,255,0.1); padding: 10px 24px; border-radius: 8px; font-weight: 600;">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, var(--gold), #b88a44); color: #1a1a2e; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700;">
                        Submit Excuse
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
