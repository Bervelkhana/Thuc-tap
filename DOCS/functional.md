# TÀI LIỆU PHÂN TÍCH CHỨC NĂNG, USE CASE, ACCEPTANCE CRITERIA

## 1. Chức năng hệ thống

### 1.1. Phân hệ khách hàng

### CF-01 — Xem danh sách sản phẩm

- Phân trang.
- Chỉ hiển thị sản phẩm đang hoạt động.
- Hiển thị tên, SKU, giá, tồn kho và ảnh đại diện.

### CF-02 — Xem chi tiết sản phẩm

- Hiển thị thông tin cơ bản.
- Hiển thị thuộc tính động theo danh mục.
- Hiển thị trạng thái còn hàng hoặc hết hàng.

### CF-03 — Tìm kiếm và lọc sản phẩm

- Tìm theo tên hoặc SKU.
- Lọc theo danh mục.
- Lọc theo khoảng giá.
- Lọc theo thuộc tính có `is_filterable = true`.

### CF-04 — Quản lý giỏ hàng

- Thêm sản phẩm.
- Tăng hoặc giảm số lượng.
- Xóa sản phẩm.
- Lưu bằng Pinia và `localStorage`.
- Không cho số lượng nhỏ hơn 1.

### CF-05 — Checkout COD

- Nhập họ tên.
- Nhập số điện thoại.
- Nhập địa chỉ giao hàng.
- Tạo đơn hàng COD.
- Backend kiểm tra lại giá và tồn kho.

### CF-06 — AI PC Builder

- Nhập ngân sách.
- Chọn nhu cầu.
- Nhận cấu hình đề xuất.
- Xem tổng giá.
- Xem cảnh báo.
- Xem giải thích từ AI.

### 1.2. Phân hệ quản trị

#### AF-01 — Quản lý danh mục

- Thêm, sửa, xóa mềm.
- Hỗ trợ tối đa hai cấp danh mục.
- Không cho xóa danh mục đang chứa sản phẩm nếu chưa xử lý sản phẩm.

#### AF-02 — Quản lý thuộc tính

- Khai báo mã thuộc tính.
- Khai báo tên.
- Khai báo kiểu dữ liệu.
- Khai báo đơn vị.
- Đánh dấu có thể lọc.
- Gán thuộc tính cho danh mục.

#### AF-03 — Quản lý sản phẩm

- Thêm, sửa, xem, xóa mềm.
- SKU duy nhất.
- Giá không âm.
- Tồn kho không âm.
- Gán thuộc tính theo danh mục.

#### AF-04 — Quản lý đơn hàng

- Xem danh sách.
- Xem chi tiết.
- Lọc theo trạng thái.
- Cập nhật trạng thái hợp lệ.
- Hủy đơn và hoàn tồn kho khi cần.

### 1.3. Trạng thái đơn hàng

```text
pending
  → confirmed
  → shipping
  → completed
```

Luồng hủy:

```text
pending → cancelled
confirmed → cancelled
```

Không cho phép:

- `completed → cancelled`
- `shipping → pending`
- `cancelled → confirmed`

---

## 2. Use Case và Actor

### 2.1. Actor

| Actor            | Mô tả                                                                 |
| ---------------- | --------------------------------------------------------------------- |
| Guest            | Người dùng chưa đăng nhập, được xem sản phẩm và sử dụng PC Builder    |
| Customer         | Người dùng đã đăng nhập, có thể tạo và xem đơn hàng của mình          |
| Admin            | Quản trị danh mục, thuộc tính, sản phẩm, tồn kho và đơn hàng          |
| AI Provider      | Dịch vụ bên ngoài dùng để sinh phần giải thích                        |
| System Scheduler | Thành phần tùy chọn dùng cho tác vụ định kỳ, không bắt buộc trong MVP |

### 2.2. Danh sách use case

| ID    | Use case                      | Actor chính     | Ưu tiên |
| ----- | ----------------------------- | --------------- | ------- |
| UC-01 | Xem danh sách sản phẩm        | Guest, Customer | Must    |
| UC-02 | Xem chi tiết sản phẩm         | Guest, Customer | Must    |
| UC-03 | Tìm kiếm và lọc sản phẩm      | Guest, Customer | Must    |
| UC-04 | Quản lý giỏ hàng              | Guest, Customer | Must    |
| UC-05 | Tạo đơn hàng COD              | Customer        | Must    |
| UC-06 | Xem lịch sử đơn hàng          | Customer        | Should  |
| UC-07 | Tạo đề xuất cấu hình PC       | Guest, Customer | Must    |
| UC-08 | Kiểm tra tương thích cấu hình | Guest, Customer | Must    |
| UC-09 | Đăng nhập admin               | Admin           | Must    |
| UC-10 | Quản lý danh mục              | Admin           | Must    |
| UC-11 | Quản lý thuộc tính            | Admin           | Must    |
| UC-12 | Quản lý sản phẩm              | Admin           | Must    |
| UC-13 | Quản lý đơn hàng              | Admin           | Must    |
| UC-14 | Gọi AI giải thích cấu hình    | System          | Should  |

