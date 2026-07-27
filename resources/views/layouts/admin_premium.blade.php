<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') | {{ config('app.name', 'School Attendance') }}</title>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom SaaS CSS -->
    <link rel="stylesheet" href="{{ asset('css/admin-saas.css') }}">
    
    <style>
        .saas-sidebar { transition: width 0.3s ease; overflow-x: hidden; }
        .saas-main { transition: margin-left 0.3s ease; }
        
        @media (min-width: 992px) {
            .saas-sidebar.mini { width: 70px; }
            .saas-main.maximized { margin-left: 70px; }
            
            .saas-sidebar.mini .saas-brand-text,
            .saas-sidebar.mini .saas-nav-group,
            .saas-sidebar.mini .saas-nav-header { display: none; }
            
            .saas-sidebar.mini .saas-nav-item { 
                justify-content: center; 
                padding: 12px 0; 
                font-size: 0; 
            }
            .saas-sidebar.mini .saas-nav-item i { 
                margin-right: 0; 
                font-size: 1.3rem; 
            }
            .saas-sidebar.mini .saas-brand-logo { margin: 0 auto; }
        }
        
        /* Dropdown Profile Menu */
        .fb-dropdown {
            width: 300px !important; border-radius: 14px !important;
            border: 1px solid rgba(207,164,111,0.24) !important;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25) !important;
            padding: 10px !important;
            background: rgba(17, 9, 6, 0.96) !important;
        }
        .fb-profile-header {
            display: flex; align-items: center; gap: 12px;
            padding: 10px; border-radius: 10px;
            text-decoration: none; color: #f3e7cd !important; transition: background 0.15s;
        }
        .fb-profile-header:hover { background: rgba(255,235,190,0.08); }
        .fb-icon-circle {
            width: 36px; height: 36px; background: #43332a;
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; font-size: 1rem; color: #e7d7b4;
        }
        .fb-dropdown-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px; border-radius: 10px;
            color: #f3e7cd !important; font-weight: 500; font-size: 0.875rem;
            text-decoration: none; width: 100%; border: none; background: none;
            transition: background 0.15s;
        }
        .fb-dropdown-item:hover { background: rgba(255,235,190,0.08); }
    </style>

    @stack('styles')
</head>
<body>

