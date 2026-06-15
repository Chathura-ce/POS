<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasUuid;

    protected $fillable = [
        'id',
        'opening_cash',
        'expected_cash',
        'actual_cash',
        'opened_at',
        'closed_at',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'opening_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
