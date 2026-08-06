@extends('layouts.app')

@section('title', 'Announcements')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Announcements</h1>
        <p class="saas-text-muted" style="margin:0;">Broadcast messages to students, parents, and staff.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <button class="saas-btn saas-btn-primary" onclick="openModal('addAnnouncementModal')">
            <i class="bi bi-megaphone"></i> New Announcement
        </button>
    </div>
</div>

<div class="saas-card" style="margin-bottom:24px;">
    <div class="saas-card-header" style="gap:16px; flex-wrap:wrap;">
        <div class="saas-search" style="width:300px;">
            <i class="bi bi-search"></i>
            <input type="text" class="saas-search-input" placeholder="Search announcements...">
        </div>
    </div>
    
    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <thead>
                <tr>
                    <th>Title & Content</th>
                    <th>Target Audience</th>
                    <th>Date Posted</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($announcements as $announcement)
                <tr>
                    <td style="max-width:300px;">
                        <div style="font-weight:600;font-size:0.9rem;">{{ $announcement->title }}</div>
                        <div class="saas-text-muted" style="font-size:0.8rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            {{ strip_tags($announcement->content) }}
                        </div>
                    </td>
                    <td>
                        @if($announcement->target_role == 'all')
                            <span class="saas-badge saas-badge-info">Everyone</span>
                        @elseif($announcement->target_role == 'student')
                            <span class="saas-badge saas-badge-success">Students Only</span>
                        @elseif($announcement->target_role == 'teacher')
                            <span class="saas-badge saas-badge-warning">Instructors Only</span>
                        @else
                            <span class="saas-badge saas-badge-default">{{ ucfirst($announcement->target_role) }}</span>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight:500;">{{ $announcement->created_at->format('M d, Y') }}</div>
                        <div class="saas-text-muted" style="font-size:0.75rem;">{{ $announcement->created_at->format('h:i A') }}</div>
                    </td>
                    <td>
                        @if($announcement->is_active ?? true)
                            <span class="saas-badge saas-badge-success">Published</span>
                        @else
                            <span class="saas-badge saas-badge-default">Draft</span>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        <button class="saas-btn saas-btn-secondary" style="padding:4px 8px;" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="saas-btn saas-btn-secondary" style="padding:4px 8px; color:var(--saas-danger);" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding:48px 20px;">
                        <i class="bi bi-megaphone saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                        <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No announcements</div>
                        <p class="saas-text-muted" style="margin-bottom:20px; max-width:400px; margin-inline:auto;">Keep your users informed by posting an announcement.</p>
                        <button class="saas-btn saas-btn-primary" onclick="openModal('addAnnouncementModal')">
                            <i class="bi bi-megaphone"></i> Create Announcement
                        </button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($announcements->hasPages())
    <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:space-between; align-items:center;">
        <div class="saas-text-muted">
            Showing {{ $announcements->firstItem() ?? 0 }} to {{ $announcements->lastItem() ?? 0 }} of {{ $announcements->total() }} results
        </div>
        <div>
            {{ $announcements->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Add Announcement Modal -->
<div id="addAnnouncementModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:100; align-items:center; justify-content:center; opacity:0; transition:opacity 0.2s;">
    <div class="saas-card" style="width:100%; max-width:600px; transform:scale(0.95); transition:transform 0.2s;" id="addAnnouncementCard">
        <div class="saas-card-header">
            <div class="saas-heading saas-heading-sm">Broadcast Announcement</div>
            <button onclick="closeModal('addAnnouncementModal')" style="background:none; border:none; color:var(--saas-text-muted); cursor:pointer;"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.announcements.store') }}" method="POST">
            @csrf
            <div class="saas-card-body">
                <div class="saas-form-group">
                    <label class="saas-label">Target Audience</label>
                    <select name="target_role" class="saas-input saas-select" required>
                        <option value="all">Everyone</option>
                        <option value="student">Students</option>
                        <option value="teacher">Instructors</option>
                        <option value="parent">Parents</option>
                    </select>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label">Title</label>
                    <input type="text" name="title" class="saas-input" placeholder="Announcement Title" required>
                </div>
                <div class="saas-form-group" style="margin-bottom:0;">
                    <label class="saas-label">Message Content</label>
                    <textarea name="content" class="saas-input" rows="5" placeholder="Type your message here..." required></textarea>
                </div>
            </div>
            <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:flex-end; gap:12px; background:rgba(0,0,0,0.2);">
                <button type="button" class="saas-btn saas-btn-secondary" onclick="closeModal('addAnnouncementModal')">Cancel</button>
                <button type="submit" class="saas-btn saas-btn-primary"><i class="bi bi-send" style="margin-right:6px;"></i> Post Announcement</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openModal(id) {
        const modal = document.getElementById(id);
        const card = modal.querySelector('.saas-card');
        modal.style.display = 'flex';
        void modal.offsetWidth;
        modal.style.opacity = '1';
        card.style.transform = 'scale(1)';
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        const card = modal.querySelector('.saas-card');
        modal.style.opacity = '0';
        card.style.transform = 'scale(0.95)';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 200);
    }
</script>
@endpush
