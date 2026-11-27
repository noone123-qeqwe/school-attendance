@extends('layouts.app')

@section('portal-title', 'Excuses & Corrections')
@section('page-title', 'Excuses & Corrections')

@section('content')
<div class="tc-workspace" id="requestsHubWorkspace" data-active-tab="{{ $activeTab }}">
    @if(session('success'))
        <div class="portal-banner is-success mb-3" role="status">
            <div class="portal-banner-lead">
                <span class="portal-banner-icon" aria-hidden="true"><i class="bi bi-check-circle-fill"></i></span>
                <div><div class="portal-banner-title">{{ session('success') }}</div></div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="portal-banner is-error mb-3" role="alert">
            <div class="portal-banner-lead">
                <span class="portal-banner-icon" aria-hidden="true"><i class="bi bi-exclamation-circle-fill"></i></span>
                <div><div class="portal-banner-title">{{ session('error') }}</div></div>
            </div>
        </div>
    @endif

    <header class="tc-header">
        <div class="tc-header-top">
            <div class="tc-header-copy">
                <h1 class="tc-title" style="margin-top: 0;">Excuses &amp; Corrections</h1>
                <p style="margin: 6px 0 0; font-size: 0.9rem; color: #a49890;">
                    Review student excuse letters, attendance correction requests, and your own faculty excuses.
                </p>
            </div>
            <div class="tc-actions-bar">
                <a href="{{ route('teacher.excuses.create') }}" class="ent-btn ent-btn-primary">
                    <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> Submit New Excuse
                </a>
            </div>
        </div>
    </header>

    <div class="tc-tab-nav" role="tablist" aria-label="Excuses and corrections sections">
        <button
            type="button"
            role="tab"
            id="tab-btn-excuses"
            class="tc-tab-btn {{ $activeTab === 'excuses' ? 'active' : '' }}"
            data-tab="excuses"
            data-url="{{ route('teacher.excuse.reviews') }}"
            aria-controls="excuses-tab"
            aria-selected="{{ $activeTab === 'excuses' ? 'true' : 'false' }}">
            <i class="bi bi-file-earmark-text-fill" aria-hidden="true"></i>
            <span>Excuse Reviews</span>
            <span class="tc-tab-count portal-mono">{{ $totalSubmissions }}</span>
            @if($pendingCount > 0)
                <span style="background: rgba(251, 191, 36, 0.16); color: #fbbf24; padding: 1px 6px; border-radius: 999px; font-size: 0.68rem; font-weight: 700;">{{ $pendingCount }} pending</span>
            @endif
        </button>

        <button
            type="button"
            role="tab"
            id="tab-btn-corrections"
            class="tc-tab-btn {{ $activeTab === 'corrections' ? 'active' : '' }}"
            data-tab="corrections"
            data-url="{{ route('teacher.corrections') }}"
            aria-controls="corrections-tab"
            aria-selected="{{ $activeTab === 'corrections' ? 'true' : 'false' }}">
            <i class="bi bi-pencil-square" aria-hidden="true"></i>
            <span>Correction Requests</span>
            <span class="tc-tab-count portal-mono">{{ $totalCorrectionsCount }}</span>
            @if($pendingCorrectionsTotal > 0)
                <span style="background: rgba(251, 191, 36, 0.16); color: #fbbf24; padding: 1px 6px; border-radius: 999px; font-size: 0.68rem; font-weight: 700;">{{ $pendingCorrectionsTotal }} pending</span>
            @endif
        </button>

        <button
            type="button"
            role="tab"
            id="tab-btn-my-excuses"
            class="tc-tab-btn {{ $activeTab === 'my-excuses' ? 'active' : '' }}"
            data-tab="my-excuses"
            data-url="{{ route('teacher.excuses') }}"
            aria-controls="my-excuses-tab"
            aria-selected="{{ $activeTab === 'my-excuses' ? 'true' : 'false' }}">
            <i class="bi bi-file-earmark-person-fill" aria-hidden="true"></i>
            <span>My Excuses</span>
            <span class="tc-tab-count portal-mono">{{ $myExcusesCount }}</span>
        </button>
    </div>

    <section
        id="excuses-tab"
        role="tabpanel"
        aria-labelledby="tab-btn-excuses"
        class="tc-tab-panel {{ $activeTab === 'excuses' ? 'active' : '' }}"
        @if($activeTab !== 'excuses') hidden @endif>
        @include('teacher.partials.excuse-reviews-tab')
    </section>

    <section
        id="corrections-tab"
        role="tabpanel"
        aria-labelledby="tab-btn-corrections"
        class="tc-tab-panel {{ $activeTab === 'corrections' ? 'active' : '' }}"
        @if($activeTab !== 'corrections') hidden @endif>
        @include('teacher.partials.correction-requests-tab')
    </section>

    <section
        id="my-excuses-tab"
        role="tabpanel"
        aria-labelledby="tab-btn-my-excuses"
        class="tc-tab-panel {{ $activeTab === 'my-excuses' ? 'active' : '' }}"
        @if($activeTab !== 'my-excuses') hidden @endif>
        @include('teacher.partials.my-excuses-tab')
    </section>
