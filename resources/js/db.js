import Dexie from 'dexie';

const db = new Dexie('PosDatabase');

db.version(1).stores({
    products: 'id, category_id, name, sku, barcode, is_active, updated_at',
    categories: 'id, name, is_active',
    customers: 'id, name, phone',
    offline_sales: 'id, synced',
});

db.version(2).stores({
    products: 'id, category_id, name, sku, barcode, is_active, updated_at, synced',
    categories: 'id, name, is_active',
    customers: 'id, name, phone',
    offline_sales: 'id, synced',
});

db.version(3).stores({
    products: 'id, category_id, name, sku, barcode, is_active, updated_at, synced',
    categories: 'id, name, is_active',
    customers: 'id, name, phone, credit_balance, updated_at, synced',
    offline_sales: 'id, synced',
});

db.version(4).stores({
    products: 'id, category_id, name, sku, barcode, is_active, updated_at, synced',
    categories: 'id, name, is_active',
    customers: 'id, name, phone, credit_balance, updated_at, synced',
    offline_sales: 'id, synced',
    credit_payments: 'id, customer_id, amount, created_at, synced',
    inventory_movements: 'id, product_id, qty_change, reason, created_at, synced',
    shifts: 'id, opened_at, closed_at, opening_cash, expected_cash, actual_cash, synced',
});

db.version(5).stores({
    products: 'id, category_id, name, sku, barcode, is_active, updated_at, synced',
    categories: 'id, name, is_active',
    customers: 'id, name, phone, credit_balance, updated_at, synced',
    offline_sales: 'id, payment_type, created_at, synced',
    credit_payments: 'id, customer_id, amount, created_at, synced',
    inventory_movements: 'id, product_id, qty_change, reason, created_at, synced',
    shifts: 'id, opened_at, closed_at, opening_cash, expected_cash, actual_cash, synced',
});

export default db;
