<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** When a maintenance job last ran, and what it found. */
class SystemRun extends Model
{
    public const SCHEDULER = 'scheduler';

    public const PRUNE_ORPHANS = 'media:prune-orphans';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['key', 'ran_at', 'summary'];

    protected $casts = [
        'ran_at' => 'datetime',
        'summary' => 'array',
    ];

    public static function mark(string $key, ?array $summary = null): void
    {
        self::updateOrCreate(['key' => $key], ['ran_at' => now(), 'summary' => $summary]);
    }
}
