@extends('layouts.app')

@section('title', 'Database Backups & Restore Hub')

@section('content')
<div class="backups-hub-wrapper">

    <!-- Backups Executive Hero -->
    <div class="backups-hero-card">
        <div class="hero-backdrop-pattern"></div>
        <div class="hero-left">
            <div class="hero-badge-pill">
                <i class="bi bi-shield-lock-fill text-gold me-1"></i>
                <span>Database Protection Engine</span>
                <span class="hero-badge-divider">•</span>
                <span>{{ $backups->total() }} Total Snapshots</span>
            </div>
            <h1 class="backups-hero-title">
                <span class="hero-icon-box">
                    <i class="bi bi-shield-check"></i>
                </span>
                <span class="hero-title-text">Database Backups & Disaster Recovery Hub</span>
            </h1>
            <p class="backups-hero-desc">
                Capture instant point-in-time database snapshots, download offline SQL archives, and restore historical states in 1 click.
            </p>
        </div>
        
        <div class="hero-actions">
            <button type="button" class="saas-btn saas-btn-secondary hero-btn-secondary" onclick="document.getElementById('uploadRestoreModal').style.display='flex'">
                <i class="bi bi-upload text-gold me-1"></i>
                <span>Upload & Restore .SQL</span>
            </button>
            <form action="{{ route('admin.backups.create') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="hero-btn-primary" onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span> Generating Backup...'; this.form.submit();">
                    <span class="btn-glow-layer"></span>
                    <span class="btn-shimmer-sweep"></span>
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i>
                    <span>1-Click New Backup</span>
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="hub-alert hub-alert-success">
        <div class="hub-alert-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="hub-alert-content">
            <div class="hub-alert-title">Operation Successful</div>
            <div class="hub-alert-text">{{ session('success') }}</div>
        </div>
        <button type="button" class="hub-alert-close" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
    @endif

    @if(session('error'))
    <div class="hub-alert hub-alert-danger">
        <div class="hub-alert-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="hub-alert-content">
            <div class="hub-alert-title">Attention Required</div>
            <div class="hub-alert-text">{{ session('error') }}</div>
        </div>
        <button type="button" class="hub-alert-close" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
    @endif

    <!-- Quick Telemetry Strip -->
    <div class="telemetry-grid">
        <div class="telemetry-card card-glow-gold">
            <div class="telemetry-header">
                <span class="telemetry-label">Available Snapshots</span>
                <div class="telemetry-icon-box icon-version"><i class="bi bi-archive-fill"></i></div>
            </div>
            <div class="telemetry-value-lg gold-gradient-text">{{ $backups->total() }}</div>
            <div class="telemetry-footer"><span class="telemetry-sub">Stored in local protected storage vault</span></div>
        </div>

        <div class="telemetry-card card-glow-emerald">
            <div class="telemetry-header">
                <span class="telemetry-label">Latest Snapshot</span>
                <div class="telemetry-icon-box icon-state icon-state-green"><i class="bi bi-clock-history"></i></div>
            </div>
            <div class="telemetry-value-md text-emerald">
                {{ $backups->first() ? $backups->first()->created_at->diffForHumans() : 'None Recorded' }}
            </div>
            <div class="telemetry-footer"><span class="telemetry-sub font-mono">{{ $backups->first() ? $backups->first()->created_at->format('M d, Y h:i A') : '-' }}</span></div>
        </div>

        <div class="telemetry-card card-glow-blue">
            <div class="telemetry-header">
                <span class="telemetry-label">Storage Vault Path</span>
                <div class="telemetry-icon-box icon-db"><i class="bi bi-folder2-open"></i></div>
            </div>
            <div class="telemetry-value-md font-mono" style="font-size:0.86rem; color:#cbd5e1; word-break:break-all;">
                storage/app/backups/
            </div>
            <div class="telemetry-footer"><span class="telemetry-sub">Permissions: <strong class="text-emerald">Writable (0755)</strong></span></div>
        </div>
    </div>

    <!-- Main Snapshots Table Card -->
    <div class="history-table-card">
        <div class="table-card-header">
            <div class="table-header-left">
                <div class="table-icon-wrap"><i class="bi bi-database-check"></i></div>
                <div>
                    <h3 class="table-card-title">Stored Database Snapshots Repository</h3>
                    <p class="table-card-subtitle">Manage historical backups, verify archive integrity, and execute point-in-time database restores.</p>
                </div>
            </div>
            <div class="table-header-right">
                <span class="vault-counter-pill">
                    <i class="bi bi-layers-fill text-gold me-1"></i> {{ $backups->total() }} Snapshots Stored
                </span>
            </div>
        </div>
        
        <div class="saas-table-container">
            <table class="saas-table modern-update-table">
                <thead>
                    <tr>
                        <th style="min-width: 170px;">Backup Date</th>
                        <th>Archive Filename</th>
                        <th style="width: 130px; text-align: center;">Size</th>
                        <th style="width: 150px; text-align: center;">Category</th>
                        <th style="width: 140px; text-align: center;">Integrity</th>
                        <th style="min-width: 240px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups ?? [] as $backup)
                    <tr>
                        <td>
                            <div class="time-main font-mono">{{ $backup->created_at->format('M d, Y') }}</div>
                            <div class="time-relative font-mono">{{ $backup->created_at->format('h:i A') }} • {{ $backup->created_at->diffForHumans(null, true) }} ago</div>
                        </td>
                        <td>
                            <div class="filename-row">
                                <div class="file-type-icon"><i class="bi bi-filetype-sql"></i></div>
                                <span class="filename-text font-mono" title="{{ $backup->filename }}">{{ $backup->filename }}</span>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <span class="vault-size-badge">{{ number_format($backup->size / 1024 / 1024, 2) }} MB</span>
                        </td>
                        <td style="text-align: center;">
                            @if(str_contains($backup->filename, 'pre_update'))
                                <span class="category-badge cat-auto"><i class="bi bi-lightning-fill"></i> Pre-Update</span>
                            @elseif(str_contains($backup->filename, 'uploaded'))
                                <span class="category-badge cat-upload"><i class="bi bi-upload"></i> Uploaded</span>
                            @else
                                <span class="category-badge cat-manual"><i class="bi bi-person-fill"></i> Manual</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <span class="integrity-chip chip-verified">
                                <i class="bi bi-shield-fill-check"></i> Verified
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div class="vault-actions-cluster">
                                <a href="{{ route('admin.backups.download', $backup) }}" class="vault-btn vault-btn-download" title="Download .SQL archive">
                                    <i class="bi bi-download"></i>
                                    <span>Download</span>
                                </a>
                                <form action="{{ route('admin.backups.restore', $backup) }}" method="POST" style="margin:0; display:inline;" onsubmit="return confirm('⚠️ RESTORE DATABASE WARNING:\n\nThis will restore the database to this exact snapshot ({{ $backup->filename }}).\nCurrent unsaved data will be replaced.\n\nDo you want to proceed?');">
                                    @csrf
                                    <button type="submit" class="vault-btn vault-btn-restore" title="Restore Snapshot">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                        <span>Restore</span>
                                    </button>
                                </form>
                                <form action="{{ route('admin.backups.destroy', $backup) }}" method="POST" style="display:inline-block; margin:0;" onsubmit="return confirm('Are you sure you want to permanently delete this backup file?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="vault-btn vault-btn-delete" title="Delete archive">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="empty-state-cell">
                            <div class="empty-state-wrap">
                                <div class="empty-state-icon-box">
                                    <i class="bi bi-hdd-rack"></i>
                                </div>
                                <div class="empty-state-title">No database backups created yet</div>
                                <p class="empty-state-desc">It is highly recommended to create a snapshot before performing system updates or modifications.</p>
                                <form action="{{ route('admin.backups.create') }}" method="POST" style="margin-top:14px;">
                                    @csrf
                                    <button type="submit" class="hero-btn-primary" onclick="this.disabled=true; this.form.submit();">
                                        <span class="btn-glow-layer"></span>
                                        <i class="bi bi-cloud-arrow-up-fill me-1"></i>
                                        <span>Create First Snapshot</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if(isset($backups) && $backups->hasPages())
        <div class="pagination-footer-row">
            <div class="saas-text-muted" style="font-size:0.82rem;">
                Showing {{ $backups->firstItem() ?? 0 }} to {{ $backups->lastItem() ?? 0 }} of {{ $backups->total() }} snapshots
            </div>
            <div>
                {{ $backups->links() }}
            </div>
        </div>
        @endif
    </div>

