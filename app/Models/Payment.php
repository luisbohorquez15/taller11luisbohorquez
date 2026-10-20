<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'payment_intent_id',
        'amount',
        'currency',
        'status',
        'description',
    ];
}