import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import db from '../db';
import axios from 'axios';

export const usePosStore = defineStore('pos', () => {
    const cart = ref([]);
    const products = ref([]);
    const customers = ref([]);
    const suppliers = ref([]);
    const purchaseOrders = ref([]);
    const activeCustomer = ref(null);
    const taxRate = ref(0.15);
    const isOnline = ref(navigator.onLine);

    window.addEventListener('online', () => { isOnline.value = true; });
    window.addEventListener('offline', () => { isOnline.value = false; });

    const cartSubtotal = computed(() =>
        cart.value.reduce((sum, item) => sum + item.line_total, 0)
    );

    const cartTax = computed(() =>
        cartSubtotal.value * taxRate.value
    );

    const cartTotal = computed(() =>
        cartSubtotal.value + cartTax.value
    );

    async function loadProducts() {
        products.value = await db.products.toArray();
    }

    async function loadCustomers() {
        customers.value = await db.customers.toArray();
    }

    function setActiveCustomer(customer) {
        activeCustomer.value = customer;
    }

    function clearActiveCustomer() {
        activeCustomer.value = null;
    }

    async function saveCustomer(customer) {
        const data = {
            ...customer,
            id: customer.id || crypto.randomUUID(),
            credit_balance: parseFloat(customer.credit_balance) || 0,
            updated_at: new Date().toISOString(),
            synced: 0,
        };

        await db.customers.put(data);
        await loadCustomers();
        syncData();
    }

    async function settleCredit(customerId, amount) {
        const customer = await db.customers.get(customerId);
        if (!customer) return;

        const newBalance = Math.max(0, (parseFloat(customer.credit_balance) || 0) - amount);

        await db.customers.update(customerId, {
            credit_balance: newBalance,
            updated_at: new Date().toISOString(),
            synced: 0,
        });

        await db.credit_payments.put({
            id: crypto.randomUUID(),
            customer_id: customerId,
            amount,
            created_at: new Date().toISOString(),
            synced: 0,
        });

        await loadCustomers();
        syncData();
    }

    async function loadProcurement() {
        suppliers.value = await db.suppliers.toArray();
        purchaseOrders.value = await db.purchase_orders.toArray();
    }

    async function saveSupplier(supplier) {
        const data = {
            ...supplier,
            id: supplier.id || crypto.randomUUID(),
            balance: parseFloat(supplier.balance) || 0,
            updated_at: new Date().toISOString(),
            synced: 0,
        };

        await db.suppliers.put(data);
        await loadProcurement();
        syncData();
    }

    async function createPO(poData) {
        const po = {
            id: crypto.randomUUID(),
            supplier_id: poData.supplier_id,
            total_amount: poData.total_amount,
            status: 'pending',
            created_at: new Date().toISOString(),
            items: poData.items || [],
            synced: 0,
        };

        await db.purchase_orders.put(po);
        await loadProcurement();
        syncData();
    }

    async function receivePO(poId) {
        const po = await db.purchase_orders.get(poId);
        if (!po || po.status === 'received') return;

        for (const item of po.items) {
            const product = await db.products.get(item.product_id);
            if (product) {
                await db.products.update(item.product_id, {
                    stock_quantity: (product.stock_quantity || 0) + item.quantity,
                    updated_at: new Date().toISOString(),
                    synced: 0,
                });
            }

            await db.inventory_movements.put({
                id: crypto.randomUUID(),
                product_id: item.product_id,
                qty_change: item.quantity,
                reason: 'restock',
                created_at: new Date().toISOString(),
                synced: 0,
            });
        }

        const supplier = await db.suppliers.get(po.supplier_id);
        if (supplier) {
            await db.suppliers.update(po.supplier_id, {
                balance: (parseFloat(supplier.balance) || 0) + parseFloat(po.total_amount),
                updated_at: new Date().toISOString(),
                synced: 0,
            });
        }

        await db.purchase_orders.update(poId, {
            status: 'received',
            synced: 0,
        });

        await loadProducts();
        await loadProcurement();
        syncData();
    }

    async function paySupplier(supplierId, amount) {
        const supplier = await db.suppliers.get(supplierId);
        if (!supplier) return;

        const newBalance = Math.max(0, (parseFloat(supplier.balance) || 0) - amount);

        await db.suppliers.update(supplierId, {
            balance: newBalance,
            updated_at: new Date().toISOString(),
            synced: 0,
        });

        await db.supplier_payments.put({
            id: crypto.randomUUID(),
            supplier_id: supplierId,
            amount,
            synced: 0,
        });

        await loadProcurement();
        syncData();
    }

    async function adjustStock(productId, qtyChange, reason) {
        const product = await db.products.get(productId);
        if (!product) return;

        const newQty = Math.max(0, (product.stock_quantity || 0) + qtyChange);

        await db.products.update(productId, {
            stock_quantity: newQty,
            updated_at: new Date().toISOString(),
            synced: 0,
        });

        await db.inventory_movements.put({
            id: crypto.randomUUID(),
            product_id: productId,
            qty_change: qtyChange,
            reason,
            created_at: new Date().toISOString(),
            synced: 0,
        });

        await loadProducts();
        syncData();
    }

    async function openShift(amount) {
        await db.shifts.put({
            id: crypto.randomUUID(),
            opening_cash: amount,
            opened_at: new Date().toISOString(),
            synced: 0,
        });
        syncData();
    }

    async function closeShift(shiftId, actualCash) {
        const shift = await db.shifts.get(shiftId);
        if (!shift) return;

        const cashSales = await db.offline_sales
            .where('payment_type')
            .equals('cash')
            .filter(s => s.status !== 'refunded')
            .toArray();

        const totalCash = cashSales.reduce((sum, s) => sum + parseFloat(s.total), 0);
        const expectedCash = parseFloat(shift.opening_cash) + totalCash;

        await db.shifts.update(shiftId, {
            expected_cash: expectedCash,
            actual_cash: actualCash,
            closed_at: new Date().toISOString(),
            synced: 0,
        });
        syncData();
    }

    async function refundSale(saleId) {
        const sale = await db.offline_sales.get(saleId);
        if (!sale || sale.status === 'refunded') return;

        const items = sale.items || [];

        for (const item of items) {
            const product = await db.products.get(item.product_id);
            if (product) {
                await db.products.update(item.product_id, {
                    stock_quantity: (product.stock_quantity || 0) + item.quantity,
                    updated_at: new Date().toISOString(),
                    synced: 0,
                });
            }
        }

        if (sale.payment_type === 'credit' && sale.customer_id) {
            const customer = await db.customers.get(sale.customer_id);
            if (customer) {
                const newBalance = Math.max(0, (parseFloat(customer.credit_balance) || 0) - parseFloat(sale.total));
                await db.customers.update(sale.customer_id, {
                    credit_balance: newBalance,
                    updated_at: new Date().toISOString(),
                    synced: 0,
                });
            }
        }

        await db.offline_sales.update(saleId, {
            status: 'refunded',
            synced: 0,
        });

        await loadProducts();
        await loadCustomers();
        syncData();
    }

    function addToCart(product) {
        const existing = cart.value.find(i => i.product_id === product.id);
        if (existing) {
            existing.quantity += 1;
            existing.line_total = existing.quantity * existing.unit_price;
        } else {
            cart.value.push({
                product_id: product.id,
                name: product.name,
                unit_price: parseFloat(product.sell_price),
                quantity: 1,
                line_total: parseFloat(product.sell_price),
            });
        }
    }

    function removeFromCart(productId) {
        cart.value = cart.value.filter(i => i.product_id !== productId);
    }

    function clearCart() {
        cart.value = [];
    }

    async function checkout(paymentDetails = {}) {
        const paymentType = paymentDetails.paymentType || 'cash';
        const change = paymentDetails.changeDue || 0;
        const subtotal = cartSubtotal.value;
        const tax = cartTax.value;
        const total = cartTotal.value;

        const sale = {
            id: crypto.randomUUID(),
            user_id: localStorage.getItem('pos_user_id') || 'offline',
            subtotal,
            discount: 0,
            tax,
            total,
            payment_type: paymentType,
            amount_tendered: parseFloat(paymentDetails.amountTendered) || total,
            change,
            status: 'completed',
            created_at: new Date().toISOString(),
            items: cart.value.map(item => ({
                id: crypto.randomUUID(),
                product_id: item.product_id,
                name: item.name,
                quantity: item.quantity,
                unit_price: item.unit_price,
                line_total: item.line_total,
            })),
        };

        if (activeCustomer.value) {
            sale.customer_id = activeCustomer.value.id;
        }

        await db.offline_sales.put({ ...sale, synced: 0 });

        if (paymentType === 'credit' && activeCustomer.value) {
            const newBalance = (parseFloat(activeCustomer.value.credit_balance) || 0) + total;
            await db.customers.update(activeCustomer.value.id, {
                credit_balance: newBalance,
                updated_at: new Date().toISOString(),
                synced: 0,
            });
            activeCustomer.value.credit_balance = newBalance;
        }

        clearCart();
        clearActiveCustomer();
        syncData();
        return sale;
    }

    async function saveProduct(product) {
        const data = {
            ...product,
            id: product.id || crypto.randomUUID(),
            updated_at: new Date().toISOString(),
            synced: 0,
        };

        await db.products.put(data);
        await loadProducts();
        syncData();
    }

    async function pullCatalog() {
        if (!isOnline.value) return;

        try {
            const { data } = await axios.get('/api/sync/pull');
            if (data.products.length) await db.products.bulkPut(data.products);
            if (data.categories.length) await db.categories.bulkPut(data.categories);
            if (data.customers.length) await db.customers.bulkPut(data.customers);
            if (data.suppliers?.length) await db.suppliers.bulkPut(data.suppliers);
            await loadProducts();
            await loadCustomers();
            await loadProcurement();
        } catch (e) {
            console.warn('Pull catalog failed:', e.message);
        }
    }

    async function syncData() {
        if (!isOnline.value) return;

        const pendingSales = await db.offline_sales
            .where('synced')
            .equals(0)
            .toArray();

        const pendingProducts = await db.products
            .where('synced')
            .equals(0)
            .toArray();

        const pendingCustomers = await db.customers
            .where('synced')
            .equals(0)
            .toArray();

        const pendingCreditPayments = await db.credit_payments
            .where('synced')
            .equals(0)
            .toArray();

        const pendingMovements = await db.inventory_movements
            .where('synced')
            .equals(0)
            .toArray();

        const pendingShifts = await db.shifts
            .where('synced')
            .equals(0)
            .toArray();

        const pendingSuppliers = await db.suppliers
            .where('synced')
            .equals(0)
            .toArray();

        const pendingPOs = await db.purchase_orders
            .where('synced')
            .equals(0)
            .toArray();

        const pendingSupplierPayments = await db.supplier_payments
            .where('synced')
            .equals(0)
            .toArray();

        if (pendingSales.length === 0 && pendingProducts.length === 0 && pendingCustomers.length === 0 &&
            pendingCreditPayments.length === 0 && pendingMovements.length === 0 && pendingShifts.length === 0 &&
            pendingSuppliers.length === 0 && pendingPOs.length === 0 && pendingSupplierPayments.length === 0) return;

        try {
            const { data } = await axios.post('/api/sync/push', {
                sales: pendingSales,
                products: pendingProducts,
                customers: pendingCustomers,
                credit_payments: pendingCreditPayments,
                inventory_movements: pendingMovements,
                shifts: pendingShifts,
                suppliers: pendingSuppliers,
                purchase_orders: pendingPOs,
                supplier_payments: pendingSupplierPayments,
            });
            if (data.success) {
                for (const sale of pendingSales) {
                    await db.offline_sales.update(sale.id, { synced: 1 });
                }
                for (const product of pendingProducts) {
                    await db.products.update(product.id, { synced: 1 });
                }
                for (const customer of pendingCustomers) {
                    await db.customers.update(customer.id, { synced: 1 });
                }
                for (const payment of pendingCreditPayments) {
                    await db.credit_payments.update(payment.id, { synced: 1 });
                }
                for (const movement of pendingMovements) {
                    await db.inventory_movements.update(movement.id, { synced: 1 });
                }
                for (const shift of pendingShifts) {
                    await db.shifts.update(shift.id, { synced: 1 });
                }
                for (const supplier of pendingSuppliers) {
                    await db.suppliers.update(supplier.id, { synced: 1 });
                }
                for (const po of pendingPOs) {
                    await db.purchase_orders.update(po.id, { synced: 1 });
                }
                for (const payment of pendingSupplierPayments) {
                    await db.supplier_payments.update(payment.id, { synced: 1 });
                }
            }
        } catch (e) {
            console.warn('Sync failed:', e.message);
        }
    }

    return {
        cart,
        products,
        customers,
        suppliers,
        purchaseOrders,
        activeCustomer,
        taxRate,
        isOnline,
        cartSubtotal,
        cartTax,
        cartTotal,
        loadProducts,
        loadCustomers,
        setActiveCustomer,
        clearActiveCustomer,
        saveCustomer,
        settleCredit,
        loadProcurement,
        saveSupplier,
        createPO,
        receivePO,
        paySupplier,
        adjustStock,
        openShift,
        closeShift,
        refundSale,
        addToCart,
        removeFromCart,
        clearCart,
        checkout,
        saveProduct,
        pullCatalog,
        syncData,
    };
});
