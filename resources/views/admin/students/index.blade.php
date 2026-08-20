@extends('layouts.app')

@section('title', 'Students')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h1 class="saas-heading saas-heading-lg mb-1">Students</h1>
        <p class="saas-text-muted m-0">Manage student enrollments and records.</p>
    </div>
    
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.students.template') }}" class="btn btn-outline" style="border-color: rgba(207,164,111,0.3); color: var(--gold);" title="Download CSV Template">
            <i class="bi bi-download"></i> Template
        </a>
        <button type="button" class="btn btn-outline" style="border-color: rgba(207,164,111,0.3); color: var(--gold);" onclick="openImportModal()">
            <i class="bi bi-file-earmark-arrow-up"></i> Import CSV
        </button>
        <a href="{{ route('admin.students.csv', request()->query()) }}" class="btn btn-outline" style="border-color: rgba(207,164,111,0.3); color: var(--gold);">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
        </a>
        <a href="{{ route('admin.students.preview', request()->query()) }}" class="btn btn-outline" style="border-color: rgba(207,164,111,0.3); color: var(--gold);">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </a>
        <a href="{{ route('admin.student.create') }}" class="btn btn-primary" style="background: var(--gold); color: #1a0808; font-weight: 600; border: none;">
            <i class="bi bi-plus-lg"></i> Add Student
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success d-flex align-items-center mb-4" style="background:rgba(34,197,94,0.15); border:1px solid rgba(34,197,94,0.4); color:#4ade80; border-radius:10px; padding:12px 16px;">
    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
    <div>{{ session('success') }}</div>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger d-flex align-items-center mb-4" style="background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.4); color:#f87171; border-radius:10px; padding:12px 16px;">
    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
    <div>{{ session('error') }}</div>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger mb-4" style="background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.4); color:#f87171; border-radius:10px; padding:12px 16px;">
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<x-card title="Student Directory" icon="bi bi-people">
    <x-slot name="headerActions">
        {{-- Unified filter bar --}}
        <div class="d-flex align-items-center gap-2 flex-wrap flex-md-nowrap justify-content-end" style="width:100%;">

            {{-- Live search input with embedded icon --}}
            <div class="position-relative" style="min-width:220px; flex:1; max-width:300px;">
                <i class="bi bi-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--gold); font-size:0.85rem; pointer-events:none;"></i>
                <input
                    type="text"
                    id="studentSearchInput"
                    placeholder="Name, ID or Email..."
                    value="{{ request('search') }}"
                    autocomplete="off"
                    class="form-control"
                    style="height:38px; padding-left:36px; padding-right:32px; background:rgba(0,0,0,0.35); border:1px solid rgba(207,164,111,0.3); border-radius:8px; color:#f3e7cd; font-size:0.875rem;"
                >
                {{-- Spinner --}}
                <span id="searchSpinner" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); color:var(--gold);">
                    <i class="bi bi-arrow-repeat spin"></i>
                </span>
            </div>
            
            {{-- Course Filter --}}
            <select id="filterCourse" class="form-select" style="height:38px; min-width:130px; width:auto; background-color:#1e1414; border:1px solid rgba(207,164,111,0.3); color:#f3e7cd; border-radius:8px; font-size:0.875rem; cursor:pointer;">
                <option value="" style="background:#1e1414; color:#f3e7cd;">All Courses</option>
                @foreach(['BSCS','BSIT','BSIS'] as $c)
                <option value="{{ $c }}" {{ request('course')==$c?'selected':'' }} style="background:#1e1414; color:#f3e7cd;">{{ $c }}</option>
                @endforeach
            </select>
            
            {{-- Year Filter (Default: All Students) --}}
            <select id="filterYear" class="form-select" style="height:38px; min-width:130px; width:auto; background-color:#1e1414; border:1px solid rgba(207,164,111,0.3); color:#f3e7cd; border-radius:8px; font-size:0.875rem; cursor:pointer;">
                <option value="" style="background:#1e1414; color:#f3e7cd;">All Students</option>
                <option value="1" {{ request('year_level')=='1'?'selected':'' }} style="background:#1e1414; color:#f3e7cd;">1st Year</option>
                <option value="2" {{ request('year_level')=='2'?'selected':'' }} style="background:#1e1414; color:#f3e7cd;">2nd Year</option>
                <option value="3" {{ request('year_level')=='3'?'selected':'' }} style="background:#1e1414; color:#f3e7cd;">3rd Year</option>
                <option value="4" {{ request('year_level')=='4'?'selected':'' }} style="background:#1e1414; color:#f3e7cd;">4th Year</option>
            </select>

            @if(request()->hasAny(['search','course','year_level']))
            <a href="{{ url('/admin/students') }}" id="clearFiltersBtn" class="btn btn-outline d-flex align-items-center justify-content-center" style="height:38px; color:#f87171; border-color:rgba(239,68,68,0.3); padding:0 12px; border-radius:8px;" title="Clear Filters">
                <i class="bi bi-x-lg"></i>
            </a>
            @endif
        </div>

        {{-- Result count badge --}}
        <div id="searchResultCount" class="mt-2" style="font-size:0.8rem; color:#b39b82; display:none;">
            <span id="resultCountText"></span>
        </div>
    </x-slot>
    
    <div class="table-responsive">
        <table class="adm-table">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" id="selectAll" style="accent-color:var(--gold);" onclick="document.querySelectorAll('.student-checkbox').forEach(c => c.checked = this.checked)"></th>
                    <th>Student</th>
                    <th>ID</th>
                    <th>Course &amp; Year</th>
                    <th>Bound Device</th>
                    <th>Absences</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="studentTableBody">
                @include('admin.students._rows', ['students' => $students])
            </tbody>
        </table>
    </div>
    
    <div id="paginationContainer" class="mt-4 d-flex justify-content-between align-items-center">
        @if($students->hasPages())
        <div style="color: #b39b82; font-size: 0.85rem;" id="pageInfo">
            Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} results
        </div>
        <div id="paginationLinks">
            {{ $students->links('pagination::bootstrap-4') }}
        </div>
        @endif
    </div>
