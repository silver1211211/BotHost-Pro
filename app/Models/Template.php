<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'category', 'description', 'is_active', 'files'])]
class Template extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'files' => 'array',
        ];
    }
}
