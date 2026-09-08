<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
    use HasUuids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'rubro_id',
        'name',
        'description',
        'order',
        'status',
    ];

    public function rubro(): BelongsTo
    {
        return $this->belongsTo(Rubro::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
