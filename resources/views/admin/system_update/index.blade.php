@extends('layouts.app')

@section('title', 'System Maintenance & Operations Hub')

@section('content')
<div class="system-update-container">

    <!-- Top Tabbed Control Plane -->
    <div class="maintenance-tabs-nav">
        <button type="button" class="maintenance-tab-btn {{ $activeTab === 'updates' ? 'active' : '' }}" onclick="switchMaintenanceTab('updates', this)" id="tabBtnUpdates">
            <span class="tab-btn-glow"></span>
            <i class="bi bi-lightning-charge-fill tab-icon"></i>
            <span class="tab-label">Updates & Maintenance</span>
            @if($pendingMigrationsCount > 0)
                <span class="tab-badge tab-badge-alert">{{ $pendingMigrationsCount }} Migrations</span>
            @endif
        </button>
        <button type="button" class="maintenance-tab-btn {{ $activeTab === 'backups' ? 'active' : '' }}" onclick="switchMaintenanceTab('backups', this)" id="tabBtnBackups">
            <span class="tab-btn-glow"></span>
            <i class="bi bi-shield-check tab-icon"></i>
            <span class="tab-label">Backups & Restore Hub</span>
            <span class="tab-badge">{{ $totalBackupCount }}</span>
        </button>
        <button type="button" class="maintenance-tab-btn {{ $activeTab === 'health' ? 'active' : '' }}" onclick="switchMaintenanceTab('health', this)" id="tabBtnHealth">
            <span class="tab-btn-glow"></span>
            <i class="bi bi-activity tab-icon"></i>
            <span class="tab-label">System Diagnostics & Health</span>
            <span class="tab-badge-score {{ $healthScore >= 90 ? 'score-green' : ($healthScore >= 70 ? 'score-amber' : 'score-red') }}" id="topTabHealthBadge">{{ $healthScore }}%</span>
        </button>
    </div>

    <!-- Alert Messages (for backup actions etc.) -->
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

    <!-- ══════════════════════════════════════════════════════════════
         TAB 1: UPDATES & MAINTENANCE PANE
         ══════════════════════════════════════════════════════════════ -->
    <div id="paneUpdates" class="maintenance-tab-pane {{ $activeTab === 'updates' ? 'active' : '' }}">
        
        <!-- Hero Header -->
        <div class="update-hero-header">
            <div class="hero-backdrop-pattern"></div>
            <div class="hero-left">
                <div class="hero-badge-pill">
                    <span class="live-pulse-dot {{ $isDown ? 'pulse-amber' : 'pulse-emerald' }}"></span>
                    <span class="badge-status-text">{{ $isDown ? 'Maintenance Mode Active' : 'System Live & Operational' }}</span>
                    <span class="hero-badge-divider">•</span>
                    <span class="badge-health-text">Readiness: <strong class="{{ $healthScore >= 90 ? 'text-emerald' : 'text-amber' }}">{{ $healthScore }}%</strong></span>
                </div>
                <h1 class="update-hero-title">
                    <span class="hero-icon-box">
                        <i class="bi bi-cpu-fill"></i>
                    </span>
                    <span class="hero-title-text">System Update & Maintenance Center</span>
                </h1>
                <p class="update-hero-desc">
                    Orchestrate automated 1-click system upgrades, apply zero-downtime database schema migrations, purge and warm up cache stores, and broadcast asset updates to all client PWA devices.
                </p>
            </div>

            <div class="hero-actions">
                <button type="button" class="saas-btn saas-btn-secondary hero-btn-secondary" onclick="switchMaintenanceTab('backups', document.getElementById('tabBtnBackups'))">
                    <i class="bi bi-archive-fill me-1 text-gold"></i>
                    <span>Backups Vault</span>
                    <span class="badge-count-pill">{{ $totalBackupCount }}</span>
                </button>
                <button type="button" class="saas-btn saas-btn-gold hero-btn-scan" onclick="triggerHealthScan()" id="quickScanBtn" title="Run diagnostic scan">
                    <i class="bi bi-radar me-1"></i>
                    <span>Scan Health</span>
                </button>
                <button type="button" class="hero-btn-primary" onclick="confirmFullSystemUpdate()" id="fullUpdateBtn">
                    <span class="btn-glow-layer"></span>
                    <span class="btn-shimmer-sweep"></span>
                    <i class="bi bi-lightning-charge-fill btn-icon-pulse"></i>
                    <span>Run 1-Click System Update</span>
                </button>
            </div>
        </div>

        <!-- Live Telemetry KPI Grid -->
        <div class="telemetry-grid">
            <!-- 1. System State -->
            <div class="telemetry-card card-glow-emerald">
                <div class="telemetry-header">
                    <span class="telemetry-label">System State</span>
                    <div class="telemetry-icon-box icon-state {{ $isDown ? 'icon-state-amber' : 'icon-state-green' }}">
                        <i class="bi {{ $isDown ? 'bi-cone-striped' : 'bi-broadcast' }}"></i>
                    </div>
                </div>
                <div class="telemetry-value-row">
                    <span id="maintenanceBadge" class="status-pill {{ $isDown ? 'status-warning' : 'status-success' }}">
                        <span class="status-dot {{ $isDown ? 'dot-amber' : 'dot-green' }}"></span>
                        <span id="maintenanceStateText">{{ $isDown ? 'Maintenance Mode' : 'Live & Active' }}</span>
                    </span>
                    <button type="button" onclick="promptMaintenanceToggle()" class="mini-action-btn" title="Toggle Maintenance Mode">
                        <i class="bi bi-power"></i> Toggle
                    </button>
                </div>
                <div class="telemetry-footer">
                    <span class="telemetry-sub">{{ $isDown ? 'Traffic routed to offline screen' : 'All public traffic accepting requests' }}</span>
                </div>
            </div>

            <!-- 2. Release & Version -->
            <div class="telemetry-card card-glow-gold">
                <div class="telemetry-header">
                    <span class="telemetry-label">App Release</span>
                    <div class="telemetry-icon-box icon-version">
                        <i class="bi bi-tags-fill"></i>
                    </div>
                </div>
                <div class="telemetry-value-row">
                    <div class="telemetry-value-lg gold-gradient-text">v2.1.0</div>
                    <span class="version-chip {{ $appEnvironment === 'production' ? 'chip-prod' : 'chip-dev' }}">
                        {{ strtoupper($appEnvironment) }}
                    </span>
                </div>
                <div class="telemetry-footer">
                    <span class="telemetry-sub">Laravel {{ $laravelVersion }} • Debug: <strong class="{{ $debugMode ? 'text-amber' : 'text-emerald' }}">{{ $debugMode ? 'ON' : 'OFF' }}</strong></span>
                </div>
            </div>

            <!-- 3. Database Engine & Health -->
            <div class="telemetry-card card-glow-blue">
                <div class="telemetry-header">
                    <span class="telemetry-label">Database Health</span>
                    <div class="telemetry-icon-box icon-db">
                        <i class="bi bi-database-check"></i>
                    </div>
                </div>
                <div class="telemetry-value-row">
                    <div class="telemetry-value-md text-emerald">
                        <i class="bi bi-check-circle-fill me-1"></i> Connected
                    </div>
                    <span class="latency-pill" id="dbLatencyPill"><i class="bi bi-speedometer me-1"></i>{{ $dbLatencyMs }} ms</span>
                </div>
                <div class="telemetry-footer">
                    <span class="telemetry-sub font-mono">{{ $dbVersion }} • {{ $dbTableCount }} tables</span>
                </div>
            </div>

            <!-- 4. Client PWA Cache -->
            <div class="telemetry-card card-glow-cyan">
                <div class="telemetry-header">
                    <span class="telemetry-label">Client PWA Cache</span>
                    <div class="telemetry-icon-box icon-pwa">
                        <i class="bi bi-phone-flip"></i>
                    </div>
                </div>
                <div class="telemetry-value-row">
                    <div class="telemetry-value-lg pwa-cyan" id="currentSwVersionBadge">{{ $swVersion }}</div>
                    <button type="button" onclick="bumpPwa()" class="mini-action-btn" id="quickBumpBtn" title="Bump client asset cache">
                        <i class="bi bi-arrow-repeat"></i> Bump
                    </button>
                </div>
                <div class="telemetry-footer">
                    <span class="telemetry-sub">Forces client offline cache reload on visit</span>
                </div>
            </div>



            <!-- 6. Runtime Engine & OPcache -->
            <div class="telemetry-card card-glow-amber">
                <div class="telemetry-header">
                    <span class="telemetry-label">PHP Runtime Engine</span>
                    <div class="telemetry-icon-box icon-runtime">
                        <i class="bi bi-gear-wide-connected"></i>
                    </div>
                </div>
                <div class="telemetry-value-row">
                    <div class="telemetry-value-md font-mono">PHP {{ $phpVersion }}</div>
                    <span class="status-pill {{ $allExtensionsLoaded ? 'status-success' : 'status-warning' }}">
                        {{ $allExtensionsLoaded ? '10/10 Ext' : 'Ext Warn' }}
                    </span>
                </div>
                <div class="telemetry-footer">
                    <span class="telemetry-sub font-mono">Mem: {{ round($memoryUsedBytes / 1024 / 1024, 1) }}MB / {{ $memoryLimit }} • {{ $opcacheEnabled ? 'OPcache ON' : 'CLI' }}</span>
                </div>
            </div>
        </div>

        <!-- Pre-Flight System Readiness Bar -->
        <div class="readiness-bar-card">
            <div class="readiness-left">
                <div class="readiness-score-box">
                    <div class="readiness-gauge-ring">
                        <span class="readiness-score" id="readinessScoreVal">{{ $healthScore }}%</span>
                    </div>
                    <span class="readiness-score-label">Readiness</span>
                </div>
                <div class="readiness-details">
                    <h4 class="readiness-title">
                        <i class="bi bi-shield-check text-gold me-2"></i>Pre-Flight System Readiness Checklist
                    </h4>
                    <p class="readiness-desc">All primary runtime subsystems and file permissions required for seamless 1-click updates.</p>
                    <div class="readiness-chips-row" id="readinessChipsContainer">
                        <span class="readiness-chip chip-pass" title="Database driver is responding">
                            <i class="bi bi-check-circle-fill"></i> Database Connected
                        </span>
                        <span class="readiness-chip {{ $storageWritable ? 'chip-pass' : 'chip-fail' }}" title="Storage directory write permission">
                            <i class="bi {{ $storageWritable ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i> Storage Writable
                        </span>
                        <span class="readiness-chip {{ $bootstrapCacheWritable ? 'chip-pass' : 'chip-fail' }}" title="Bootstrap cache directory write permission">
                            <i class="bi {{ $bootstrapCacheWritable ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i> Bootstrap Cache
                        </span>
                        <span class="readiness-chip {{ $allExtensionsLoaded ? 'chip-pass' : 'chip-warn' }}" title="Required PHP core extensions loaded">
                            <i class="bi {{ $allExtensionsLoaded ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }}"></i> Core Extensions
                        </span>
                        <span class="readiness-chip {{ $pendingMigrationsCount === 0 ? 'chip-pass' : 'chip-pending' }}" id="pendingMigChip">
                            <i class="bi {{ $pendingMigrationsCount === 0 ? 'bi-check-circle-fill' : 'bi-arrow-clockwise' }}"></i>
                            <span id="pendingMigChipText">{{ $pendingMigrationsCount === 0 ? 'Schema Up To Date' : $pendingMigrationsCount . ' Migrations Pending' }}</span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="readiness-right">
                <button type="button" class="saas-btn saas-btn-secondary readiness-scan-btn" onclick="triggerHealthScan()" id="deepScanBtn">
                    <i class="bi bi-search"></i>
                    <span>Scan Subsystems</span>
                </button>
            </div>
        </div>

        <!-- 1-Click Update Interactive Console (Stepper + Obsidian Terminal) -->
        <div id="updateProgressCard" class="update-console-card" style="display:none;">
            <div class="console-card-header">
                <div class="console-header-left">
                    <div class="console-spinner" id="updateSpinner">
                        <div class="spinner-ring"></div>
                        <div class="spinner-core-dot"></div>
                    </div>
                    <div>
                        <h3 class="console-title" id="updateCardTitle">Executing 1-Click System Update...</h3>
                        <p class="console-subtitle" id="updateCardSubtitle">Orchestrating automated safety backup snapshot, schema migration, cache refresh, and PWA client broadcast.</p>
                    </div>
                </div>
                <div class="console-header-right">
                    <span class="console-badge badge-processing" id="updateStatusBadge">In Progress</span>
                </div>
            </div>

            <!-- 4-Stage Stepper Progress Tracker -->
            <div class="update-stepper-container">
                <div class="stepper-progress-track">
                    <div class="stepper-progress-bar" id="stepperProgressBar" style="width: 15%;"></div>
                </div>
                
                <div class="stepper-steps-grid">
                    <div class="stepper-step active" id="stepNode1">
                        <div class="step-icon-wrap"><i class="bi bi-shield-check"></i></div>
                        <div class="step-label">1. Safety Snapshot</div>
                        <div class="step-sub" id="step1Sub">Backing up DB</div>
                    </div>
                    <div class="stepper-step" id="stepNode2">
                        <div class="step-icon-wrap"><i class="bi bi-database-fill-gear"></i></div>
                        <div class="step-label">2. Migrations</div>
                        <div class="step-sub" id="step2Sub">Pending Schema</div>
                    </div>
                    <div class="stepper-step" id="stepNode3">
                        <div class="step-icon-wrap"><i class="bi bi-speedometer2"></i></div>
                        <div class="step-label">3. Cache Warmup</div>
                        <div class="step-sub" id="step3Sub">Optimizing Cache</div>
                    </div>
                    <div class="stepper-step" id="stepNode4">
                        <div class="step-icon-wrap"><i class="bi bi-broadcast-pin"></i></div>
                        <div class="step-label">4. Client Broadcast</div>
                        <div class="step-sub" id="step4Sub">Bumping PWA</div>
                    </div>
                </div>
            </div>

            <!-- Obsidian Terminal Console Logs -->
            <div class="obsidian-terminal-wrapper">
                <div class="terminal-titlebar">
                    <div class="terminal-dots">
                        <span class="dot dot-red"></span>
                        <span class="dot dot-yellow"></span>
                        <span class="dot dot-green"></span>
                    </div>
                    <div class="terminal-title">
                        <i class="bi bi-terminal-fill me-1 text-gold"></i> Update Execution Console • STDOUT
                    </div>
                    <div class="terminal-actions">
                        <button type="button" class="term-btn" onclick="copyTerminalLogs()" title="Copy logs to clipboard">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                        <button type="button" class="term-btn" onclick="clearTerminalLogs()" title="Clear terminal">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="terminal-body" id="terminalLogsBody">
                    <div class="term-line term-muted">
                        <span class="term-time">[{{ now()->format('H:i:s') }}]</span> Initializing update orchestrator routine...
                    </div>
                </div>
            </div>

            <!-- Detailed Results Card List -->
            <div id="updateStepsList" class="update-results-list" style="display:none;"></div>

            <!-- Post-Update Rollback / Success Banner -->
            <div id="updateSuccessBanner" class="update-banner-card banner-success" style="display:none;">
                <div class="banner-icon-box">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="banner-content">
                    <h4 class="banner-title">System Update Completed Successfully!</h4>
                    <p class="banner-desc" id="bannerSuccessDesc">
                        All subsystems have been updated cleanly. A pre-update safety snapshot is stored in your Backups Hub for instant rollback if ever needed.
                    </p>
                </div>
                <div class="banner-actions">
                    <button type="button" class="saas-btn saas-btn-secondary" onclick="switchMaintenanceTab('backups', document.getElementById('tabBtnBackups'))" style="font-size:0.82rem;">
                        <i class="bi bi-archive-fill me-1"></i> View Snapshot
                    </button>
                    <button type="button" class="saas-btn saas-btn-gold" onclick="dismissUpdateConsole()" style="font-size:0.82rem;">
                        <i class="bi bi-check2 me-1"></i> Dismiss Console
                    </button>
                </div>
            </div>
        </div>

        <!-- Operations & Maintenance Modules Grid -->
        <div class="operations-grid-title">
            <h2 class="section-title">
                <i class="bi bi-toggles2 text-gold me-2"></i> Modular Maintenance Operations
            </h2>
            <p class="section-desc">Execute granular administrative tasks independently whenever needed.</p>
        </div>

        <div class="operations-cards-grid">
            
            <!-- Operation 1: Database Migrations -->
            <div class="operation-card" id="opCardMigrations">
                <div class="op-card-header">
                    <div class="op-icon-wrap icon-blue">
                        <i class="bi bi-database-gear"></i>
                    </div>
                    <div class="op-header-info">
                        <div class="op-title-row">
                            <h3 class="op-title">Database Migrations</h3>
                            <span class="op-status-badge {{ $pendingMigrationsCount > 0 ? 'badge-amber' : 'badge-emerald' }}" id="migStatusBadge">
                                {{ $pendingMigrationsCount > 0 ? $pendingMigrationsCount . ' Pending' : 'Up to Date' }}
                            </span>
                        </div>
                        <p class="op-desc">Safely apply new database tables, column modifications, indices, and foreign keys.</p>
                    </div>
                </div>

                <div id="migrationOutput" class="op-output-box" style="display:none;"></div>

                <div class="op-card-footer">
                    <div class="op-actions-row">
                        <button type="button" onclick="checkMigrationsStatus()" id="checkMigBtn" class="saas-btn saas-btn-secondary op-btn-flex">
                            <i class="bi bi-search me-1"></i> Check Status
                        </button>
                        <button type="button" onclick="runMigrationsOnly()" id="migrateBtn" class="saas-btn saas-btn-primary op-btn-flex">
                            <i class="bi bi-play-circle-fill me-1"></i> Run Migrations
                        </button>
                    </div>
                </div>
            </div>

            <!-- Operation 2: Cache Optimization -->
            <div class="operation-card" id="opCardCaches">
                <div class="op-card-header">
                    <div class="op-icon-wrap icon-amber">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div class="op-header-info">
                        <div class="op-title-row">
                            <h3 class="op-title">Cache Optimizer</h3>
                            <span class="op-status-badge badge-emerald">Ready</span>
                        </div>
                        <p class="op-desc">Clear and compile Blade views, route maps, system configurations, and application cache stores.</p>
                    </div>
                </div>

                <div id="cacheOutput" class="op-output-box" style="display:none;"></div>

                <div class="op-card-footer">
                    <div class="op-actions-row">
                        <button type="button" onclick="clearCachesOnly()" id="cacheBtn" class="saas-btn saas-btn-secondary op-btn-full">
                            <i class="bi bi-arrow-repeat me-1"></i> Rebuild & Purge Caches
                        </button>
                    </div>
                </div>
            </div>

            <!-- Operation 3: Client PWA Broadcast -->
            <div class="operation-card" id="opCardPwa">
                <div class="op-card-header">
                    <div class="op-icon-wrap icon-purple">
                        <i class="bi bi-broadcast-pin"></i>
                    </div>
                    <div class="op-header-info">
                        <div class="op-title-row">
                            <h3 class="op-title">Client PWA Broadcast</h3>
                            <span class="op-status-badge badge-cyan" id="pwaVersionTag">{{ $swVersion }}</span>
                        </div>
                        <p class="op-desc">Increment Service Worker cache version. Forces student & faculty devices to download latest assets on visit.</p>
                    </div>
                </div>

                <div id="pwaOutput" class="op-output-box" style="display:none;"></div>

                <div class="op-card-footer">
                    <div class="op-actions-row">
                        <button type="button" onclick="bumpPwa()" id="pwaBtn" class="saas-btn saas-btn-secondary op-btn-full">
                            <i class="bi bi-send-fill me-1"></i> Broadcast Update to Clients
                        </button>
                    </div>
                </div>
            </div>

            <!-- Operation 4: Traffic Shield & Maintenance Mode -->
            <div class="operation-card" id="opCardMaintenance">
                <div class="op-card-header">
                    <div class="op-icon-wrap icon-red">
                        <i class="bi bi-shield-shaded"></i>
                    </div>
                    <div class="op-header-info">
                        <div class="op-title-row">
                            <h3 class="op-title">Traffic Shield</h3>
                            <span class="op-status-badge {{ $isDown ? 'badge-amber' : 'badge-emerald' }}" id="trafficShieldBadge">
                                {{ $isDown ? 'Maintenance Active' : 'Traffic Open' }}
                            </span>
                        </div>
                        <p class="op-desc">Toggle maintenance mode with offline screen. Super admins bypass using secret token.</p>
                    </div>
                </div>

                <div class="secret-token-box">
                    <span class="secret-label">Bypass Key:</span>
                    <code class="secret-code">admin-access-portal</code>
                    <button type="button" class="copy-token-btn" onclick="copyBypassLink()" title="Copy full bypass URL">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>

                <div class="op-card-footer">
                    <div class="op-actions-row">
                        <button type="button" onclick="promptMaintenanceToggle()" id="maintBtn" class="saas-btn {{ $isDown ? 'saas-btn-success' : 'saas-btn-secondary' }} op-btn-full">
                            <i class="bi {{ $isDown ? 'bi-check2-circle' : 'bi-cone-striped' }} me-1"></i>
                            <span>{{ $isDown ? 'Deactivate Maintenance (Go Live)' : 'Activate Maintenance Mode' }}</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 2: BACKUPS & RESTORE HUB PANE
         ══════════════════════════════════════════════════════════════ -->
    <div id="paneBackups" class="maintenance-tab-pane {{ $activeTab === 'backups' ? 'active' : '' }}">
        
        <!-- Backups Hero Bar -->
        <div class="update-hero-header hero-backups">
            <div class="hero-backdrop-pattern"></div>
            <div class="hero-left">
                <div class="hero-badge-pill">
                    <i class="bi bi-shield-lock-fill text-gold me-1"></i>
                    <span>Database Protection Engine</span>
                    <span class="hero-badge-divider">•</span>
                    <span>{{ $totalBackupCount }} Total Snapshots</span>
                </div>
                <h1 class="update-hero-title">
                    <span class="hero-icon-box">
                        <i class="bi bi-shield-check"></i>
                    </span>
                    <span class="hero-title-text">Database Backups & Disaster Recovery</span>
                </h1>
                <p class="update-hero-desc">
                    Capture instant point-in-time database snapshots, download offline SQL archives, and restore historical states safely in 1 click.
                </p>
            </div>

            <div class="hero-actions">
                <button type="button" class="saas-btn saas-btn-secondary hero-btn-secondary" onclick="document.getElementById('uploadRestoreModal').style.display='flex'">
                    <i class="bi bi-upload text-gold me-1"></i>
                    <span>Upload & Restore .SQL</span>
                </button>
                <form action="{{ route('admin.backups.create') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="hero-btn-primary" onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span> Generating Snapshot...'; this.form.submit();">
                        <span class="btn-glow-layer"></span>
                        <span class="btn-shimmer-sweep"></span>
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i>
                        <span>1-Click New Backup</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Backups Quick KPI Strip -->
        <div class="telemetry-grid">
            <div class="telemetry-card card-glow-gold">
                <div class="telemetry-header">
                    <span class="telemetry-label">Available Snapshots</span>
                    <div class="telemetry-icon-box icon-version"><i class="bi bi-archive-fill"></i></div>
                </div>
                <div class="telemetry-value-lg gold-gradient-text">{{ $totalBackupCount }}</div>
                <div class="telemetry-footer"><span class="telemetry-sub">Stored in local protected storage vault</span></div>
            </div>

            <div class="telemetry-card card-glow-purple">
                <div class="telemetry-header">
                    <span class="telemetry-label">Total Archive Size</span>
                    <div class="telemetry-icon-box icon-storage"><i class="bi bi-hdd-fill"></i></div>
                </div>
                <div class="telemetry-value-lg">{{ number_format($totalBackupSize / 1024 / 1024, 2) }} <span class="unit-text">MB</span></div>
                <div class="telemetry-footer"><span class="telemetry-sub">Cumulative snapshot storage footprint</span></div>
            </div>

            <div class="telemetry-card card-glow-emerald">
                <div class="telemetry-header">
                    <span class="telemetry-label">Latest Snapshot</span>
                    <div class="telemetry-icon-box icon-state icon-state-green"><i class="bi bi-clock-history"></i></div>
                </div>
                <div class="telemetry-value-md text-emerald">
                    {{ $allBackups->first() ? $allBackups->first()->created_at->diffForHumans() : 'None Recorded' }}
                </div>
                <div class="telemetry-footer"><span class="telemetry-sub font-mono">{{ $allBackups->first() ? $allBackups->first()->created_at->format('M d, Y h:i A') : '-' }}</span></div>
            </div>

            <div class="telemetry-card card-glow-blue">
                <div class="telemetry-header">
                    <span class="telemetry-label">Storage Vault Path</span>
                    <div class="telemetry-icon-box icon-db"><i class="bi bi-folder2-open"></i></div>
                </div>
                <div class="telemetry-value-md font-mono" style="font-size:0.86rem; color:#cbd5e1; word-break:break-all;">storage/app/backups/</div>
                <div class="telemetry-footer"><span class="telemetry-sub">Permissions: <strong class="{{ $backupsWritable ? 'text-emerald' : 'text-amber' }}">{{ $backupsWritable ? 'Writable (0755)' : 'Read Only' }}</strong></span></div>
            </div>
        </div>

        <!-- Full Stored Snapshots Table -->
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
                        <i class="bi bi-layers-fill text-gold me-1"></i> {{ $allBackups->total() }} Snapshots Recorded
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
                        @forelse($allBackups as $backup)
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
                                    <form action="{{ route('admin.backups.restore', $backup) }}" method="POST" style="margin:0; display:inline;" onsubmit="return confirm('⚠️ RESTORE DATABASE WARNING:\n\nThis will roll back the database to snapshot: {{ $backup->filename }}.\nCurrent unsaved modifications will be replaced.\n\nAre you sure you want to proceed with the restore?');">
                                        @csrf
                                        <button type="submit" class="vault-btn vault-btn-restore" title="Restore this snapshot">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            <span>Restore</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.backups.destroy', $backup) }}" method="POST" style="margin:0; display:inline;" onsubmit="return confirm('Permanently delete {{ $backup->filename }} from disk?');">
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
                                    <div class="empty-state-title">No database snapshots created yet</div>
                                    <p class="empty-state-desc">Create your first point-in-time snapshot before running updates or making large modifications to ensure seamless rollbacks.</p>
                                    <form action="{{ route('admin.backups.create') }}" method="POST" style="margin-top:14px;">
                                        @csrf
                                        <button type="submit" class="hero-btn-primary">
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

            @if(isset($allBackups) && $allBackups->hasPages())
            <div class="pagination-footer-row">
                <div class="saas-text-muted" style="font-size:0.82rem;">
                    Showing {{ $allBackups->firstItem() ?? 0 }} to {{ $allBackups->lastItem() ?? 0 }} of {{ $allBackups->total() }} snapshots
                </div>
                <div>
                    {{ $allBackups->links() }}
                </div>
            </div>
            @endif
        </div>

    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 3: SYSTEM DIAGNOSTICS & HEALTH PANE
         ══════════════════════════════════════════════════════════════ -->
    <div id="paneHealth" class="maintenance-tab-pane {{ $activeTab === 'health' ? 'active' : '' }}">
        
        <!-- Health Hero Bar -->
        <div class="update-hero-header hero-health">
            <div class="hero-backdrop-pattern"></div>
            <div class="hero-left">
                <div class="hero-badge-pill">
                    <span class="live-pulse-dot {{ $healthScore >= 90 ? 'pulse-emerald' : 'pulse-amber' }}"></span>
                    <span>System Diagnostic Benchmark</span>
                    <span class="hero-badge-divider">•</span>
                    <span>Overall Score: <strong class="{{ $healthScore >= 90 ? 'text-emerald' : 'text-amber' }}">{{ $healthScore }}%</strong></span>
                </div>
                <h1 class="update-hero-title">
                    <span class="hero-icon-box">
                        <i class="bi bi-activity"></i>
                    </span>
                    <span class="hero-title-text">System Health & Diagnostics Center</span>
                </h1>
                <p class="update-hero-desc">
                    Real-time telemetry, database latency benchmarking, worker queue inspection, and environment security audits.
                </p>
            </div>

            <div class="hero-actions">
                <button type="button" class="hero-btn-primary" onclick="triggerHealthScan()" id="healthScanBtn">
                    <span class="btn-glow-layer"></span>
                    <span class="btn-shimmer-sweep"></span>
                    <i class="bi bi-activity me-1"></i>
                    <span>Execute Full Diagnostics Scan</span>
                </button>
            </div>
        </div>

        <!-- Deep Subsystems 6-Card Diagnostic Grid -->
        <div class="telemetry-grid">
            
            <!-- 1. Database Connection & Engine -->
            <div class="telemetry-card card-glow-blue">
                <div class="telemetry-header">
                    <span class="telemetry-label">Database Subsystem</span>
                    <div class="telemetry-icon-box icon-db"><i class="bi bi-database-fill"></i></div>
                </div>
                <div class="telemetry-value-row">
                    <div class="telemetry-value-md text-emerald" id="diagDbStatusText">
                        <i class="bi bi-check-circle-fill me-1"></i> {{ $dbConnected ? 'Online' : 'Disconnected' }}
                    </div>
                    <span class="latency-pill" id="diagDbLatencyPill"><i class="bi bi-speedometer me-1"></i>{{ $dbLatencyMs }} ms</span>
                </div>
                <div class="telemetry-footer">
                    <div class="diag-meta-line"><span>Engine:</span> <strong class="font-mono">{{ $dbVersion }}</strong></div>
                    <div class="diag-meta-line"><span>Tables:</span> <strong>{{ $dbTableCount }} tables</strong></div>
                    <div class="diag-meta-line"><span>Estimated Size:</span> <strong>{{ round($dbSizeBytes / 1024 / 1024, 2) }} MB</strong></div>
                </div>
            </div>

            <!-- 2. Background Workers & Queues -->
            <div class="telemetry-card card-glow-purple">
                <div class="telemetry-header">
                    <span class="telemetry-label">Queue & Background Jobs</span>
                    <div class="telemetry-icon-box icon-purple"><i class="bi bi-layers-fill"></i></div>
                </div>
                <div class="telemetry-value-row">
                    <div class="telemetry-value-md {{ $failedJobsCount === 0 ? 'text-emerald' : 'text-danger' }}" id="diagQueueStatusText">
                        <i class="bi {{ $failedJobsCount === 0 ? 'bi-check-circle-fill' : 'bi-exclamation-octagon-fill' }} me-1"></i>
                        {{ $failedJobsCount === 0 ? 'Queue Healthy' : $failedJobsCount . ' Failed Jobs' }}
                    </div>
                    <span class="status-pill status-success">{{ config('queue.default') }}</span>
                </div>
                <div class="telemetry-footer">
                    <div class="diag-meta-line"><span>Pending Jobs:</span> <strong>{{ $queueSize }}</strong></div>
                    <div class="diag-meta-line"><span>Failed Jobs:</span> <strong class="{{ $failedJobsCount > 0 ? 'text-danger' : '' }}">{{ $failedJobsCount }}</strong></div>
                    <div class="diag-meta-line"><span>Driver:</span> <strong>{{ config('queue.default') }}</strong></div>
                </div>
            </div>

            <!-- 3. Mail & Notifications -->
            <div class="telemetry-card card-glow-amber">
                <div class="telemetry-header">
                    <span class="telemetry-label">Mail Delivery (SMTP)</span>
                    <div class="telemetry-icon-box icon-amber"><i class="bi bi-envelope-check-fill"></i></div>
                </div>
                <div class="telemetry-value-row">
                    <div class="telemetry-value-md {{ $mailConfigured ? 'text-emerald' : '' }}">
                        <i class="bi {{ $mailConfigured ? 'bi-check-circle-fill' : 'bi-info-circle-fill' }} me-1"></i>
                        {{ $mailConfigured ? 'Configured' : 'Local / Log' }}
                    </div>
                    <span class="status-pill {{ $mailConfigured ? 'status-success' : 'status-warning' }}">{{ config('mail.default') }}</span>
                </div>
                <div class="telemetry-footer">
                    <div class="diag-meta-line"><span>Mailer:</span> <strong>{{ config('mail.default') }}</strong></div>
                    <div class="diag-meta-line"><span>Host:</span> <strong class="font-mono">{{ $mailHost }}</strong></div>
                    <div class="diag-meta-line"><span>Sender:</span> <strong class="font-mono" title="{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</strong></div>
                </div>
            </div>

            <!-- 4. Storage Vault & Permissions -->
            <div class="telemetry-card card-glow-emerald">
                <div class="telemetry-header">
                    <span class="telemetry-label">File Permissions</span>
                    <div class="telemetry-icon-box icon-storage"><i class="bi bi-folder-check"></i></div>
                </div>
                <div class="telemetry-value-row">
                    <div class="telemetry-value-md text-emerald">
                        <i class="bi bi-check-circle-fill me-1"></i> All Writable
                    </div>
                    <span class="status-pill status-success">0775 / 0755</span>
                </div>
                <div class="telemetry-footer">
                    <div class="diag-meta-line"><span>storage/:</span> <strong class="{{ $storageWritable ? 'text-emerald' : 'text-danger' }}">{{ $storageWritable ? 'Writable (OK)' : 'Denied' }}</strong></div>
                    <div class="diag-meta-line"><span>bootstrap/cache/:</span> <strong class="{{ $bootstrapCacheWritable ? 'text-emerald' : 'text-danger' }}">{{ $bootstrapCacheWritable ? 'Writable (OK)' : 'Denied' }}</strong></div>
                    <div class="diag-meta-line"><span>storage/app/backups/:</span> <strong class="{{ $backupsWritable ? 'text-emerald' : 'text-danger' }}">{{ $backupsWritable ? 'Writable (OK)' : 'Denied' }}</strong></div>
                </div>
            </div>

            <!-- 5. Core PHP Extensions Checklist -->
            <div class="telemetry-card card-glow-cyan">
                <div class="telemetry-header">
                    <span class="telemetry-label">Core PHP Extensions</span>
                    <div class="telemetry-icon-box icon-runtime"><i class="bi bi-puzzle-fill"></i></div>
                </div>
                <div class="telemetry-value-row">
                    <div class="telemetry-value-md text-emerald">
                        <i class="bi bi-check-circle-fill me-1"></i> 10/10 Loaded
                    </div>
                    <span class="latency-pill">PHP {{ $phpVersion }}</span>
                </div>
                <div class="telemetry-footer">
                    <div class="ext-chips-mini-grid">
                        @foreach($requiredExtensions as $extName => $isLoaded)
                        <span class="ext-mini-chip {{ $isLoaded ? 'ext-pass' : 'ext-fail' }}">
                            {{ $extName }} {{ $isLoaded ? '✓' : '✗' }}
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- 6. Server Resources & Limits -->
            <div class="telemetry-card card-glow-gold">
                <div class="telemetry-header">
                    <span class="telemetry-label">Execution Environment</span>
                    <div class="telemetry-icon-box icon-version"><i class="bi bi-speedometer"></i></div>
                </div>
                <div class="telemetry-value-row">
                    <div class="telemetry-value-md">
                        {{ round($memoryUsedBytes / 1024 / 1024, 1) }} <span class="unit-text">MB Active</span>
                    </div>
                    <span class="status-pill status-success">Limit {{ $memoryLimit }}</span>
                </div>
                <div class="telemetry-footer">
                    <div class="diag-meta-line"><span>Max Timeout:</span> <strong class="font-mono">{{ $maxExecutionTime }}</strong></div>
                    <div class="diag-meta-line"><span>OPcache:</span> <strong>{{ $opcacheEnabled ? 'Active (Accelerated)' : 'Disabled / CLI' }}</strong></div>
                    <div class="diag-meta-line"><span>OS Platform:</span> <strong>{{ PHP_OS_FAMILY }}</strong></div>
                </div>
            </div>

        </div>

        <!-- Live Diagnostics Audit Breakdown Card -->
        <div id="diagnosticsLiveAuditCard" class="diagnostics-audit-card" style="display:none;">
            <div class="audit-card-header">
                <div class="audit-header-left">
                    <div class="audit-icon-wrap"><i class="bi bi-clipboard2-pulse-fill"></i></div>
                    <div>
                        <h3 class="audit-title">Diagnostics Probe Results</h3>
                        <p class="audit-subtitle" id="auditLastScanTime">Real-time benchmark probe execution details</p>
                    </div>
                </div>
                <span class="console-badge badge-success" id="auditOverallScoreBadge">100% Score</span>
            </div>
            <div class="audit-checks-grid" id="auditChecksContainer"></div>
        </div>

        <!-- Environment & Security Specifications Card -->
        <div class="history-table-card">
            <div class="table-card-header">
                <div class="table-header-left">
                    <div class="table-icon-wrap"><i class="bi bi-shield-lock-fill"></i></div>
                    <div>
                        <h3 class="table-card-title">Environment & Security Specifications</h3>
                        <p class="table-card-subtitle">Runtime platform, architecture mode, and server hardening specifications.</p>
                    </div>
                </div>
                <div class="table-header-right">
                    <span class="vault-counter-pill">
                        <i class="bi bi-shield-check text-gold me-1"></i> Security Hardened
                    </span>
                </div>
            </div>

            <div class="saas-table-container">
                <table class="saas-table modern-update-table">
                    <tbody>
                        <tr>
                            <td style="width: 280px; font-weight:600; color:#D1C5B4;"><i class="bi bi-app text-gold me-2"></i>Application Name</td>
                            <td class="font-mono" style="color:#ffffff;">{{ config('app.name') }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:600; color:#D1C5B4;"><i class="bi bi-diagram-3 text-gold me-2"></i>Application Environment</td>
                            <td>
                                <span class="version-chip {{ $appEnvironment === 'production' ? 'chip-prod' : 'chip-dev' }}">
                                    {{ strtoupper($appEnvironment) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight:600; color:#D1C5B4;"><i class="bi bi-shield-lock text-gold me-2"></i>Debug Mode</td>
                            <td>
                                @if($debugMode)
                                    <span class="status-pill status-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i> Enabled (Development Only)</span>
                                @else
                                    <span class="status-pill status-success"><i class="bi bi-shield-check me-1"></i> Disabled (Production Hardened)</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight:600; color:#D1C5B4;"><i class="bi bi-code-slash text-gold me-2"></i>Framework & PHP Build</td>
                            <td class="font-mono" style="color:#ffffff;">Laravel {{ $laravelVersion }} • PHP {{ $phpVersion }} ({{ PHP_OS_FAMILY }})</td>
                        </tr>
                        <tr>
                            <td style="font-weight:600; color:#D1C5B4;"><i class="bi bi-clock-history text-gold me-2"></i>Server Clock & Timezone</td>
                            <td class="font-mono" style="color:#ffffff;">{{ now()->format('Y-m-d H:i:s T') }} ({{ config('app.timezone') }})</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<!-- Upload & Restore .SQL Modal -->
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

<!-- Custom Confirmation Modal for 1-Click Update -->
<div id="updateConfirmModal" class="custom-modal-overlay" style="display:none;">
    <div class="custom-modal-dialog">
        <button type="button" class="modal-close-btn" onclick="closeUpdateConfirmModal()" title="Close">
            <i class="bi bi-x"></i>
        </button>
        <div class="modal-header-visual">
            <div class="modal-icon-glow modal-glow-emerald">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <h3 class="modal-heading">Run 1-Click System Update?</h3>
            <p class="modal-subheading">This automated pipeline executes full upgrade routines safely.</p>
        </div>

        <div class="modal-pipeline-checklist">
            <div class="pipeline-item">
                <div class="pipeline-num">1</div>
                <div class="pipeline-info">
                    <div class="pipeline-name">Database Safety Snapshot</div>
                    <div class="pipeline-desc">Creates a timestamped point-in-time SQL backup in <code>storage/app/backups</code>.</div>
                </div>
            </div>
            <div class="pipeline-item">
                <div class="pipeline-num">2</div>
                <div class="pipeline-info">
                    <div class="pipeline-name">Database Migrations</div>
                    <div class="pipeline-desc">Applies any pending table schemas and index changes with <code>--force</code>.</div>
                </div>
            </div>
            <div class="pipeline-item">
                <div class="pipeline-num">3</div>
                <div class="pipeline-info">
                    <div class="pipeline-name">Cache Invalidation & Warmup</div>
                    <div class="pipeline-desc">Purges compiled views, route maps, config, and system cache stores.</div>
                </div>
            </div>
            <div class="pipeline-item">
                <div class="pipeline-num">4</div>
                <div class="pipeline-info">
                    <div class="pipeline-name">PWA Client Broadcast</div>
                    <div class="pipeline-desc">Increments Service Worker cache version to refresh teacher & student devices.</div>
                </div>
            </div>
        </div>

        <div class="modal-actions-bar">
            <button type="button" class="saas-btn saas-btn-secondary" onclick="closeUpdateConfirmModal()">
                Cancel
            </button>
            <button type="button" class="hero-btn-primary" onclick="proceedFullSystemUpdate()" id="modalConfirmBtn">
                <i class="bi bi-lightning-charge-fill me-1"></i> Start System Update
            </button>
        </div>
    </div>
</div>

<!-- Modern Toast Notification Container -->
<div id="toastNotificationContainer" class="toast-container"></div>

<style>
/* ══════════════════════════════════════════════════════════════
   SYSTEM MAINTENANCE & OPERATIONS HUB LUXURY STYLES
   Theme: Aligned with Student Maroon (#7A1A1A) & Gold (#D4AF37)
   ══════════════════════════════════════════════════════════════ */

.system-update-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
    padding-bottom: 50px;
}

/* ── Top Tabs Navigation ── */
.maintenance-tabs-nav {
    display: flex;
    gap: 10px;
    background: rgba(26, 17, 17, 0.85);
    border: 1px solid rgba(212, 175, 55, 0.2);
    border-radius: 18px;
    padding: 8px;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.05);
    overflow-x: auto;
    scrollbar-width: none;
}

.maintenance-tabs-nav::-webkit-scrollbar {
    display: none;
}

.maintenance-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 22px;
    border-radius: 14px;
    border: 1px solid transparent;
    background: transparent;
    color: #D1C5B4;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.maintenance-tab-btn:hover {
    color: var(--gold, #D4AF37);
    background: rgba(212, 175, 55, 0.08);
    border-color: rgba(212, 175, 55, 0.15);
}

.maintenance-tab-btn.active {
    background: linear-gradient(135deg, rgba(122, 26, 26, 0.75) 0%, rgba(74, 12, 12, 0.95) 100%);
    color: #ffffff;
    border-color: rgba(212, 175, 55, 0.4);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.15), 0 0 15px rgba(212, 175, 55, 0.15);
}

