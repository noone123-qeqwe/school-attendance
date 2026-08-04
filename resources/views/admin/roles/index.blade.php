@extends('layouts.admin_premium')

@section('title', 'User Management')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">User Management</h1>
        <p class="saas-text-muted" style="margin:0;">Manage system access, user accounts, and roles.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <button class="saas-btn saas-btn-primary" onclick="openModal('addUserModal')">
            <i class="bi bi-person-plus"></i> Add User
        </button>
    </div>
</div>

<div class="saas-card" style="margin-bottom:24px;">
    <form method="GET" action="{{ route('admin.roles.index') }}" class="saas-card-header" style="gap:16px; flex-wrap:wrap; display:flex;">
        <div class="saas-search" style="width:280px;">
            <i class="bi bi-search"></i>
            <input type="text" name="search" class="saas-search-input" placeholder="Search name, email, or ID..." value="{{ request('search') }}">
        </div>
        
        <div style="display:flex; gap:12px; align-items:center;">
            <select name="role" class="saas-input saas-select" style="width:160px; padding:6px 30px 6px 12px;">
                <option value="">All Roles</option>
                <option value="admin" {{ request('role')=='admin'?'selected':'' }}>Administrator</option>
                <option value="teacher" {{ request('role')=='teacher'?'selected':'' }}>Instructor</option>
                <option value="student" {{ request('role')=='student'?'selected':'' }}>Student</option>
                <option value="parent" {{ request('role')=='parent'?'selected':'' }}>Parent</option>
                <option value="department_head" {{ request('role')=='department_head'?'selected':'' }}>Department Head</option>
            </select>
            
            <button type="submit" class="saas-btn saas-btn-secondary" style="padding:6px 12px;">
                <i class="bi bi-funnel"></i> Filter
            </button>
            
            @if(request()->hasAny(['search','role']))
            <a href="{{ route('admin.roles.index') }}" class="saas-btn saas-btn-secondary" style="padding:6px 12px; color:var(--saas-danger);">
                Clear
            </a>
            @endif
        </div>
    </form>
    
    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" style="accent-color:var(--saas-primary);"></th>
                    <th>User</th>
                    <th>ID / Credentials</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td><input type="checkbox" style="accent-color:var(--saas-primary);"></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <img src="{{ $user->profile_image ? (str_starts_with($user->profile_image, 'http') ? $user->profile_image : asset('storage/'.$user->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=333&color=fff' }}"
                                 style="width:36px;height:36px;border-radius:var(--saas-radius-sm);object-fit:cover;border:1px solid var(--saas-border);">
                            <div>
                                <div style="font-weight:600;font-size:0.875rem;">{{ $user->name }}</div>
                                <div class="saas-text-muted" style="font-size:0.75rem;">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($user->student_number)
                            <span class="saas-badge saas-badge-default" style="font-family:monospace;" title="Student Number">STU: {{ $user->student_number }}</span>
                        @elseif($user->employee_id)
                            <span class="saas-badge saas-badge-default" style="font-family:monospace;" title="Employee ID">EMP: {{ $user->employee_id }}</span>
                        @else
                            <span class="saas-text-muted" style="font-style:italic;">No ID Assigned</span>
                        @endif
                    </td>
                    <td>
                        @if($user->role === 'admin')
                            <span class="saas-badge saas-badge-danger"><i class="bi bi-shield-lock" style="margin-right:4px;"></i> Administrator</span>
                            @if($user->admin_sub_role)
                                <span class="saas-badge" style="background:var(--saas-border); color:var(--saas-text);">{{ ucwords(str_replace('_', ' ', $user->admin_sub_role)) }}</span>
                            @endif
                        @elseif($user->role === 'teacher')
                            <span class="saas-badge saas-badge-info"><i class="bi bi-person-workspace" style="margin-right:4px;"></i> Instructor</span>
                        @elseif($user->role === 'student')
                            <span class="saas-badge saas-badge-success"><i class="bi bi-mortarboard" style="margin-right:4px;"></i> Student</span>
                        @elseif($user->role === 'department_head')
                            <span class="saas-badge saas-badge-primary" style="background:var(--saas-primary);color:#fff;"><i class="bi bi-briefcase" style="margin-right:4px;"></i> Department Head</span>
                        @else
                            <span class="saas-badge saas-badge-warning"><i class="bi bi-people" style="margin-right:4px;"></i> Parent</span>
                        @endif
                    </td>
                    <td>
                        <span class="saas-badge saas-badge-success" style="background:transparent; border:1px solid var(--saas-success);">
                            <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--saas-success);margin-right:4px;"></span> Active
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <button class="saas-btn saas-btn-secondary" style="padding:4px 8px;" title="Edit Roles" onclick="openEditRoleModal({{ $user->id }}, '{{ $user->role }}', '{{ $user->admin_sub_role }}', '{{ $user->name }}')">
                            <i class="bi bi-shield-check"></i>
                        </button>
                        <button class="saas-btn saas-btn-secondary" style="padding:4px 8px;" title="Edit Details">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:48px 20px;">
                        <i class="bi bi-people saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                        <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No users found</div>
                        <p class="saas-text-muted" style="margin-bottom:20px; max-width:400px; margin-inline:auto;">There are no users matching your current filters.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($users->hasPages())
    <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:space-between; align-items:center;">
        <div class="saas-text-muted">
            Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} results
        </div>
        <div>
            {{ $users->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Add User Modal (Placeholder) -->
<div id="addUserModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:100; align-items:center; justify-content:center; opacity:0; transition:opacity 0.2s;">
    <div class="saas-card" style="width:100%; max-width:500px; transform:scale(0.95); transition:transform 0.2s;" id="addUserCard">
        <div class="saas-card-header">
            <div class="saas-heading saas-heading-sm">Add System User</div>
            <button onclick="closeModal('addUserModal')" style="background:none; border:none; color:var(--saas-text-muted); cursor:pointer;"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="saas-card-body" style="text-align:center; padding:40px 20px;">
            <i class="bi bi-tools saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block;"></i>
            <h3 class="saas-heading">Quick Add Under Construction</h3>
            <p class="saas-text-muted" style="margin-bottom:0;">Please use the dedicated Student or Instructor modules to add users for now.</p>
        </div>
        <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:flex-end; gap:12px; background:rgba(0,0,0,0.2);">
            <button type="button" class="saas-btn saas-btn-secondary" onclick="closeModal('addUserModal')">Close</button>
        </div>
    </div>
</div>

<!-- Edit Role Modal -->
<div id="editRoleModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:100; align-items:center; justify-content:center; opacity:0; transition:opacity 0.2s;">
    <div class="saas-card" style="width:100%; max-width:500px; transform:scale(0.95); transition:transform 0.2s;">
        <div class="saas-card-header">
            <div class="saas-heading saas-heading-sm">Edit Role for <span id="editRoleUserName"></span></div>
            <button onclick="closeModal('editRoleModal')" style="background:none; border:none; color:var(--saas-text-muted); cursor:pointer;"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="editRoleForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="saas-card-body" style="padding:24px;">
                
                <div class="saas-form-group" style="margin-bottom:16px;">
                    <label class="saas-label">System Role</label>
                    <select name="role" id="editRoleSelect" class="saas-input saas-select" onchange="toggleSubRole(this.value)">
                        <option value="admin">Administrator</option>
                        <option value="teacher">Instructor</option>
                        <option value="student">Student</option>
                        <option value="parent">Parent</option>
                        <option value="department_head">Department Head</option>
                    </select>
                </div>
                
                <div class="saas-form-group" id="adminSubRoleGroup" style="display:none; margin-bottom:16px;">
                    <label class="saas-label">Admin Sub-Role</label>
                    <select name="admin_sub_role" id="editSubRoleSelect" class="saas-input saas-select">
                        <option value="">Standard Admin (Full Access)</option>
                        <option value="super_admin">Super Admin (IT/System)</option>
                        <option value="data_entry">Data Entry</option>
                        <option value="auditor">Auditor (Read Only)</option>
                    </select>
                </div>

            </div>
            <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:flex-end; gap:12px; background:rgba(0,0,0,0.02);">
                <button type="button" class="saas-btn saas-btn-secondary" onclick="closeModal('editRoleModal')">Cancel</button>
                <button type="submit" class="saas-btn saas-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openModal(id) {
        const modal = document.getElementById(id);
        const card = modal.querySelector('.saas-card');
        modal.style.display = 'flex';
        void modal.offsetWidth;
        modal.style.opacity = '1';
        card.style.transform = 'scale(1)';
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        const card = modal.querySelector('.saas-card');
        modal.style.opacity = '0';
        card.style.transform = 'scale(0.95)';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 200);
    }
    
    function openEditRoleModal(userId, role, subRole, name) {
        document.getElementById('editRoleUserName').textContent = name;
        
        let form = document.getElementById('editRoleForm');
        form.action = `/admin/roles/${userId}`;
        
        document.getElementById('editRoleSelect').value = role;
        
        toggleSubRole(role);
        
        if (role === 'admin' && subRole) {
            document.getElementById('editSubRoleSelect').value = subRole;
        } else {
            document.getElementById('editSubRoleSelect').value = '';
        }
        
        openModal('editRoleModal');
    }
    
    function toggleSubRole(role) {
        if (role === 'admin') {
            document.getElementById('adminSubRoleGroup').style.display = 'block';
        } else {
            document.getElementById('adminSubRoleGroup').style.display = 'none';
        }
    }
</script>
@endpush
