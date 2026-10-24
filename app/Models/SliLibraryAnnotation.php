<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SliLibraryAnnotation extends Model
{
    protected $fillable = ['document_id', 'usuario_id', 'page', 'kind', 'quote', 'note', 'rects'];

    protected function casts(): array
    {
        return ['rects' => 'array', 'page' => 'integer'];
    }
}
