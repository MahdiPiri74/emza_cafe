<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SentenceCategory extends Model
{
    public function sentences() : HasMany
    {
        return $this->hasMany(Sentence::class,'category_id','id');
    }

    public function children() : HasMany
    {
        return $this->hasMany(SentenceCategory::class,'parent_id');
    }

    public function parent() : BelongsTo
    {
        return $this->belongsTo(SentenceCategory::class,'parent_id');
    }
}
