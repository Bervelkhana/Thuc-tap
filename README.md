# TechGear - Hệ thống thương mại điện tử linh kiện PC

Hệ thống bán linh kiện máy tính trực tuyến với trợ lý AI tư vấn cấu hình PC, được xây dựng bằng **Laravel 10+** (Backend) và **Vue.js 3** (Frontend).

## Tính năng chính

- **Catalog sản phẩm:** Lọc động, tìm kiếm, phân trang
- **Giỏ hàng & Checkout:** Quản lý giỏ hàng bằng Pinia, thanh toán COD/VietQR, unified 4-step checkout flow (Cart → Address → Payment → Confirm)
- **AI ChatBox:** Trợ lý AI tư vấn mua sắm với streaming response
- **AI PC Builder:** Tự động build cấu hình PC theo ngân sách và nhu cầu
- **Manual PC Builder:** Chọn linh kiện thủ công với kiểm tra tương thích
- **Admin Dashboard:** Quản lý sản phẩm, đơn hàng, cấu hình build sẵn

## Công nghệ sử dụng

| Layer | Công nghệ |
|-------|-----------|
| Backend | PHP 8.2+, Laravel 10+, Eloquent ORM |
| Frontend | Vue 3 (Composition API), Tailwind CSS, Pinia |
| Database | MySQL |
| AI | NVIDIA NIM API (LLaMA 3.1), OpenAI API, Groq API |
| Testing | PHPUnit 9.5+ |

## Yêu cầu hệ thống

- PHP >= 8.0.2
- Composer
- Node.js >= 16.x
- MySQL >= 5.7
- Extension PHP: pdo_mysql, mbstring, curl, json

## Cài đặt & Chạy dự án

### 1. Clone repository

```bash
git clone <repository-url> techgear
cd techgear
```

### 2. Cấu hình môi trường

```bash
# Sao chép file .env
cp .env.example .env

# Tạo APP_KEY
php artisan key:generate
```

Mở file `.env` và cấu hình các thông số sau:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=techgear
DB_USERNAME=root
DB_PASSWORD=

# Admin credentials (dùng cho đăng nhập admin)
ADMIN_EMAIL=admin@techgear.vn
ADMIN_PASSWORD=admin123

# AI API Keys (chọn 1 trong 2)
NVIDIA_NIM_API_KEY=nvapi-...
NVIDIA_NIM_BASE_URL=https://integrate.api.nvidia.com/v1
NVIDIA_NIM_MODEL=meta/llama-3.1-70b-instruct

# Hoặc dùng OpenAI
OPENAI_API_KEY=sk-...

# App
APP_URL=http://localhost:8000
APP_DEBUG=true
```

### 3. Cài đặt Backend dependencies

```bash
composer install
```

### 4. Chạy Migration & Seed dữ liệu

```bash
# Chạy tất cả migrations
php artisan migrate

# Seed dữ liệu mẫu (categories, products, attributes)
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=HardwareProductsSeeder
php artisan db:seed --class=PCBuilderSampleDataSeeder

# Hoặc seed tất cả
php artisan db:seed
```

### 5. Cài đặt Frontend dependencies

```bash
npm install
```

### 6. Chạy dự án

Mở 2 terminal:

**Terminal 1 - Backend:**
```bash
php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal 2 - Frontend:**
```bash
npm run dev
```

Truy cập: `http://localhost:5173`

Hoặc chạy cả 2 cùng lúc:
```bash
npm run dev:all
```

## Chạy Tests

### Chạy tất cả tests

```bash
php artisan test
```

### Chạy tests theo nhóm

```bash
# Feature tests (API, integration)
php artisan test --testsuite=Feature

# Unit tests
php artisan test --testsuite=Unit

# Chạy test cụ thể
php artisan test --filter=OrderApiTest
php artisan test --filter=ChatAuthorizationTest
```

### Chạy test với coverage

```bash
php artisan test --coverage
```

## Cấu trúc thư mục

