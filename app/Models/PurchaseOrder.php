<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    use HasUuid;

    protected $fillable = [
        'id',
        'supplier_id',
        'total_amount',
        'status',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'synced_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