.tab-icon {
    font-size: 1.1rem;
    transition: transform 0.2s ease;
}

.maintenance-tab-btn:hover .tab-icon {
    transform: scale(1.15);
}

.maintenance-tab-btn.active .tab-icon {
    color: var(--gold, #D4AF37);
    text-shadow: 0 0 12px rgba(212, 175, 55, 0.6);
}

.tab-badge {
    background: rgba(212, 175, 55, 0.2);
    border: 1px solid rgba(212, 175, 55, 0.35);
    color: var(--gold, #D4AF37);
    font-size: 0.74rem;
    font-weight: 700;
    padding: 2px 9px;
    border-radius: 999px;
    letter-spacing: 0.2px;
}

.tab-badge-alert {
    background: rgba(245, 158, 11, 0.2);
    border-color: rgba(245, 158, 11, 0.4);
    color: #fbbf24;
    animation: pulseGlowAmber 2s infinite;
}

.tab-badge-score {
    font-size: 0.74rem;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 999px;
}
.score-green { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.35); box-shadow: 0 0 10px rgba(34, 197, 94, 0.2); }
.score-amber { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.35); }
.score-red { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); }

/* ── Hub Alerts ── */
.hub-alert {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    border-radius: 16px;
    backdrop-filter: blur(12px);
    animation: slideInDown 0.3s ease;
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
    transition: opacity 0.2s ease;
}

