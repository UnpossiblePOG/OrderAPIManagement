# Project Documentation

## 1. APIs

The application provides a RESTful API for managing orders.

### Base URL
`/api`

### Endpoints

#### **1. List Orders**
Retrieves a paginated list of orders with optional filters.
- **URL**: `GET /orders/{page_no?}`
- **Parameters**:
  - `page_no` (Optional): Page number for pagination (default: 1).
  - `status` (Query Param): Filter by status (e.g., `Pending`, `Processing`, `Completed`, `Cancelled`).
  - `from_date` (Query Param): Filter orders created after this date (format: YYYY-MM-DD).
  - `to_date` (Query Param): Filter orders created before this date.
- **Response**: JSON object containing order data, total count, and status.

#### **2. Create Order**
Creates a new order with multiple items.
- **URL**: `POST /orders`
- **Headers**: `Content-Type: application/json`
- **Body**:
  ```json
  {
      "customer_name": "John Doe",
      "customer_email": "john@example.com",
      "items": [
          {
              "product_name": "Widget A",
              "quantity": 2,
              "price": 50.00
          }
      ]
  }
  ```
- **Validation**: Checks for valid email, non-empty fields, and positive quantity/price.

#### **3. Update Order Status**
Updates the status of a specific order.
- **URL**: `PUT /orders/{id}`
- **Body**:
  ```json
  {
      "status": "Completed"
  }
  ```
- **Response**: Success message.

---

## 2. User Interface (UI)

The project includes a simple Single Page Interface (SPI) built with **Blade** templates and **Vanilla JavaScript**.

- **URL**: `/orders-ui`
- **File**: `resources/views/orders.blade.php`
- **Features**:
  - **Dashboard**: View list of orders in a table format.
  - **Filtering**: Filter orders by status and date range.
  - **Pagination**: Navigate through pages of orders.
  - **Order Creation**: Form to add a new order with dynamic item rows.
  - **Status Update**: Dropdown to inspect and update the status of existing orders.

---

## 3. Test Cases

Testing is implemented using **PHPUnit** and Laravel's testing features.

### Prerequisites
- PHP 8.4+
- SQLite drivers enabled in `php.ini` (`extension=pdo_sqlite`, `extension=sqlite3`)

### Running Tests
Execute the tests using the Artisan command:
```bash
# Run all tests
php artisan test

# Run a specific test file
php artisan test tests/Feature/OrderUpdateStatusTest.php
```

### Test Coverage

#### **Feature Tests**
Located in `tests/Feature/`.
- **`OrderUpdateStatusTest.php`**:
  - `test_update_order_status_successfully`: Verifies that an order's status can be updated via the API and reflected in the database.
  - `test_update_order_status_not_found`: Verifies that attempting to update a non-existent order ID returns a 500 error.

#### **Unit Tests**
Located in `tests/Unit/`.
- **`OrderTotalTest.php`**:
  - `test_it_calculates_total_sum_of_items`: Tests the `calculateOrderTotal` helper function in `OrderController`. It asserts that the arithmetic logic for summing `price * quantity` across multiple items is correct.
