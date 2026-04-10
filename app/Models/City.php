<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    /** @var list<string> */
    protected $fillable = ['name'];

    /**
     * Users in this city.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Vendors (stores) in this city.
     */
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
}