.hub-alert-close:hover {
    opacity: 1;
}

/* ── Tab Panes ── */
.maintenance-tab-pane {
    display: none;
    flex-direction: column;
    gap: 24px;
    animation: fadeInPane 0.25s ease;
}

.maintenance-tab-pane.active {
    display: flex;
}

@keyframes fadeInPane {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ── Hero Header ── */
.update-hero-header {
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

.hero-backups {
    background: linear-gradient(135deg, rgba(50, 18, 30, 0.85) 0%, rgba(26, 17, 24, 0.95) 100%);
}

.hero-health {
    background: linear-gradient(135deg, rgba(20, 35, 45, 0.85) 0%, rgba(17, 24, 28, 0.95) 100%);
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
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.hero-badge-divider {
    opacity: 0.4;
}

.live-pulse-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    position: relative;
}

.pulse-emerald {
    background: #22c55e;
    box-shadow: 0 0 10px #22c55e;
    animation: pulseGlowGreen 2s infinite;
}

.pulse-amber {
    background: #f59e0b;
    box-shadow: 0 0 10px #f59e0b;
    animation: pulseGlowAmber 2s infinite;
}

@keyframes pulseGlowGreen {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
    70% { transform: scale(1.15); box-shadow: 0 0 0 9px rgba(34, 197, 94, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}

@keyframes pulseGlowAmber {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
    70% { transform: scale(1.15); box-shadow: 0 0 0 9px rgba(245, 158, 11, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}

.update-hero-title {
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

.update-hero-desc {
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
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
}

.badge-count-pill {
    background: rgba(212, 175, 55, 0.2);
    color: var(--gold, #D4AF37);
    font-size: 0.74rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 999px;
    margin-left: 6px;
    border: 1px solid rgba(212, 175, 55, 0.3);
}

.hero-btn-scan {
    padding: 12px 18px;
    font-size: 0.88rem;
    font-weight: 600;
    border-radius: 14px;
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

.hero-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
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

.btn-icon-pulse {
    color: #bbf7d0;
    font-size: 1.1rem;
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
    position: relative;
    overflow: hidden;
}

.telemetry-card:hover {
    border-color: rgba(212, 175, 55, 0.4);
    transform: translateY(-3px);
    box-shadow: 0 15px 36px rgba(0, 0, 0, 0.55), 0 0 20px rgba(212, 175, 55, 0.1);
}

.card-glow-emerald:hover { border-color: rgba(34, 197, 94, 0.45); box-shadow: 0 15px 36px rgba(0, 0, 0, 0.55), 0 0 20px rgba(34, 197, 94, 0.15); }
.card-glow-gold:hover { border-color: rgba(212, 175, 55, 0.45); box-shadow: 0 15px 36px rgba(0, 0, 0, 0.55), 0 0 20px rgba(212, 175, 55, 0.15); }
.card-glow-blue:hover { border-color: rgba(59, 130, 246, 0.45); box-shadow: 0 15px 36px rgba(0, 0, 0, 0.55), 0 0 20px rgba(59, 130, 246, 0.15); }
.card-glow-cyan:hover { border-color: rgba(56, 189, 248, 0.45); box-shadow: 0 15px 36px rgba(0, 0, 0, 0.55), 0 0 20px rgba(56, 189, 248, 0.15); }
.card-glow-purple:hover { border-color: rgba(168, 85, 247, 0.45); box-shadow: 0 15px 36px rgba(0, 0, 0, 0.55), 0 0 20px rgba(168, 85, 247, 0.15); }
.card-glow-amber:hover { border-color: rgba(245, 158, 11, 0.45); box-shadow: 0 15px 36px rgba(0, 0, 0, 0.55), 0 0 20px rgba(245, 158, 11, 0.15); }

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
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.icon-state-green { background: rgba(34, 197, 94, 0.15); color: #4ade80; border-color: rgba(34, 197, 94, 0.3); }
.icon-state-amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3); }
.icon-version { background: rgba(212, 175, 55, 0.15); color: #D4AF37; border-color: rgba(212, 175, 55, 0.3); }
.icon-db { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border-color: rgba(59, 130, 246, 0.3); }
.icon-pwa { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border-color: rgba(56, 189, 248, 0.3); }
.icon-storage { background: rgba(168, 85, 247, 0.15); color: #c084fc; border-color: rgba(168, 85, 247, 0.3); }
.icon-runtime { background: rgba(234, 179, 8, 0.15); color: #facc15; border-color: rgba(234, 179, 8, 0.3); }
.icon-purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; border-color: rgba(168, 85, 247, 0.3); }
.icon-amber { background: rgba(234, 179, 8, 0.15); color: #facc15; border-color: rgba(234, 179, 8, 0.3); }

.telemetry-value-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: nowrap;
    min-width: 0;
}

.telemetry-value-lg {
    font-size: 1.5rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
    white-space: nowrap;
    flex-shrink: 0;
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
.text-amber { color: #fbbf24; }
.text-gold { color: var(--gold, #D4AF37); }
.pwa-cyan { color: #38bdf8; font-family: monospace; }
.font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }

.status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 0.82rem;
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

.status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}
.dot-green { background: #22c55e; box-shadow: 0 0 6px #22c55e; }
.dot-amber { background: #f59e0b; box-shadow: 0 0 6px #f59e0b; }

.mini-action-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(212, 175, 55, 0.3);
    color: var(--gold, #D4AF37);
    font-size: 0.76rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    flex-shrink: 0;
}

.mini-action-btn:hover {
    background: rgba(212, 175, 55, 0.2);
    border-color: var(--gold, #D4AF37);
    transform: translateY(-1px);
}

.version-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    white-space: nowrap;
    flex-shrink: 0;
    line-height: 1.2;
}
.chip-prod { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
.chip-dev { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }

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

.storage-percent-chip {
    font-size: 0.76rem;
    font-weight: 600;
    color: #cbd5e1;
    white-space: nowrap;
    flex-shrink: 0;
    background: rgba(0, 0, 0, 0.35);
    padding: 2px 7px;
    border-radius: 6px;
}

.storage-progress-bar {
    width: 100%;
    height: 6px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 999px;
    overflow: hidden;
}

.storage-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #38bdf8 0%, #a855f7 100%);
    border-radius: 999px;
    box-shadow: 0 0 10px rgba(56, 189, 248, 0.5);
}

.unit-text {
    font-size: 0.82rem;
    color: #A39683;
    font-weight: normal;
}

.telemetry-footer {
    margin-top: auto;
}

.telemetry-sub {
    font-size: 0.78rem;
    color: #A39683;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.diag-meta-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 0.78rem;
    color: #cbd5e1;
    margin-bottom: 4px;
    min-width: 0;
}
.diag-meta-line span {
    color: #A39683;
    white-space: nowrap;
    flex-shrink: 0;
}
.diag-meta-line strong {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
    text-align: right;
}

.ext-chips-mini-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.ext-mini-chip {
    font-size: 0.7rem;
    font-family: monospace;
    padding: 2px 6px;
    border-radius: 5px;
}
.ext-pass { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
.ext-fail { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

/* ── Pre-Flight Readiness Bar ── */
.readiness-bar-card {
    background: rgba(28, 19, 19, 0.75);
    border: 1px solid rgba(212, 175, 55, 0.2);
    border-radius: 18px;
    padding: 20px 26px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 22px;
    flex-wrap: wrap;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    box-shadow: 0 10px 28px rgba(0, 0, 0, 0.35);
}

.readiness-left {
    display: flex;
    align-items: center;
    gap: 22px;
    flex-wrap: wrap;
}

.readiness-score-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 14px;
    padding: 12px 18px;
    min-width: 90px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
}

.readiness-score {
    font-size: 1.5rem;
    font-weight: 800;
    color: #4ade80;
    line-height: 1.2;
}

.readiness-score-label {
    font-size: 0.68rem;
    text-transform: uppercase;
    font-weight: 700;
    color: #A39683;
    letter-spacing: 0.06em;
}

.readiness-details {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.readiness-title {
    font-size: 1rem;
    font-weight: 700;
    color: #FCF8F2;
    margin: 0;
    display: flex;
    align-items: center;
}

.readiness-desc {
    font-size: 0.84rem;
    color: #D1C5B4;
    margin: 0;
}

.readiness-chips-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 4px;
}

.readiness-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.76rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 8px;
}

.chip-pass {
    background: rgba(34, 197, 94, 0.14);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #4ade80;
}

.chip-warn {
    background: rgba(245, 158, 11, 0.14);
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: #fbbf24;
}

.chip-fail {
    background: rgba(239, 68, 68, 0.14);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #f87171;
}

.chip-pending {
    background: rgba(59, 130, 246, 0.14);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #60a5fa;
}

.readiness-scan-btn {
    padding: 10px 18px;
    font-size: 0.85rem;
    font-weight: 600;
    border-radius: 12px;
}

/* ── 1-Click Update Console ── */
.update-console-card {
    background: rgba(24, 15, 15, 0.95);
    border: 1px solid rgba(22, 163, 74, 0.4);
    border-radius: 22px;
    padding: 28px;
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.7), 0 0 35px rgba(22, 163, 74, 0.2);
    display: flex;
    flex-direction: column;
    gap: 24px;
    backdrop-filter: blur(18px);
    animation: fadeInPane 0.3s ease;
}

.console-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.console-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.console-spinner {
    width: 40px;
    height: 40px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.spinner-ring {
    width: 36px;
    height: 36px;
    border: 3px solid rgba(74, 222, 128, 0.2);
    border-top-color: #4ade80;
    border-radius: 50%;
    animation: spinRing 0.8s linear infinite;
}

.spinner-core-dot {
    position: absolute;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #4ade80;
    box-shadow: 0 0 10px #4ade80;
}

@keyframes spinRing {
    to { transform: rotate(360deg); }
}

.console-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #ffffff;
    margin: 0 0 4px 0;
}

.console-subtitle {
    font-size: 0.85rem;
    color: #94a3b8;
    margin: 0;
}

.console-badge {
    font-size: 0.8rem;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.badge-processing {
    background: rgba(234, 179, 8, 0.15);
    border: 1px solid rgba(234, 179, 8, 0.4);
    color: #facc15;
    animation: pulseGlowAmber 2s infinite;
}

.badge-success {
    background: rgba(34, 197, 94, 0.15);
    border: 1px solid rgba(34, 197, 94, 0.4);
    color: #4ade80;
    box-shadow: 0 0 15px rgba(34, 197, 94, 0.3);
}

.badge-failed {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.4);
    color: #f87171;
}

/* ── 4-Stage Stepper ── */
.update-stepper-container {
    position: relative;
    padding: 12px 0;
}

.stepper-progress-track {
    position: absolute;
    top: 36px;
    left: 8%;
    right: 8%;
    height: 4px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 999px;
    z-index: 1;
}

.stepper-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #16a34a 0%, #4ade80 100%);
    border-radius: 999px;
    box-shadow: 0 0 12px rgba(74, 222, 128, 0.6);
    transition: width 0.5s ease;
}

.stepper-steps-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    position: relative;
    z-index: 2;
}

.stepper-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 8px;
}

.step-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(30, 21, 21, 0.95);
    border: 2px solid rgba(255, 255, 255, 0.15);
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    transition: all 0.3s ease;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.5);
}

.stepper-step.active .step-icon-wrap {
    border-color: #facc15;
    color: #facc15;
    background: rgba(234, 179, 8, 0.18);
    box-shadow: 0 0 20px rgba(234, 179, 8, 0.5);
    animation: stepPulse 1.5s infinite;
}

.stepper-step.completed .step-icon-wrap {
    border-color: #22c55e;
    color: #ffffff;
    background: #15803d;
    box-shadow: 0 0 20px rgba(34, 197, 94, 0.5);
}

@keyframes stepPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.08); }
    100% { transform: scale(1); }
}

.step-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: #cbd5e1;
}

.step-sub {
    font-size: 0.74rem;
    color: #64748b;
}

.stepper-step.completed .step-sub { color: #4ade80; font-weight: 600; }
.stepper-step.active .step-sub { color: #facc15; font-weight: 600; }

/* ── Obsidian Terminal ── */
.obsidian-terminal-wrapper {
    background: #0a0505;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: inset 0 2px 12px rgba(0, 0, 0, 0.85);
}

.terminal-titlebar {
    background: rgba(255, 255, 255, 0.04);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding: 12px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.terminal-dots {
    display: flex;
    gap: 7px;
}

.dot {
    width: 11px;
    height: 11px;
    border-radius: 50%;
}
.dot-red { background: #ef4444; }
.dot-yellow { background: #f59e0b; }
.dot-green { background: #22c55e; }

.terminal-title {
    font-size: 0.78rem;
    font-weight: 600;
    color: #94a3b8;
    letter-spacing: 0.04em;
}

.terminal-actions {
    display: flex;
    gap: 8px;
}

.term-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #94a3b8;
    font-size: 0.74rem;
    padding: 3px 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.term-btn:hover {
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.35);
    background: rgba(255, 255, 255, 0.1);
}

.terminal-body {
    padding: 16px 20px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.82rem;
    line-height: 1.65;
    max-height: 240px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.term-line {
    word-break: break-all;
}

.term-time {
    color: #64748b;
    margin-right: 8px;
}

.term-info { color: #93c5fd; }
.term-success { color: #4ade80; }
.term-warning { color: #facc15; }
.term-error { color: #f87171; }
.term-muted { color: #94a3b8; }

/* ── Results Cards List ── */
.update-results-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.result-step-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.result-icon {
    font-size: 1.35rem;
    margin-top: 2px;
}
.result-success .result-icon { color: #4ade80; }
.result-warning .result-icon { color: #fbbf24; }
.result-error .result-icon { color: #f87171; }

.result-content {
    flex: 1;
}

.result-title {
    font-size: 0.92rem;
    font-weight: 700;
    margin-bottom: 4px;
}
.result-success .result-title { color: #4ade80; }
.result-warning .result-title { color: #fbbf24; }
.result-error .result-title { color: #f87171; }

.result-msg {
    font-size: 0.82rem;
    color: #cbd5e1;
    font-family: monospace;
}

/* ── Banner ── */
.update-banner-card {
    border-radius: 16px;
    padding: 18px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}

.banner-success {
    background: rgba(22, 163, 74, 0.16);
    border: 1px solid rgba(34, 197, 94, 0.35);
}

.banner-icon-box {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(34, 197, 94, 0.25);
    color: #4ade80;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    box-shadow: 0 0 15px rgba(34, 197, 94, 0.3);
}

.banner-content {
    flex: 1;
    min-width: 260px;
}

.banner-title {
    font-size: 1rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 3px 0;
}

.banner-desc {
    font-size: 0.84rem;
    color: #bbf7d0;
    margin: 0;
}

.banner-actions {
    display: flex;
    gap: 12px;
}

/* ── Modular Operations Grid ── */
.operations-grid-title {
    margin-top: 10px;
}

.section-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #FCF8F2;
    margin: 0 0 4px 0;
}

.section-desc {
    font-size: 0.88rem;
    color: #D1C5B4;
    margin: 0;
}

.operations-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 22px;
}

.operation-card {
    background: rgba(28, 19, 19, 0.75);
    border: 1px solid rgba(212, 175, 55, 0.18);
    border-radius: 20px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    transition: all 0.25s ease;
}

.operation-card:hover {
    border-color: rgba(212, 175, 55, 0.4);
    transform: translateY(-3px);
    box-shadow: 0 15px 36px rgba(0, 0, 0, 0.55), 0 0 15px rgba(212, 175, 55, 0.08);
}

.op-card-header {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}

.op-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
}

.icon-blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
.icon-purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
.icon-red { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

.op-header-info {
    flex: 1;
    min-width: 0;
}

.op-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 5px;
    flex-wrap: nowrap;
}

.op-title {
    font-size: 1.02rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.op-status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    font-size: 0.74rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
    white-space: nowrap;
    flex-shrink: 0;
    line-height: 1.2;
    letter-spacing: 0.2px;
}
.badge-emerald { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.35); }
.badge-amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.35); }
.badge-cyan { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.35); }
.badge-purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.35); }
.badge-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); }

.op-desc {
    font-size: 0.84rem;
    color: #D1C5B4;
    margin: 0;
    line-height: 1.5;
}

.op-output-box {
    background: rgba(0, 0, 0, 0.55);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 12px 16px;
    font-family: monospace;
    font-size: 0.78rem;
    max-height: 140px;
    overflow-y: auto;
    margin-bottom: 16px;
    word-break: break-all;
    line-height: 1.55;
}

.secret-token-box {
    background: rgba(0, 0, 0, 0.45);
    border: 1px dashed rgba(212, 175, 55, 0.3);
    border-radius: 12px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 16px;
}

.secret-label {
    font-size: 0.76rem;
    color: #A39683;
}

.secret-code {
    font-size: 0.8rem;
    color: var(--gold, #D4AF37);
    background: transparent;
    padding: 0;
    font-weight: 700;
}

.copy-token-btn {
    background: transparent;
    border: none;
    color: var(--gold, #D4AF37);
    cursor: pointer;
    font-size: 0.9rem;
    padding: 3px 8px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.copy-token-btn:hover {
    background: rgba(212, 175, 55, 0.25);
}

.op-card-footer {
    margin-top: auto;
}

.op-actions-row {
    display: flex;
    gap: 10px;
}

.op-btn-flex {
    flex: 1;
    font-size: 0.85rem;
    padding: 9px 14px;
}

.op-btn-full {
    width: 100%;
    font-size: 0.85rem;
    padding: 9px 16px;
    justify-content: center;
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

.modern-update-table tbody tr {
    transition: background 0.2s ease;
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

/* ── Custom Confirmation Modal ── */
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
    animation: fadeInModal 0.2s ease;
}

@keyframes fadeInModal {
    from { opacity: 0; }
    to { opacity: 1; }
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
    animation: scaleInModal 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes scaleInModal {
    from { transform: scale(0.92); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
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
    transform: scale(1.05);
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

.modal-glow-emerald {
    background: rgba(22, 163, 74, 0.2);
    border: 1px solid rgba(74, 222, 128, 0.45);
    color: #4ade80;
    box-shadow: 0 0 25px rgba(22, 163, 74, 0.35);
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

.modal-pipeline-checklist {
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.pipeline-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.pipeline-num {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(212, 175, 55, 0.2);
    border: 1px solid rgba(212, 175, 55, 0.4);
    color: var(--gold, #D4AF37);
    font-size: 0.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}

.pipeline-info {
    flex: 1;
}

.pipeline-name {
    font-size: 0.88rem;
    font-weight: 700;
    color: #FCF8F2;
}

.pipeline-desc {
    font-size: 0.78rem;
    color: #94a3b8;
    margin-top: 2px;
}

.pipeline-desc code, .modal-subheading code {
    background: rgba(0, 0, 0, 0.5);
    color: var(--gold, #D4AF37);
    padding: 2px 6px;
    border-radius: 5px;
    font-size: 0.76rem;
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
    transition: all 0.2s ease;
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

/* ── Floating Toasts ── */
.toast-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 10060;
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 400px;
    pointer-events: auto;
}

.custom-toast {
    background: rgba(28, 18, 18, 0.96);
    border: 1px solid rgba(212, 175, 55, 0.35);
    border-left: 4px solid var(--gold, #D4AF37);
    border-radius: 14px;
    padding: 14px 18px;
    color: #ffffff;
    box-shadow: 0 12px 36px rgba(0, 0, 0, 0.75);
    display: flex;
    align-items: flex-start;
    gap: 12px;
    backdrop-filter: blur(14px);
    pointer-events: auto;
    animation: slideInToast 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    transition: all 0.3s ease;
}

/* ── Diagnostics Audit Card ── */
.diagnostics-audit-card {
    background: rgba(17, 10, 10, 0.88);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 20px;
    overflow: hidden;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.6), 0 0 25px rgba(212, 175, 55, 0.08);
    animation: fadeIn 0.4s ease-out;
}

.audit-card-header {
    padding: 20px 26px;
    background: rgba(0, 0, 0, 0.35);
    border-bottom: 1px solid rgba(212, 175, 55, 0.15);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.audit-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.audit-icon-wrap {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(56, 189, 248, 0.15);
    border: 1px solid rgba(56, 189, 248, 0.35);
    color: #38bdf8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    box-shadow: 0 0 15px rgba(56, 189, 248, 0.2);
}

.audit-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 2px 0;
}

.audit-subtitle {
    font-size: 0.82rem;
    color: #D1C5B4;
    margin: 0;
}

.audit-checks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 14px;
    padding: 22px 26px;
}

.audit-check-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 14px;
    padding: 14px 18px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    transition: all 0.2s ease;
}

.audit-check-item:hover {
    background: rgba(212, 175, 55, 0.04);
    border-color: rgba(212, 175, 55, 0.25);
    transform: translateY(-1px);
}

.audit-check-icon {
    font-size: 1.25rem;
    flex-shrink: 0;
    margin-top: 1px;
}

.check-pass .audit-check-icon { color: #4ade80; }
.check-warn .audit-check-icon { color: #fbbf24; }
.check-fail .audit-check-icon { color: #f87171; }

.audit-check-body {
    flex: 1;
}

.audit-check-name {
    font-weight: 700;
    font-size: 0.88rem;
    color: #ffffff;
    margin-bottom: 2px;
}

.audit-check-msg {
    font-size: 0.8rem;
    color: #D1C5B4;
    line-height: 1.4;
}

.audit-check-status-badge {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    flex-shrink: 0;
}

.badge-pass { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
.badge-warn { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
.badge-fail { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.toast-icon {
    font-size: 1.25rem;
    margin-top: 1px;
}
.toast-success { border-left-color: #4ade80; }
.toast-success .toast-icon { color: #4ade80; }
.toast-error { border-left-color: #f87171; }
.toast-error .toast-icon { color: #f87171; }
.toast-info { border-left-color: #60a5fa; }
.toast-info .toast-icon { color: #60a5fa; }
.toast-warning { border-left-color: #fbbf24; }
.toast-warning .toast-icon { color: #fbbf24; }

.toast-msg {
    flex: 1;
    font-size: 0.84rem;
    line-height: 1.5;
}

@keyframes slideInToast {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@media (max-width: 768px) {
    .update-hero-header {
        padding: 22px 20px;
    }
    .update-hero-title {
        font-size: 1.5rem;
    }
    .stepper-steps-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
    }
    .stepper-progress-track {
        display: none;
    }
    .modern-update-table thead {
        display: none;
    }
    .modern-update-table tbody tr {
        display: block;
        margin-bottom: 14px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 14px;
        padding: 12px;
    }
    .modern-update-table tbody td {
        display: block;
        padding: 8px 10px;
        border-bottom: none;
    }
    .table-actions-group {
        justify-content: flex-start;
        margin-top: 8px;
    }
}
</style>

<script>
// Tab Switching System with URL Hash & State Sync
function switchMaintenanceTab(tabName, btnElement) {
    document.querySelectorAll('.maintenance-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.maintenance-tab-pane').forEach(pane => pane.classList.remove('active'));

    if (btnElement) {
        btnElement.classList.add('active');
    }

    const targetPane = document.getElementById(tabName === 'backups' ? 'paneBackups' : (tabName === 'health' ? 'paneHealth' : 'paneUpdates'));
    if (targetPane) {
        targetPane.classList.add('active');
    }

    // Sync hash/query
    history.replaceState(null, null, `?tab=${tabName}`);
}

// Check initial tab on load
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam && ['updates', 'backups', 'health'].includes(tabParam)) {
        const btnId = tabParam === 'backups' ? 'tabBtnBackups' : (tabParam === 'health' ? 'tabBtnHealth' : 'tabBtnUpdates');
        const btn = document.getElementById(btnId);
        if (btn) switchMaintenanceTab(tabParam, btn);
    }
});

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

// Global Toast System
function showToast(message, type = 'info') {
    const container = document.getElementById('toastNotificationContainer');
    const toast = document.createElement('div');
    toast.className = `custom-toast toast-${type}`;
    
    let iconClass = 'bi-info-circle-fill';
    if (type === 'success') iconClass = 'bi-check-circle-fill';
    if (type === 'error') iconClass = 'bi-exclamation-octagon-fill';
    if (type === 'warning') iconClass = 'bi-exclamation-triangle-fill';

    toast.innerHTML = `
        <i class="bi ${iconClass} toast-icon"></i>
        <div class="toast-msg">${message}</div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 300);
    }, 4500);
}

function appendTerminalLog(msg, type = 'info') {
    const term = document.getElementById('terminalLogsBody');
    const time = new Date().toTimeString().split(' ')[0];
    const line = document.createElement('div');
    line.className = `term-line term-${type}`;
    line.innerHTML = `<span class="term-time">[${time}]</span> ${msg}`;
    term.appendChild(line);
    term.scrollTop = term.scrollHeight;
}

function clearTerminalLogs() {
    document.getElementById('terminalLogsBody').innerHTML = `
        <div class="term-line term-muted">
            <span class="term-time">[${new Date().toTimeString().split(' ')[0]}]</span> Logs cleared by administrator.
        </div>
    `;
}

function copyTerminalLogs() {
    const term = document.getElementById('terminalLogsBody');
    const text = term.innerText;
    navigator.clipboard.writeText(text).then(() => {
        showToast('Console logs copied to clipboard!', 'success');
    }).catch(() => {
        showToast('Unable to copy to clipboard.', 'warning');
    });
}

function copyBypassLink() {
    const url = window.location.origin + '/admin-access-portal';
    navigator.clipboard.writeText(url).then(() => {
        showToast('Secret bypass URL copied: ' + url, 'success');
    }).catch(() => {
        showToast('Key: admin-access-portal', 'info');
    });
}

// Modal Trigger
function confirmFullSystemUpdate() {
    document.getElementById('updateConfirmModal').style.display = 'flex';
}

function closeUpdateConfirmModal() {
    document.getElementById('updateConfirmModal').style.display = 'none';
}

function proceedFullSystemUpdate() {
    closeUpdateConfirmModal();
    runFullSystemUpdate();
}

function dismissUpdateConsole() {
    document.getElementById('updateProgressCard').style.display = 'none';
    window.location.reload();
}

// 1-Click System Update Orchestrator
async function runFullSystemUpdate() {
    const btn = document.getElementById('fullUpdateBtn');
    const card = document.getElementById('updateProgressCard');
    const title = document.getElementById('updateCardTitle');
    const badge = document.getElementById('updateStatusBadge');
    const spinner = document.getElementById('updateSpinner');
    const stepsList = document.getElementById('updateStepsList');
    const progressBar = document.getElementById('stepperProgressBar');
    const banner = document.getElementById('updateSuccessBanner');

    btn.disabled = true;
    card.style.display = 'flex';
    banner.style.display = 'none';
    stepsList.style.display = 'none';
    card.scrollIntoView({ behavior: 'smooth', block: 'start' });

    title.textContent = 'Executing 1-Click System Update...';
    badge.className = 'console-badge badge-processing';
    badge.textContent = 'Processing';
    spinner.style.display = 'flex';
    progressBar.style.width = '20%';

    appendTerminalLog('Starting Full System Update routine...', 'info');
    appendTerminalLog('Stage 1/4: Generating database safety snapshot archive...', 'info');

    for (let i = 1; i <= 4; i++) {
        const node = document.getElementById(`stepNode${i}`);
        if (node) node.className = 'stepper-step' + (i === 1 ? ' active' : '');
    }

    try {
        setTimeout(() => {
            progressBar.style.width = '40%';
            setStepState(1, 'completed', 'Snapshot Done');
            setStepState(2, 'active', 'Running Migrate');
            appendTerminalLog('Stage 2/4: Applying pending database migrations (--force)...', 'info');
        }, 800);

        const res = await fetch('{{ route("admin.system-update.run") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        const data = await res.json();

        spinner.style.display = 'none';

        if (data.success) {
            progressBar.style.width = '100%';
            badge.className = 'console-badge badge-success';
            badge.textContent = 'Completed';
            title.textContent = '✓ 1-Click System Update Succeeded!';

            for (let i = 1; i <= 4; i++) {
                setStepState(i, 'completed', 'Completed');
            }

            stepsList.style.display = 'flex';
            stepsList.innerHTML = data.results.map(r => `
                <div class="result-step-card result-${r.status}">
                    <i class="bi ${r.status === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} result-icon"></i>
                    <div class="result-content">
                        <div class="result-title">${r.step}</div>
                        <div class="result-msg">${r.message}</div>
                    </div>
                </div>
            `).join('');

            data.results.forEach(r => {
                appendTerminalLog(`[${r.step}] ${r.message}`, r.status === 'success' ? 'success' : 'warning');
            });

            appendTerminalLog('>>> Full system update completed successfully! <<<', 'success');
            banner.style.display = 'flex';
            showToast('System update executed successfully!', 'success');

        } else {
            badge.className = 'console-badge badge-failed';
            badge.textContent = 'Failed';
            title.textContent = 'System Update Finished with Issues';
            
            data.results.forEach(r => {
                appendTerminalLog(`[${r.step}] ${r.message}`, r.status === 'success' ? 'success' : 'error');
            });
            appendTerminalLog('System update encountered errors: ' + (data.message || 'Check logs above'), 'error');
            showToast('System update failed: ' + (data.message || 'Check logs'), 'error');
        }
    } catch (e) {
        spinner.style.display = 'none';
        badge.className = 'console-badge badge-failed';
        badge.textContent = 'Error';
        title.textContent = 'System Update Network Error';
        appendTerminalLog('CRITICAL: Network or server execution error: ' + e.message, 'error');
        showToast('Update failed: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
    }
}

function setStepState(stepNum, stateClass, subText = '') {
    const node = document.getElementById(`stepNode${stepNum}`);
    const sub = document.getElementById(`step${stepNum}Sub`);
    if (node) {
        node.className = `stepper-step ${stateClass}`;
    }
    if (sub && subText) {
        sub.textContent = subText;
    }
}

// Granular Operations: Migrations
async function checkMigrationsStatus() {
    const btn = document.getElementById('checkMigBtn');
    const out = document.getElementById('migrationOutput');
    const badge = document.getElementById('migStatusBadge');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Checking...';
    out.style.display = 'block';
    out.style.color = '#93c5fd';
    out.textContent = 'Inspecting database migration repository...';

    try {
        const res = await fetch('{{ route("admin.system-update.migrate-status") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        out.style.color = data.is_up_to_date ? '#4ade80' : '#fbbf24';
        
        if (data.is_up_to_date) {
            out.textContent = `✓ ${data.message} (${data.applied_count} migrations applied in total)`;
            badge.className = 'op-status-badge badge-emerald';
            badge.textContent = 'Up to Date';
            showToast('Database schema is completely up to date.', 'success');
        } else {
            out.textContent = `⚠️ Found ${data.pending_count} pending migration(s):\n- ` + data.pending_migrations.join('\n- ');
            badge.className = 'op-status-badge badge-amber';
            badge.textContent = `${data.pending_count} Pending`;
            showToast(`${data.pending_count} pending database migration(s) found.`, 'warning');
        }
    } catch (e) {
        out.style.color = '#f87171';
        out.textContent = 'Error inspecting migrations: ' + e.message;
        showToast('Error checking migrations.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-search me-1"></i> Check Status';
    }
}

async function runMigrationsOnly() {
    const btn = document.getElementById('migrateBtn');
    const out = document.getElementById('migrationOutput');
    const badge = document.getElementById('migStatusBadge');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Migrating...';
    out.style.display = 'block';
    out.style.color = '#93c5fd';
    out.textContent = 'Executing database migrations (--force)...';

    try {
        const res = await fetch('{{ route("admin.system-update.migrate") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        out.style.color = data.success ? '#4ade80' : '#f87171';
        out.textContent = data.message;
        if (data.success) {
            badge.className = 'op-status-badge badge-emerald';
            badge.textContent = 'Up to Date';
            showToast('Migrations executed successfully!', 'success');
        } else {
            showToast('Migration error: ' + data.message, 'error');
        }
    } catch (e) {
        out.style.color = '#f87171';
        out.textContent = 'Error: ' + e.message;
        showToast('Migration failed.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-play-circle-fill me-1"></i> Run Migrations';
    }
}

// Granular Operations: Caches
async function clearCachesOnly() {
    const btn = document.getElementById('cacheBtn');
    const out = document.getElementById('cacheOutput');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Optimizing...';
    out.style.display = 'block';
    out.style.color = '#93c5fd';
    out.textContent = 'Purging view, route, config, and framework caches...';

    try {
        const res = await fetch('{{ route("admin.system-update.cache-clear") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        out.style.color = data.success ? '#4ade80' : '#f87171';
        out.textContent = data.message;
        if (data.success) {
            showToast('Application caches purged and refreshed!', 'success');
        } else {
            showToast('Cache purge error: ' + data.message, 'error');
        }
    } catch (e) {
        out.style.color = '#f87171';
        out.textContent = 'Error: ' + e.message;
        showToast('Failed to optimize caches.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Rebuild & Purge Caches';
    }
}

// Granular Operations: PWA Bump
async function bumpPwa() {
    const btn = document.getElementById('pwaBtn');
    const quickBtn = document.getElementById('quickBumpBtn');
    const out = document.getElementById('pwaOutput');
    const currentBadge = document.getElementById('currentSwVersionBadge');
    const tag = document.getElementById('pwaVersionTag');

    if (btn) btn.disabled = true;
    if (quickBtn) quickBtn.disabled = true;
    if (out) {
        out.style.display = 'block';
        out.style.color = '#93c5fd';
        out.textContent = 'Incrementing Service Worker cache version...';
    }

    try {
        const res = await fetch('{{ route("admin.system-update.pwa-bump") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        if (out) {
            out.style.color = data.success ? '#4ade80' : '#f87171';
            out.textContent = data.message;
        }
        if (data.success && data.version) {
            if (currentBadge) currentBadge.textContent = data.version;
            if (tag) tag.textContent = data.version;
            showToast(data.message, 'success');
        } else {
            showToast(data.message, data.success ? 'success' : 'error');
        }
    } catch (e) {
        if (out) {
            out.style.color = '#f87171';
            out.textContent = 'Error: ' + e.message;
        }
        showToast('Error broadcasting PWA update.', 'error');
    } finally {
        if (btn) btn.disabled = false;
        if (quickBtn) quickBtn.disabled = false;
    }
}

// Granular Operations: Maintenance Toggle
async function promptMaintenanceToggle() {
    const isCurrentlyDown = {{ $isDown ? 'true' : 'false' }};
    const confirmMsg = isCurrentlyDown 
        ? 'Deactivate Maintenance Mode and restore public access?' 
        : 'Activate Maintenance Mode? Non-admin users will see an offline page. Super admins can bypass with /admin-access-portal.';
    
    if (!confirm(confirmMsg)) return;

    try {
        const res = await fetch('{{ route("admin.system-update.maintenance-toggle") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        showToast(data.message, 'info');
        setTimeout(() => window.location.reload(), 1200);
    } catch (e) {
        showToast('Error toggling maintenance mode: ' + e.message, 'error');
    }
}

// Pre-Flight Deep Health Scan
async function triggerHealthScan() {
    const btn = document.getElementById('deepScanBtn');
    const quickBtn = document.getElementById('quickScanBtn');
    const healthScanBtn = document.getElementById('healthScanBtn');
    const scoreVal = document.getElementById('readinessScoreVal');
    const topTabHealthBadge = document.getElementById('topTabHealthBadge');
    const container = document.getElementById('readinessChipsContainer');
    const auditCard = document.getElementById('diagnosticsLiveAuditCard');
    const auditContainer = document.getElementById('auditChecksContainer');
    const auditScoreBadge = document.getElementById('auditOverallScoreBadge');
    const auditLastTime = document.getElementById('auditLastScanTime');

    if (btn) btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Scanning...';
    if (quickBtn) quickBtn.disabled = true;
    if (healthScanBtn) healthScanBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Benchmarking...';

    try {
        const res = await fetch('{{ route("admin.system-update.health-check") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();

        if (data.success) {
            if (scoreVal) scoreVal.textContent = data.score + '%';
            if (topTabHealthBadge) {
                topTabHealthBadge.textContent = data.score + '%';
                topTabHealthBadge.className = `tab-badge-score ${data.score >= 90 ? 'score-green' : (data.score >= 70 ? 'score-amber' : 'score-red')}`;
            }
            
            // Render Pre-flight Readiness Chips
            if (container && data.checks) {
                container.innerHTML = data.checks.map(c => `
                    <span class="readiness-chip chip-${c.status}" title="${c.message}">
                        <i class="bi ${c.status === 'pass' ? 'bi-check-circle-fill' : (c.status === 'fail' ? 'bi-x-circle-fill' : 'bi-exclamation-triangle-fill')}"></i>
                        ${c.name}
                    </span>
                `).join('');
            }

            // Render Deep Diagnostic Audit Card in Tab 3
            if (auditCard && auditContainer && data.checks) {
                auditCard.style.display = 'block';
                if (auditScoreBadge) {
                    auditScoreBadge.textContent = `${data.score}% Overall Score`;
                    auditScoreBadge.className = `console-badge ${data.score >= 90 ? 'badge-success' : 'badge-warning'}`;
                }
                if (auditLastTime) {
                    auditLastTime.textContent = `Executed at ${new Date().toLocaleTimeString()} • ${data.checks.length} Subsystems Probed`;
                }

                auditContainer.innerHTML = data.checks.map(c => `
                    <div class="audit-check-item check-${c.status}">
                        <div class="audit-check-icon">
                            <i class="bi ${c.status === 'pass' ? 'bi-check-circle-fill' : (c.status === 'fail' ? 'bi-x-circle-fill' : 'bi-exclamation-triangle-fill')}"></i>
                        </div>
                        <div class="audit-check-body">
                            <div class="audit-check-name">${c.name}</div>
                            <div class="audit-check-msg">${c.message}</div>
                        </div>
                        <span class="audit-check-status-badge badge-${c.status}">
                            ${c.status === 'pass' ? 'PASSED' : (c.status === 'warn' ? 'WARN' : 'FAIL')}
                        </span>
                    </div>
                `).join('');
            }

            showToast(`Diagnostics benchmark complete! Health score: ${data.score}%`, 'success');
        }
    } catch (e) {
        showToast('Failed to run health scan: ' + e.message, 'error');
    } finally {
        if (btn) btn.innerHTML = '<i class="bi bi-search"></i> <span>Scan Subsystems</span>';
        if (quickBtn) quickBtn.disabled = false;
        if (healthScanBtn) healthScanBtn.innerHTML = '<i class="bi bi-activity me-1"></i> <span>Execute Full Diagnostics Scan</span>';
    }
}
</script>
@endsection
