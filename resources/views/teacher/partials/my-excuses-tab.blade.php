<div class="tch-card">
    <div class="tch-card-head" style="flex-wrap: wrap; gap: 12px;">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background: rgba(122,26,26,0.22); color: var(--portal-gold);">
                <i class="bi bi-file-earmark-person-fill" aria-hidden="true"></i>
            </div>
            <div>
                <div>Submission History</div>
                <div style="font-size: 0.78rem; font-weight: 500; color: #a49890;">
                    Submit and track your absence excuses for your classes
                </div>
            </div>
        </div>
        <a href="{{ route('teacher.excuses.create') }}" class="tch-btn tch-btn-primary">
            <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> Submit New Excuse
        </a>
    </div>

    <div>
        @forelse($myExcuseSubmissions as $excuse)
            <div style="padding: 20px 22px; border-bottom: 1px solid rgba(255,255,255,0.06);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 700; color: #faf8f5; margin-bottom: 4px;">
                            {{ $excuse->attendance->subject->name ?? $excuse->attendance->subject_code }}
                        </h3>
                        <div style="font-size: 0.82rem; color: #a49890;">
                            <span style="color: #fca5a5; font-weight: 600;">Absent:</span> {{ \Carbon\Carbon::parse($excuse->attendance->date)->format('M d, Y') }}
                            <span style="margin: 0 6px; opacity: 0.5;">&middot;</span>
                            Submitted {{ $excuse->created_at->diffForHumans() }}
                        </div>
                    </div>

                    @if($excuse->status === 'pending')
                        <span class="status-badge status-pending">Pending</span>
                    @elseif($excuse->status === 'approved')
                        <span class="status-badge status-approved">Approved</span>
                    @else
                        <span class="status-badge status-rejected">Rejected</span>
                    @endif
                </div>

                <div style="background: rgba(0, 0, 0, 0.28); border-radius: 10px; padding: 12px 14px; border: 1px solid rgba(255,255,255,0.06);">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--portal-gold, #d4af37); margin-bottom: 4px;">
                        Reason: {{ $excuse->reason }}
                    </div>
                    <div style="font-size: 0.9rem; color: #d6ccc6; line-height: 1.5;">
                        {{ $excuse->description }}
                    </div>
                </div>

                @if($excuse->attachments)
                    <div style="margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
                        @foreach($excuse->attachments ?? [] as $index => $attachment)
                            <a href="{{ route('excuse-attachments.show', [$excuse->id, $index]) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="tch-btn tch-btn-ghost"
                               style="padding: 6px 12px; font-size: 0.78rem;">
                                <i class="bi bi-paperclip" aria-hidden="true"></i> View Attachment
                            </a>
                        @endforeach
                    </div>
                @endif

                @if($excuse->status === 'rejected' && $excuse->admin_notes)
                    <div style="margin-top: 12px; padding: 10px 12px; background: rgba(239,68,68,0.08); border-left: 3px solid #f87171; border-radius: 0 8px 8px 0;">
                        <div style="font-size: 0.75rem; font-weight: 700; color: #fca5a5; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">
                            Reviewer Note
                        </div>
                        <p style="font-size: 0.84rem; color: #faf8f5; margin: 0;">{{ $excuse->admin_notes }}</p>
                    </div>
                @endif
            </div>
        @empty
            <div style="padding: 48px 24px; text-align: center;">
                <x-empty-state
                    icon="bi-inbox"
                    title="No Submissions Yet"
                    subtitle="You haven't submitted any excuses."
                />
            </div>
        @endforelse
    </div>
</div>