</div>

<!-- Upload & Restore Modal -->
<div id="uploadRestoreModal" class="custom-modal-overlay" style="display:none;">
    <div class="custom-modal-dialog">
        <button type="button" class="modal-close-btn" onclick="document.getElementById('uploadRestoreModal').style.display='none'" title="Close">
            <i class="bi bi-x"></i>
        </button>
        <form action="{{ route('admin.backups.upload-restore') }}" method="POST" enctype="multipart/form-data" style="margin:0; display:flex; flex-direction:column; gap:18px;">
            @csrf
            <div class="modal-header-visual">
                <div class="modal-icon-glow modal-glow-cyan">
                    <i class="bi bi-cloud-arrow-up-fill"></i>
                </div>
                <h3 class="modal-heading">Upload & Restore Database Archive</h3>
                <p class="modal-subheading">Upload a <code>.sql</code> backup archive to immediately restore the database.</p>
            </div>

            <div class="upload-dropzone-box" onclick="document.getElementById('backupFileInput').click()">
                <i class="bi bi-file-earmark-arrow-up text-gold dropzone-icon"></i>
                <label for="backupFileInput" class="upload-file-label">Choose .SQL file</label>
                <input type="file" name="backup_file" id="backupFileInput" accept=".sql,.txt" required class="upload-file-input" onchange="updateSelectedFilename(this)">
                <div id="selectedFilenameDisplay" class="selected-file-text">No file selected (Max: 100MB)</div>
            </div>

            <div class="restore-warning-note">
                <i class="bi bi-exclamation-triangle-fill text-amber me-2" style="font-size:1.1rem; flex-shrink:0;"></i>
                <span><strong>Warning:</strong> Restoring an archive will overwrite current database tables with data from the uploaded file.</span>
            </div>

            <div class="modal-actions-bar">
                <button type="button" class="saas-btn saas-btn-secondary" onclick="document.getElementById('uploadRestoreModal').style.display='none'">
                    Cancel
                </button>
                <button type="submit" class="hero-btn-primary" onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span> Restoring Database...'; this.form.submit();">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Upload & Restore Now
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.backups-hub-wrapper {
    display: flex;
    flex-direction: column;
    gap: 24px;
    padding-bottom: 50px;
}

