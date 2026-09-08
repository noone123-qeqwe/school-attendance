<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Submit Excuse Letter - Smart Classroom Attendance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-accent: #3b82f6;
            --primary-accent-hover: #2563eb;
            --bg-canvas: #0f172a;
            --card-bg: #1e293b;
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-radius-lg: 20px;
            --border-radius-md: 12px;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(145deg, #0b0f19 0%, #0f172a 50%, #1e1e38 100%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            margin: 0;
        }

        .portal-container {
            max-width: 640px;
            width: 100%;
            margin: 0 auto;
        }

        .portal-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: var(--border-radius-lg);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            overflow: hidden;
        }

        .portal-header {
            padding: 32px 32px 24px;
            background: linear-gradient(180deg, rgba(59, 130, 246, 0.08) 0%, transparent 100%);
            border-bottom: 1px solid var(--card-border);
            text-align: center;
            position: relative;
        }

        .portal-badge-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(99, 102, 241, 0.2) 100%);
            border: 1px solid rgba(96, 165, 250, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #60a5fa;
            font-size: 2rem;
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.3);
        }

        .portal-title {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 6px;
            color: #ffffff;
        }

        .portal-subtitle {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin: 0;
        }

        .portal-body {
            padding: 28px 32px 36px;
        }

        /* Student Record Banner */
        .student-banner {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: var(--border-radius-md);
            padding: 18px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .student-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .student-meta {
            flex: 1;
            min-width: 0;
        }

        .student-name {
            font-weight: 700;
            font-size: 1.05rem;
            color: #ffffff;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .student-sub {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .status-pill {
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            flex-shrink: 0;
        }

        .status-absent {
            background: rgba(239, 68, 68, 0.16);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .status-late {
            background: rgba(245, 158, 11, 0.16);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        /* Class Details Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }

        .info-chip {
            background: rgba(15, 23, 42, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 10px 14px;
        }

        .info-chip-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            margin-bottom: 2px;
        }

        .info-chip-val {
            font-size: 0.88rem;
            font-weight: 600;
            color: #f1f5f9;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Form Controls */
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-control, .form-select {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: var(--border-radius-md);
            color: #ffffff;
            padding: 12px 16px;
            font-size: 0.92rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            background: rgba(15, 23, 42, 0.9);
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
            color: #ffffff;
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .form-select option {
            background: #1e293b;
            color: #ffffff;
        }

        /* Drag & Drop Upload Zone */
        .dropzone {
            border: 2px dashed rgba(255, 255, 255, 0.15);
            border-radius: var(--border-radius-md);
            background: rgba(15, 23, 42, 0.35);
            padding: 24px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .dropzone:hover, .dropzone.dragover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.06);
        }

        .dropzone-icon {
            font-size: 2.2rem;
            color: #60a5fa;
            margin-bottom: 8px;
        }

        .dropzone-text {
            font-weight: 600;
            font-size: 0.9rem;
            color: #f1f5f9;
            margin-bottom: 4px;
        }

        .dropzone-hint {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin: 0;
        }

        /* Attachment Preview Card */
        .attachment-preview {
            display: none;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: var(--border-radius-md);
            padding: 12px 16px;
            margin-top: 12px;
            align-items: center;
            gap: 14px;
        }

        .attachment-thumb {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .attachment-doc-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .attachment-details {
            flex: 1;
            min-width: 0;
        }

        .attachment-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: #f8fafc;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .attachment-size {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .btn-remove-attachment {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 0.8rem;
            transition: all 0.2s;
        }

        .btn-remove-attachment:hover {
            background: rgba(239, 68, 68, 0.3);
            color: #ffffff;
        }

        /* Submit Button */
        .btn-submit {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: var(--border-radius-md);
            padding: 14px 24px;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
            cursor: pointer;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.5);
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }

        .btn-submit:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .portal-security-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 0.75rem;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        @media (max-width: 576px) {
            .portal-header {
                padding: 24px 20px 20px;
            }
            .portal-body {
                padding: 20px;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
            .student-banner {
                flex-direction: column;
                text-align: center;
            }
            .student-avatar {
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <div class="portal-container">
        <div class="portal-card">
            <div class="portal-header">
                <div class="portal-badge-icon">
                    <i class="bi bi-file-earmark-medical"></i>
                </div>
                <h1 class="portal-title">Submit Excuse Letter</h1>
                <p class="portal-subtitle">Smart Classroom Attendance • Verified Parent / Guardian Portal</p>
            </div>

            <div class="portal-body">
                <!-- Student Banner -->
                <div class="student-banner">
                    <div class="student-avatar">
                        {{ strtoupper(substr($attendance->user->name ?? 'S', 0, 1)) }}
                    </div>
                    <div class="student-meta">
                        <div class="student-name">{{ $attendance->user->name }}</div>
                        <div class="student-sub">
                            Student ID: <strong>{{ $attendance->user->student_number ?? 'N/A' }}</strong>
                            @if($attendance->user->course)
                                &bull; {{ $attendance->user->course }} {{ $attendance->user->section }}
                            @endif
                        </div>
                    </div>
                    <div>
                        <span class="status-pill {{ strtolower($attendance->status) === 'absent' ? 'status-absent' : 'status-late' }}">
                            {{ $attendance->status }}
                        </span>
                    </div>
                </div>

                <!-- Class & Date Details -->
                <div class="info-grid">
                    <div class="info-chip">
                        <div class="info-chip-label"><i class="bi bi-journal-bookmark me-1"></i> Subject / Course</div>
                        <div class="info-chip-val" title="{{ $attendance->subject?->name ?? $attendance->subject_code }}">
                            {{ $attendance->subject_code }} &bull; {{ $attendance->subject?->name ?? 'Class' }}
                        </div>
                    </div>
                    <div class="info-chip">
                        <div class="info-chip-label"><i class="bi bi-calendar-event me-1"></i> Date of Record</div>
                        <div class="info-chip-val">
                            {{ $attendance->date ? $attendance->date->format('l, F j, Y') : 'N/A' }}
                        </div>
                    </div>
                    <div class="info-chip">
                        <div class="info-chip-label"><i class="bi bi-person-badge me-1"></i> Class Instructor</div>
                        <div class="info-chip-val">
                            {{ $attendance->subject?->instructorUser?->name ?? 'Assigned Instructor' }}
                        </div>
                    </div>
                    <div class="info-chip">
                        <div class="info-chip-label"><i class="bi bi-clock-history me-1"></i> Submission Deadline</div>
                        <div class="info-chip-val text-info">
                            {{ now()->addDays(3)->format('M d, Y') }} (Standard)
                        </div>
                    </div>
                </div>

                @if(session('error'))
                    <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #fca5a5; border-radius: var(--border-radius-md);">
                        <i class="bi bi-exclamation-circle me-2"></i> {{ session('error') }}
                    </div>
                @endif

                <div id="clientErrorAlert" class="alert alert-danger d-none" style="background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #fca5a5; border-radius: var(--border-radius-md);">
                    <i class="bi bi-exclamation-circle me-2"></i> <span id="clientErrorMsg"></span>
                </div>

                <form id="guestExcuseForm" action="{{ \Illuminate\Support\Facades\URL::signedRoute('guest.excuse.store', ['attendance' => $attendance->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Reason Category -->
                    <div class="mb-3">
                        <label class="form-label" for="reasonCategory">
                            <span>Primary Reason Category</span>
                            <span class="text-danger small">*</span>
                        </label>
                        <select name="reason_category" id="reasonCategory" class="form-select" required>
                            <option value="" disabled selected>Select the primary reason...</option>
                            <option value="Medical Illness">🩺 Illness / Medical Health Issue</option>
                            <option value="Family Emergency">👨‍👩‍👧 Urgent Family Emergency</option>
                            <option value="Official School Activity">🏛️ Official School / Extracurricular Activity</option>
                            <option value="Severe Weather">🌧️ Severe Weather / Transport Interruption</option>
                            <option value="Personal / Other">📝 Other Urgent Personal Matter</option>
                        </select>
                    </div>

                    <!-- Reason Description -->
                    <div class="mb-3">
                        <label class="form-label" for="reasonText">
                            <span>Explanation & Details</span>
                            <span class="small text-muted" id="charCount">0 / 1000</span>
                        </label>
                        <textarea name="reason" id="reasonText" class="form-control" rows="4" maxlength="1000" required placeholder="Please provide specific details explaining why the student was absent or late, including symptoms, recovery dates, or emergency circumstances..."></textarea>
                    </div>

                    <!-- Parent / Guardian Contact Details -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="parentName">
                                <span>Parent / Guardian Name</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: rgba(15, 23, 42, 0.8); border-color: rgba(255,255,255,0.12); color: var(--text-muted);">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" name="parent_name" id="parentName" class="form-control" placeholder="e.g. Maria Santos">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="parentPhone">
                                <span>Contact Phone Number</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: rgba(15, 23, 42, 0.8); border-color: rgba(255,255,255,0.12); color: var(--text-muted);">
                                    <i class="bi bi-telephone"></i>
                                </span>
                                <input type="tel" name="parent_phone" id="parentPhone" class="form-control" placeholder="e.g. 0917-123-4567">
                            </div>
                        </div>
                    </div>

                    <!-- Attachment Upload Zone -->
                    <div class="mb-4">
                        <label class="form-label">
                            <span>Supporting Document / Medical Certificate</span>
                            <span class="small text-muted">Optional (Max 5MB)</span>
                        </label>

                        <div id="dropzone" class="dropzone">
                            <input type="file" id="attachmentInput" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf" class="d-none">
                            <div class="dropzone-icon">
                                <i class="bi bi-cloud-arrow-up"></i>
                            </div>
                            <div class="dropzone-text">Click or drag file here to attach document</div>
                            <p class="dropzone-hint">Accepted formats: JPG, PNG, WEBP, or PDF (Medical note, doctor's excuse, parent letter)</p>
                        </div>

                        <!-- Preview Card -->
                        <div id="attachmentPreview" class="attachment-preview">
                            <img id="previewImg" class="attachment-thumb d-none" alt="Document Preview">
                            <div id="previewDocIcon" class="attachment-doc-icon d-none">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </div>
                            <div class="attachment-details">
                                <div id="previewName" class="attachment-name">document.pdf</div>
                                <div id="previewSize" class="attachment-size">0 KB</div>
                            </div>
                            <button type="button" id="removeAttachmentBtn" class="btn-remove-attachment">
                                <i class="bi bi-trash3 me-1"></i> Remove
                            </button>
                        </div>
                    </div>

                    <!-- Submit Action -->
                    <button type="submit" id="submitBtn" class="btn-submit">
                        <i class="bi bi-send-check"></i>
                        <span id="submitBtnText">Submit Excuse Letter for Review</span>
                        <div id="submitSpinner" class="spinner-border spinner-border-sm d-none" role="status"></div>
                    </button>
                </form>

                <div class="portal-security-footer">
                    <i class="bi bi-shield-lock-fill text-primary"></i>
                    <span>Secure single-use verified portal. Submissions are delivered directly to the instructor.</span>
                </div>
            </div>
        </div>
    </div>

    <script nonce="{{ csp_nonce() }}">
        document.addEventListener('DOMContentLoaded', function() {
            const dropzone = document.getElementById('dropzone');
            const fileInput = document.getElementById('attachmentInput');
            const preview = document.getElementById('attachmentPreview');
            const previewImg = document.getElementById('previewImg');
            const previewDocIcon = document.getElementById('previewDocIcon');
            const previewName = document.getElementById('previewName');
            const previewSize = document.getElementById('previewSize');
            const removeBtn = document.getElementById('removeAttachmentBtn');
            const charCount = document.getElementById('charCount');
            const reasonText = document.getElementById('reasonText');
            const form = document.getElementById('guestExcuseForm');
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnText = document.getElementById('submitBtnText');
            const submitSpinner = document.getElementById('submitSpinner');
            const clientErrorAlert = document.getElementById('clientErrorAlert');
            const clientErrorMsg = document.getElementById('clientErrorMsg');

            function showError(msg) {
                clientErrorMsg.textContent = msg;
                clientErrorAlert.classList.remove('d-none');
                clientErrorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            function clearError() {
                clientErrorAlert.classList.add('d-none');
                clientErrorMsg.textContent = '';
            }

            // Character Counter
            reasonText.addEventListener('input', function() {
                const len = this.value.length;
                charCount.textContent = `${len} / 1000`;
                if (len >= 950) {
                    charCount.classList.add('text-warning');
                } else {
                    charCount.classList.remove('text-warning');
                }
            });

            // Dropzone click triggers input
            dropzone.addEventListener('click', function() {
                fileInput.click();
            });

            // Drag and drop events
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('dragover');
                });
            });

            dropzone.addEventListener('drop', function(e) {
                const files = e.dataTransfer.files;
                if (files && files.length > 0) {
                    handleSelectedFile(files[0]);
                }
            });

            fileInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    handleSelectedFile(this.files[0]);
                }
            });

            function formatBytes(bytes, decimals = 1) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const dm = decimals < 0 ? 0 : decimals;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
            }

            function handleSelectedFile(file) {
                clearError();
                const maxBytes = 5 * 1024 * 1024; // 5MB

                if (file.size > maxBytes) {
                    showError('Selected file exceeds the 5MB size limit. Please upload a smaller file or compressed image.');
                    fileInput.value = '';
                    preview.style.display = 'none';
                    return;
                }

                const validMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'];
                const validExts = /\.(jpg|jpeg|png|webp|pdf)$/i;

                if (!validMimes.includes(file.type) && !validExts.test(file.name)) {
                    showError('Invalid file type. Only JPG, PNG, WEBP, and PDF documents are supported.');
                    fileInput.value = '';
                    preview.style.display = 'none';
                    return;
                }

                previewName.textContent = file.name;
                previewSize.textContent = formatBytes(file.size);

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        previewImg.classList.remove('d-none');
                        previewDocIcon.classList.add('d-none');
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewImg.classList.add('d-none');
                    previewDocIcon.classList.remove('d-none');
                }

                preview.style.display = 'flex';
            }

            removeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                fileInput.value = '';
                preview.style.display = 'none';
                previewImg.src = '';
                clearError();
            });

            // Prevent double submission
            form.addEventListener('submit', function(e) {
                clearError();
                const text = reasonText.value.trim();
                if (text.length < 5) {
                    e.preventDefault();
                    showError('Please provide a meaningful explanation (at least 5 characters).');
                    reasonText.focus();
                    return;
                }

                submitBtn.disabled = true;
                submitBtnText.textContent = 'Submitting Excuse...';
                submitSpinner.classList.remove('d-none');
            });
        });
    </script>
</body>
</html>
