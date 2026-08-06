@extends('teacher.layout')
@section('page-title', 'Compose Message')

@section('content')
<div class="p-4">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
        <h2 style="color: #f3e7cd; font-weight: 800; margin: 0;">
            <i class="bi bi-pencil-square" style="color: #cfa46f; margin-right: 8px;"></i>Compose Message
        </h2>
        <a href="{{ route('messages.index') }}" class="tch-btn" style="background: rgba(255,255,255,0.05); color: #b39b82; border-color: rgba(255,215,145,0.2);">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="tch-card p-4">
        <form action="{{ route('messages.store') }}" method="POST">
            @csrf
            
            <div class="mb-3">
                <label for="receiver_id" class="form-label" style="color: #cfa46f;">To</label>
                @if($replyTo)
                    <input type="hidden" name="receiver_id" value="{{ $replyTo->id }}">
                    <input type="text" class="form-control" value="{{ $replyTo->name }}" readonly style="background: rgba(0,0,0,0.2); color: #b39b82; border: 1px solid rgba(255,215,145,0.2);">
                @else
                    <select name="receiver_id" id="receiver_id" class="form-control" required style="background: rgba(0,0,0,0.2); color: #e7dcc8; border: 1px solid rgba(255,215,145,0.2);">
                        <option value="">Select a Parent</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                @endif
                @error('receiver_id') <span class="text-danger" style="font-size:0.8rem;">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label for="subject" class="form-label" style="color: #cfa46f;">Subject</label>
                <input type="text" name="subject" id="subject" class="form-control" placeholder="Message Subject" value="{{ old('subject') }}" style="background: rgba(0,0,0,0.2); color: #e7dcc8; border: 1px solid rgba(255,215,145,0.2);">
                @error('subject') <span class="text-danger" style="font-size:0.8rem;">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label for="body" class="form-label" style="color: #cfa46f;">Message</label>
                <textarea name="body" id="body" rows="6" class="form-control" required placeholder="Type your message here..." style="background: rgba(0,0,0,0.2); color: #e7dcc8; border: 1px solid rgba(255,215,145,0.2);">{{ old('body') }}</textarea>
                @error('body') <span class="text-danger" style="font-size:0.8rem;">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="tch-btn">
                <i class="bi bi-send me-1"></i> Send Message
            </button>
        </form>
    </div>
</div>
@endsection
