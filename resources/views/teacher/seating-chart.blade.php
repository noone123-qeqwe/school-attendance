@extends('teacher.layout')
@section('page-title', 'Seating Chart: ' . $subject->name)

@section('content')
<style>
    .seating-dashboard {
        padding-bottom: 24px;
    }
    .grid-container {
        display: grid;
        gap: 12px;
        margin: 20px auto;
        padding: 24px;
        background: rgba(30, 10, 15, 0.4);
        border: 2px dashed rgba(255,255,255,0.1);
        border-radius: 24px;
        min-height: 400px;
        justify-content: center;
        overflow-x: auto;
    }
    .desk {
        width: 100px;
        height: 100px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }
    .desk.drag-over {
        background: rgba(253,230,138,0.2);
        border-color: #fde68a;
        transform: scale(1.05);
    }
    .desk.assigned {
        background: rgba(67, 12, 29, 0.4);
        border-color: rgba(253,230,138,0.3);
    }
    .desk-student-name {
        font-size: 0.75rem;
        font-weight: 700;
        color: #f8fafc;
        margin-top: 6px;
        line-height: 1.2;
        word-break: break-word;
    }
    .desk-remove {
        position: absolute;
        top: -6px;
        right: -6px;
        width: 20px;
        height: 20px;
        background: #dc2626;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.2s;
    }
    .desk.assigned:hover .desk-remove {
        opacity: 1;
    }
    .students-pool {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 20px;
        height: 100%;
        max-height: 600px;
        overflow-y: auto;
    }
    .student-item {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.12);
        padding: 10px 14px;
        border-radius: 12px;
        margin-bottom: 8px;
        cursor: grab;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: background 0.2s;
    }
    .student-item:active {
        cursor: grabbing;
    }
    .student-item:hover {
        background: rgba(255,255,255,0.12);
    }
    .student-item.assigned {
        opacity: 0.4;
        cursor: not-allowed;
    }
    .student-avatar {
        width: 32px;
        height: 32px;
        background: #7c2d12;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.8rem;
        color: #fde68a;
    }
    .tch-btn-save {
        background: linear-gradient(135deg, #059669, #047857);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 14px;
        font-weight: 700;
        box-shadow: 0 12px 24px rgba(4,120,87,0.25);
        transition: transform 0.2s;
    }
    .tch-btn-save:hover {
        transform: translateY(-2px);
    }
    
    /* Rapid Roll Call Mode */
    .roll-call-mode .desk-remove { display: none !important; }
    .roll-call-mode .desk {
        cursor: pointer;
    }
    .roll-call-mode .desk.status-present {
        background: rgba(16,185,129,0.2);
        border-color: #10b981;
    }
    .roll-call-mode .desk.status-late {
        background: rgba(245,158,11,0.2);
        border-color: #f59e0b;
    }
    .roll-call-mode .desk.status-absent {
        background: rgba(239,68,68,0.2);
        border-color: #ef4444;
    }
    .status-badge {
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        padding: 2px 8px;
        border-radius: 99px;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: none;
    }
    .roll-call-mode .desk.status-present .status-badge { display: block; background: #10b981; color: white; }
    .roll-call-mode .desk.status-late .status-badge { display: block; background: #f59e0b; color: white; }
    .roll-call-mode .desk.status-absent .status-badge { display: block; background: #ef4444; color: white; }

    /* Roll Call Warning Banner */
    .roll-call-warning {
        display: none;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        border-radius: var(--ds-radius-md, 12px);
        background: var(--ds-warning-alpha, rgba(251,191,36,0.15));
        border: 1px solid var(--ds-warning-border, rgba(251,191,36,0.25));
        color: var(--ds-warning-text, #fbbf24);
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 16px;
        animation: dsFadeSlideIn 0.3s ease forwards;
    }
    .roll-call-warning.active { display: flex; }
    .roll-call-warning i { font-size: 1.2rem; flex-shrink: 0; }

    /* Roll Call Summary Counter */
    .roll-call-summary {
        display: none;
        gap: 16px;
        align-items: center;
        padding: 12px 18px;
        border-radius: var(--ds-radius-md, 12px);
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--ds-border, rgba(207,164,111,0.12));
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    .roll-call-summary.active { display: flex; }
    .roll-call-counter {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        font-weight: 700;
    }
    .roll-call-counter .dot {
        width: 10px; height: 10px;
        border-radius: 50%;
    }
    .roll-call-counter .dot-present { background: #10b981; }
    .roll-call-counter .dot-late { background: #f59e0b; }
    .roll-call-counter .dot-absent { background: #ef4444; }

    /* Mobile responsive for seating chart */
    @media (max-width: 768px) {
        .grid-container {
            padding: 12px;
            gap: 8px;
            border-radius: 16px;
            justify-content: flex-start;
        }
        .desk {
            width: 72px;
            height: 72px;
            border-radius: 12px;
            padding: 4px;
        }
        .desk-student-name {
            font-size: 0.6rem;
        }
        .desk i.bi-person-fill {
            font-size: 1.2rem !important;
        }
        .students-pool {
            max-height: 300px;
            padding: 14px;
        }
    }
</style>

<div class="seating-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 style="font-weight:800; color:#fde68a; margin:0;">Seating Chart</h4>
            <div style="color:#cbd5e1; font-size:0.9rem;">Drag and drop students to assign desks, or use Rapid Roll Call.</div>
        </div>
        <div class="d-flex gap-2">
            <button onclick="toggleRollCall()" id="btnRollCall" class="btn" style="background:rgba(245,158,11,0.2); color:#fcd34d; border:1px solid rgba(245,158,11,0.4); border-radius:12px; font-weight:600;">
                <i class="bi bi-list-check me-1"></i> Rapid Roll Call
            </button>
            <button onclick="saveSeatingChart()" id="btnSave" class="tch-btn-save">
                <i class="bi bi-floppy-fill me-1"></i> Save Layout
            </button>
        </div>
    </div>

    <!-- Roll Call Warning Banner (hidden until Roll Call mode activated) -->
    <div class="roll-call-warning" id="rollCallWarning">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <strong>Roll Call Mode Active — Marks Are Visual Only</strong><br>
            <span style="font-weight:400;opacity:0.85;">Attendance marks made here are NOT saved to the server. This feature is a visual preview only. A future update will add backend persistence.</span>
        </div>
    </div>

    <!-- Roll Call Summary (hidden until Roll Call mode activated) -->
    <div class="roll-call-summary" id="rollCallSummary">
        <div class="roll-call-counter"><div class="dot dot-present"></div> Present: <span id="rcPresent">0</span></div>
        <div class="roll-call-counter"><div class="dot dot-late"></div> Late: <span id="rcLate">0</span></div>
        <div class="roll-call-counter"><div class="dot dot-absent"></div> Absent: <span id="rcAbsent">0</span></div>
        <div class="roll-call-counter" style="color:var(--ds-text-muted,#8f826f);">Total Assigned: <span id="rcTotal">0</span></div>
    </div>

    <div class="row g-4">
        <!-- Settings & Students -->
        <div class="col-lg-3">
            <div class="students-pool">
                <div class="mb-4">
                    <label class="ds-label">Grid Size</label>
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background:rgba(255,255,255,0.1); color:white; border:none; border-radius:var(--ds-radius-sm,8px) 0 0 var(--ds-radius-sm,8px);">Rows</span>
                            <input type="number" id="gridRows" class="ds-input text-center" style="border-radius:0 var(--ds-radius-sm,8px) var(--ds-radius-sm,8px) 0;" value="{{ $seatingChart->rows }}" min="2" max="15" onchange="generateGrid()">
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background:rgba(255,255,255,0.1); color:white; border:none; border-radius:var(--ds-radius-sm,8px) 0 0 var(--ds-radius-sm,8px);">Cols</span>
                            <input type="number" id="gridCols" class="ds-input text-center" style="border-radius:0 var(--ds-radius-sm,8px) var(--ds-radius-sm,8px) 0;" value="{{ $seatingChart->cols }}" min="2" max="15" onchange="generateGrid()">
                        </div>
                    </div>
                </div>

                <label class="ds-label" style="margin-bottom:12px;">Students</label>
                <div id="studentList">
                    @foreach($students as $student)
                        <div class="student-item" draggable="true" ondragstart="dragStart(event, {{ $student->id }}, '{{ addslashes($student->name) }}')" id="student-{{ $student->id }}">
                            <div class="student-avatar">{{ substr($student->name, 0, 1) }}</div>
                            <div style="font-size:0.85rem; font-weight:600; color:#f8fafc;">{{ $student->name }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Grid -->
        <div class="col-lg-9">
            <div style="text-align:center; padding:10px; background:rgba(255,255,255,0.05); border-radius:12px; margin-bottom:10px; font-weight:700; color:#cbd5e1; letter-spacing:4px; text-transform:uppercase;">Teacher's Desk</div>
            <div class="grid-container" id="seatingGrid">
                <!-- Generated via JS -->
            </div>
        </div>
    </div>
</div>

<script>
    let gridData = {!! json_encode($seatingChart->grid_data ?? []) !!};
    let rollCallMode = false;
    
    // Fallback if gridData is empty array from backend instead of object
    if (Array.isArray(gridData)) {
        gridData = {};
    }

    const students = {!! json_encode($students->keyBy('id')) !!};

    function generateGrid() {
        const rows = parseInt(document.getElementById('gridRows').value);
        const cols = parseInt(document.getElementById('gridCols').value);
        const grid = document.getElementById('seatingGrid');
        
        grid.style.gridTemplateColumns = `repeat(${cols}, 100px)`;
        grid.innerHTML = '';

        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                const cellId = `${r}-${c}`;
                const studentId = gridData[cellId];
                
                const desk = document.createElement('div');
                desk.className = 'desk';
                desk.dataset.row = r;
                desk.dataset.col = c;
                
                desk.ondragover = dragOver;
                desk.ondragleave = dragLeave;
                desk.ondrop = drop;
                desk.onclick = () => handleDeskClick(cellId);

                if (studentId && students[studentId]) {
                    const student = students[studentId];
                    desk.classList.add('assigned');
                    desk.dataset.studentId = studentId;
                    desk.innerHTML = `
                        <i class="bi bi-person-fill" style="font-size:1.8rem; color:#fde68a;"></i>
                        <div class="desk-student-name">${student.name}</div>
                        <div class="desk-remove" onclick="removeStudent(event, '${cellId}')"><i class="bi bi-x"></i></div>
                        <div class="status-badge">Present</div>
                    `;
                    
                    // Mark student as assigned in the list
                    const studentEl = document.getElementById(`student-${studentId}`);
                    if (studentEl) {
                        studentEl.classList.add('assigned');
                        studentEl.setAttribute('draggable', 'false');
                    }
                } else {
                    desk.innerHTML = `<i class="bi bi-plus" style="font-size:2rem; color:rgba(255,255,255,0.2);"></i><div class="status-badge"></div>`;
                }
                
                grid.appendChild(desk);
            }
        }
        
        updateStudentListVisibility();
    }

    function dragStart(event, studentId, studentName) {
        if (rollCallMode) return;
        event.dataTransfer.setData('studentId', studentId);
        event.dataTransfer.setData('studentName', studentName);
    }

    function dragOver(event) {
        if (rollCallMode) return;
        event.preventDefault();
        const desk = event.currentTarget;
        if (!desk.classList.contains('assigned')) {
            desk.classList.add('drag-over');
        }
    }

    function dragLeave(event) {
        if (rollCallMode) return;
        event.currentTarget.classList.remove('drag-over');
    }

    function drop(event) {
        if (rollCallMode) return;
        event.preventDefault();
        const desk = event.currentTarget;
        desk.classList.remove('drag-over');

        if (desk.classList.contains('assigned')) return;

        const studentId = event.dataTransfer.getData('studentId');
        const cellId = `${desk.dataset.row}-${desk.dataset.col}`;

        // Assign
        gridData[cellId] = studentId;
        generateGrid();
    }

    function removeStudent(event, cellId) {
        if (rollCallMode) return;
        event.stopPropagation();
        
        const studentId = gridData[cellId];
        delete gridData[cellId];
        
        const studentEl = document.getElementById(`student-${studentId}`);
        if (studentEl) {
            studentEl.classList.remove('assigned');
            studentEl.setAttribute('draggable', 'true');
        }
        
        generateGrid();
    }

    function updateStudentListVisibility() {
        // Reset all first
        document.querySelectorAll('.student-item').forEach(el => {
            el.classList.remove('assigned');
            el.setAttribute('draggable', 'true');
        });
        
        // Mark assigned
        Object.values(gridData).forEach(studentId => {
            const el = document.getElementById(`student-${studentId}`);
            if (el) {
                el.classList.add('assigned');
                el.setAttribute('draggable', 'false');
            }
        });
    }

    function saveSeatingChart() {
        const rows = document.getElementById('gridRows').value;
        const cols = document.getElementById('gridCols').value;
        const btn = document.getElementById('btnSave');
        
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
        btn.disabled = true;

        fetch(`{{ route('teacher.subjects.seating-chart.save', $subject->code) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ grid_data: gridData, rows, cols })
        })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Saved!';
            btn.classList.add('bg-success');
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-floppy-fill me-1"></i> Save Layout';
                btn.classList.remove('bg-success');
                btn.disabled = false;
            }, 2000);
        })
        .catch(err => {
            if (typeof showPremiumToast === 'function') {
                showPremiumToast('Failed to save seating layout. Please try again.', 'error');
            }
            btn.innerHTML = '<i class="bi bi-floppy-fill me-1"></i> Save Layout';
            btn.disabled = false;
        });
    }

    // --- RAPID ROLL CALL ---
    let rollCallDirty = false; // Track if any roll call marks have been made

    function toggleRollCall() {
        rollCallMode = !rollCallMode;
        const grid = document.getElementById('seatingGrid');
        const btn = document.getElementById('btnRollCall');
        const warning = document.getElementById('rollCallWarning');
        const summary = document.getElementById('rollCallSummary');
        
        if (rollCallMode) {
            grid.classList.add('roll-call-mode');
            btn.style.background = '#f59e0b';
            btn.style.color = 'white';
            btn.innerHTML = '<i class="bi bi-record-circle me-1"></i> Exit Roll Call';
            document.getElementById('btnSave').style.display = 'none';
            warning.classList.add('active');
            summary.classList.add('active');
            
            // Initialize all assigned desks as Present visually
            document.querySelectorAll('.desk.assigned').forEach(desk => {
                desk.classList.add('status-present');
                desk.querySelector('.status-badge').textContent = 'Present';
            });
            rollCallDirty = true;
            updateRollCallSummary();
        } else {
            grid.classList.remove('roll-call-mode');
            btn.style.background = 'rgba(245,158,11,0.2)';
            btn.style.color = '#fcd34d';
            btn.innerHTML = '<i class="bi bi-list-check me-1"></i> Rapid Roll Call';
            document.getElementById('btnSave').style.display = 'block';
            warning.classList.remove('active');
            summary.classList.remove('active');
            
            // Remove status classes
            document.querySelectorAll('.desk').forEach(desk => {
                desk.classList.remove('status-present', 'status-late', 'status-absent');
            });
            rollCallDirty = false;
        }
    }

    function updateRollCallSummary() {
        const present = document.querySelectorAll('.desk.status-present').length;
        const late = document.querySelectorAll('.desk.status-late').length;
        const absent = document.querySelectorAll('.desk.status-absent').length;
        const total = document.querySelectorAll('.desk.assigned').length;
        document.getElementById('rcPresent').textContent = present;
        document.getElementById('rcLate').textContent = late;
        document.getElementById('rcAbsent').textContent = absent;
        document.getElementById('rcTotal').textContent = total;
    }

    function handleDeskClick(cellId) {
        if (!rollCallMode) return;
        
        const studentId = gridData[cellId];
        if (!studentId) return; // empty desk

        const desk = document.querySelector(`.desk[data-row="${cellId.split('-')[0]}"][data-col="${cellId.split('-')[1]}"]`);
        const badge = desk.querySelector('.status-badge');
        
        let newStatus = 'Present';
        
        if (desk.classList.contains('status-present')) {
            desk.classList.remove('status-present');
            desk.classList.add('status-late');
            badge.textContent = 'Late';
            newStatus = 'Late';
        } else if (desk.classList.contains('status-late')) {
            desk.classList.remove('status-late');
            desk.classList.add('status-absent');
            badge.textContent = 'Absent';
            newStatus = 'Absent';
        } else {
            desk.classList.remove('status-absent');
            desk.classList.add('status-present');
            badge.textContent = 'Present';
            newStatus = 'Present';
        }
        
        // NOTE: Roll call marks are currently visual-only.
        // Backend persistence requires a new API endpoint (e.g. POST /teacher/rapid-roll-call)
        // that accepts {subject_code, student_id, status, date} and creates/updates attendance records.
        // This is tracked as a functional bug, not a UI issue.
        updateRollCallSummary();
    }

    // Prevent accidental data loss if roll call marks exist
    window.addEventListener('beforeunload', function(e) {
        if (rollCallMode && rollCallDirty) {
            e.preventDefault();
            e.returnValue = 'You have unsaved roll call marks. Are you sure you want to leave?';
            return e.returnValue;
        }
    });

    document.addEventListener('DOMContentLoaded', generateGrid);
</script>
@endsection
