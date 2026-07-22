# THIẾT KẾ CƠ SỞ DỮ LIỆU (DATABASE DESIGN)

Hệ thống áp dụng kiến trúc EAV (Entity-Attribute-Value) tối ưu hóa cho module AI PC Builder.

## 1. Cấu trúc các bảng cốt lõi (Table Schema)

**Nhóm Quản lý (Users & Orders):**
* `users` (id, name, email, password, role, phone, address, created_at)
* `orders` (id, user_id, total_amount, status, payment_method, created_at)
* `order_items` (id, order_id, product_id, quantity, price)

**Nhóm Thực thể (Entity):**
* `categories` (id, parent_id, name)
* `products` (id, category_id, sku, name, price, stock_quantity, description, thumbnail_url)

**Nhóm EAV (Quản lý Thuộc tính Động):**
* `attributes` (id, name - VD: "Socket", "VRAM")
* `category_attribute` (category_id, attribute_id)
* `product_attribute_values` (product_id, attribute_id, value - VD: "LGA 1700")

## 2. Bảng ERD (Sơ đồ Thực thể Liên kết)

![Sơ đồ ERD](erd.png)