</div>

<script @cspNonce>
(function () {
    const workspace = document.getElementById('requestsHubWorkspace');
    if (!workspace) return;

    const tabButtons = Array.from(workspace.querySelectorAll('.tc-tab-btn[data-tab]'));
    const tabPanels = Array.from(workspace.querySelectorAll('.tc-tab-panel[role="tabpanel"]'));

    function syncBulkBarForTab(tabName) {
        const bulkBar = document.getElementById('bulkActionBar');
        if (!bulkBar) return;
        if (tabName !== 'excuses') {
            bulkBar.classList.add('d-none');
        } else if (typeof window.updateBulkActionBar === 'function') {
            window.updateBulkActionBar();
        }
    }

    function activateTab(tabName, options = {}) {
        const targetBtn = tabButtons.find((btn) => btn.dataset.tab === tabName);
        if (!targetBtn) return;

        tabButtons.forEach((btn) => {
            const selected = btn.dataset.tab === tabName;
            btn.classList.toggle('active', selected);
            btn.setAttribute('aria-selected', selected ? 'true' : 'false');
        });

        tabPanels.forEach((panel) => {
            const isTarget = panel.id === tabName + '-tab';
            panel.classList.toggle('active', isTarget);
            panel.hidden = !isTarget;
        });

        workspace.dataset.activeTab = tabName;
        syncBulkBarForTab(tabName);

        if (options.updateHistory !== false && targetBtn.dataset.url) {
            try {
                const nextUrl = new URL(targetBtn.dataset.url, window.location.origin);
                window.history.pushState({ tab: tabName }, '', nextUrl.toString());
            } catch (e) {
                window.history.pushState({ tab: tabName }, '', targetBtn.dataset.url);
            }
        }
    }

    tabButtons.forEach((btn, idx) => {
        btn.addEventListener('click', () => activateTab(btn.dataset.tab));
        btn.addEventListener('keydown', (e) => {
            if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
            e.preventDefault();
            const step = e.key === 'ArrowRight' ? 1 : -1;
            const next = tabButtons[(idx + step + tabButtons.length) % tabButtons.length];
            next.focus();
            activateTab(next.dataset.tab);
        });
    });

    window.addEventListener('popstate', (event) => {
        const params = new URLSearchParams(window.location.search);
        const queryTab = params.get('tab');
        if (queryTab) {
            activateTab(queryTab, { updateHistory: false });
            return;
        }
        if (event.state && event.state.tab) {
            activateTab(event.state.tab, { updateHistory: false });
            return;
        }
        const path = window.location.pathname;
        if (path.endsWith('/corrections')) {
            activateTab('corrections', { updateHistory: false });
        } else if (path.endsWith('/excuses')) {
            activateTab('my-excuses', { updateHistory: false });
        } else {
            activateTab('excuses', { updateHistory: false });
        }
    });
})();
</script>
@endsection
