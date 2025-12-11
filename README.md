## Medieval Café POS (PHP + MSSQL + Bootstrap)

This project is a lightweight point-of-sale web app for a medieval-themed café. It uses PHP, MSSQL (via PDO sqlsrv), and Bootstrap—no frontend frameworks.

### Prerequisites
- PHP 8+ with `pdo_sqlsrv` and `sqlsrv` extensions enabled.
- MSSQL Server reachable from the web server.
- A web server (Apache in XAMPP is fine).

### 1) Configure database
1. Create a database (e.g., `CafePOS`).
2. Run the SQL in `sql/schema.sql` to create tables and seed a demo admin/cashier.
3. Update `config.php` with your SQL Server host, database, user, and password.

### 2) Run locally
1. Place this project in your web root (e.g., `D:\xampp\htdocs\awoj`).
2. Start Apache and MSSQL.
3. Browse to `/login.php`. Default seeded users:  
   - Admin: `admin@example.com` / `Password123!`  
   - Cashier: `cashier@example.com` / `Password123!`

### 3) Pages
- `login.php` – staff login.
- `dashboard.php` – key metrics.
- `orders.php` – take orders, cart, checkout, receipt.
- `products.php` – menu CRUD (admin only).
- `reports.php` – date-filtered sales.
- `settings.php` – change own password.

### 4) Notes
- Sessions protect routes; role checks gate admin-only pages.
- Styling lives in `assets/css/theme.css`; Bootstrap is pulled from CDN.
- All SQL uses prepared statements.


