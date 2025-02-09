<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $guarded = ['id'];

    public function product():BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sentence():BelongsTo
    {
        return $this->belongsTo(Sentence::class);
    }

    public function template():BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
