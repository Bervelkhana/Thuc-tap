# TÀI LIỆU PHÂN TÍCH API CONTRACT

## 1. API List

### 1.1. Authentication

| Method | Endpoint             | Auth | Mô tả             |
| ------ | -------------------- | ---- | ----------------- |
| POST   | `/api/admin/login`   | No   | Đăng nhập admin   |
| POST   | `/api/admin/logout`  | Yes  | Đăng xuất admin   |
| GET    | `/api/admin/me`      | Yes  | Lấy admin hiện tại |

### 1.2. Public catalog

| Method | Endpoint                       | Auth | Mô tả                            |
| ------ | ------------------------------ | ---- | -------------------------------- |
| GET    | `/api/categories`              | No   | Danh sách danh mục               |
| GET    | `/api/products`                | No   | Danh sách, tìm kiếm và lọc       |
| GET    | `/api/products/{id}`           | No   | Chi tiết sản phẩm                |
| GET    | `/api/products/search`         | No   | Tìm kiếm sản phẩm                |
| GET    | `/api/products/sales`          | No   | Sản phẩm đang sale               |
| GET    | `/api/products/newest`         | No   | Sản phẩm mới nhất                |
| GET    | `/api/products/recent-discounts` | No | Sản phẩm giảm giá gần nhất     |

### 1.3. Orders

| Method | Endpoint           | Auth     | Mô tả                  |
| ------ | ------------------ | -------- | ---------------------- |
| POST   | `/api/orders`      | Customer | Tạo đơn COD            |

### 1.4. PC Builder

| Method | Endpoint                             | Auth | Mô tả                       |
| ------ | ------------------------------------ | ---- | --------------------------- |
| GET    | `/api/pc-builder/categories`         | No   | Danh mục PC Builder         |
| GET    | `/api/pc-builder/components`         | No   | Linh kiện theo danh mục     |
| GET    | `/api/pc-builder/search`             | No   | Tìm kiếm linh kiện          |
| GET    | `/api/pc-builder/compatible-mainboards` | No | Mainboard tương thích CPU  |
| GET    | `/api/pc-builder/compatible-cases`   | No   | Case tương thích VGA        |
| POST   | `/api/pc-builder/validate`           | No   | Kiểm tra tương thích        |
| POST   | `/api/pc-builder/recommend`          | No   | AI recommend cấu hình       |

### 1.5. Chat AI

| Method | Endpoint   | Auth | Mô tả           |
| ------ | ---------- | ---- | --------------- |
| POST   | `/api/chat` | No   | AI Chat message |
| POST   | `/api/chat/stream` | No | AI Chat streaming |

### 1.6. Prebuilt Configs

| Method | Endpoint                                  | Auth | Mô tả           |
| ------ | ----------------------------------------- | ---- | --------------- |
| GET    | `/api/prebuilt-configs`                   | No   | Danh sách       |
| GET    | `/api/prebuilt-configs/{id}`              | No   | Chi tiết        |
| POST   | `/api/prebuilt-configs`                   | Admin| Tạo cấu hình    |
| PUT    | `/api/prebuilt-configs/{id}`              | Admin| Cập nhật        |
| DELETE | `/api/prebuilt-configs/{id}`              | Admin| Xóa             |
| PATCH  | `/api/prebuilt-configs/{id}/toggle-active` | Admin | Bật/tắt       |
| PATCH  | `/api/prebuilt-configs/{id}/toggle-featured` | Admin | Đặt nổi bật |

### 1.7. Admin products (CRUD)

| Method | Endpoint                    | Auth  | Mô tả        |
| ------ | --------------------------- | ----- | ------------ |
| POST   | `/api/products`             | Admin | Tạo          |
| PUT    | `/api/products/{id}`        | Admin | Cập nhật     |
| DELETE | `/api/products/{id}`        | Admin | Xóa mềm      |

### 1.8. Admin orders

| Method | Endpoint                        | Auth  | Mô tả           |
| ------ | ------------------------------- | ----- | --------------- |
| GET    | `/api/admin/orders`             | Admin | Danh sách đơn   |
| GET    | `/api/admin/orders/{id}`        | Admin | Chi tiết        |
| PATCH  | `/api/admin/orders/{id}/status` | Admin | Đổi trạng thái  |
| POST   | `/api/admin/orders/{id}/cancel` | Admin | Hủy và hoàn kho |
| DELETE | `/api/admin/orders/{id}`        | Admin | Xóa đơn         |

### 1.9. Admin stats

| Method | Endpoint       | Auth  | Mô tả         |
| ------ | -------------- | ----- | ------------- |
| GET    | `/api/admin/stats` | Admin | Thống kê đơn hàng |
