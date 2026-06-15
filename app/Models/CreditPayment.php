<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class CreditPayment extends Model
{
    use HasUuid;

    protected $fillable = [
        'id',
        'customer_id',
        'amount',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'synced_at' => 'datetime',
        ];
    }
}
