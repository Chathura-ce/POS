<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasUuid;

    protected $fillable = [
        'id',
        'name',
        'phone',
        'credit_balance',
    ];

    protected function casts(): array
    {
        return [
            'credit_balance' => 'decimal:2',
        ];
    }
}
