@extends('layouts.app')

@section('title', 'Backup & Restore')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Backup & Restore</h1>
        <p class="saas-text-muted" style="margin:0;">Manage database backups and restore points.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <form action="{{ route('backups.create') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="saas-btn saas-btn-primary" onclick="this.disabled=true; this.form.submit();">
                <i class="bi bi-cloud-arrow-up"></i> Run Manual Backup
            </button>
        </form>
    </div>
</div>

<div class="saas-card">
    <div class="saas-card-header" style="gap:16px; flex-wrap:wrap;">
        <div class="saas-search" style="width:250px;">
            <i class="bi bi-search"></i>
            <input type="text" class="saas-search-input" placeholder="Search backups...">
        </div>
    </div>
    
    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <thead>
                <tr>
                    <th>Backup Date</th>
                    <th>File Name</th>
                    <th>Size</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups ?? [] as $backup)
                <tr>
                    <td>
                        <div style="font-weight:500;">{{ $backup->created_at->format('M d, Y') }}</div>
                        <div class="saas-text-muted" style="font-size:0.75rem;">{{ $backup->created_at->format('H:i A') }}</div>
                    </td>
                    <td>
                        <div style="font-family:monospace; color:var(--saas-gold); font-size:0.85rem;">{{ $backup->filename }}</div>
                    </td>
                    <td><span class="saas-text-muted">{{ number_format($backup->size / 1024 / 1024, 2) }} MB</span></td>
                    <td>
                        @if($backup->status === 'completed')
                            <span class="saas-badge saas-badge-success">Completed</span>
                        @elseif($backup->status === 'failed')
                            <span class="saas-badge saas-badge-danger">Failed</span>
                        @else
                            <span class="saas-badge saas-badge-warning">Processing</span>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        <a href="{{ route('backups.download', $backup) }}" class="saas-btn saas-btn-secondary" style="padding:4px 8px;" title="Download">
                            <i class="bi bi-download"></i>
                        </a>
                        <form action="{{ route('backups.destroy', $backup) }}" method="POST" style="display:inline-block; margin:0;" onsubmit="return confirm('Are you sure you want to delete this backup?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="saas-btn saas-btn-secondary" style="padding:4px 8px; color:var(--saas-danger);" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding:48px 20px;">
                        <i class="bi bi-hdd-rack saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                        <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No backups found</div>
                        <p class="saas-text-muted" style="margin-bottom:20px; max-width:400px; margin-inline:auto;">It's highly recommended to run a manual backup to secure your data.</p>
                        <form action="{{ route('backups.create') }}" method="POST" style="margin:0; display:inline-block;">
                            @csrf
                            <button type="submit" class="saas-btn saas-btn-primary" onclick="this.disabled=true; this.form.submit();">
                                <i class="bi bi-cloud-arrow-up"></i> Run First Backup
                            </button>
                        </form>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if(isset($backups) && $backups->hasPages())
    <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:space-between; align-items:center;">
        <div class="saas-text-muted">
            Showing {{ $backups->firstItem() ?? 0 }} to {{ $backups->lastItem() ?? 0 }} of {{ $backups->total() }} results
        </div>
        <div>
            {{ $backups->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
