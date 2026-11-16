<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Medio extends Model
{
    protected $fillable = ['titulo', 'alt'];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return $this->proveedor === 'cloudinary'
            ? $this->url_externa
            : Storage::disk(config('cms.medios.disco'))->url($this->ruta);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
