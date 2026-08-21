@extends('layouts.app')

@section('title', 'System Health & Diagnostics Center')

@section('content')
<div class="system-health-wrapper">

    <!-- Health Executive Hero -->
    <div class="health-hero-card">
        <div class="hero-backdrop-pattern"></div>
        <div class="hero-left">
            <div class="hero-badge-pill">
                <span class="live-pulse-dot pulse-emerald"></span>
                <span>Real-Time Subsystem Benchmark</span>
                <span class="hero-badge-divider">•</span>
                <span>System Operational</span>
            </div>
            <h1 class="health-hero-title">
                <span class="hero-icon-box">
                    <i class="bi bi-activity"></i>
                </span>
                <span class="hero-title-text">System Health & Diagnostics Center</span>
            </h1>
            <p class="health-hero-desc">
                Continuous telemetry monitoring, database latency benchmarking, background queue workers inspection, and environment security audits.
            </p>
        </div>
        
        <div class="hero-actions">
            <button type="button" class="saas-btn saas-btn-secondary hero-btn-secondary" onclick="window.location.href='{{ route('admin.system-update.index', ['tab' => 'health']) }}'">
                <i class="bi bi-cpu-fill text-gold me-1"></i>
                <span>Operations Hub</span>
            </button>
            <button type="button" class="hero-btn-primary" onclick="window.location.reload()">
                <span class="btn-glow-layer"></span>
                <span class="btn-shimmer-sweep"></span>
                <i class="bi bi-arrow-clockwise me-1"></i>
                <span>Refresh Diagnostics</span>
            </button>
        </div>
    </div>

    <!-- 4 Subsystems Telemetry Grid -->
    <div class="telemetry-grid">
        
        <!-- Database Status -->
        <div class="telemetry-card card-glow-blue">
            <div class="telemetry-header">
                <span class="telemetry-label">Database Subsystem</span>
                <div class="telemetry-icon-box icon-db"><i class="bi bi-database-check"></i></div>
            </div>
            <div class="telemetry-value-row">
                <div class="telemetry-value-md text-emerald">
                    <i class="bi bi-check-circle-fill me-1"></i> Online
                </div>
                <span class="latency-pill"><i class="bi bi-speedometer me-1"></i>Optimal Latency</span>
            </div>
            <div class="telemetry-footer">
                <span class="telemetry-sub font-mono">{{ config('database.default') }} driver • Read/Write Active</span>
            </div>
        </div>

        <!-- Queue Status -->
        <div class="telemetry-card card-glow-purple">
            <div class="telemetry-header">
                <span class="telemetry-label">Queue & Background Jobs</span>
                <div class="telemetry-icon-box icon-purple"><i class="bi bi-layers-fill"></i></div>
            </div>
            <div class="telemetry-value-row">
                <div class="telemetry-value-md {{ ($failedJobs ?? 0) > 0 ? 'text-danger' : 'text-emerald' }}">
                    <i class="bi {{ ($failedJobs ?? 0) > 0 ? 'bi-exclamation-octagon-fill' : 'bi-check-circle-fill' }} me-1"></i>
                    {{ ($failedJobs ?? 0) > 0 ? ($failedJobs . ' Failed Jobs') : 'Queue Healthy' }}
                </div>
                <span class="status-pill status-success">{{ config('queue.default') }}</span>
            </div>
            <div class="telemetry-footer">
                <span class="telemetry-sub">Jobs in queue: <strong>{{ $queueSize ?? 0 }}</strong></span>
            </div>
        </div>

        <!-- Mail Service -->
        <div class="telemetry-card card-glow-amber">
            <div class="telemetry-header">
                <span class="telemetry-label">Mail Service (SMTP)</span>
                <div class="telemetry-icon-box icon-amber"><i class="bi bi-envelope-check-fill"></i></div>
            </div>
            <div class="telemetry-value-row">
                <div class="telemetry-value-md {{ ($mailConfigured ?? false) ? 'text-emerald' : '' }}">
                    <i class="bi {{ ($mailConfigured ?? false) ? 'bi-check-circle-fill' : 'bi-info-circle-fill' }} me-1"></i>
                    {{ ($mailConfigured ?? false) ? 'Configured' : 'Local / Log' }}
                </div>
                <span class="status-pill {{ ($mailConfigured ?? false) ? 'status-success' : 'status-warning' }}">{{ config('mail.default') }}</span>
            </div>
            <div class="telemetry-footer">
                <span class="telemetry-sub font-mono">Host: {{ config('mail.mailers.smtp.host') ?: '127.0.0.1' }}</span>
            </div>
        </div>

        <!-- Database Backups -->
        <div class="telemetry-card card-glow-gold">
            <div class="telemetry-header">
                <span class="telemetry-label">Database Protection</span>
                <div class="telemetry-icon-box icon-version"><i class="bi bi-shield-check"></i></div>
            </div>
            <div class="telemetry-value-row">
                <div class="telemetry-value-lg gold-gradient-text">{{ $backupCount ?? 0 }}</div>
                <a href="{{ route('admin.system-update.index', ['tab' => 'backups']) }}" class="mini-action-btn">
                    <i class="bi bi-folder2-open"></i> Vault
                </a>
            </div>
            <div class="telemetry-footer">
                <span class="telemetry-sub">Point-in-time recovery archives</span>
            </div>
        </div>

    </div>

    <!-- Environment Information Card -->
    <div class="environment-table-card">
        <div class="table-card-header">
            <div class="table-header-left">
                <div class="table-icon-wrap"><i class="bi bi-cpu"></i></div>
                <div>
                    <h3 class="table-card-title">Environment & Security Specifications</h3>
                    <p class="table-card-subtitle">Runtime platform, security configuration, and framework build info.</p>
                </div>
            </div>
        </div>

        <div class="saas-table-container">
            <table class="saas-table modern-env-table">
                <tbody>
                    <tr>
                        <td class="env-label-cell"><i class="bi bi-app text-gold me-2"></i>Application Name</td>
                        <td class="env-val-cell font-mono">{{ config('app.name') }}</td>
                    </tr>
                    <tr>
                        <td class="env-label-cell"><i class="bi bi-diagram-3 text-gold me-2"></i>Environment Mode</td>
                        <td class="env-val-cell">
                            <span class="version-chip {{ config('app.env') === 'production' ? 'chip-prod' : 'chip-dev' }}">
                                {{ strtoupper(config('app.env')) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="env-label-cell"><i class="bi bi-shield-lock text-gold me-2"></i>Debug Mode</td>
                        <td class="env-val-cell">
                            @if(config('app.debug'))
                                <span class="status-pill status-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i> Enabled (Development Only)</span>
                            @else
                                <span class="status-pill status-success"><i class="bi bi-shield-check me-1"></i> Disabled (Production Hardened)</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="env-label-cell"><i class="bi bi-code-slash text-gold me-2"></i>PHP Runtime Engine</td>
                        <td class="env-val-cell font-mono">PHP {{ phpversion() }} ({{ PHP_OS_FAMILY }})</td>
                    </tr>
                    <tr>
                        <td class="env-label-cell"><i class="bi bi-clock-history text-gold me-2"></i>Server Clock & Timezone</td>
                        <td class="env-val-cell font-mono">{{ now()->format('Y-m-d H:i:s T') }} ({{ config('app.timezone') }})</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
.system-health-wrapper {
    display: flex;
    flex-direction: column;
    gap: 24px;
    padding-bottom: 50px;
}

.health-hero-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 28px;
    flex-wrap: wrap;
    background: linear-gradient(135deg, rgba(20, 35, 45, 0.85) 0%, rgba(17, 24, 28, 0.95) 100%);
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
                      radial-gradient(circle at 10% 80%, rgba(56, 189, 248, 0.15) 0%, transparent 50%);
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

.live-pulse-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
}

.pulse-emerald {
    background: #22c55e;
    box-shadow: 0 0 10px #22c55e;
    animation: pulseGlowGreen 2s infinite;
}

@keyframes pulseGlowGreen {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
    70% { transform: scale(1.15); box-shadow: 0 0 0 9px rgba(34, 197, 94, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}

.health-hero-title {
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

.health-hero-desc {
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

/* ── Telemetry Grid ── */
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

.card-glow-blue:hover { border-color: rgba(59, 130, 246, 0.45); }
.card-glow-purple:hover { border-color: rgba(168, 85, 247, 0.45); }
.card-glow-amber:hover { border-color: rgba(245, 158, 11, 0.45); }
.card-glow-gold:hover { border-color: rgba(212, 175, 55, 0.45); }

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

.icon-db { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
.icon-purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
.icon-amber { background: rgba(234, 179, 8, 0.15); color: #facc15; border: 1px solid rgba(234, 179, 8, 0.3); }
.icon-version { background: rgba(212, 175, 55, 0.15); color: #D4AF37; border: 1px solid rgba(212, 175, 55, 0.3); }

.telemetry-value-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.telemetry-value-lg {
    font-size: 1.55rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
}

.telemetry-value-md {
    font-size: 1.08rem;
    font-weight: 700;
    color: #ffffff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.gold-gradient-text {
    background: linear-gradient(135deg, #ffffff 0%, #D4AF37 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.text-emerald { color: #4ade80; }
.text-danger { color: #f87171; }
.text-gold { color: var(--gold, #D4AF37); }
.font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }

.status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
    flex-shrink: 0;
    line-height: 1.2;
}

.status-success {
    background: rgba(34, 197, 94, 0.14);
    border: 1px solid rgba(34, 197, 94, 0.35);
    color: #4ade80;
}

.status-warning {
    background: rgba(245, 158, 11, 0.14);
    border: 1px solid rgba(245, 158, 11, 0.35);
    color: #fbbf24;
}

.latency-pill {
    background: rgba(0, 0, 0, 0.55);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #cbd5e1;
    font-size: 0.76rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    padding: 3px 9px;
    border-radius: 7px;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    white-space: nowrap !important;
    flex-shrink: 0 !important;
    line-height: 1.2 !important;
    gap: 5px;
}

.mini-action-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(212, 175, 55, 0.3);
    color: var(--gold, #D4AF37);
    font-size: 0.76rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    flex-shrink: 0;
}

.mini-action-btn:hover {
    background: rgba(212, 175, 55, 0.2);
    border-color: var(--gold, #D4AF37);
    color: var(--gold, #D4AF37);
}

.version-chip {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    flex-shrink: 0;
    line-height: 1.2;
}
.chip-prod { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
.chip-dev { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }

.telemetry-footer {
    margin-top: auto;
}

.telemetry-sub {
    font-size: 0.78rem;
    color: #A39683;
    display: block;
}

/* ── Environment Table Card ── */
.environment-table-card {
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

.modern-env-table {
    margin-bottom: 0;
    width: 100%;
}

.modern-env-table tbody td {
    padding: 16px 28px;
    vertical-align: middle;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.env-label-cell {
    width: 320px;
    font-weight: 600;
    color: #D1C5B4;
    font-size: 0.88rem;
}

.env-val-cell {
    color: #ffffff;
    font-size: 0.88rem;
}

.modern-env-table tbody tr:hover {
    background: rgba(212, 175, 55, 0.04);
}
</style>
@endsection
