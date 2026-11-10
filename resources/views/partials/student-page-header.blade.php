<header class="student-page-header no-print">
    <div>
        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>
    </div>
    @isset($actionUrl)
    <a class="student-page-link" href="{{ $actionUrl }}">{{ $actionLabel }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    @endisset
</header>
