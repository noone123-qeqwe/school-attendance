@props(['icon', 'title', 'message', 'action' => null])

<div class="empty-card d-flex flex-column align-items-center justify-content-center p-5 text-center empty-card-content">
    <div>
        <div class="icon-circle mb-4">
            <i class="{{ $icon }} empty-icon-wrapper"></i>
        </div>
        <h4 class="mb-3 empty-text">{{ $title }}</h4>
        <p class="empty-link-msg">{{ $message }}</p>
        @if($action)
            {{ $action }}
        @endif
    </div>
</div>
