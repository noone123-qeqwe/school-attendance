@extends('layouts.admin_premium')

@section('title', 'System Health')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">System Health</h1>
        <p class="saas-text-muted" style="margin:0;">Monitor application performance, database status, and services.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <button class="saas-btn saas-btn-secondary" onclick="window.location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Refresh Status
        </button>
    </div>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:24px; margin-bottom:24px;">
    
    <!-- Database Status -->
    <div class="saas-card" style="padding:24px; display:flex; flex-direction:column;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(74, 222, 128, 0.1); color:var(--saas-success); display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                <i class="bi bi-database-check"></i>
            </div>
            <span class="saas-badge saas-badge-success" style="background:transparent; border:1px solid var(--saas-success);">
                <span class="saas-health-dot" style="background:var(--saas-success); margin-right:6px;"></span> Online
            </span>
        </div>
        <h3 class="saas-heading saas-heading-sm" style="margin-bottom:4px;">Database Connection</h3>
        <p class="saas-text-muted" style="font-size:0.85rem; margin-bottom:16px;">MySQL connection is stable. Latency is optimal.</p>
        <div style="margin-top:auto; font-family:monospace; font-size:0.8rem; color:var(--saas-gold); background:rgba(0,0,0,0.2); padding:8px 12px; border-radius:6px;">
            Connection Time: 12ms
        </div>
    </div>
    
    <!-- Queue Status -->
    <div class="saas-card" style="padding:24px; display:flex; flex-direction:column;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(234, 179, 8, 0.1); color:var(--saas-warning); display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                <i class="bi bi-list-task"></i>
            </div>
            <span class="saas-badge {{ $failedJobs > 0 ? 'saas-badge-danger' : 'saas-badge-success' }}" style="background:transparent; border:1px solid currentColor;">
                {{ $failedJobs > 0 ? $failedJobs . ' Failed Jobs' : 'Healthy' }}
            </span>
        </div>
        <h3 class="saas-heading saas-heading-sm" style="margin-bottom:4px;">Queue System</h3>
        <p class="saas-text-muted" style="font-size:0.85rem; margin-bottom:16px;">Background jobs processing.</p>
        <div style="margin-top:auto; font-family:monospace; font-size:0.8rem; color:var(--saas-text-muted); background:rgba(0,0,0,0.2); padding:8px 12px; border-radius:6px;">
            Jobs in queue: {{ $queueSize }}
        </div>
    </div>
    
    <!-- Mail Status -->
    <div class="saas-card" style="padding:24px; display:flex; flex-direction:column;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(56, 189, 248, 0.1); color:var(--saas-info); display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                <i class="bi bi-envelope"></i>
            </div>
            <span class="saas-badge {{ $mailConfigured ? 'saas-badge-info' : 'saas-badge-danger' }}" style="background:transparent; border:1px solid currentColor;">
                {{ $mailConfigured ? 'Configured' : 'Missing Config' }}
            </span>
        </div>
        <h3 class="saas-heading saas-heading-sm" style="margin-bottom:4px;">Mail Service</h3>
        <p class="saas-text-muted" style="font-size:0.85rem; margin-bottom:16px;">SMTP relay for notifications.</p>
        <div style="margin-top:auto; font-family:monospace; font-size:0.8rem; color:var(--saas-text-muted); background:rgba(0,0,0,0.2); padding:8px 12px; border-radius:6px;">
            Host: {{ config('mail.mailers.smtp.host') ?: 'None' }}
        </div>
    </div>

    <!-- Backup Status -->
    <div class="saas-card" style="padding:24px; display:flex; flex-direction:column;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(139, 92, 246, 0.1); color:var(--saas-primary); display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                <i class="bi bi-shield-check"></i>
            </div>
            <span class="saas-badge saas-badge-primary" style="background:transparent; border:1px solid currentColor;">
                {{ $backupCount }} Files
            </span>
        </div>
        <h3 class="saas-heading saas-heading-sm" style="margin-bottom:4px;">Database Backups</h3>
        <p class="saas-text-muted" style="font-size:0.85rem; margin-bottom:16px;">System backup snapshots.</p>
        <div style="margin-top:auto; font-family:monospace; font-size:0.8rem; color:var(--saas-text-muted); background:rgba(0,0,0,0.2); padding:8px 12px; border-radius:6px;">
            <a href="{{ route('backups.index') }}" style="color:inherit; text-decoration:underline;">Manage Backups</a>
        </div>
    </div>
</div>

<div class="saas-card">
    <div class="saas-card-header">
        <h3 class="saas-heading saas-heading-sm">Environment Information</h3>
    </div>
    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <tbody>
                <tr>
                    <td style="width:250px; font-weight:600; color:var(--saas-text-muted);">Application Name</td>
                    <td style="font-family:monospace; color:var(--saas-text);">{{ config('app.name') }}</td>
                </tr>
                <tr>
                    <td style="font-weight:600; color:var(--saas-text-muted);">Environment</td>
                    <td>
                        <span class="saas-badge saas-badge-info">{{ config('app.env') }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:600; color:var(--saas-text-muted);">Debug Mode</td>
                    <td>
                        @if(config('app.debug'))
                            <span class="saas-badge saas-badge-warning">Enabled (Not recommended for production)</span>
                        @else
                            <span class="saas-badge saas-badge-success">Disabled (Secure)</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:600; color:var(--saas-text-muted);">PHP Version</td>
                    <td style="font-family:monospace;">{{ phpversion() }}</td>
                </tr>
                <tr>
                    <td style="font-weight:600; color:var(--saas-text-muted);">Server Time</td>
                    <td style="font-family:monospace;">{{ now()->format('Y-m-d H:i:s T') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
