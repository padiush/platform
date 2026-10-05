<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The browsers a user is signed in on, read from the database session store.
 *
 * A session's id is what signs a browser in, so it never leaves the server:
 * each one is named to the page by a keyed hash instead, which is enough to
 * pick it out again and useless to anyone who reads it.
 */
class BrowserSessions
{
    /** Whether this installation keeps sessions where they can be listed. */
    public function available(): bool
    {
        return config('session.driver') === 'database';
    }

    /**
     * The user's sessions, most recently active first.
     *
     * @return list<array{key: string, browser: ?string, platform: ?string, ip_address: ?string, last_active: string, current: bool}>
     */
    public function for(User $user, string $currentId): array
    {
        return $this->rows($user)
            ->sortByDesc('last_activity')
            ->map(fn ($row) => [
                'key' => $this->key($row->id),
                ...$this->describe((string) $row->user_agent),
                'ip_address' => $row->ip_address,
                'last_active' => CarbonImmutable::createFromTimestamp($row->last_activity)->toIso8601String(),
                'current' => hash_equals($row->id, $currentId),
            ])
            ->values()
            ->all();
    }

    /** Sign out the one session the key names, unless it is this one. */
    public function destroy(User $user, string $key, string $currentId): bool
    {
        $row = $this->rows($user)->first(fn ($row) => hash_equals($this->key($row->id), $key));

        if (! $row || hash_equals($row->id, $currentId)) {
            return false;
        }

        return DB::table($this->table())->where('id', $row->id)->delete() > 0;
    }

    /** Sign out every session but this one. */
    public function destroyOthers(User $user, string $currentId): int
    {
        return DB::table($this->table())
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentId)
            ->delete();
    }

    /**
     * The browser and the system it runs on, as far as the user agent says.
     * Rough on purpose: enough to tell one's own browsers apart.
     *
     * @return array{browser: ?string, platform: ?string}
     */
    public function describe(string $agent): array
    {
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/'), str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'Firefox/'), str_contains($agent, 'FxiOS/') => 'Firefox',
            str_contains($agent, 'Chrome/'), str_contains($agent, 'CriOS/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => null,
        };

        $platform = match (true) {
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS X'), str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'CrOS') => 'ChromeOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        return ['browser' => $browser, 'platform' => $platform];
    }

    private function rows(User $user)
    {
        return DB::table($this->table())
            ->where('user_id', $user->id)
            ->get(['id', 'ip_address', 'user_agent', 'last_activity']);
    }

    private function key(string $id): string
    {
        return hash_hmac('sha256', $id, (string) config('app.key'));
    }

    private function table(): string
    {
        return config('session.table', 'sessions');
    }
}
