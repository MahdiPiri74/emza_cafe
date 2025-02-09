<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{

    protected $guarded = ['id'];
    public function orderItems():HasMany
    {
        return $this->hasMany(orderItem::class,'order_id','id');
    }
}