### 2.3. Đặc tả use case trọng yếu

#### UC-05 — Tạo đơn hàng COD

**Actor:** Customer  
**Tiền điều kiện:**

- Giỏ hàng có ít nhất một sản phẩm.
- Customer đã đăng nhập.
- Sản phẩm còn hoạt động.

**Luồng chính:**

1. Customer mở trang checkout.
2. Customer nhập thông tin nhận hàng.
3. Frontend gửi danh sách sản phẩm và số lượng.
4. Backend validate dữ liệu.
5. Backend khóa các dòng sản phẩm.
6. Backend kiểm tra tồn kho.
7. Backend tính tổng tiền.
8. Backend tạo đơn và chi tiết đơn.
9. Backend trừ tồn kho.
10. Backend trả mã đơn hàng.

**Luồng thay thế:**

- Nếu sản phẩm không tồn tại: trả `404`.
- Nếu không đủ tồn kho: trả `409`.
- Nếu validation thất bại: trả `422`.
- Nếu transaction lỗi: rollback và trả `500`.

**Hậu điều kiện:**

- Đơn hàng được lưu.
- Tồn kho giảm đúng.
- Giá trong `order_items` là giá tại thời điểm mua.

#### UC-07 — Tạo đề xuất cấu hình PC

**Actor:** Guest, Customer  
**Tiền điều kiện:**

- Ngân sách hợp lệ.
- Nhu cầu thuộc danh sách hỗ trợ.
- Database có sản phẩm đang hoạt động và còn hàng.

**Luồng chính:**

1. Người dùng nhập ngân sách và nhu cầu.
2. Backend validate input.
3. Backend phân bổ ngân sách.
4. Backend lấy ứng viên cho từng nhóm linh kiện.
5. Backend chấm điểm và chọn sản phẩm.
6. Backend chạy compatibility rules.
7. Backend điều chỉnh cấu hình khi có xung đột.
8. Backend tính tổng giá.
9. Backend gửi cấu hình hợp lệ cho AI.
10. AI trả phần giải thích.
11. Backend kiểm tra output và trả response.

**Hậu điều kiện:**

- Không có sản phẩm ngoài database.
- Không có ID không tồn tại.
- Tổng giá được tính bởi backend.
- Cấu hình thỏa ba rule compatibility bắt buộc.

---

## 3. Acceptance Criteria

### 3.1. Catalog và EAV

#### AC-CATALOG-01

**Given** admin tạo sản phẩm mới  
**When** SKU đã tồn tại  
**Then** hệ thống từ chối và trả lỗi validation.

#### AC-CATALOG-02

**Given** sản phẩm thuộc danh mục CPU  
**When** admin nhập thuộc tính  
**Then** chỉ những thuộc tính đã gán cho danh mục CPU được phép lưu.

#### AC-CATALOG-03

**Given** thuộc tính có kiểu `number`  
**When** admin nhập chuỗi không phải số  
**Then** hệ thống không lưu dữ liệu.

#### AC-CATALOG-04

**Given** người dùng lọc theo socket  
**When** chọn `LGA1700`  
**Then** chỉ trả sản phẩm có giá trị socket tương ứng.

### 3.2. Giỏ hàng

#### AC-CART-01

**Given** sản phẩm còn hàng  
**When** người dùng thêm vào giỏ  
**Then** sản phẩm xuất hiện trong Pinia store và `localStorage`.

#### AC-CART-02

**Given** số lượng sản phẩm trong giỏ là 1  
**When** người dùng giảm số lượng  
**Then** số lượng không được nhỏ hơn 1.

#### AC-CART-03

**Given** giá sản phẩm thay đổi sau khi thêm vào giỏ  
**When** checkout  
**Then** backend sử dụng giá hiện tại trong database.

### 3.3. Đơn hàng

#### AC-ORDER-01

**Given** giỏ hàng rỗng  
**When** gọi API tạo đơn  
**Then** trả lỗi `422`.

