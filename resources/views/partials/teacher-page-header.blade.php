<header class="portal-sub-header no-print">
    <div>
        <h1>{{ $title }}</h1>
        @isset($description)
            <p>{{ $description }}</p>
        @endisset
    </div>
    @isset($actionUrl)
        <a class="portal-cta-secondary" href="{{ $actionUrl }}">
            {{ $actionLabel ?? 'Open' }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    @endisset
</header>
