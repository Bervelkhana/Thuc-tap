# TÀI LIỆU PHÂN TÍCH API CONTRACT

## 1. API List

### 1.1. Authentication

| Method | Endpoint             | Auth | Mô tả             |
| ------ | -------------------- | ---- | ----------------- |
| POST   | `/api/auth/register` | No   | Đăng ký customer  |
| POST   | `/api/auth/login`    | No   | Đăng nhập         |
| POST   | `/api/auth/logout`   | Yes  | Đăng xuất         |
| GET    | `/api/auth/me`       | Yes  | Lấy user hiện tại |

### 1.2. Public catalog

| Method | Endpoint                       | Auth | Mô tả                            |
| ------ | ------------------------------ | ---- | -------------------------------- |
| GET    | `/api/categories`              | No   | Danh sách danh mục               |
| GET    | `/api/products`                | No   | Danh sách, tìm kiếm và lọc       |
| GET    | `/api/products/{id}`           | No   | Chi tiết sản phẩm                |
| GET    | `/api/categories/{id}/filters` | No   | Lấy thuộc tính lọc theo danh mục |

Ví dụ query:

```text
GET /api/products?category_id=1
GET /api/products?min_price=1000000&max_price=5000000
GET /api/products?filters[cpu_socket]=LGA1700
GET /api/products?filters[vram_gb][min]=8
```

### 1.3. Orders

| Method | Endpoint           | Auth     | Mô tả                  |
| ------ | ------------------ | -------- | ---------------------- |
| POST   | `/api/orders`      | Customer | Tạo đơn COD            |
| GET    | `/api/orders`      | Customer | Danh sách đơn của user |
| GET    | `/api/orders/{id}` | Customer | Chi tiết đơn của user  |

### 1.4. PC Builder

| Method | Endpoint                             | Auth | Mô tả                       |
| ------ | ------------------------------------ | ---- | --------------------------- |
| POST   | `/api/pc-builds/recommend`           | No   | Tạo cấu hình                |
| POST   | `/api/pc-builds/check-compatibility` | No   | Kiểm tra danh sách sản phẩm |

### 1.5. Admin categories

| Method | Endpoint                     | Auth  | Mô tả     |
| ------ | ---------------------------- | ----- | --------- |
| GET    | `/api/admin/categories`      | Admin | Danh sách |
| POST   | `/api/admin/categories`      | Admin | Tạo       |
| GET    | `/api/admin/categories/{id}` | Admin | Chi tiết  |
| PUT    | `/api/admin/categories/{id}` | Admin | Cập nhật  |
| DELETE | `/api/admin/categories/{id}` | Admin | Xóa mềm   |

### 1.6. Admin attributes

| Method | Endpoint                                | Auth  | Mô tả          |
| ------ | --------------------------------------- | ----- | -------------- |
| GET    | `/api/admin/attributes`                 | Admin | Danh sách      |
| POST   | `/api/admin/attributes`                 | Admin | Tạo            |
| PUT    | `/api/admin/attributes/{id}`            | Admin | Cập nhật       |
| DELETE | `/api/admin/attributes/{id}`            | Admin | Xóa            |
| PUT    | `/api/admin/categories/{id}/attributes` | Admin | Gán thuộc tính |

### 1.7. Admin products

| Method | Endpoint                              | Auth  | Mô tả        |
| ------ | ------------------------------------- | ----- | ------------ |
| GET    | `/api/admin/products`                 | Admin | Danh sách    |
| POST   | `/api/admin/products`                 | Admin | Tạo          |
| GET    | `/api/admin/products/{id}`            | Admin | Chi tiết     |
| PUT    | `/api/admin/products/{id}`            | Admin | Cập nhật     |
| DELETE | `/api/admin/products/{id}`            | Admin | Xóa mềm      |
| PUT    | `/api/admin/products/{id}/attributes` | Admin | Cập nhật EAV |

### 1.8. Admin orders

| Method | Endpoint                        | Auth  | Mô tả           |
| ------ | ------------------------------- | ----- | --------------- |
| GET    | `/api/admin/orders`             | Admin | Danh sách đơn   |
| GET    | `/api/admin/orders/{id}`        | Admin | Chi tiết        |
| PATCH  | `/api/admin/orders/{id}/status` | Admin | Đổi trạng thái  |
| POST   | `/api/admin/orders/{id}/cancel` | Admin | Hủy và hoàn kho |

---
