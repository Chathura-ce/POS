<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasUuid;

    protected $fillable = [
        'id',
        'name',
        'phone',
        'email',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }
}
