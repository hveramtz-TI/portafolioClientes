<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserCatalogItem extends Model
{
    use HasUuids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'item_type',
        'base_id',
        'parent_fork_id',
        'overrides',
        'status',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'overrides' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parentFork(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_fork_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_fork_id');
    }

    public function base(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'item_type', 'base_id');
    }
}
