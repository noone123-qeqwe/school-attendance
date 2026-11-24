<ul class="assistant-report-list list-unstyled d-grid gap-2 mb-0">
    @foreach($attendanceReports as $report)
        @php
            [$reviewState, $reviewLabel, $reviewIcon] = match ($report->review_status) {
                'approved' => ['approved', 'Approved', 'bi-check-circle'],
                'dismissed' => ['dismissed', 'Dismissed', 'bi-dash-circle'],
                default => ['pending', 'Awaiting review', 'bi-clock'],
            };
        @endphp
        <li class="assistant-report-entry">
            <div class="assistant-report-entry-top d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <strong class="text-break">{{ $report->student?->name ?? 'Classmate' }}</strong>
                <span class="assistant-status assistant-status--{{ $reviewState }}">
                    <i class="bi {{ $reviewIcon }}" aria-hidden="true"></i> {{ $reviewLabel }}
                </span>
            </div>
            <div class="assistant-report-meta assistant-muted text-break">
                @if($showReportSession)
                    {{ $report->session?->subject?->name ?? 'Class' }} ·
                @endif
                Reported {{ strtolower($report->suggested_status) }}
                @if($report->created_at)
                    <span aria-hidden="true"> · </span>
                    <time datetime="{{ $report->created_at->toAtomString() }}">
                        {{ $report->created_at->copy()->timezone('Asia/Manila')->format($showReportSession ? 'M j, g:i A' : 'g:i A') }}
                    </time>
                @endif
            </div>
            @if($report->reason)
                <p class="assistant-report-note mb-0 text-break">{{ $report->reason }}</p>
            @endif
        </li>
    @endforeach
</ul>