<div class="saas-layout">
    
    <!-- Sidebar -->
    <aside class="saas-sidebar" id="sidebar">
        <div class="saas-sidebar-header">
            <div class="saas-brand-logo">
                <i class="bi bi-qr-code-scan"></i>
            </div>
            <div class="saas-brand-text">Admin HQ</div>
            
            <button class="d-lg-none" id="closeSidebar" style="background:none;border:none;color:var(--saas-text-muted);margin-left:auto;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <nav class="saas-sidebar-nav">
            <!-- Overview -->
            <div class="saas-nav-group">Overview</div>
            <a href="{{ route('admin.dashboard') }}" class="saas-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
            
            <!-- People -->
            <div class="saas-nav-group">People</div>
            <a href="{{ route('admin.students') }}" class="saas-nav-item {{ request()->routeIs('admin.student*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Students
            </a>
            <a href="{{ route('admin.teachers') }}" class="saas-nav-item {{ request()->routeIs('admin.teacher*') ? 'active' : '' }}">
                <i class="bi bi-person-workspace"></i> Instructors
            </a>
            <a href="{{ route('admin.roles.index') }}" class="saas-nav-item {{ request()->routeIs('admin.roles*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i> User Management
            </a>
            
            <!-- Academics -->
            <div class="saas-nav-group">Academics</div>
            <a href="{{ route('admin.departments.index') }}" class="saas-nav-item {{ request()->routeIs('admin.departments*') ? 'active' : '' }}">
                <i class="bi bi-building"></i> Departments
            </a>
            <a href="{{ route('admin.courses.index') }}" class="saas-nav-item {{ request()->routeIs('admin.courses*') ? 'active' : '' }}">
                <i class="bi bi-mortarboard"></i> Courses
            </a>
            <a href="{{ route('admin.sections.index') }}" class="saas-nav-item {{ request()->routeIs('admin.sections*') ? 'active' : '' }}">
                <i class="bi bi-diagram-3"></i> Sections
            </a>
            <a href="{{ route('admin.subjects') }}" class="saas-nav-item {{ request()->routeIs('admin.subject*') ? 'active' : '' }}">
                <i class="bi bi-book"></i> Subjects
            </a>
            <a href="{{ route('admin.class-schedules.index') }}" class="saas-nav-item {{ request()->routeIs('admin.class-schedules*') ? 'active' : '' }}">
                <i class="bi bi-calendar-range"></i> Class Schedules
            </a>
            
            <!-- Attendance & Ops -->
            <div class="saas-nav-group">Operations</div>
            <a href="{{ route('admin.attendance') }}" class="saas-nav-item {{ request()->routeIs('admin.attendance') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check"></i> Attendance
            </a>
            <a href="{{ route('admin.qr') }}" class="saas-nav-item {{ request()->routeIs('admin.qr*') ? 'active' : '' }}">
                <i class="bi bi-qr-code"></i> QR Management
            </a>
            <a href="{{ route('admin.attendance.pdf') }}" class="saas-nav-item">
                <i class="bi bi-file-earmark-bar-graph"></i> Reports
            </a>
            
            <!-- Communication -->
            <div class="saas-nav-group">Communication</div>
            <a href="{{ route('admin.announcements.index') }}" class="saas-nav-item {{ request()->routeIs('admin.announcements*') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> Announcements
            </a>
            <a href="{{ route('admin.notifications') }}" class="saas-nav-item {{ request()->routeIs('admin.notifications*') ? 'active' : '' }}">
                <i class="bi bi-bell"></i> Notifications
            </a>
            
            <!-- System -->
            <div class="saas-nav-group">System</div>
            <a href="{{ route('admin.settings') }}" class="saas-nav-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <i class="bi bi-sliders"></i> Settings
            </a>
            <a href="{{ route('admin.activity.log') }}" class="saas-nav-item {{ request()->routeIs('admin.activity.log') ? 'active' : '' }}">
                <i class="bi bi-journal-code"></i> Audit Logs
            </a>
            <a href="{{ route('admin.system-health.index') }}" class="saas-nav-item {{ request()->routeIs('admin.system-health*') ? 'active' : '' }}">
                <i class="bi bi-heart-pulse"></i> System Health
            </a>
            <a href="{{ route('admin.backups.index') }}" class="saas-nav-item {{ request()->routeIs('admin.backups*') ? 'active' : '' }}">
                <i class="bi bi-database-down"></i> Backup & Restore
            </a>
            
            <div style="height:40px;"></div>
        </nav>
    </aside>

    <!-- Main Workspace -->
    <main class="saas-main">
        
        <!-- Topbar -->
        <header class="saas-topbar">
            <div style="display:flex;align-items:center;gap:16px;">
                <button class="d-none d-lg-block" id="toggleDesktopSidebar" style="background:none;border:none;color:var(--saas-text-primary);font-size:1.2rem;cursor:pointer;transition:color 0.2s;" title="Toggle Sidebar">
                    <i class="bi bi-layout-sidebar"></i>
                </button>
                
                <div class="saas-search d-none d-md-block">
                    <i class="bi bi-search"></i>
                    <input type="text" class="saas-search-input" placeholder="Search anything (Cmd+K)">
                    <span class="saas-search-shortcut">⌘K</span>
                </div>
            </div>
            
            <div style="display:flex;align-items:center;gap:16px;">
                <a href="{{ route('admin.notifications') }}" style="color:var(--saas-text-muted);position:relative;text-decoration:none;">
                    <i class="bi bi-bell" style="font-size:1.1rem;"></i>
                    @if(\App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count() > 0)
                        <span style="position:absolute;top:-4px;right:-4px;width:8px;height:8px;background:var(--saas-danger);border-radius:50%;"></span>
                    @endif
                </a>
                
                <div class="dropdown">
                    <a href="#" data-bs-toggle="dropdown" class="text-decoration-none d-flex align-items-center gap-2" style="padding-left:16px;border-left:1px solid var(--saas-border);">
                        <img src="{{ auth()->user()->profile_image ? (str_starts_with(auth()->user()->profile_image, 'http') ? auth()->user()->profile_image : asset('storage/'.auth()->user()->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=900000&color=fff' }}" 
                             alt="Profile" style="width:36px;height:36px;border-radius:var(--saas-radius-sm);object-fit:cover;border:1px solid var(--saas-border);">
                        <div class="d-none d-md-block text-start" style="line-height:1.2;">
                            <div style="font-size:0.8rem;font-weight:600;color:var(--saas-text-primary);">{{ auth()->user()->name }}</div>
                        </div>
                        <i class="bi bi-chevron-down d-none d-md-block" style="font-size:0.7rem;color:var(--saas-text-muted);"></i>
                    </a>
                    
                    <div class="dropdown-menu dropdown-menu-end fb-dropdown mt-2" style="position: absolute;">
                        <div class="fb-profile-header" style="cursor:default;">
                            <img src="{{ auth()->user()->profile_image ? (str_starts_with(auth()->user()->profile_image, 'http') ? auth()->user()->profile_image : asset('storage/'.auth()->user()->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=900000&color=fff' }}" 
                                 style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,225,170,0.9);">
                            <div>
                                <div class="fw-bold" style="font-size:0.9rem;">{{ auth()->user()->name }}</div>
                                <div style="font-size:0.75rem;color:#b39b82;">Administrator</div>
                            </div>
                        </div>
                        <hr class="my-2" style="border-color:rgba(255,215,145,0.18);">
                        <a class="fb-dropdown-item" href="{{ route('settings') }}">
                            <div class="fb-icon-circle"><i class="bi bi-gear-fill"></i></div>
                            <span>Settings</span>
                        </a>
                        <form action="{{ route('logout') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="fb-dropdown-item" style="color:#dc2626 !important;">
                                <div class="fb-icon-circle" style="background:#3a1d18;color:#dc2626;"><i class="bi bi-box-arrow-right"></i></div>
                                <span>Log Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="saas-content">
            @if(session('success'))
            <div class="saas-animate-up" style="background:var(--saas-success-alpha);border:1px solid rgba(74,222,128,0.2);color:var(--saas-success);padding:12px 16px;border-radius:var(--saas-radius-md);margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <i class="bi bi-check-circle-fill"></i>
                <div style="font-size:0.875rem;font-weight:500;">{{ session('success') }}</div>
            </div>
            @endif
            
            @if(session('error'))
            <div class="saas-animate-up" style="background:var(--saas-danger-alpha);border:1px solid rgba(248,113,113,0.2);color:var(--saas-danger);padding:12px 16px;border-radius:var(--saas-radius-md);margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div style="font-size:0.875rem;font-weight:500;">{{ session('error') }}</div>
            </div>
            @endif

            @yield('content')
        </div>
        
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('toggleSidebar');
        const toggleDesktopBtn = document.getElementById('toggleDesktopSidebar');
        const closeBtn = document.getElementById('closeSidebar');
        const sidebar = document.getElementById('sidebar');
        const main = document.querySelector('.saas-main');
        
        if(toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.add('show');
            });
        }
        
        if(toggleDesktopBtn) {
            toggleDesktopBtn.addEventListener('click', () => {
                sidebar.classList.toggle('mini');
                main.classList.toggle('maximized');
            });
        }
        
        if(closeBtn) {
            closeBtn.addEventListener('click', () => {
                sidebar.classList.remove('show');
            });
        }
    });
</script>

@stack('scripts')
</body>
</html>
