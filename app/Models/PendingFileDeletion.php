<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingFileDeletion extends Model
{
    protected $fillable = ['disk', 'path', 'available_at', 'attempts'];

    protected function casts(): array
    {
        return ['available_at' => 'datetime'];
    }
}
