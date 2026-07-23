# TÀI LIỆU PHÂN TÍCH KIẾN TRÚC HỆ THỐNG

## 1. Kiến trúc tổng thể

```text
┌──────────────────────────────┐
│ Vue 3 Client                 │
│ - Customer UI                │
│ - Admin UI                   │
│ - Pinia                      │
│ - Axios                      │
└──────────────┬───────────────┘
               │ HTTPS / JSON
               ▼
┌──────────────────────────────┐
│ Laravel REST API             │
│ - Authentication            │
│ - Validation                │
│ - Authorization             │
│ - Application Services      │
│ - Domain Rules              │
│ - API Resources             │
└──────────────┬───────────────┘
               │
       ┌───────┴────────┐
       ▼                ▼
┌──────────────┐  ┌──────────────────┐
│ MySQL        │  │ AI Provider      │
│ - Catalog    │  │ Gemini           │
│ - EAV        │  │ Explanation only │
│ - Orders     │  └──────────────────┘
│ - Inventory  │
└──────────────┘
```

## 2. Phân lớp backend

```text
Route
  → Controller
  → Form Request
  → Application Service
  → Domain Service
  → Eloquent Model / Query
  → API Resource
```

### Controller

Chỉ xử lý:

- Nhận request.
- Gọi service.
- Trả response.
- Không chứa logic tương thích hoặc logic phân bổ ngân sách.

### Form Request

Chịu trách nhiệm:

- Validate input.
- Chuẩn hóa dữ liệu.
- Kiểm tra quyền cơ bản.

### Application Service

Điều phối use case:

- `ProductService`
- `OrderService`
- `InventoryService`
- `PcBuildService`
- `AiAdvisorService`

### Domain Service

Chứa business rule độc lập:

- `CompatibilityService`
- `BudgetAllocationService`
- `ProductScoringService`

### API Resource

Chuẩn hóa JSON trả về, tránh trả trực tiếp toàn bộ model.

## 3. Xác thực và phân quyền

- Sử dụng Laravel Sanctum.
- Hai role:
    - `admin`
    - `customer`
- Route admin phải sử dụng middleware kiểm tra authentication và role.
- Guest được phép xem catalog và sử dụng PC Builder.
- Customer đăng nhập mới được xem lịch sử đơn hàng của chính mình.
- Admin được quản lý sản phẩm, tồn kho và đơn hàng.

## 4. Chuẩn response API

### Thành công

```json
{
    "success": true,
    "message": "Request processed successfully",
    "data": {}
}
```

### Thất bại validation

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field": ["Error message"]
    }
}
```

### Xung đột nghiệp vụ

```json
{
    "success": false,
    "message": "Insufficient stock",
    "code": "INSUFFICIENT_STOCK"
}
```

## 5. Chiến lược nhất quán dữ liệu

Khi tạo đơn hàng:

1. Backend nhận danh sách `product_id` và `quantity`.
2. Backend không sử dụng giá do frontend gửi.
3. Backend khóa các dòng sản phẩm cần mua bằng `lockForUpdate()`.
   Backend kiểm tra tồn kho.
4. Backend tính lại giá từng sản phẩm và tổng tiền.
5. Backend tạo `orders`.
6. Backend tạo `order_items`.
7. Backend trừ tồn kho.
8. Toàn bộ quá trình chạy trong `DB::transaction()`.
9. Nếu một bước lỗi, toàn bộ transaction phải rollback.
