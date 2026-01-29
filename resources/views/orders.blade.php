<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Orders UI</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #222; }
        h1 { margin-bottom: 8px; }
        .section { margin: 20px 0; padding: 16px; border: 1px solid #ddd; border-radius: 8px; }
        label { display: block; margin: 8px 0 4px; }
        input, select, button { padding: 8px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        .row { display: flex; gap: 12px; flex-wrap: wrap; }
        .row > div { flex: 1; min-width: 160px; }
        .muted { color: #666; font-size: 12px; }
        .btn { cursor: pointer; }
        .btn-danger { background: #b91c1c; color: #fff; border: 0; }
        .btn-secondary { background: #374151; color: #fff; border: 0; }
        .btn-primary { background: #1d4ed8; color: #fff; border: 0; }
        .item-row { display: flex; gap: 8px; margin-bottom: 6px; }
        .item-row input { flex: 1; }
        .pager { display: flex; gap: 8px; align-items: center; margin-top: 12px; }
        .status-pill { padding: 2px 6px; border-radius: 6px; background: #eee; }
        .error { color: #b91c1c; }
        .success { color: #15803d; }
    </style>
</head>
<body>
    <h1>Orders UI</h1>
    <p class="muted">Simple Blade UI that calls your existing APIs.</p>

    <div class="section">
        <h2>Filters</h2>
        <div class="row">
            <div>
                <label for="filter-status">Status</label>
                <select id="filter-status">
                    <option value="">All</option>
                    <option value="pending">pending</option>
                    <option value="processing">processing</option>
                    <option value="completed">completed</option>
                    <option value="cancelled">cancelled</option>
                </select>
            </div>
            <div>
                <label for="filter-from">From date</label>
                <input type="date" id="filter-from" />
            </div>
            <div>
                <label for="filter-to">To date</label>
                <input type="date" id="filter-to" />
            </div>
            <div style="align-self: end;">
                <button class="btn btn-primary" id="apply-filters">Apply</button>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Create Order</h2>
        <div class="row">
            <div>
                <label for="customer-name">Customer name</label>
                <input type="text" id="customer-name" placeholder="John Doe" />
            </div>
            <div>
                <label for="customer-email">Customer email</label>
                <input type="email" id="customer-email" placeholder="john@example.com" />
            </div>
        </div>
        <div style="margin-top: 12px;">
            <label>Items</label>
            <div id="items-container"></div>
            <button class="btn btn-secondary" id="add-item">Add item</button>
        </div>
        <div style="margin-top: 12px;">
            <button class="btn btn-primary" id="create-order">Create</button>
            <span id="create-message"></span>
        </div>
    </div>

    <div class="section">
        <h2>Orders</h2>
        <div id="list-message"></div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Update Status</th>
                </tr>
            </thead>
            <tbody id="orders-body"></tbody>
        </table>
        <div class="pager">
            <button class="btn btn-secondary" id="prev-page">Prev</button>
            <span id="page-info">Page 1</span>
            <button class="btn btn-secondary" id="next-page">Next</button>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let totalCount = 0;
        const pageSize = 5;

        const ordersBody = document.getElementById('orders-body');
        const listMessage = document.getElementById('list-message');
        const pageInfo = document.getElementById('page-info');

        function buildQuery() {
            const status = document.getElementById('filter-status').value.trim();
            const fromDate = document.getElementById('filter-from').value;
            const toDate = document.getElementById('filter-to').value;
            const params = new URLSearchParams();
            if (status) params.append('status', status);
            if (fromDate) params.append('from_date', fromDate);
            if (toDate) params.append('to_date', toDate);
            const query = params.toString();
            return query ? `?${query}` : '';
        }

        async function fetchOrders() {
            listMessage.textContent = 'Loading...';
            try {
                const res = await fetch(`/api/orders/${currentPage}${buildQuery()}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();
                if (!res.ok || json.status !== 'success') {
                    throw new Error(json.message || 'Failed to load orders');
                }
                totalCount = json.total_count || 0;
                renderOrders(json.data || []);
                pageInfo.textContent = `Page ${currentPage} of ${Math.max(1, Math.ceil(totalCount / pageSize))}`;
                listMessage.textContent = '';
            } catch (err) {
                listMessage.innerHTML = `<span class="error">${err.message}</span>`;
                ordersBody.innerHTML = '';
            }
        }

        function renderOrders(orders) {
            ordersBody.innerHTML = '';
            if (!orders.length) {
                ordersBody.innerHTML = '<tr><td colspan="7">No orders found.</td></tr>';
                return;
            }
            orders.forEach(order => {
                const items = (order.products || [])
                    .map(p => `${p.product_name} (${p.quantity}) - ${p.price}`)
                    .join('<br/>');
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${order.id}</td>
                    <td>${order.order_number}</td>
                    <td>${order.customer_name}<br/><span class="muted">${order.customer_email}</span></td>
                    <td>${order.total_amount}</td>
                    <td><span class="status-pill">${order.status || 'pending'}</span></td>
                    <td>${items || '-'}</td>
                    <td>
                        <select data-order-id="${order.id}">
                            <option value="">Select</option>
                            <option value="pending">pending</option>
                            <option value="processing">processing</option>
                            <option value="completed">completed</option>
                            <option value="cancelled">cancelled</option>
                        </select>
                        <button class="btn btn-primary" data-update-id="${order.id}">Update</button>
                        <div class="muted" id="update-msg-${order.id}"></div>
                    </td>
                `;
                ordersBody.appendChild(row);
            });
        }

        async function createOrder() {
            const messageEl = document.getElementById('create-message');
            messageEl.textContent = '';
            messageEl.className = '';

            const customerName = document.getElementById('customer-name').value.trim();
            const customerEmail = document.getElementById('customer-email').value.trim();

            const items = Array.from(document.querySelectorAll('.item-row')).map(row => ({
                product_name: row.querySelector('[data-field="name"]').value.trim(),
                quantity: row.querySelector('[data-field="qty"]').value.trim(),
                price: row.querySelector('[data-field="price"]').value.trim()
            })).filter(item => item.product_name || item.quantity || item.price);

            try {
                const res = await fetch('/api/orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        customer_name: customerName,
                        customer_email: customerEmail,
                        items: items
                    })
                });
                const json = await res.json();
                if (!res.ok || json.status !== 'success') {
                    throw new Error(json.message || 'Failed to create order');
                }
                messageEl.textContent = json.message || 'Order created';
                messageEl.className = 'success';
                resetCreateForm();
                await fetchOrders();
            } catch (err) {
                messageEl.textContent = err.message;
                messageEl.className = 'error';
            }
        }

        async function updateStatus(orderId) {
            const select = document.querySelector(`select[data-order-id="${orderId}"]`);
            const status = select.value;
            const msgEl = document.getElementById(`update-msg-${orderId}`);
            msgEl.textContent = '';
            if (!status) {
                msgEl.textContent = 'Select status first.';
                return;
            }
            try {
                const res = await fetch(`/api/orders/${orderId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status })
                });
                const json = await res.json();
                if (!res.ok || json.status !== 'success') {
                    throw new Error(json.message || 'Failed to update status');
                }
                msgEl.textContent = 'Updated.';
                await fetchOrders();
            } catch (err) {
                msgEl.textContent = err.message;
            }
        }

        function addItemRow(defaults = {}) {
            const container = document.getElementById('items-container');
            const row = document.createElement('div');
            row.className = 'item-row';
            row.innerHTML = `
                <input type="text" data-field="name" placeholder="Product name" value="${defaults.name || ''}" />
                <input type="number" data-field="qty" placeholder="Qty" value="${defaults.qty || ''}" />
                <input type="number" data-field="price" placeholder="Price" value="${defaults.price || ''}" />
                <button class="btn btn-danger" type="button">Remove</button>
            `;
            row.querySelector('button').addEventListener('click', () => row.remove());
            container.appendChild(row);
        }

        function resetCreateForm() {
            document.getElementById('customer-name').value = '';
            document.getElementById('customer-email').value = '';
            document.getElementById('items-container').innerHTML = '';
            addItemRow();
        }

        document.getElementById('apply-filters').addEventListener('click', () => {
            currentPage = 1;
            fetchOrders();
        });

        document.getElementById('add-item').addEventListener('click', () => addItemRow());
        document.getElementById('create-order').addEventListener('click', createOrder);

        document.getElementById('prev-page').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage -= 1;
                fetchOrders();
            }
        });
        document.getElementById('next-page').addEventListener('click', () => {
            const maxPage = Math.max(1, Math.ceil(totalCount / pageSize));
            if (currentPage < maxPage) {
                currentPage += 1;
                fetchOrders();
            }
        });

        ordersBody.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-update-id]');
            if (btn) updateStatus(btn.getAttribute('data-update-id'));
        });

        resetCreateForm();
        fetchOrders();
    </script>
</body>
</html>