.backups-hero-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 28px;
    flex-wrap: wrap;
    background: linear-gradient(135deg, rgba(85, 14, 14, 0.85) 0%, rgba(32, 19, 19, 0.95) 100%);
    border: 1px solid rgba(212, 175, 55, 0.25);
    border-radius: 22px;
    padding: 30px 34px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.1), 0 0 30px rgba(212, 175, 55, 0.08);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

.hero-backdrop-pattern {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle at 85% 20%, rgba(212, 175, 55, 0.12) 0%, transparent 60%),
                      radial-gradient(circle at 10% 80%, rgba(122, 26, 26, 0.2) 0%, transparent 50%);
    pointer-events: none;
}

.hero-left {
    max-width: 680px;
    position: relative;
    z-index: 2;
}

.hero-badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    background: rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(212, 175, 55, 0.25);
    border-radius: 999px;
    padding: 5px 14px;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--gold, #D4AF37);
    margin-bottom: 14px;
    letter-spacing: 0.3px;
}

.hero-badge-divider {
    opacity: 0.4;
}

.backups-hero-title {
    font-size: 1.95rem;
    font-weight: 800;
    color: #FCF8F2;
    letter-spacing: -0.03em;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 14px;
}

.hero-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(212, 175, 55, 0.15);
    border: 1px solid rgba(212, 175, 55, 0.35);
    color: var(--gold, #D4AF37);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    box-shadow: 0 0 20px rgba(212, 175, 55, 0.25);
    flex-shrink: 0;
}

.backups-hero-desc {
    color: #D1C5B4;
    font-size: 0.94rem;
    line-height: 1.6;
    margin: 0;
}

.hero-actions {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    position: relative;
    z-index: 2;
}

