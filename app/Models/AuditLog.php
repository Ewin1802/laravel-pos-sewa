<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_id',
        'action',
        'target_type',
        'target_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function target()
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    public function scopeForModel($query, $modelType, $modelId = null)
    {
        $query = $query->where('target_type', $modelType);

        if ($modelId) {
            $query->where('target_id', $modelId);
        }

        return $query;
    }

    public function scopeForAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByActor($query, $actorId)
    {
        return $query->where('actor_id', $actorId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public static function log(string $action, Model $target, ?User $actor = null, array $meta = []): self
    {
        return static::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_type' => get_class($target),
            'target_id' => $target->id,
            'meta' => $meta,
        ]);
    }
}
