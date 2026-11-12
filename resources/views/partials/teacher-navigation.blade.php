@php $mobile = $mobile ?? false; @endphp
@foreach(\App\Support\TeacherNavigation::groups() as $group)
    <div class="teacher-nav-group">
        <p class="teacher-nav-label">{{ $group['label'] }}</p>
        @foreach($group['items'] as [$key, $label, $destination, $icon, $patterns, $description, $query])
            @php
                $active = \App\Support\TeacherNavigation::isActive($key, $patterns);
                $pendingCount = $key === 'excuses' ? ($pendingExcusesCount ?? 0) : ($key === 'corrections' ? ($pendingCorrectionsCount ?? 0) : 0);
            @endphp
            <a href="{{ route($destination, $query) }}" class="{{ $mobile ? 'more-sheet-item' : 'nav-link' }} {{ $active ? 'active' : '' }}"
               data-title="{{ $label }}" @if($active) aria-current="page" @endif @if($mobile) data-color="gold" onclick="closeMoreSheet()" @endif>
                @if($mobile)<span class="more-sheet-item-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                @else<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif
                <span class="teacher-nav-copy">
                    <span class="{{ $mobile ? 'more-sheet-item-label' : 'nav-link-text' }}">{{ $label }}</span>
                    <small class="teacher-nav-description">{{ $description }}</small>
                </span>
                @if($pendingCount > 0)<span class="teacher-nav-count" aria-label="{{ $pendingCount }} pending requests">{{ $pendingCount > 99 ? '99+' : $pendingCount }}</span>@endif
            </a>
        @endforeach
    </div>
@endforeach
