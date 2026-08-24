<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'rut',
        'email',
        'phone',
        'address',
        'website',
    ];

    /**
     * The clients that belong to this company.
     *
     * `company_id` en `clients` todavía no tiene FK; la relación se completa
     * en un slice posterior.
     */
    public function clients()
    {
        return $this->hasMany(Client::class);
    }
}