</x-card>

<style>
@keyframes spin { to { transform: translateY(-50%) rotate(360deg); } }
.spin { animation: spin 0.7s linear infinite; }
#studentSearchInput:focus, #filterCourse:focus, #filterYear:focus {
    border-color: var(--gold) !important;
    box-shadow: 0 0 0 2px rgba(207, 164, 111, 0.25) !important;
    outline: none !important;
}
</style>

<script>
(function () {
    const searchInput    = document.getElementById('studentSearchInput');
    const courseSelect   = document.getElementById('filterCourse');
    const yearSelect     = document.getElementById('filterYear');
    const tableBody      = document.getElementById('studentTableBody');
    const spinner        = document.getElementById('searchSpinner');
    const resultCount    = document.getElementById('searchResultCount');
    const resultText     = document.getElementById('resultCountText');
    const pagination     = document.getElementById('paginationContainer');
    const searchUrl      = "{{ route('admin.students.search') }}";

    let debounceTimer = null;
    let currentRequest = null;
    let lastSearchedValue = searchInput.value;

    function getParams() {
        const params = new URLSearchParams();
        const search = searchInput.value.trim();
        const course = courseSelect.value;
        const year   = yearSelect.value;

        if (search) params.append('search', search);
        if (course) params.append('course', course);
        if (year)   params.append('year_level', year);

        return params;
    }

    function doSearch() {
        lastSearchedValue = searchInput.value;
        const params = getParams();
        const queryString = params.toString();
        const url = searchUrl + (queryString ? '?' + queryString : '');

        // Update browser URL without reload
        const pageUrl = "{{ url('/admin/students') }}" + (queryString ? '?' + queryString : '');
        window.history.replaceState({}, '', pageUrl);

        spinner.style.display = 'inline';

        if (currentRequest) currentRequest.abort();
        const ctrl = new AbortController();
        currentRequest = ctrl;

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            signal: ctrl.signal,
        })
        .then(r => r.json())
        .then(data => {
            tableBody.innerHTML = data.html;
            spinner.style.display = 'none';

            // Show result count
            if (searchInput.value.trim() || courseSelect.value || yearSelect.value) {
                resultText.textContent = data.total + ' student' + (data.total !== 1 ? 's' : '') + ' found';
                resultCount.style.display = 'block';
            } else {
                resultCount.style.display = 'none';
            }

            // Update pagination
            if (data.pagination) {
                pagination.innerHTML = data.pagination;
            } else {
                pagination.innerHTML = '';
            }

            // Re-attach select-all handler
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.onclick = () => document.querySelectorAll('.student-checkbox').forEach(c => c.checked = selectAll.checked);
            }
        })
        .catch(err => {
            if (err.name !== 'AbortError') spinner.style.display = 'none';
        });
    }

    function scheduleSearch() {
        clearTimeout(debounceTimer);
        if (searchInput.value.trim() === '') {
            doSearch();
        } else {
            debounceTimer = setTimeout(doSearch, 300);
        }
    }

    // Prevent Enter from navigating away
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(debounceTimer);
            doSearch();
        }
    });

    // Trigger on both input and keyup
    searchInput.addEventListener('input', scheduleSearch);
    
    searchInput.addEventListener('keyup', function (e) {
        if (searchInput.value !== lastSearchedValue) {
            scheduleSearch();
        }
    });

    courseSelect.addEventListener('change', doSearch);
    yearSelect.addEventListener('change', doSearch);
})();

