<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'logo_path',
        'notes',
    ];

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function captures(): HasMany
    {
        return $this->hasMany(Capture::class);
    }

    public function findings(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Finding::class, Capture::class);
    }

    public function reports(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Report::class, Capture::class);
    }
}
