<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncPushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales' => 'sometimes|array',
            'sales.*.id' => 'required|uuid',
            'sales.*.user_id' => 'required|uuid',
            'sales.*.subtotal' => 'required|numeric|min:0',
            'sales.*.discount' => 'required|numeric|min:0',
            'sales.*.total' => 'required|numeric|min:0',
            'sales.*.payment_type' => 'required|in:cash,card,credit',
            'sales.*.status' => 'required|in:pending,completed,refunded',
            'sales.*.created_at' => 'required|date',
            'sales.*.items' => 'required|array|min:1',
            'sales.*.items.*.id' => 'required|uuid',
            'sales.*.items.*.product_id' => 'required|uuid',
            'sales.*.items.*.quantity' => 'required|integer|min:1',
            'sales.*.items.*.unit_price' => 'required|numeric|min:0',
            'sales.*.items.*.line_total' => 'required|numeric|min:0',
            'products' => 'sometimes|array',
            'products.*.id' => 'required|uuid',
            'products.*.category_id' => 'required|uuid',
            'products.*.name' => 'required|string|max:255',
            'products.*.sku' => 'required|string|max:255',
            'products.*.barcode' => 'nullable|string|max:255',
            'products.*.cost_price' => 'required|numeric|min:0',
            'products.*.sell_price' => 'required|numeric|min:0',
            'products.*.stock_quantity' => 'required|integer|min:0',
            'products.*.is_active' => 'required|boolean',
            'products.*.updated_at' => 'required|date',
            'customers' => 'sometimes|array',
            'customers.*.id' => 'required|uuid',
            'customers.*.name' => 'required|string|max:255',
            'customers.*.phone' => 'nullable|string|max:50',
            'customers.*.credit_balance' => 'required|numeric|min:0',
            'customers.*.updated_at' => 'required|date',
            'credit_payments' => 'sometimes|array',
            'credit_payments.*.id' => 'required|uuid',
            'credit_payments.*.customer_id' => 'required|uuid',
            'credit_payments.*.amount' => 'required|numeric|min:0',
            'credit_payments.*.created_at' => 'required|date',
            'inventory_movements' => 'sometimes|array',
            'inventory_movements.*.id' => 'required|uuid',
            'inventory_movements.*.product_id' => 'required|uuid',
            'inventory_movements.*.qty_change' => 'required|integer',
            'inventory_movements.*.reason' => 'required|string|max:255',
            'inventory_movements.*.created_at' => 'required|date',
            'shifts' => 'sometimes|array',
            'shifts.*.id' => 'required|uuid',
            'shifts.*.opening_cash' => 'required|numeric|min:0',
            'shifts.*.expected_cash' => 'nullable|numeric|min:0',
            'shifts.*.actual_cash' => 'nullable|numeric|min:0',
            'shifts.*.opened_at' => 'required|date',
            'shifts.*.closed_at' => 'nullable|date',
        ];
    }
}
