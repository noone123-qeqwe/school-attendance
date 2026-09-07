@extends('layouts.app')

@section('portal-title', 'Policy Management')
@section('page-title', 'Policy & Legal Management')
@section('page-sub', 'Manage, version, and publish Privacy Policy and Terms & Conditions')

@section('content')
<style>
    .policy-admin-card {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(212, 175, 55, 0.22);
        border-radius: 20px;
        backdrop-filter: blur(16px);
        box-shadow: 0 16px 40px rgba(0,0,0,0.35);
        overflow: hidden;
        margin-bottom: 30px;
    }
    .policy-admin-header {
        padding: 24px 28px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
    }
    .policy-admin-tabs {
        display: flex;
        gap: 8px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding: 0 28px;
        background: rgba(0, 0, 0, 0.15);
    }
    .policy-tab-btn {
        background: transparent;
        border: none;
        color: rgba(255, 255, 255, 0.65);
        padding: 16px 20px;
        font-size: 0.92rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        position: relative;
        transition: all 0.2s;
    }
    .policy-tab-btn:hover {
        color: #fff;
    }
    .policy-tab-btn.active {
        color: var(--admin-gold, #D4AF37);
        font-weight: 700;
    }
    .policy-tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--admin-gold, #D4AF37);
        border-radius: 3px 3px 0 0;
    }
    .policy-admin-body {
        padding: 32px 28px;
    }
    .field-label {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(248, 231, 211, 0.8);
        margin-bottom: 8px;
        display: block;
    }
    .policy-editor-textarea {
        width: 100%;
        min-height: 480px;
        padding: 18px;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: rgba(15, 8, 10, 0.85);
        color: #f8e7d3;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        font-size: 0.88rem;
        line-height: 1.6;
        outline: none;
        resize: vertical;
        box-sizing: border-box;
        transition: border-color 0.2s;
    }
    .policy-editor-textarea:focus {
        border-color: var(--admin-gold, #D4AF37);
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
    }
    .btn-gold {
        background: linear-gradient(135deg, #d8b35c, #b8974d);
        color: #1a080a;
        font-weight: 700;
        border: none;
        border-radius: 12px;
        padding: 10px 22px;
        transition: all 0.2s;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    .btn-gold:hover {
        background: linear-gradient(135deg, #c9a551, #a7843f);
        color: #000;
        transform: translateY(-1px);
    }
    .btn-outline-custom {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.16);
        color: #f8e7d3;
        border-radius: 12px;
        padding: 10px 18px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: all 0.2s;
    }
    .btn-outline-custom:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
    }
    .badge-status {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .badge-custom {
        background: rgba(59, 130, 246, 0.2);
        color: #93c5fd;
        border: 1px solid rgba(59, 130, 246, 0.4);
    }
    .badge-default {
        background: rgba(34, 197, 94, 0.2);
        color: #86efac;
        border: 1px solid rgba(34, 197, 94, 0.4);
    }
</style>

<div class="container-fluid px-4 py-3">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" style="background:rgba(34,197,94,0.18); border-color:rgba(34,197,94,0.4); color:#86efac; border-radius:14px;" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" style="background:rgba(239,68,68,0.18); border-color:rgba(239,68,68,0.4); color:#fca5a5; border-radius:14px;" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ $errors->first() }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="policy-admin-card">
        <div class="policy-admin-header">
            <div>
                <h3 class="mb-1" style="font-family:'Outfit'; font-weight:700; color:#fff; font-size:1.35rem;">
                    <i class="bi bi-journal-text me-2" style="color:var(--admin-gold, #D4AF37);"></i>
                    Legal Policies & Notices
                </h3>
                <p class="mb-0 text-muted" style="font-size:0.88rem;">
                    Control the published content, version numbers, and effective dates for your institutional Privacy Notice and Terms & Conditions.
                </p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('privacy') }}" target="_blank" class="btn-outline-custom">
                    <i class="bi bi-box-arrow-up-right"></i> Live Privacy
                </a>
                <a href="{{ route('terms') }}" target="_blank" class="btn-outline-custom">
                    <i class="bi bi-box-arrow-up-right"></i> Live Terms
                </a>
            </div>
        </div>

        @php
            $activeTab = request('tab', 'privacy');
        @endphp

        <!-- Navigation Tabs -->
        <div class="policy-admin-tabs">
            <button type="button" class="policy-tab-btn {{ $activeTab === 'privacy' ? 'active' : '' }}" onclick="switchPolicyTab('privacy')">
                <i class="bi bi-shield-check"></i> Privacy Notice
                @if($privacy['is_custom'])
                    <span class="badge-status badge-custom ms-1">Customized</span>
                @else
                    <span class="badge-status badge-default ms-1">System Default</span>
                @endif
            </button>
            <button type="button" class="policy-tab-btn {{ $activeTab === 'terms' ? 'active' : '' }}" onclick="switchPolicyTab('terms')">
                <i class="bi bi-file-earmark-text"></i> Terms & Conditions
                @if($terms['is_custom'])
                    <span class="badge-status badge-custom ms-1">Customized</span>
                @else
                    <span class="badge-status badge-default ms-1">System Default</span>
                @endif
            </button>
        </div>

        <div class="policy-admin-body">
            <!-- TAB 1: Privacy Policy Form -->
            <div id="tab-privacy" style="display: {{ $activeTab === 'privacy' ? 'block' : 'none' }};">
                <form action="{{ route('admin.policies.update') }}" method="POST" id="formPrivacy">
                    @csrf
                    <input type="hidden" name="policy_type" value="privacy">

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="field-label" for="privacy_version">Policy Version</label>
                            <input type="text" name="privacy_version" id="privacy_version" class="form-control" style="background:rgba(255,255,255,0.06); border-color:rgba(255,255,255,0.15); color:#fff; border-radius:10px;" value="{{ old('privacy_version', $privacy['version']) }}" required>
                            <small class="text-muted">e.g., 1.0, 1.1, 2.0</small>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label" for="privacy_effective_date">Effective Date</label>
                            <input type="date" name="privacy_effective_date" id="privacy_effective_date" class="form-control" style="background:rgba(255,255,255,0.06); border-color:rgba(255,255,255,0.15); color:#fff; border-radius:10px;" value="{{ old('privacy_effective_date', $privacy['raw_effective']) }}" required>
                            <small class="text-muted">Date the policy became active</small>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Last Updated</label>
                            <input type="text" class="form-control" style="background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.1); color:#aaa; border-radius:10px;" value="{{ $privacy['updated_at'] }}" readonly>
                            <small class="text-muted">Automatically updated on save</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="field-label mb-0" for="privacy_content">Document Content (HTML / Markdown Supported)</label>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="previewPolicy('privacy')">
                                <i class="bi bi-eye me-1"></i> Preview HTML
                            </button>
                        </div>
                        <textarea name="privacy_content" id="privacy_content" class="policy-editor-textarea" required>{{ old('privacy_content', $privacy['content']) }}</textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-3 border-top border-secondary border-opacity-25">
                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" class="btn-gold">
                                <i class="bi bi-check2-circle"></i> Save & Publish Privacy Notice
                            </button>
                            <button type="button" class="btn-outline-custom" onclick="previewPolicy('privacy')">
                                <i class="bi bi-eye"></i> Preview
                            </button>
                        </div>

                        @if($privacy['is_custom'])
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmResetPolicy('privacy')">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Default
                        </button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- TAB 2: Terms & Conditions Form -->
            <div id="tab-terms" style="display: {{ $activeTab === 'terms' ? 'block' : 'none' }};">
                <form action="{{ route('admin.policies.update') }}" method="POST" id="formTerms">
                    @csrf
                    <input type="hidden" name="policy_type" value="terms">

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="field-label" for="terms_version">Terms Version</label>
                            <input type="text" name="terms_version" id="terms_version" class="form-control" style="background:rgba(255,255,255,0.06); border-color:rgba(255,255,255,0.15); color:#fff; border-radius:10px;" value="{{ old('terms_version', $terms['version']) }}" required>
                            <small class="text-muted">e.g., 1.0, 1.1, 2.0</small>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label" for="terms_effective_date">Effective Date</label>
                            <input type="date" name="terms_effective_date" id="terms_effective_date" class="form-control" style="background:rgba(255,255,255,0.06); border-color:rgba(255,255,255,0.15); color:#fff; border-radius:10px;" value="{{ old('terms_effective_date', $terms['raw_effective']) }}" required>
                            <small class="text-muted">Date terms became effective</small>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Last Updated</label>
                            <input type="text" class="form-control" style="background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.1); color:#aaa; border-radius:10px;" value="{{ $terms['updated_at'] }}" readonly>
                            <small class="text-muted">Automatically updated on save</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="field-label mb-0" for="terms_content">Document Content (HTML / Markdown Supported)</label>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="previewPolicy('terms')">
                                <i class="bi bi-eye me-1"></i> Preview HTML
                            </button>
                        </div>
                        <textarea name="terms_content" id="terms_content" class="policy-editor-textarea" required>{{ old('terms_content', $terms['content']) }}</textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-3 border-top border-secondary border-opacity-25">
                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" class="btn-gold">
                                <i class="bi bi-check2-circle"></i> Save & Publish Terms & Conditions
                            </button>
                            <button type="button" class="btn-outline-custom" onclick="previewPolicy('terms')">
                                <i class="bi bi-eye"></i> Preview
                            </button>
                        </div>

                        @if($terms['is_custom'])
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmResetPolicy('terms')">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Default
                        </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Live Preview -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="background:#150a07; border:1px solid rgba(212,175,55,0.3); border-radius:20px; color:#f8e7d3;">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,0.1);">
                <h5 class="modal-title" id="previewModalLabel" style="color:var(--admin-gold, #D4AF37); font-family:'Outfit'; font-weight:700;">
                    Document Preview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="previewModalBody" style="background:rgba(25,12,16,0.6);">
                <!-- Dynamic Content Injected Here -->
            </div>
            <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,0.1);">
                <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Close Preview</button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Form (Hidden) -->
