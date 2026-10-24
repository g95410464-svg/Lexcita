<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SliLibraryDocument extends Model
{
    protected $fillable = [
        'title', 'category', 'jurisdiction', 'description', 'source_url', 'storage_path', 'created_by',
    ];
}
