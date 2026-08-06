@extends('layouts.app')
@section('page-title', 'Compose Message')

@section('content')
<div class="p-4">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
        <h2 style="color: #f3e7cd; font-weight: 800; margin: 0;">
            <i class="bi bi-pencil-square" style="color: #cfa46f; margin-right: 8px;"></i>Compose Message
        </h2>
        <a href="{{ route('messages.index') }}" class="adm-btn adm-btn-ghost">
            <i class="bi bi-arrow-left me-1"></i> Back to Messages
        </a>
    </div>

    <div class="adm-card p-4">
        <form action="{{ route('messages.store') }}" method="POST">
            @csrf
            
            <div class="mb-3">
                <label for="receiver_id" class="form-label" style="color: #cfa46f;">To (Teacher)</label>
                @if($replyTo)
                    <input type="hidden" name="receiver_id" value="{{ $replyTo->id }}">
                    <input type="text" class="adm-input" value="{{ $replyTo->name }}" readonly style="background: rgba(255,255,255,0.05); color: #b39b82;">
                @else
                    <select name="receiver_id" id="receiver_id" class="adm-input" required>
                        <option value="">Select a Teacher</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                @endif
                @error('receiver_id') <span class="text-danger" style="font-size:0.8rem;">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label for="subject" class="form-label" style="color: #cfa46f;">Subject</label>
                <input type="text" name="subject" id="subject" class="adm-input" placeholder="Message Subject" value="{{ old('subject') }}">
                @error('subject') <span class="text-danger" style="font-size:0.8rem;">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label for="body" class="form-label" style="color: #cfa46f;">Message</label>
                <textarea name="body" id="body" rows="6" class="adm-input" required placeholder="Type your message here...">{{ old('body') }}</textarea>
                @error('body') <span class="text-danger" style="font-size:0.8rem;">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="adm-btn adm-btn-primary">
                <i class="bi bi-send me-1"></i> Send Message
            </button>
        </form>
    </div>
</div>
@endsection