```
techgear/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/                    # REST API Controllers
│   │   │   ├── ChatController.php
│   │   │   ├── ProductController.php
│   │   │   ├── OrderController.php
│   │   │   ├── PCBuilderController.php
│   │   │   └── AdminOrderController.php
│   │   └── AiBuilderController.php # AI Build page
│   ├── Services/                   # Business logic
│   │   ├── NvidiaNimChatService.php
│   │   ├── AiChatService.php
│   │   ├── NvidiaNimBuildService.php
│   │   ├── IntentAnalyzerService.php
│   │   ├── ProductService.php
│   │   └── PCCompatibilityValidator.php
│   └── Models/
│       ├── User.php
│       ├── Product.php
│       ├── Category.php
│       └── Order.php
├── resources/
│   └── js/
│       ├── components/
│       │   ├── ChatBoxAI.vue
│       │   └── backend/            # Admin components
│       └── views/                  # Page views
├── routes/
│   ├── api.php                     # API routes
│   └── web.php                     # Web routes
├── tests/
│   ├── Feature/                    # Feature tests
│   │   ├── OrderApiTest.php
│   │   └── ChatAuthorizationTest.php
│   └── Unit/                       # Unit tests
│       ├── ProductServiceTest.php
│       └── AiFallbackTest.php
└── DOCS/
    └── checklist.md
```

## Testing

### Test Coverage hiện tại

| Module | Tests | Trạng thái |
|--------|-------|-----------|
| Order API | 12 tests | ✅ Passing |
| Order Concurrency | 4 tests | ✅ Passing |
| Product Attribute Service | 7 tests | ✅ Passing |
| Admin Authorization | 3 tests | ✅ Passing |

### Chạy tests

```bash
# Chạy tất cả tests
php artisan test

# Chạy tests theo nhóm
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Chạy test cụ thể
php artisan test --filter=OrderApiTest
php artisan test --filter=OrderConcurrencyTest
php artisan test --filter=ProductAttributeServiceTest
```

## API Endpoints

### Public APIs

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/categories` | Danh sách danh mục |
| GET | `/api/products` | Danh sách sản phẩm (có filter) |
| GET | `/api/products/{id}` | Chi tiết sản phẩm |
| GET | `/api/products/search` | Tìm kiếm sản phẩm |
| GET | `/api/products/sales` | Sản phẩm đang sale |
| GET | `/api/products/newest` | Sản phẩm mới nhất |
| POST | `/api/orders` | Tạo đơn hàng |
| POST | `/api/chat` | AI Chat |
| GET | `/api/pc-builder/categories` | Danh mục PC Builder |
| GET | `/api/pc-builder/components` | Linh kiện theo danh mục |
| POST | `/api/pc-builder/validate` | Kiểm tra tương thích |
| POST | `/api/pc-builder/recommend` | AI recommend cấu hình |

### Admin APIs

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| POST | `/api/admin/login` | Đăng nhập admin |
| POST | `/api/admin/logout` | Đăng xuất admin |
| GET | `/api/admin/stats` | Thống kê đơn hàng |
| GET | `/api/admin/orders` | Danh sách đơn hàng |
| GET | `/api/admin/orders/{id}` | Chi tiết đơn hàng |
| PATCH | `/api/admin/orders/{id}/status` | Cập nhật trạng thái |
| POST | `/api/admin/orders/{id}/cancel` | Hủy đơn hàng |
| POST | `/api/products` | Tạo sản phẩm |
| PUT | `/api/products/{id}` | Cập nhật sản phẩm |
| DELETE | `/api/products/{id}` | Xóa sản phẩm |

### Prebuilt Config APIs

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/prebuilt-configs` | Danh sách cấu hình build sẵn |
| GET | `/api/prebuilt-configs/{id}` | Chi tiết cấu hình |
| POST | `/api/prebuilt-configs` | Tạo cấu hình |
| PUT | `/api/prebuilt-configs/{id}` | Cập nhật cấu hình |
| DELETE | `/api/prebuilt-configs/{id}` | Xóa cấu hình |
| PATCH | `/api/prebuilt-configs/{id}/toggle-active` | Bật/tắt cấu hình |
| PATCH | `/api/prebuilt-configs/{id}/toggle-featured` | Đặt làm nổi bật |

