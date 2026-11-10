@php
    $settingsGroups = [
        'Account' => [
            ['profile', 'bi-person-circle', 'Profile & school details', 'Photo, contact number, and student information', 'avatar phone student id course academic'],
            ['family', 'bi-people', 'Guardians', 'Manage who can view your attendance', 'parent family link code'],
        ],
        'Security & attendance' => [
            ['credentials', 'bi-shield-lock', 'Password & email', 'Update your sign-in details', 'security otp reset'],
            ['face', 'bi-camera-video', 'Face registration', 'Use your camera to register for attendance', 'camera facial recognition capture'],
            ['passkeys', 'bi-key', 'Device sign-in', 'Manage passkeys and fingerprint sign-in', 'biometric webauthn fingerprint credentials'],
            ['device', 'bi-phone', 'Attendance device', 'Manage the device you use to check in', 'binding trusted lock phone scan'],
            ['recovery', 'bi-life-preserver', 'Recovery codes', 'Keep a backup way to access your account', 'emergency vault lost password'],
        ],
        'App' => [
            ['preferences', 'bi-sliders', 'Notifications & app', 'Choose your alerts and check for updates', 'preferences push sms email display sidebar version software'],
        ],
    ];
@endphp

<aside class="student-settings-menu" aria-label="Settings navigation">
    <div class="student-settings-account">
        <img src="{{ $user->profile_photo_url }}" alt="" width="42" height="42">
        <div><strong>{{ $user->name }}</strong><span>Student ID {{ $user->student_number ?: 'not assigned' }}</span></div>
    </div>
    <label class="student-settings-search" for="studentSettingsFilter">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input id="studentSettingsFilter" type="search" placeholder="Find a setting" aria-label="Find a setting" autocomplete="off">
    </label>
    <nav aria-label="Student settings">
        @foreach($settingsGroups as $group => $items)
        <section class="student-settings-menu-group" aria-label="{{ $group }}">
            <h2>{{ $group }}</h2>
            <div class="student-settings-menu-list">
                @foreach($items as [$destination, $icon, $title, $description, $keywords])
                <button type="button" class="student-settings-link" data-settings-page="{{ $destination }}" data-settings-keywords="{{ $keywords }}" aria-controls="studentSettingsContent">
                    <span class="student-settings-link-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                    <span class="student-settings-link-copy"><strong>{{ $title }}</strong><span>{{ $description }}</span></span>
                    <i class="bi bi-chevron-right student-settings-link-arrow" aria-hidden="true"></i>
                </button>
                @endforeach
            </div>
        </section>
        @endforeach
    </nav>
    <p class="student-settings-no-results" id="studentSettingsNoResults" role="status" hidden>No matching settings. Try “password”, “face”, or “notifications”.</p>
</aside>
