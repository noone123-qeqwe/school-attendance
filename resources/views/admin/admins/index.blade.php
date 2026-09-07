@extends('layouts.app')

@section('title', 'Admins')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Admins</h1>
        <p class="saas-text-muted" style="margin:0;">Manage administrator accounts, permissions, and security roles.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <a href="{{ route('admin.activity.log') }}" class="saas-btn saas-btn-secondary">
            <i class="bi bi-clock-history"></i> Audit Logs
        </a>
        <a href="{{ route('admin.admin.create') }}" class="saas-btn saas-btn-primary">
            <i class="bi bi-plus-lg"></i> Add Admin
        </a>
    </div>
</div>

<div class="saas-card" style="margin-bottom:24px;">
    <form method="GET" action="{{ route('admin.admins') }}" class="saas-card-header" style="gap:16px; flex-wrap:wrap; display:flex;">
        <div class="saas-search" style="width:250px;">
            <i class="bi bi-search"></i>
            <input type="text" name="search" class="saas-search-input" placeholder="Name or Employee ID" value="{{ request('search') }}">
        </div>
        
        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <select name="department" class="saas-input saas-select" style="width:140px; padding:6px 30px 6px 12px;">
                <option value="">All Departments</option>
                <option value="CS" {{ request('department')=='CS'?'selected':'' }}>Computer Science</option>
                <option value="IT" {{ request('department')=='IT'?'selected':'' }}>Information Tech</option>
            </select>
            
            <button type="submit" class="saas-btn saas-btn-secondary" style="padding:6px 12px;">
                <i class="bi bi-funnel"></i> Filter
            </button>
            
            @if(request()->hasAny(['search','department']))
            <a href="{{ route('admin.admins') }}" class="saas-btn saas-btn-secondary" style="padding:6px 12px; color:var(--saas-danger);">
                Clear
            </a>
            @endif
        </div>
    </form>
    
    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" style="accent-color:var(--saas-primary);" onclick="document.querySelectorAll('.admin-checkbox').forEach(c => c.checked = this.checked)"></th>
                    <th>Admin</th>
                    <th>Employee ID</th>
                    <th>Department</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $admin)
                <tr>
                    <td><input type="checkbox" class="admin-checkbox" name="selected_admins[]" value="{{ $admin->id }}" style="accent-color:var(--saas-primary);"></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <img src="{{ $admin->profile_image ? (str_starts_with($admin->profile_image, 'http') ? $admin->profile_image : asset('storage/'.$admin->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($admin->name).'&background=900000&color=fff' }}"
                                 style="width:36px;height:36px;border-radius:var(--saas-radius-sm);object-fit:cover;border:1px solid var(--saas-border);">
                            <div>
                                <div style="font-weight:600;font-size:0.875rem;">{{ $admin->name }}</div>
                                <div class="saas-text-muted" style="font-size:0.75rem;">{{ $admin->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="saas-badge saas-badge-default" style="font-family:monospace;">{{ $admin->employee_id ?? 'N/A' }}</span></td>
                    <td>
                        <span class="saas-badge saas-badge-info">{{ $admin->department ?? 'General' }}</span>
                    </td>
                    <td style="text-align:right;">
                        <div style="display:flex; gap:6px; justify-content:flex-end;">
                            <a href="{{ route('admin.activity.log', ['search' => $admin->name]) }}" class="saas-btn saas-btn-secondary" style="padding:4px 8px;" title="Activity History">
                                <i class="bi bi-clock-history"></i>
                            </a>
                            <a href="{{ route('admin.admin.edit', $admin) }}" class="saas-btn saas-btn-secondary" style="padding:4px 8px;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if(auth()->id() !== $admin->id)
                            <form action="{{ route('admin.admin.destroy', $admin) }}" method="POST" onsubmit="return confirm('Delete {{ addslashes($admin->name) }}?')" style="margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit" class="saas-btn saas-btn-secondary" style="padding:4px 8px; color:var(--saas-danger);" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding:48px 20px;">
                        <i class="bi bi-shield-lock saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                        <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No Admins found</div>
                        <p class="saas-text-muted" style="margin-bottom:20px; max-width:400px; margin-inline:auto;">There are no Admins matching your criteria.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
</div>

@endsection
