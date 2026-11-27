<?php

namespace App\Support;

use App\Models\User;

class SettingsNavigation
{
    public static function dashboardUrl(?User $user): string
    {
        if ($user?->isAdmin()) {
            return route('admin.dashboard');
        }
        if ($user?->isTeacher()) {
            return route('teacher.dashboard');
        }
        if ($user?->isParent()) {
            return route('parent.dashboard');
        }

        return route('home');
    }

    /**
     * Settings sidebar groups matching the Account, Security & attendance, and App structure.
     *
     * @return array<string, list<array{destination: string, icon: string, label: string, description: string}>>
     */
    public static function groups(?User $user): array
    {
        if ($user?->isTeacher()) {
            return [
                'Account' => [
                    [
                        'destination' => 'profile',
                        'icon' => 'bi-person-circle',
                        'label' => 'Profile & faculty details',
                        'description' => 'Photo, employee details, and department info',
                    ],
                ],
                'Security' => [
                    [
                        'destination' => 'credentials',
                        'icon' => 'bi-shield-lock',
                        'label' => 'Password & email',
                        'description' => 'Update your sign-in email and password',
                    ],
                    [
                        'destination' => 'passkeys',
                        'icon' => 'bi-fingerprint',
                        'label' => 'Device passkeys',
                        'description' => 'Sign in faster with your device lock or biometrics',
                    ],
                    [
                        'destination' => 'recovery',
                        'icon' => 'bi-life-preserver',
                        'label' => 'Recovery codes',
                        'description' => 'Backup keys for account access',
                    ],
                ],
                'Preferences' => [
                    [
                        'destination' => 'preferences',
                        'icon' => 'bi-sliders',
                        'label' => 'Notification preferences',
                        'description' => 'Email alerts, push notifications, and layout',
                    ],
                ],
            ];
        }

        $accountItems = [
            [
                'destination' => 'profile',
                'icon' => 'bi-person-circle',
                'label' => 'Profile',
                'description' => $user?->isStudent() ? 'Photo and school information' : 'Photo, contact number, and school information',
            ],
        ];

        if ($user?->isStudent()) {
            $accountItems[] = [
                'destination' => 'family',
                'icon' => 'bi-people',
                'label' => 'Guardians',
                'description' => 'Manage who can view your attendance',
            ];
        }

        return [
            'Account' => $accountItems,
            'Security & attendance' => [
                [
                    'destination' => 'credentials',
                    'icon' => 'bi-shield-lock',
                    'label' => 'Password & email',
                    'description' => $user?->isStudent() ? 'Manage your password, email, and phone number' : 'Update your sign-in details',
                ],
                [
                    'destination' => 'biometrics',
                    'icon' => 'bi-person-bounding-box',
                    'label' => 'Biometrics',
                    'description' => 'Face verification and device sign-in',
                ],
                [
                    'destination' => 'device',
                    'icon' => 'bi-phone',
                    'label' => 'Attendance device',
                    'description' => 'Manage the device you use to check in',
                ],
                [
                    'destination' => 'recovery',
                    'icon' => 'bi-life-preserver',
                    'label' => 'Recovery codes',
                    'description' => 'Backup keys for account access',
                ],
            ],
            'App' => [
                [
                    'destination' => 'preferences',
                    'icon' => 'bi-sliders',
                    'label' => 'Notifications & app',
                    'description' => 'Alerts, layout, and updates',
                ],
            ],
        ];
    }
}