.hero-btn-secondary {
    padding: 12px 20px;
    font-size: 0.88rem;
    font-weight: 600;
    border-radius: 14px;
    background: rgba(30, 21, 21, 0.8);
    border: 1px solid rgba(212, 175, 55, 0.25);
    transition: all 0.25s ease;
}

.hero-btn-secondary:hover {
    background: rgba(212, 175, 55, 0.15);
    border-color: var(--gold, #D4AF37);
    transform: translateY(-2px);
}

.hero-btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 13px 26px;
    font-size: 0.95rem;
    font-weight: 700;
    color: #ffffff;
    background: linear-gradient(135deg, #15803d 0%, #16a34a 50%, #15803d 100%);
    background-size: 200% auto;
    border: 1px solid rgba(74, 222, 128, 0.45);
    border-radius: 14px;
    box-shadow: 0 8px 25px rgba(22, 163, 74, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.25);
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.hero-btn-primary:hover {
    background-position: right center;
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(22, 163, 74, 0.55), 0 0 20px rgba(74, 222, 128, 0.5);
}

.btn-shimmer-sweep {
    position: absolute;
    top: 0;
    left: -100%;
    width: 60%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
    transform: skewX(-20deg);
    animation: shimmerSweep 4s infinite;
}

@keyframes shimmerSweep {
    0% { left: -100%; }
    30% { left: 140%; }
    100% { left: 140%; }
}

/* ── Telemetry KPI Grid ── */
.telemetry-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 18px;
}

.telemetry-card {
    background: rgba(28, 19, 19, 0.75);
    border: 1px solid rgba(212, 175, 55, 0.16);
    border-radius: 18px;
    padding: 20px 22px;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    box-shadow: 0 10px 28px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.04);
    transition: all 0.25s ease;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.telemetry-card:hover {
    border-color: rgba(212, 175, 55, 0.4);
    transform: translateY(-3px);
    box-shadow: 0 15px 36px rgba(0, 0, 0, 0.55), 0 0 20px rgba(212, 175, 55, 0.1);
}

.card-glow-gold:hover { border-color: rgba(212, 175, 55, 0.45); }
.card-glow-emerald:hover { border-color: rgba(34, 197, 94, 0.45); }
.card-glow-blue:hover { border-color: rgba(59, 130, 246, 0.45); }

.telemetry-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.telemetry-label {
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #A39683;
}

.telemetry-icon-box {
    width: 36px;
    height: 36px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
}

.icon-version { background: rgba(212, 175, 55, 0.15); color: #D4AF37; border: 1px solid rgba(212, 175, 55, 0.3); }
.icon-state-green { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
.icon-db { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }

.telemetry-value-lg {
    font-size: 1.55rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
}

.telemetry-value-md {
    font-size: 1.2rem;
    font-weight: 700;
    color: #ffffff;
}

.gold-gradient-text {
    background: linear-gradient(135deg, #ffffff 0%, #D4AF37 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.text-emerald { color: #4ade80; }
.text-gold { color: var(--gold, #D4AF37); }
.font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }

.telemetry-footer {
    margin-top: auto;
}

.telemetry-sub {
    font-size: 0.78rem;
    color: #A39683;
    display: block;
}

/* ── History Table Card (Backups Vault) ── */
.history-table-card {
    background: rgba(28, 19, 19, 0.75);
    border: 1px solid rgba(212, 175, 55, 0.18);
    border-radius: 22px;
    overflow: hidden;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}

.table-card-header {
    padding: 22px 28px;
    border-bottom: 1px solid rgba(212, 175, 55, 0.15);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    background: rgba(0, 0, 0, 0.2);
}

.table-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.table-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(212, 175, 55, 0.18);
    border: 1px solid rgba(212, 175, 55, 0.35);
    color: var(--gold, #D4AF37);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    box-shadow: 0 0 15px rgba(212, 175, 55, 0.2);
}

.table-card-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 3px 0;
}

.table-card-subtitle {
    font-size: 0.84rem;
    color: #D1C5B4;
    margin: 0;
}

.vault-counter-pill {
    background: rgba(212, 175, 55, 0.12);
    border: 1px solid rgba(212, 175, 55, 0.25);
    color: #FCF8F2;
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
}

.modern-update-table {
    margin-bottom: 0;
    width: 100%;
}

.modern-update-table thead th {
    background: rgba(0, 0, 0, 0.35);
    color: var(--gold, #D4AF37);
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 14px 24px;
    border-bottom: 1px solid rgba(212, 175, 55, 0.15);
}

.modern-update-table tbody td {
    padding: 16px 24px;
    vertical-align: middle;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.modern-update-table tbody tr:hover {
    background: rgba(212, 175, 55, 0.04);
}

.time-main {
    font-weight: 700;
    color: #FCF8F2;
    font-size: 0.88rem;
    white-space: nowrap;
}

.time-relative {
    font-size: 0.76rem;
    color: #A39683;
    white-space: nowrap;
    margin-top: 2px;
}

.filename-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.file-type-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    background: rgba(212, 175, 55, 0.15);
    border: 1px solid rgba(212, 175, 55, 0.35);
    color: var(--gold, #D4AF37);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
    box-shadow: 0 0 12px rgba(212, 175, 55, 0.15);
}

.filename-text {
    color: var(--gold, #D4AF37);
    font-size: 0.86rem;
    font-weight: 600;
    white-space: nowrap;
}

.vault-size-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.55);
    border: 1px solid rgba(212, 175, 55, 0.2);
    color: #E2E8F0;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 8px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    white-space: nowrap;
    flex-shrink: 0;
    line-height: 1.2;
}

.category-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.76rem;
    font-weight: 700;
    padding: 5px 13px;
    border-radius: 999px;
    white-space: nowrap;
    flex-shrink: 0;
    line-height: 1.2;
    letter-spacing: 0.2px;
}

.cat-auto {
    background: rgba(34, 197, 94, 0.14);
    border: 1px solid rgba(34, 197, 94, 0.38);
    color: #4ade80;
}

.cat-manual {
    background: rgba(59, 130, 246, 0.14);
    border: 1px solid rgba(59, 130, 246, 0.38);
    color: #60a5fa;
}

.cat-upload {
    background: rgba(168, 85, 247, 0.14);
    border: 1px solid rgba(168, 85, 247, 0.38);
    color: #c084fc;
}

.integrity-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
    flex-shrink: 0;
    line-height: 1.2;
}

.chip-verified {
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.35);
    color: #4ade80;
}

/* ── Vault Action Buttons ── */
.vault-actions-cluster {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.vault-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 13px;
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
    border: 1px solid transparent;
    line-height: 1.2;
}

.vault-btn:hover {
    transform: translateY(-1px);
}

.vault-btn-download {
    background: rgba(56, 189, 248, 0.12);
    border-color: rgba(56, 189, 248, 0.3);
    color: #38bdf8 !important;
}

.vault-btn-download:hover {
    background: rgba(56, 189, 248, 0.22);
    border-color: #38bdf8;
    color: #7dd3fc !important;
    box-shadow: 0 0 14px rgba(56, 189, 248, 0.3);
}

.vault-btn-restore {
    background: rgba(212, 175, 55, 0.12);
    border-color: rgba(212, 175, 55, 0.35);
    color: #F9E596 !important;
}

.vault-btn-restore:hover {
    background: rgba(212, 175, 55, 0.24);
    border-color: var(--gold, #D4AF37);
    color: #FFFFFF !important;
    box-shadow: 0 0 14px rgba(212, 175, 55, 0.35);
}

.vault-btn-delete {
    background: rgba(239, 68, 68, 0.1);
    border-color: rgba(239, 68, 68, 0.25);
    color: #f87171 !important;
    padding: 7px 11px;
}

.vault-btn-delete:hover {
    background: rgba(239, 68, 68, 0.25);
    border-color: #ef4444;
    color: #fca5a5 !important;
    box-shadow: 0 0 14px rgba(239, 68, 68, 0.3);
}

.pagination-footer-row {
    padding: 18px 28px;
    border-top: 1px solid rgba(212, 175, 55, 0.15);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    background: rgba(0, 0, 0, 0.2);
}

.empty-state-cell {
    padding: 56px 24px !important;
    text-align: center;
}

.empty-state-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.empty-state-icon-box {
    width: 64px;
    height: 64px;
    border-radius: 20px;
    background: rgba(212, 175, 55, 0.1);
    border: 1px solid rgba(212, 175, 55, 0.25);
    color: var(--gold, #D4AF37);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    margin-bottom: 6px;
    box-shadow: 0 0 25px rgba(212, 175, 55, 0.15);
}

.empty-state-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #cbd5e1;
}

.empty-state-desc {
    font-size: 0.85rem;
    color: #A39683;
    margin: 0;
    max-width: 440px;
    line-height: 1.5;
}

/* ── Custom Modal ── */
.custom-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    z-index: 10050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.custom-modal-dialog {
    background: linear-gradient(135deg, #201414 0%, #110A0A 100%);
    border: 1px solid rgba(212, 175, 55, 0.35);
    border-radius: 24px;
    width: 100%;
    max-width: 540px;
    padding: 30px;
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.85), 0 0 35px rgba(212, 175, 55, 0.15);
    display: flex;
    flex-direction: column;
    gap: 22px;
    position: relative;
}

.modal-close-btn {
    position: absolute;
    top: 18px;
    right: 18px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #cbd5e1;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.2rem;
    transition: all 0.2s ease;
}

.modal-close-btn:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
}

.modal-header-visual {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.modal-icon-glow {
    width: 60px;
    height: 60px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

.modal-glow-cyan {
    background: rgba(56, 189, 248, 0.2);
    border: 1px solid rgba(56, 189, 248, 0.45);
    color: #38bdf8;
    box-shadow: 0 0 25px rgba(56, 189, 248, 0.35);
}

.modal-heading {
    font-size: 1.35rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
}

.modal-subheading {
    font-size: 0.88rem;
    color: #D1C5B4;
    margin: 0;
}

.upload-dropzone-box {
    background: rgba(0, 0, 0, 0.45);
    border: 2px dashed rgba(212, 175, 55, 0.35);
    border-radius: 18px;
    padding: 28px 22px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    transition: all 0.25s ease;
    cursor: pointer;
}

.upload-dropzone-box:hover {
    border-color: var(--gold, #D4AF37);
    background: rgba(212, 175, 55, 0.06);
}

.dropzone-icon {
    font-size: 2.6rem;
    margin-bottom: 4px;
    display: block;
}

.upload-file-label {
    background: rgba(212, 175, 55, 0.15);
    border: 1px solid var(--gold, #D4AF37);
    color: var(--gold, #D4AF37);
    padding: 7px 18px;
    border-radius: 11px;
    font-size: 0.86rem;
    font-weight: 600;
    cursor: pointer;
}

.upload-file-label:hover {
    background: var(--gold, #D4AF37);
    color: #110A0A;
}

.upload-file-input {
    display: none;
}

.selected-file-text {
    font-size: 0.8rem;
    color: #A39683;
    font-family: monospace;
}

.restore-warning-note {
    background: rgba(245, 158, 11, 0.14);
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 0.82rem;
    color: #fbbf24;
    display: flex;
    align-items: center;
}

.modal-actions-bar {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* ── Hub Alerts ── */
.hub-alert {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    border-radius: 16px;
    backdrop-filter: blur(12px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.hub-alert-success {
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.35);
    color: #4ade80;
}

.hub-alert-danger {
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.35);
    color: #f87171;
}

.hub-alert-icon {
    font-size: 1.4rem;
    flex-shrink: 0;
}

.hub-alert-content {
    flex: 1;
}

.hub-alert-title {
    font-weight: 700;
    font-size: 0.92rem;
    margin-bottom: 2px;
}

.hub-alert-text {
    font-size: 0.85rem;
    opacity: 0.9;
}

.hub-alert-close {
    background: transparent;
    border: none;
    color: inherit;
    font-size: 1.3rem;
    cursor: pointer;
    opacity: 0.7;
}

.hub-alert-close:hover {
    opacity: 1;
}
</style>

<script>
function updateSelectedFilename(input) {
    const display = document.getElementById('selectedFilenameDisplay');
    if (input.files && input.files[0]) {
        display.textContent = input.files[0].name + ' (' + (input.files[0].size / 1024 / 1024).toFixed(2) + ' MB)';
        display.style.color = '#4ade80';
    } else {
        display.textContent = 'No file selected (Max: 100MB)';
        display.style.color = '#A39683';
    }
}
</script>
@endsection
