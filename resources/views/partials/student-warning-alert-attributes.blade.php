@if($activeWarnings->contains(fn ($warning) => !in_array($warning->type, ['warning_2', 'custom'], true)))
    data-alert-required
@else
    data-alert-banner data-alert-key="student-attendance-warnings"
    data-alert-version="{{ $activeWarnings->sortBy('id')->map(fn ($warning) => $warning->id . ':' . $warning->updated_at?->format('U.u'))->implode(',') }}"
    data-alert-label="Dismiss attendance warning"
@endif
