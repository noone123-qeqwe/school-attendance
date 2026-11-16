<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Resolves and verifies user credentials across supported identifier formats
 * and normalizes account/password policy states for web and API logins.
 */
class ResolveAuthenticatedUserAction
{
    /**
     * @return array{status: 'ok'|'invalid'|'inactive'|'starter_password', user: ?User}
     */
    public function execute(string $identifier, string $password): array
    {
        $identifier = trim($identifier);
        if ($identifier === '' || $password === '') {
            return ['status' => 'invalid', 'user' => null];
        }

        $primaryUser = User::findByIdentifier($identifier);
        $user = ($primaryUser && $this->matchesPassword($primaryUser, $password))
            ? $primaryUser
            : null;

        if (!$user) {
            $user = $this->findMatchingCandidate($identifier, $password, $primaryUser?->id);
        }

        if (!$user) {
            return ['status' => 'invalid', 'user' => null];
        }

        if (!$user->isActive()) {
            return ['status' => 'inactive', 'user' => $user];
        }

        if ($user->usesSharedStarterPassword()) {
            return ['status' => 'starter_password', 'user' => $user];
        }

        return ['status' => 'ok', 'user' => $user];
    }

    private function findMatchingCandidate(string $identifier, string $password, ?int $excludeUserId): ?User
    {
        $normalizedIdentifier = preg_replace(
            '/^(?:student[\s_-]*(?:id|number|no|#)?|id[\s:#_-]*|sn[\s:#_-]*|lrn[\s:#_-]*)\s*/i',
            '',
            $identifier
        );
        $digitsOnly = preg_replace('/\D/', '', $normalizedIdentifier ?: $identifier);
        $numericId = $digitsOnly !== '' ? (int) $digitsOnly : null;

        $candidateVariants = array_filter(array_unique([
            $identifier,
            $normalizedIdentifier,
            ltrim($identifier, '0'),
            ltrim((string) $normalizedIdentifier, '0'),
            preg_replace('/[^a-zA-Z0-9]/', '', $identifier),
            preg_replace('/[^a-zA-Z0-9]/', '', (string) $normalizedIdentifier),
            $numericId !== null ? sprintf('%06d', $numericId) : null,
            $numericId !== null ? sprintf('%07d', $numericId) : null,
            $numericId !== null ? sprintf('%08d', $numericId) : null,
        ]));

        $phoneVariants = User::getPhoneVariants($identifier);

        $candidates = User::query()
            ->whereNull('deleted_at')
            ->where(function ($query) use ($candidateVariants, $phoneVariants, $numericId) {
                $query->whereIn('student_number', $candidateVariants)
                    ->orWhereIn('employee_id', $candidateVariants)
                    ->orWhereIn('email', $candidateVariants);
                if ($numericId !== null) {
                    $query->orWhere('id', $numericId);
                }
                if (!empty($phoneVariants)) {
                    $query->orWhereIn('phone', $phoneVariants);
                }
            })
            ->when($excludeUserId, fn ($query) => $query->where('id', '!=', $excludeUserId))
            ->get();

        foreach ($candidates as $candidate) {
            if ($this->matchesPassword($candidate, $password)) {
                return $candidate;
            }
        }

        return null;
    }

    private function matchesPassword(User $user, string $password): bool
    {
        $trimmed = trim($password);
        if (Hash::check($password, $user->password)
            || ($trimmed !== '' && $password !== $trimmed && Hash::check($trimmed, $user->password))) {
            return true;
        }

        if ($user->isStudent() && ($password === 'password' || $password === 'student123')) {
            return Hash::check('password', $user->password) || Hash::check('student123', $user->password);
        }
        if ($user->isTeacher() && ($password === 'password' || $password === 'teacher123')) {
            return Hash::check('password', $user->password) || Hash::check('teacher123', $user->password);
        }
        if ($user->isParent() && ($password === 'password' || $password === 'parent123')) {
            return Hash::check('password', $user->password) || Hash::check('parent123', $user->password);
        }

        return false;
    }
}
