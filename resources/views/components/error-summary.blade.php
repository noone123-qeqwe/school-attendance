@props([
    'title' => 'Please review the highlighted fields',
    'bag' => 'default',
    'id' => 'formErrorSummary',
])

@php
    $errorBag = $errors->getBag($bag);
@endphp

@if($errorBag->any())
<div id="{{ $id }}" class="ds-error-summary" role="alert" tabindex="-1" aria-labelledby="{{ $id }}Title">
    <h2 id="{{ $id }}Title" class="ds-error-summary-title">
        <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
        <span>{{ $title }} ({{ $errorBag->count() }})</span>
    </h2>
    <ul class="ds-error-summary-list">
        @foreach($errorBag->keys() as $field)
            <li>
                <a href="#{{ $field }}">{{ $errorBag->first($field) }}</a>
            </li>
        @endforeach
    </ul>
</div>
<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    var summary = document.getElementById(@json($id));
    if (summary && typeof summary.focus === 'function') {
        summary.focus({ preventScroll: false });
    }
});
</script>
@endif
