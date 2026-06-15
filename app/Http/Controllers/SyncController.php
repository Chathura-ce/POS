<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncPushRequest;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CreditPayment;
use App\Models\InventoryMovement;
use App\Models\Shift;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierPayment;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    public function pull(Request $request): JsonResponse
    {
        $cursor = $request->cursor ? Carbon::parse($request->cursor) : null;

        $products = Product::when($cursor, fn($q) => $q->where('updated_at', '>', $cursor))->get();
        $categories = Category::when($cursor, fn($q) => $q->where('updated_at', '>', $cursor))->get();
        $customers = Customer::when($cursor, fn($q) => $q->where('updated_at', '>', $cursor))->get();
        $suppliers = Supplier::when($cursor, fn($q) => $q->where('updated_at', '>', $cursor))->get();

        return response()->json([
            'products' => $products,
            'categories' => $categories,
            'customers' => $customers,
            'suppliers' => $suppliers,
            'server_time' => Carbon::now()->toIso8601String(),
        ]);
    }

    public function push(SyncPushRequest $request): JsonResponse
    {
        $syncedAt = Carbon::now();

        try {
            DB::transaction(function () use ($request, $syncedAt) {
                // Sales
                foreach ($request->sales ?? [] as $saleData) {
                    $sale = Sale::firstOrCreate(
                        ['id' => $saleData['id']],
                        [
                            'user_id' => $saleData['user_id'],
                            'subtotal' => $saleData['subtotal'],
                            'discount' => $saleData['discount'],
                            'total' => $saleData['total'],
                            'payment_type' => $saleData['payment_type'],
                            'status' => $saleData['status'],
                            'created_at' => $saleData['created_at'],
                            'synced_at' => $syncedAt,
                        ]
                    );

                    if ($sale->wasRecentlyCreated) {
                        foreach ($saleData['items'] as $itemData) {
                            SaleItem::create([
                                'id' => $itemData['id'],
                                'sale_id' => $sale->id,
                                'product_id' => $itemData['product_id'],
                                'quantity' => $itemData['quantity'],
                                'unit_price' => $itemData['unit_price'],
                                'line_total' => $itemData['line_total'],
                            ]);

                            Product::where('id', $itemData['product_id'])
                                ->decrement('stock_quantity', $itemData['quantity']);
                        }
                    } elseif ($saleData['status'] === 'refunded' && $sale->status !== 'refunded') {
                        $sale->update(['status' => 'refunded', 'synced_at' => $syncedAt]);

                        $items = SaleItem::where('sale_id', $sale->id)->get();
                        foreach ($items as $item) {
                            Product::where('id', $item->product_id)
                                ->increment('stock_quantity', $item->quantity);
                        }
                    }
                }

                // Customers
                foreach ($request->customers ?? [] as $customerData) {
                    $existing = Customer::find($customerData['id']);

                    if ($existing) {
                        if (Carbon::parse($customerData['updated_at'])->gt($existing->updated_at)) {
                            $existing->update([
                                'name' => $customerData['name'],
                                'phone' => $customerData['phone'] ?? null,
                                'credit_balance' => $customerData['credit_balance'],
                                'updated_at' => $customerData['updated_at'],
                            ]);
                        }
                    } else {
                        Customer::create([
                            'id' => $customerData['id'],
                            'name' => $customerData['name'],
                            'phone' => $customerData['phone'] ?? null,
                            'credit_balance' => $customerData['credit_balance'],
                            'created_at' => $customerData['updated_at'],
                            'updated_at' => $customerData['updated_at'],
                        ]);
                    }
                }

                // Products
                foreach ($request->products ?? [] as $productData) {
                    $existing = Product::find($productData['id']);

                    if ($existing) {
                        if (Carbon::parse($productData['updated_at'])->gt($existing->updated_at)) {
                            $existing->update([
                                'category_id' => $productData['category_id'],
                                'name' => $productData['name'],
                                'sku' => $productData['sku'],
                                'barcode' => $productData['barcode'] ?? null,
                                'cost_price' => $productData['cost_price'],
                                'sell_price' => $productData['sell_price'],
                                'stock_quantity' => $productData['stock_quantity'],
                                'is_active' => $productData['is_active'],
                                'updated_at' => $productData['updated_at'],
                            ]);
                        }
                    } else {
                        Product::create([
                            'id' => $productData['id'],
                            'category_id' => $productData['category_id'],
                            'name' => $productData['name'],
                            'sku' => $productData['sku'],
                            'barcode' => $productData['barcode'] ?? null,
                            'cost_price' => $productData['cost_price'],
                            'sell_price' => $productData['sell_price'],
                            'stock_quantity' => $productData['stock_quantity'],
                            'is_active' => $productData['is_active'],
                            'created_at' => $productData['updated_at'],
                            'updated_at' => $productData['updated_at'],
                        ]);
                    }
                }

                // Credit payments
                foreach ($request->credit_payments ?? [] as $paymentData) {
                    CreditPayment::firstOrCreate(
                        ['id' => $paymentData['id']],
                        [
                            'customer_id' => $paymentData['customer_id'],
                            'amount' => $paymentData['amount'],
                            'created_at' => $paymentData['created_at'],
                            'synced_at' => $syncedAt,
                        ]
                    );

                    Customer::where('id', $paymentData['customer_id'])
                        ->decrement('credit_balance', $paymentData['amount']);
                }

                // Inventory movements
                foreach ($request->inventory_movements ?? [] as $movementData) {
                    InventoryMovement::firstOrCreate(
                        ['id' => $movementData['id']],
                        [
                            'product_id' => $movementData['product_id'],
                            'qty_change' => $movementData['qty_change'],
                            'reason' => $movementData['reason'],
                            'created_at' => $movementData['created_at'],
                            'synced_at' => $syncedAt,
                        ]
                    );
                }

                // Shifts
                foreach ($request->shifts ?? [] as $shiftData) {
                    Shift::firstOrCreate(
                        ['id' => $shiftData['id']],
                        [
                            'opening_cash' => $shiftData['opening_cash'],
                            'expected_cash' => $shiftData['expected_cash'] ?? null,
                            'actual_cash' => $shiftData['actual_cash'] ?? null,
                            'opened_at' => $shiftData['opened_at'],
                            'closed_at' => $shiftData['closed_at'] ?? null,
                            'synced_at' => $syncedAt,
                        ]
                    );
                }

                // Suppliers
                foreach ($request->suppliers ?? [] as $supplierData) {
                    $existing = Supplier::find($supplierData['id']);

                    if ($existing) {
                        if (Carbon::parse($supplierData['updated_at'])->gt($existing->updated_at)) {
                            $existing->update([
                                'name' => $supplierData['name'],
                                'phone' => $supplierData['phone'] ?? null,
                                'email' => $supplierData['email'] ?? null,
                                'balance' => $supplierData['balance'],
                                'updated_at' => $supplierData['updated_at'],
                            ]);
                        }
                    } else {
                        Supplier::create([
                            'id' => $supplierData['id'],
                            'name' => $supplierData['name'],
                            'phone' => $supplierData['phone'] ?? null,
                            'email' => $supplierData['email'] ?? null,
                            'balance' => $supplierData['balance'],
                            'created_at' => $supplierData['updated_at'],
                            'updated_at' => $supplierData['updated_at'],
                        ]);
                    }
                }

                // Purchase Orders (append-only firstOrCreate)
                foreach ($request->purchase_orders ?? [] as $poData) {
                    $po = PurchaseOrder::firstOrCreate(
                        ['id' => $poData['id']],
                        [
                            'supplier_id' => $poData['supplier_id'],
                            'total_amount' => $poData['total_amount'],
                            'status' => $poData['status'],
                            'created_at' => $poData['created_at'],
                            'synced_at' => $syncedAt,
                        ]
                    );

                    if ($po->wasRecentlyCreated) {
                        foreach ($poData['items'] as $itemData) {
                            PurchaseOrderItem::create([
                                'id' => $itemData['id'],
                                'purchase_order_id' => $po->id,
                                'product_id' => $itemData['product_id'],
                                'quantity' => $itemData['quantity'],
                                'unit_cost' => $itemData['unit_cost'],
                                'line_total' => $itemData['line_total'],
                            ]);
                        }
                    }
                }

                // Supplier Payments (append-only firstOrCreate, deducts balance)
                foreach ($request->supplier_payments ?? [] as $paymentData) {
                    SupplierPayment::firstOrCreate(
                        ['id' => $paymentData['id']],
                        [
                            'supplier_id' => $paymentData['supplier_id'],
                            'amount' => $paymentData['amount'],
                            'synced_at' => $syncedAt,
                        ]
                    );

                    Supplier::where('id', $paymentData['supplier_id'])
                        ->decrement('balance', $paymentData['amount']);
                }
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'synced_at' => $syncedAt->toIso8601String(),
        ]);
    }
}