<form id="resetPolicyForm" action="{{ route('admin.policies.reset') }}" method="POST" style="display:none;">
    @csrf
    <input type="hidden" name="type" id="resetType" value="privacy">
</form>

<script @cspNonce>
function switchPolicyTab(tab) {
    document.querySelectorAll('.policy-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById('tab-privacy').style.display = 'none';
    document.getElementById('tab-terms').style.display = 'none';

    if (tab === 'privacy') {
        document.querySelectorAll('.policy-tab-btn')[0].classList.add('active');
        document.getElementById('tab-privacy').style.display = 'block';
    } else {
        document.querySelectorAll('.policy-tab-btn')[1].classList.add('active');
        document.getElementById('tab-terms').style.display = 'block';
    }
}

function previewPolicy(type) {
    const title = type === 'privacy' ? 'Privacy Notice Preview' : 'Terms & Conditions Preview';
    const textareaId = type === 'privacy' ? 'privacy_content' : 'terms_content';
    const content = document.getElementById(textareaId).value;

    document.getElementById('previewModalLabel').textContent = title;
    document.getElementById('previewModalBody').innerHTML = content;

    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

function confirmResetPolicy(type) {
    const name = type === 'privacy' ? 'Privacy Notice' : 'Terms & Conditions';
    if (confirm(`Are you sure you want to reset ${name} back to the system default content? Any custom modifications will be removed.`)) {
        document.getElementById('resetType').value = type;
        document.getElementById('resetPolicyForm').submit();
    }
}
</script>
@endsection