#### AC-ORDER-02

**Given** sản phẩm chỉ còn 2  
**When** người dùng đặt số lượng 3  
**Then** trả `409 INSUFFICIENT_STOCK`.

#### AC-ORDER-03

**Given** tạo đơn thành công  
**Then** `orders`, `order_items` và tồn kho phải được cập nhật trong cùng transaction.

#### AC-ORDER-04

**Given** một bước tạo `order_items` thất bại  
**Then** không được tạo order và không được trừ tồn kho.

#### AC-ORDER-05

**Given** đơn đang ở trạng thái `completed`  
**When** admin yêu cầu hủy  
**Then** hệ thống từ chối.

#### AC-ORDER-06

**Given** đơn ở trạng thái `confirmed`  
**When** admin hủy đơn  
**Then** tồn kho được hoàn lại đúng một lần.

### 3.4. AI PC Builder

#### AC-PCB-01

**Given** ngân sách nhỏ hơn mức tối thiểu cấu hình  
**When** gọi API đề xuất  
**Then** trả cảnh báo không đủ ngân sách và không tạo cấu hình giả.

#### AC-PCB-02

**Given** CPU có socket `LGA1700`  
**Then** mainboard được chọn phải hỗ trợ `LGA1700`.

#### AC-PCB-03

**Given** mainboard hỗ trợ `DDR5`  
**Then** RAM được chọn phải có loại `DDR5`.

#### AC-PCB-04

**Given** công suất ước tính là 500W  
**Then** PSU phải đạt ít nhất công suất yêu cầu sau khi áp dụng hệ số an toàn.

#### AC-PCB-05

**Given** AI trả một `product_id` không tồn tại  
**Then** backend không được trả ID đó cho frontend.

#### AC-PCB-06

**Given** AI Provider không phản hồi  
**Then** hệ thống vẫn trả cấu hình do backend tạo và dùng phần giải thích fallback.

---

## 4. Non-functional Requirements

### 4.1. Bảo mật

- Password phải được hash bằng cơ chế mặc định của Laravel.
- Route admin phải có middleware auth và role.
- Không nhận `price`, `total_amount` hoặc `role` từ client nếu không cần thiết.
- Validate tất cả input.
- Chống mass assignment bằng `$fillable` hoặc DTO.
- Không trả stack trace trong production.
- API AI key chỉ lưu trong environment variable.
- Rate limit endpoint PC Builder.

### 4.2. Hiệu năng

- Product list phải phân trang.
- Không query N+1 khi lấy category và attributes.
- Các cột sau cần index:
    - `products.category_id`
    - `products.sku`
    - `products.is_active`
    - `orders.user_id`
    - `orders.status`
    - `product_attribute_values.product_id`
    - `product_attribute_values.attribute_id`
- Mục tiêu response với dataset demo:
    - Catalog thông thường: dưới 1 giây.
    - PC Builder không tính thời gian AI: dưới 2 giây.
    - PC Builder có AI: dưới 10 giây hoặc fallback.

### 4.3. Tin cậy dữ liệu

- Tạo đơn phải dùng transaction.
- Cập nhật tồn kho phải chống số âm.
- Hủy đơn chỉ hoàn kho một lần.
- Giá đơn hàng phải lưu snapshot.
- Không xóa cứng dữ liệu liên quan đơn hàng.

### 4.4. Khả năng kiểm thử

Tối thiểu cần:

- Unit test cho compatibility rules.
- Feature test cho tạo đơn.
- Feature test cho insufficient stock.
- Feature test cho authorization admin.
- Feature test cho PC Builder fallback.

### 4.5. Khả năng bảo trì

- Controller không chứa business logic phức tạp.
- Rule compatibility dùng `attribute.code`, không dùng tên hiển thị.
- Có README hướng dẫn cài đặt.
- Có seed data.
- Migration phải chạy được từ database rỗng.
- API có tài liệu request và response mẫu.

### 4.6. UX

- Có loading state.
- Có empty state.
- Có error message dễ hiểu.
- Form hiển thị lỗi validation theo field.
- Giao diện hoạt động ở desktop và mobile cơ bản.
- Không yêu cầu animation phức tạp.

### 4.7. AI reliability

- Có timeout khi gọi AI.
- Có fallback.
- Không tin trực tiếp ID sản phẩm do AI trả.
- Không gửi toàn bộ database vào prompt.
- Log thời gian gọi và lỗi AI.
- Không để AI quyết định giá hoặc tồn kho.
