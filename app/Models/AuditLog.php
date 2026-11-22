<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    // The log has only a creation time, it is never changed
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'action', 'description', 'auditable_type', 'auditable_id',
        'old_values', 'new_values', 'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Writes one line of the history. $target is the changed record (optional).
    public static function record(
        string $action,
        string $description,
        ?Model $target = null,
        array $old = [],
        array $new = []
    ): void {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'auditable_type' => $target ? $target::class : null,
            'auditable_id' => $target?->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => request()->ip(),
        ]);
    }
}