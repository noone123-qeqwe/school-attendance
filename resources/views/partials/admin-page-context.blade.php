@php
    $routeName = request()->route()?->getName();
    $academic = \App\Support\AdminNavigation::academicContext($routeName);
@endphp
@if($academic)
    <div class="academic-workspace">
        <a class="academic-workspace-title" href="{{ route('admin.academic.overview') }}">
            <i class="bi bi-mortarboard" aria-hidden="true"></i> Academic Management
        </a>
        <nav class="academic-tabs" aria-label="Academic Management tabs">
            @foreach(\App\Support\AdminNavigation::academicTabs() as $tab)
                @php($destination = $tab['items'][0][1])
                <a href="{{ route($destination) }}" @if($tab['label'] === $academic['label']) aria-current="{{ $routeName === $destination ? 'page' : 'location' }}" @endif>{{ $tab['label'] }}</a>
            @endforeach
        </nav>
        @if(count($academic['items']) > 1)
            <nav class="academic-tools" aria-label="{{ $academic['label'] }} tools">
                @foreach($academic['items'] as [$label, $destination, $icon])
                    <a href="{{ route($destination) }}" @if($academic['current'][1] === $destination) aria-current="{{ $routeName === $destination ? 'page' : 'location' }}" @endif>
                        <i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}
                    </a>
                @endforeach
            </nav>
        @endif
    </div>
@elseif($adminGroup = \App\Support\AdminNavigation::currentGroup(auth()->user(), $routeName))
    @unless(request()->routeIs('admin.dashboard'))
        <nav aria-label="Breadcrumb" class="admin-breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span>{{ $adminGroup['label'] }}</span>
        </nav>
    @endunless
@endif