## Checkout Flow

Checkout flow được consolidate thành 4 bước thống nhất trong component `CheckoutView.vue`:

1. **Cart Review** - Xem lại giỏ hàng
2. **Shipping Address** - Nhập thông tin giao hàng
3. **Payment Method** - Chọn phương thức thanh toán (COD)
4. **Confirm & Submit** - Xác nhận và đặt hàng

Server thực hiện validate tổng giá (`server_total` vs `client_total`) và compatibility check trước khi tạo order. Frontend chỉ hiển thị kết quả, không tự tính toán.

## Admin Order Management

Admin order management được consolidate thành component `AdminOrderManagement.vue` với các tính năng:

- **Xem danh sách orders** với filter by status (all, pending, confirmed, shipped, delivered, cancelled)
- **Xem chi tiết order** với modal popup
- **Cập nhật trạng thái** (processing, shipped, delivered, cancelled)
- **Hủy đơn hàng** với confirmation dialog
- Auth check tự động, redirect về login nếu chưa đăng nhập

File: `resources/js/components/AdminOrderManagement.vue`
View: `resources/js/views/AdminOrderView.vue`

## Database Schema

### EAV Model

Hệ thống sử dụng mô hình **Entity-Attribute-Value** để linh hoạt quản lý thuộc tính động của linh kiện PC:

```
products (id, category_id, sku, name, price, stock_quantity...)
    ↓
product_attribute_values (product_id, attribute_id, value)
    ↓
attributes (id, name) <-> category_attribute (category_id, attribute_id)
```

### Seeders

```bash
# Seed categories + products cơ bản
php artisan db:seed --class=CategorySeeder

# Seed đầy đủ linh kiện
php artisan db:seed --class=HardwareProductsSeeder

# Seed data cho PC Builder
php artisan db:seed --class=PCBuilderSampleDataSeeder
```

## AI Integration

### Chat Mode

ChatBox AI hỗ trợ 2 nền tảng:

1. **NVIDIA NIM** (mặc định): LLaMA 3.1 70B Instruct
   - Streaming response với SSE
   - Context: 5 tin nhắn gần nhất
   - Intent analysis: stock_check, category_search, product_search, comparison, recommendation

2. **OpenAI**: GPT-3.5-turbo
   - Cấu hình trong `config/services.php`

### AI PC Builder

- **Input:** Budget + Purpose (Gaming/Work)
- **Output:** Cấu hình PC hoàn chỉnh với compatibility check
- **RAG:** Query sản phẩm từ DB -> gửi làm context cho AI

## Deployment

### Production Checklist

- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Cấu hình database production
- Cấu hình Redis cho cache/queue
- Chạy `php artisan config:cache`
- Chạy `php artisan route:cache`
- Chạy `php artisan view:cache`
- Build frontend: `npm run build`
- Cấu hình queue worker cho background jobs
- Setup HTTPS
- Configure backup database

## Contributing

1. Fork repository
2. Tạo branch mới: `git checkout -b feature/ten-tinh-nang`
3. Commit changes: `git commit -am 'Add some feature'`
4. Push: `git push origin feature/ten-tinh-nang`
5. Tạo Pull Request

## Known Limitations

- Chưa có customer authentication (đăng nhập/đăng ký khách hàng). Hiện tại khách hàng có thể đặt hàng mà không cần đăng nhập.
- Chưa tích hợp payment gateway thực tế (chỉ có UI)
- Chưa có lưu lịch sử chat vào database
- Chưa có email notification
- Chưa có Redis cache

## Demo Accounts

### Admin
- Email: admin@techgear.vn
- Password: admin123
- URL: /login-backend

### Customer
- Email: customer@techgear.vn
- Password: customer123

## License

MIT License

## Contact

**TechGear Team**  
Email: support@techgear.vn  
Project: Hệ thống thương mại điện tử linh kiện PC
