@extends('layouts.admin_premium')
@section('page-title', 'Seating Chart')

@section('content')
<style>
    .seat-cell {
        width: 80px; height: 60px;
        border: 2px dashed rgba(255,215,145,0.2);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.7rem; font-weight: 600; color: #b39b82;
        cursor: pointer; transition: all 0.2s;
        background: rgba(255,235,190,0.03);
        position: relative; overflow: hidden; text-align: center; padding: 4px;
    }
    .seat-cell.occupied {
        background: rgba(255,215,145,0.1);
        border-color: rgba(255,215,145,0.35);
        color: #f3e7cd;
    }
    .seat-cell:hover { border-color: rgba(255,215,145,0.5); background: rgba(255,235,190,0.08); }
    .seat-grid { display: grid; gap: 10px; }
</style>

<div style="max-width:1100px;margin:0 auto;">
    <div style="margin-bottom:24px;">
        <a href="{{ route('admin.subjects') }}" style="color:#b39b82;font-size:0.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:10px;">
            <i class="bi bi-arrow-left"></i> Back to Subjects
        </a>
        <div style="font-size:1.4rem;font-weight:800;color:#f3e7cd;">Seating Chart</div>
        <div style="font-size:0.9rem;color:#b39b82;margin-top:2px;">{{ $subject->name }} ({{ $subject->code }})</div>
    </div>

    @if(session('success'))
    <div style="background:rgba(34,197,94,0.14);border:1px solid rgba(34,197,94,0.3);color:#bbf7d0;border-radius:12px;padding:12px 16px;margin-bottom:18px;font-size:0.88rem;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start;">
        <!-- Grid Area -->
        <div style="background:rgba(255,235,190,0.04);border:1px solid rgba(255,215,145,0.1);border-radius:14px;padding:24px;">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap;">
                <div>
                    <label style="font-size:0.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:4px;">Rows</label>
                    <input type="number" id="rowCount" min="1" max="20" value="{{ $chart->rows }}" onchange="rebuildGrid()"
                        style="width:70px;padding:8px 10px;border-radius:8px;border:1.5px solid rgba(255,215,145,0.15);background:rgba(255,235,190,0.04);color:#f3e7cd;font-size:0.875rem;">
                </div>
                <div>
                    <label style="font-size:0.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:4px;">Columns</label>
                    <input type="number" id="colCount" min="1" max="20" value="{{ $chart->cols }}" onchange="rebuildGrid()"
                        style="width:70px;padding:8px 10px;border-radius:8px;border:1.5px solid rgba(255,215,145,0.15);background:rgba(255,235,190,0.04);color:#f3e7cd;font-size:0.875rem;">
                </div>
                <div style="align-self:flex-end;">
                    <button onclick="saveChart()" style="padding:10px 20px;background:#7f432e;color:#f3e7cd;border:none;border-radius:10px;font-size:0.875rem;font-weight:700;cursor:pointer;">
                        <i class="bi bi-save me-2"></i>Save Chart
                    </button>
                </div>
                <div style="align-self:flex-end;">
                    <button onclick="clearChart()" style="padding:10px 18px;background:rgba(220,38,38,0.15);color:#fca5a5;border:1px solid rgba(220,38,38,0.2);border-radius:10px;font-size:0.875rem;font-weight:600;cursor:pointer;">
                        <i class="bi bi-x-lg me-2"></i>Clear All
                    </button>
                </div>
            </div>
            <div style="font-size:0.8rem;color:#b39b82;margin-bottom:14px;display:flex;align-items:center;gap:6px;">
                <i class="bi bi-info-circle"></i> Click a seat then select a student to assign. Click an occupied seat to clear it.
            </div>
            <div id="seatGrid" class="seat-grid" style="grid-template-columns: repeat({{ $chart->cols }}, 80px);">
            </div>
        </div>

        <!-- Student List -->
        <div style="background:rgba(255,235,190,0.04);border:1px solid rgba(255,215,145,0.1);border-radius:14px;padding:20px;position:sticky;top:80px;">
            <div style="font-size:0.88rem;font-weight:700;color:#f3e7cd;margin-bottom:14px;">Students ({{ $students->count() }})</div>
            <input type="text" id="studentSearch" placeholder="Search students..." oninput="filterStudents()"
                style="width:100%;padding:8px 12px;border-radius:8px;border:1.5px solid rgba(255,215,145,0.12);background:rgba(255,235,190,0.04);color:#f3e7cd;font-size:0.8rem;margin-bottom:12px;box-sizing:border-box;">
            <div id="studentList" style="max-height:400px;overflow-y:auto;display:flex;flex-direction:column;gap:6px;">
                @foreach($students as $student)
                <div class="student-item" data-id="{{ $student->id }}" data-name="{{ $student->name }}"
                    onclick="selectStudent({{ $student->id }}, '{{ addslashes($student->name) }}')"
                    style="padding:8px 12px;border-radius:8px;background:rgba(255,235,190,0.05);border:1px solid rgba(255,215,145,0.1);cursor:pointer;transition:background 0.15s;font-size:0.8rem;color:#f3e7cd;"
                    onmouseover="this.style.background='rgba(255,235,190,0.12)'" onmouseout="if(!this.classList.contains('selected'))this.style.background='rgba(255,235,190,0.05)'">
                    {{ $student->name }}
                    <span style="font-size:0.72rem;color:#b39b82;display:block;">{{ $student->student_number }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
const existingData = @json($chart->grid_data ?? []);
const students = @json($students->map(fn($s) => ['id' => $s->id, 'name' => $s->name]));
let selectedStudentId = null;
let selectedStudentName = null;
// grid[row][col] = { id, name } or null
let grid = [];

function initGrid() {
    const rows = parseInt(document.getElementById('rowCount').value);
    const cols = parseInt(document.getElementById('colCount').value);

    grid = Array.from({ length: rows }, () => Array(cols).fill(null));

    // Load existing data
    if (Array.isArray(existingData)) {
        existingData.forEach(cell => {
            if (cell && cell.row < rows && cell.col < cols && cell.student_id) {
                grid[cell.row][cell.col] = { id: cell.student_id, name: cell.student_name };
            }
        });
    }

    renderGrid();
}

function renderGrid() {
    const rows = grid.length;
    const cols = rows > 0 ? grid[0].length : 0;
    const container = document.getElementById('seatGrid');
    container.style.gridTemplateColumns = `repeat(${cols}, 80px)`;
    container.innerHTML = '';

    for (let r = 0; r < rows; r++) {
        for (let c = 0; c < cols; c++) {
            const cell = document.createElement('div');
            const occupant = grid[r][c];
            cell.className = 'seat-cell' + (occupant ? ' occupied' : '');
            cell.dataset.row = r;
            cell.dataset.col = c;
            cell.textContent = occupant ? occupant.name.split(' ')[0] : `${r+1}-${c+1}`;
            cell.title = occupant ? occupant.name : `Seat ${r+1}-${c+1}`;
            cell.onclick = () => clickSeat(r, c);
            container.appendChild(cell);
        }
    }
}

function clickSeat(r, c) {
    if (grid[r][c]) {
        // Clear seat
        grid[r][c] = null;
        renderGrid();
    } else if (selectedStudentId) {
        // Assign selected student
        grid[r][c] = { id: selectedStudentId, name: selectedStudentName };
        selectedStudentId = null;
        selectedStudentName = null;
        document.querySelectorAll('.student-item.selected').forEach(el => {
            el.classList.remove('selected');
            el.style.background = 'rgba(255,235,190,0.05)';
        });
        renderGrid();
    }
}

function selectStudent(id, name) {
    selectedStudentId = id;
    selectedStudentName = name;
    document.querySelectorAll('.student-item').forEach(el => {
        el.classList.remove('selected');
        el.style.background = 'rgba(255,235,190,0.05)';
    });
    const el = document.querySelector(`.student-item[data-id="${id}"]`);
    if (el) {
        el.classList.add('selected');
        el.style.background = 'rgba(255,215,145,0.18)';
    }
}

function rebuildGrid() {
    const rows = parseInt(document.getElementById('rowCount').value);
    const cols = parseInt(document.getElementById('colCount').value);
    const newGrid = Array.from({ length: rows }, () => Array(cols).fill(null));
    for (let r = 0; r < Math.min(rows, grid.length); r++) {
        for (let c = 0; c < Math.min(cols, grid[r].length); c++) {
            newGrid[r][c] = grid[r][c];
        }
    }
    grid = newGrid;
    renderGrid();
}

function clearChart() {
    if (!confirm('Clear all seat assignments?')) return;
    grid = grid.map(row => row.map(() => null));
    renderGrid();
}

function saveChart() {
    const flat = [];
    grid.forEach((row, r) => {
        row.forEach((cell, c) => {
            if (cell) {
                flat.push({ row: r, col: c, student_id: cell.id, student_name: cell.name });
            }
        });
    });

    fetch('{{ route('admin.seating.chart.save', $subject->code) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({
            grid_data: flat,
            rows: grid.length,
            cols: grid.length > 0 ? grid[0].length : 0,
        }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const btn = document.querySelector('button[onclick="saveChart()"]');
            btn.textContent = '✓ Saved!';
            setTimeout(() => { btn.innerHTML = '<i class="bi bi-save me-2"></i>Save Chart'; }, 2000);
        }
    });
}

function filterStudents() {
    const q = document.getElementById('studentSearch').value.toLowerCase();
    document.querySelectorAll('.student-item').forEach(el => {
        el.style.display = el.dataset.name.toLowerCase().includes(q) ? '' : 'none';
    });
}

initGrid();
</script>
@endsection
