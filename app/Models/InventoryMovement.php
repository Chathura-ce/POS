<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasUuid;

    protected $fillable = [
        'id',
        'product_id',
        'qty_change',
        'reason',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'qty_change' => 'integer',
            'synced_at' => 'datetime',
        ];
    }
}