function openImportModal() {
    const modal = document.getElementById('importModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeImportModal() {
    const modal = document.getElementById('importModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImportModal();
    }
});
</script>

<!-- Import CSV Modal -->
<div id="importModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); backdrop-filter:blur(6px); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#1e1414; border:1px solid rgba(207,164,111,0.35); border-radius:16px; width:100%; max-width:520px; padding:28px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.7); color:#f3e7cd; position:relative;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 style="margin:0; font-weight:700; color:var(--gold); display:flex; align-items:center; gap:10px; font-size:1.25rem;">
                <i class="bi bi-file-earmark-arrow-up"></i> Bulk Import Students
            </h4>
            <button type="button" onclick="closeImportModal()" style="background:none; border:none; color:#b39b82; font-size:1.3rem; cursor:pointer; line-height:1;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <p style="font-size:0.875rem; color:#b39b82; margin-bottom:18px; line-height:1.5;">
            Upload a CSV file containing your student records. Existing students (matched by Student Number or Email) will be updated, and new students will be created automatically.
        </p>

        <div style="background:rgba(207,164,111,0.08); border:1px dashed rgba(207,164,111,0.35); border-radius:10px; padding:14px; margin-bottom:22px; font-size:0.825rem;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><strong>Required headers:</strong> <code>Name</code>, <code>Email</code></span>
                <a href="{{ route('admin.students.template') }}" class="btn btn-sm btn-outline" style="border-color:rgba(207,164,111,0.4); color:var(--gold); font-size:0.75rem; padding:2px 8px;">
                    <i class="bi bi-download me-1"></i> Get CSV Template
                </a>
            </div>
            <div style="color:#b39b82; margin-top:6px; font-size:0.775rem;">
                <strong>Optional headers:</strong> <code>Student Number</code>, <code>Course</code>, <code>Year Level</code>, <code>Semester</code>, <code>Section</code>, <code>Password</code>
            </div>
        </div>

        <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label for="csv_file" class="form-label" style="font-size:0.875rem; font-weight:600; color:#f3e7cd; margin-bottom:8px;">Select CSV File</label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv,text/plain" required
                       class="form-control"
                       style="background:rgba(0,0,0,0.35); border:1px solid rgba(207,164,111,0.3); color:#f3e7cd; padding:10px 12px; border-radius:8px;">
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline" style="border-color:rgba(255,255,255,0.2); color:#b39b82; padding:8px 16px;" onclick="closeImportModal()">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" style="background:var(--gold); color:#1a0808; font-weight:700; border:none; padding:8px 20px;">
                    <i class="bi bi-upload me-1"></i> Upload &amp; Import
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
