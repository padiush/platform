<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An integrity event reported by the companion app. See the migration for why
 * this carries codes rather than messages.
 */
class DeviceDiagnostic extends Model
{
    /** The capture store could not be read with or without its key, so it was deleted. */
    public const CODE_STORE_RESET_UNRECOVERABLE = 'store_reset_unrecoverable';

    /** The local store belonged to another account and was replaced. */
    public const CODE_STORE_RESET_FOREIGN_ACCOUNT = 'store_reset_foreign_account';

    /** An unencrypted original survived ingestion into the encrypted store. */
    public const CODE_PLAINTEXT_CAPTURE_RETAINED = 'plaintext_capture_retained';

    /** The capture cache directory could not be swept. */
    public const CODE_CAPTURE_CACHE_SWEEP_FAILED = 'capture_cache_sweep_failed';

    /**
     * Every code the API will accept. Anything else is a 422 — the device does
     * not get to invent categories, which is what keeps this channel free of
     * anything resembling a message.
     */
    public const CODES = [
        self::CODE_STORE_RESET_UNRECOVERABLE,
        self::CODE_STORE_RESET_FOREIGN_ACCOUNT,
        self::CODE_PLAINTEXT_CAPTURE_RETAINED,
        self::CODE_CAPTURE_CACHE_SWEEP_FAILED,
    ];

    /**
     * Codes that mean captured data was destroyed or left unprotected. Worth
     * separating because these deserve a look rather than a dashboard.
     */
    public const SEVERE_CODES = [
        self::CODE_STORE_RESET_UNRECOVERABLE,
        self::CODE_STORE_RESET_FOREIGN_ACCOUNT,
        self::CODE_PLAINTEXT_CAPTURE_RETAINED,
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'code',
        'occurred_at',
        'app_version',
        'platform',
        'os_version',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSevere(): bool
    {
        return in_array($this->code, self::SEVERE_CODES, true);
    }
}
