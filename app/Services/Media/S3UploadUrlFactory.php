<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\Storage;

/**
 * Presigned uploads backed by the S3 disk. The device PUTs the file
 * directly to the returned URL (resumable/chunked), independent of the JSON sync.
 */
class S3UploadUrlFactory implements UploadUrlFactory
{
    public function create(string $disk, string $key, string $contentType, int $ttlMinutes): array
    {
        $expiresAt = now()->addMinutes($ttlMinutes);

        $presigned = Storage::disk($disk)->temporaryUploadUrl($key, $expiresAt);

        return [
            'url' => $presigned['url'],
            'headers' => array_merge(
                ['Content-Type' => $contentType],
                $this->sendableHeaders($presigned['headers'] ?? [])
            ),
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * The signed headers as the device must send them: one string per header,
     * and no Host.
     *
     * The SDK returns PSR-7 headers, where every value is a list, and Host is
     * always among them. Passed through as-is they broke the contract
     * (`headers` maps a name to a string) and every upload from the companion:
     * its HTTP client hands header values to native code untouched, and a list
     * is not a string, so the request failed before a byte was sent. Host is
     * left out because a client sets it from the URL — which is the host the
     * signature covers — and some refuse to send one supplied by the caller.
     *
     * @param  array<string, string|array<int, string>>  $headers
     * @return array<string, string>
     */
    private function sendableHeaders(array $headers): array
    {
        $sendable = [];

        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) === 'host') {
                continue;
            }

            $sendable[$name] = is_array($value) ? implode(', ', $value) : (string) $value;
        }

        return $sendable;
    }
}
