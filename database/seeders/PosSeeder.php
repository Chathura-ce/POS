<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PosSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Owner',
            'email' => 'owner@pos.test',
            'password' => Hash::make('password'),
            'pin_hash' => Hash::make('1234'),
            'role' => 'owner',
        ]);

        User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Cashier',
            'email' => 'cashier@pos.test',
            'password' => Hash::make('password'),
            'pin_hash' => Hash::make('5678'),
            'role' => 'cashier',
        ]);

        $cat1 = Category::create(['id' => (string) Str::uuid(), 'name' => 'Beverages']);
        $cat2 = Category::create(['id' => (string) Str::uuid(), 'name' => 'Snacks']);

        Product::create(['id' => (string) Str::uuid(), 'category_id' => $cat1->id, 'name' => 'Coffee', 'sku' => 'BEV-001', 'cost_price' => 1.50, 'sell_price' => 3.00, 'stock_quantity' => 100]);
        Product::create(['id' => (string) Str::uuid(), 'category_id' => $cat1->id, 'name' => 'Tea', 'sku' => 'BEV-002', 'cost_price' => 1.00, 'sell_price' => 2.50, 'stock_quantity' => 100]);
        Product::create(['id' => (string) Str::uuid(), 'category_id' => $cat2->id, 'name' => 'Chips', 'sku' => 'SNK-001', 'cost_price' => 0.80, 'sell_price' => 1.50, 'stock_quantity' => 200]);
        Product::create(['id' => (string) Str::uuid(), 'category_id' => $cat2->id, 'name' => 'Cookies', 'sku' => 'SNK-002', 'cost_price' => 1.20, 'sell_price' => 2.00, 'stock_quantity' => 150]);

        Customer::create(['id' => (string) Str::uuid(), 'name' => 'Walk-in Customer', 'phone' => null, 'credit_balance' => 0]);
    }
}
