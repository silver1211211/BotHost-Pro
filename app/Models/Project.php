<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'name', 'slug', 'description', 'status', 'language', 'template_id'])]
class Project extends Model
{
    public const STATUSES = ['running', 'paused', 'stopped'];

    public const LANGUAGES = ['javascript'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class)->orderBy('relative_path');
    }

    public function variables(): HasMany
    {
        return $this->hasMany(ProjectVariable::class)->orderBy('key');
    }

    public function setting(): HasOne
    {
        return $this->hasOne(ProjectSetting::class);
    }
}
