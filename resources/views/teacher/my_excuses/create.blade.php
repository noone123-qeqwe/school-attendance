@extends('layouts.app')

@section('portal-title', 'Submit Faculty Excuse')
@section('page-title', 'Submit New Excuse')

@section('content')
@php
    $reasonOptions = [
        'Medical / Illness' => 'bi-heart-pulse',
        'Emergency' => 'bi-exclamation-diamond',
        'Official Business' => 'bi-briefcase',
        'Other' => 'bi-chat-left-text',
    ];
    $selectedReason = old('reason', '');
@endphp

<div class="texc-workspace">
    <header class="texc-hero">
        <x-back-link
            :href="route('teacher.excuses')"
            label="Back to Excuses & Corrections"
            variant="plain"
            class="texc-back-link"
        />
        <h1 class="texc-title">Submit Faculty Excuse</h1>
        <p class="texc-sub">Submit an absence notice and optional supporting documents for an assigned class.</p>
    </header>

    <x-error-summary title="Please review your excuse submission" />

    @if(session('error'))
        <div class="portal-banner is-error" role="alert">
            <div class="portal-banner-lead">
                <span class="portal-banner-icon" aria-hidden="true"><i class="bi bi-exclamation-circle-fill"></i></span>
                <div><div class="portal-banner-title">{{ session('error') }}</div></div>
            </div>
        </div>
    @endif

    @if($subjects->isEmpty())
        <div class="portal-banner is-warning" role="status">
            <div class="portal-banner-lead">
                <span class="portal-banner-icon" aria-hidden="true"><i class="bi bi-journal-x"></i></span>
                <div>
                    <div class="portal-banner-title">No Assigned Classes Found</div>
                    <div class="portal-banner-sub">Create or receive a class assignment before submitting an absence excuse.</div>
                </div>
            </div>
            <a href="{{ route('teacher.subjects.create') }}" class="portal-banner-btn">
                Create Class <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    @endif

    <div class="texc-grid">
        <section class="texc-card" aria-labelledby="texcFormHeading">
            <div class="texc-card-head">
                <div class="tch-card-title">
                    <div class="tch-card-icon" style="background: rgba(122,26,26,0.22); color: var(--portal-gold);">
                        <i class="bi bi-file-earmark-plus-fill" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div id="texcFormHeading">Excuse Details</div>
                        <div style="font-size: 0.78rem; font-weight: 500; color: #a49890;">
                            Select the affected class, absence date, reason, and explanation
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('teacher.excuses.store') }}" method="POST" enctype="multipart/form-data" id="excuseForm">
                @csrf

                <div class="texc-card-body">
                    <div class="texc-row-2">
                        <div class="texc-field">
                            <label for="subject_code" class="texc-label">
                                Class / Subject <span class="texc-req" aria-hidden="true">*</span>
                            </label>
                            <select
                                name="subject_code"
                                id="subject_code"
                                class="tch-input @error('subject_code') is-invalid @enderror"
                                style="width: 100%;"
                                @disabled($subjects->isEmpty())
                                required>
                                <option value="">-- Choose Subject --</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->code }}" {{ old('subject_code') == $subject->code ? 'selected' : '' }}>
                                        {{ $subject->code }} &mdash; {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_code')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="texc-field">
                            <label for="date" class="texc-label">
                                Date of Absence <span class="texc-req" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="date"
                                name="date"
                                id="date"
                                class="tch-input @error('date') is-invalid @enderror"
                                value="{{ old('date', date('Y-m-d')) }}"
                                max="{{ date('Y-m-d') }}"
                                style="width: 100%; color-scheme: dark;"
                                required>
                            @error('date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="texc-field">
                        <label for="reason" class="texc-label">
                            Reason <span class="texc-req" aria-hidden="true">*</span>
                        </label>
                        <div class="texc-reason-chips" role="group" aria-label="Quick-select excuse reason">
                            @foreach($reasonOptions as $reasonLabel => $reasonIcon)
                                <button
                                    type="button"
                                    class="texc-reason-chip {{ $selectedReason === $reasonLabel ? 'is-active' : '' }}"
                                    data-reason-chip="{{ $reasonLabel }}"
                                    aria-pressed="{{ $selectedReason === $reasonLabel ? 'true' : 'false' }}">
                                    <i class="bi {{ $reasonIcon }}" aria-hidden="true"></i>
                                    <span>{{ $reasonLabel }}</span>
                                </button>
                            @endforeach
                        </div>
                        <select
                            name="reason"
                            id="reason"
                            class="tch-input @error('reason') is-invalid @enderror"
                            style="width: 100%;"
                            required>
                            <option value="">-- Select Reason --</option>
                            @foreach(array_keys($reasonOptions) as $reasonLabel)
                                <option value="{{ $reasonLabel }}" {{ $selectedReason === $reasonLabel ? 'selected' : '' }}>
                                    {{ $reasonLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error('reason')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="texc-field">
                        <div class="texc-label-row">
                            <label for="description" class="texc-label">
                                Detailed Explanation <span class="texc-req" aria-hidden="true">*</span>
                            </label>
                            <span id="charCount" class="texc-char-count" aria-live="polite">0 / 1000</span>
                        </div>
                        <textarea
                            name="description"
                            id="description"
                            class="tch-input @error('description') is-invalid @enderror"
                            rows="5"
                            maxlength="1000"
                            placeholder="Provide context about your absence, substitute arrangements, or official schedule notes..."
                            style="width: 100%; resize: vertical;"
                            required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="texc-field">
                        <label for="attachments" class="texc-label">
                            Supporting Documents (Optional)
                        </label>
                        <input
                            type="file"
                            name="attachments[]"
                            id="attachments"
                            class="visually-hidden"
                            multiple
                            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <label for="attachments" id="dropZone" class="texc-dropzone" tabindex="0">
                            <div class="texc-dropzone-icon" aria-hidden="true">
                                <i class="bi bi-cloud-arrow-up"></i>
                            </div>
                            <span class="texc-dropzone-title">Click to upload or drag &amp; drop files here</span>
                            <span class="texc-dropzone-sub">Medical slips, travel orders, or memos &middot; JPG, PNG, PDF, DOC, DOCX up to 5 MB each</span>
                        </label>
                        <div id="fileError" class="texc-file-error" role="alert" hidden></div>
                        <div id="fileList" class="texc-file-list" aria-live="polite"></div>
                        @error('attachments')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @error('attachments.*')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="texc-footer">
                    <span class="texc-footer-note">
                        <i class="bi bi-shield-check me-1" aria-hidden="true"></i> Sent directly for administrative review
                    </span>
                    <div class="texc-footer-actions">
                        <a href="{{ route('teacher.excuses') }}" class="tch-btn tch-btn-ghost">Cancel</a>
                        <button
                            type="submit"
                            id="submitBtn"
                            class="tch-btn tch-btn-primary"
                            @disabled($subjects->isEmpty())>
                            <i class="bi bi-send-fill" aria-hidden="true"></i>
                            <span>Submit Excuse</span>
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <aside class="texc-aside" aria-label="Submission guidelines">
            <div class="texc-aside-head">
                <i class="bi bi-info-circle" style="color: #d4af37;" aria-hidden="true"></i>
                <span>Before You Submit</span>
            </div>
            <ol class="texc-steps">
                <li class="texc-step">
                    <span class="texc-step-num" aria-hidden="true">1</span>
                    <div>
                        <div class="texc-step-title">One Submission Per Class Date</div>
                        <div class="texc-step-desc">Each excuse links to one assigned class and date so records stay distinct.</div>
                    </div>
                </li>
                <li class="texc-step">
                    <span class="texc-step-num" aria-hidden="true">2</span>
                    <div>
                        <div class="texc-step-title">Attach Supporting Proof</div>
                        <div class="texc-step-desc">Add medical certificates or official memos when available to speed up approval.</div>
                    </div>
                </li>
                <li class="texc-step">
                    <span class="texc-step-num" aria-hidden="true">3</span>
                    <div>
                        <div class="texc-step-title">Track Review Status</div>
                        <div class="texc-step-desc">Updates and reviewer feedback appear under the My Excuses tab once processed.</div>
                    </div>
                </li>
            </ol>
        </aside>
    </div>
</div>

@push('scripts')
<script @cspNonce>
(function () {
    const descInput = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    const maxLen = 1000;

    function updateCharCount() {
        if (!descInput || !charCount) return;
        const len = descInput.value.length;
        charCount.textContent = len + ' / ' + maxLen;
        charCount.classList.toggle('is-warn', len >= 850 && len < 980);
        charCount.classList.toggle('is-limit', len >= 980);
    }

    if (descInput && charCount) {
        descInput.addEventListener('input', updateCharCount);
        updateCharCount();
    }

    const reasonSelect = document.getElementById('reason');
    const reasonChips = Array.from(document.querySelectorAll('[data-reason-chip]'));

    function syncChipsFromSelect() {
        if (!reasonSelect) return;
        const current = reasonSelect.value;
        reasonChips.forEach((chip) => {
            const match = chip.getAttribute('data-reason-chip') === current;
            chip.classList.toggle('is-active', match);
            chip.setAttribute('aria-pressed', match ? 'true' : 'false');
        });
    }

    reasonChips.forEach((chip) => {
        chip.addEventListener('click', () => {
            if (!reasonSelect) return;
            reasonSelect.value = chip.getAttribute('data-reason-chip') || '';
            syncChipsFromSelect();
        });
    });

    reasonSelect?.addEventListener('change', syncChipsFromSelect);

    const fileInput = document.getElementById('attachments');
    const dropZone = document.getElementById('dropZone');
    const fileList = document.getElementById('fileList');
    const fileError = document.getElementById('fileError');
    const allowedExts = new Set(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);
    const maxBytes = 5 * 1024 * 1024;
    let collectedFiles = new DataTransfer();
    let previewUrls = [];

    function revokePreviewUrls() {
        previewUrls.forEach((url) => URL.revokeObjectURL(url));
        previewUrls = [];
    }

    function fileKey(file) {
        return [file.name, file.size, file.lastModified].join('::');
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function renderFileList() {
        if (!fileList) return;
        revokePreviewUrls();
        fileList.replaceChildren();

        Array.from(collectedFiles.files).forEach((file, index) => {
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png'].includes(ext);

            const item = document.createElement('div');
            item.className = 'texc-file-item';

            const main = document.createElement('div');
            main.className = 'texc-file-main';

            const thumb = document.createElement('div');
            thumb.className = 'texc-file-thumb';
            if (isImage) {
                const imgUrl = URL.createObjectURL(file);
                previewUrls.push(imgUrl);
                const img = document.createElement('img');
                img.src = imgUrl;
                img.alt = file.name;
                thumb.appendChild(img);
            } else {
                const icon = document.createElement('i');
                icon.className = ext === 'pdf'
                    ? 'bi bi-file-earmark-pdf-fill text-danger'
                    : 'bi bi-file-earmark-word-fill text-info';
                icon.setAttribute('aria-hidden', 'true');
                thumb.appendChild(icon);
            }

            const meta = document.createElement('div');
            meta.className = 'texc-file-meta';

            const nameEl = document.createElement('span');
            nameEl.className = 'texc-file-name';
            nameEl.textContent = file.name;

            const sizeEl = document.createElement('span');
            sizeEl.className = 'texc-file-size';
            sizeEl.textContent = formatBytes(file.size);

            meta.appendChild(nameEl);
            meta.appendChild(sizeEl);
            main.appendChild(thumb);
            main.appendChild(meta);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'texc-file-remove';
            removeBtn.setAttribute('data-remove-index', String(index));
            removeBtn.setAttribute('aria-label', 'Remove ' + file.name);
            removeBtn.title = 'Remove file';

            const trashIcon = document.createElement('i');
            trashIcon.className = 'bi bi-trash3';
            trashIcon.setAttribute('aria-hidden', 'true');
            removeBtn.appendChild(trashIcon);

            item.appendChild(main);
            item.appendChild(removeBtn);
            fileList.appendChild(item);
        });
    }

    function addFiles(incoming) {
        if (!fileInput || !incoming || incoming.length === 0) return;

        const existingKeys = new Set(Array.from(collectedFiles.files).map(fileKey));
        const rejected = [];

        Array.from(incoming).forEach((file) => {
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            if (!allowedExts.has(ext)) {
                rejected.push(file.name + ' (unsupported format)');
                return;
            }
            if (file.size > maxBytes) {
                rejected.push(file.name + ' (exceeds 5 MB)');
                return;
            }
            const key = fileKey(file);
            if (!existingKeys.has(key)) {
                existingKeys.add(key);
                collectedFiles.items.add(file);
            }
        });

        fileInput.files = collectedFiles.files;
        renderFileList();

        if (fileError) {
            if (rejected.length > 0) {
                fileError.textContent = 'Skipped: ' + rejected.join(', ');
                fileError.hidden = false;
            } else {
                fileError.textContent = '';
                fileError.hidden = true;
            }
        }
    }

    fileInput?.addEventListener('change', () => addFiles(fileInput.files));

    fileList?.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-remove-index]');
        if (!btn || !fileInput) return;
        const removeIdx = Number(btn.getAttribute('data-remove-index'));
        const nextDt = new DataTransfer();
        Array.from(collectedFiles.files).forEach((file, idx) => {
            if (idx !== removeIdx) {
                nextDt.items.add(file);
            }
        });
        collectedFiles = nextDt;
        fileInput.files = collectedFiles.files;
        renderFileList();
    });

    if (dropZone && fileInput) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
            dropZone.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
            });
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('is-dragover'));
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('is-dragover'));
        });

        dropZone.addEventListener('drop', (event) => {
            if (event.dataTransfer && event.dataTransfer.files.length) {
                addFiles(event.dataTransfer.files);
            }
        });

        dropZone.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                fileInput.click();
            }
        });
    }

    const form = document.getElementById('excuseForm');
    form?.addEventListener('submit', () => {
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Submitting...</span>';
        }
    });
})();
</script>
@endpush
@endsection
