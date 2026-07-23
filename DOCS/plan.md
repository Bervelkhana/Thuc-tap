# TÀI LIỆU PHÂN TÍCH VÀ THIẾT KẾ HỆ THỐNG MVP

## Tuần 3 — Catalog, EAV, Filter và Cart

### Mục tiêu

Hoàn thiện luồng quản lý và hiển thị sản phẩm, đồng thời có giỏ hàng hoạt động ổn định.

### Công việc bắt buộc

#### Backend

- Hoàn thiện migration và model cho:
    - categories
    - products
    - attributes
    - category_attributes
    - product_attribute_values
- CRUD category.
- CRUD attribute.
- Gán attribute vào category.
- CRUD product.
- Gán giá trị EAV cho product.
- API product list.
- API product detail.
- Filter category, price và thuộc tính.
- Validation theo `data_type`.

#### Frontend

- Admin UI:
    - Category form.
    - Attribute form.
    - Product form.
- Customer UI:
    - Product list.
    - Product detail.
    - Filter panel.
- Cart:
    - Pinia store.
    - `localStorage`.
    - Thêm, sửa số lượng, xóa.

### Deliverables

1. Migration chạy thành công từ database rỗng.
2. Seed tối thiểu:
    - 5 category.
    - 10 attribute.
    - 20 product.
3. Admin tạo được một product có thuộc tính EAV.
4. Customer lọc được ít nhất:
    - category.
    - price.
    - một thuộc tính text.
    - một thuộc tính number.
5. Cart giữ dữ liệu sau khi reload trang.
6. Postman collection hoặc API documentation cho catalog.
7. Video demo hoặc buổi demo trực tiếp luồng admin tạo sản phẩm đến customer thêm vào giỏ.

### Tiêu chí đánh giá tuần 3

| Tiêu chí                     | Tỷ trọng |
| ---------------------------- | -------: |
| Migration và constraint đúng |      20% |
| CRUD catalog hoạt động       |      25% |
| EAV validation đúng          |      20% |
| Filter hoạt động             |      20% |
| Cart hoạt động ổn định       |      15% |

### Điều kiện không đạt

- Chỉ có giao diện nhưng API chưa hoàn thiện.
- EAV lưu được nhưng không validate kiểu dữ liệu.
- Filter thực hiện toàn bộ ở frontend.
- SKU không unique.
- Cart mất sau khi reload.

---

## Tuần 4 — Checkout, Order và Inventory

### Mục tiêu

Hoàn thành luồng mua hàng COD từ giỏ hàng đến quản trị đơn.

### Công việc bắt buộc

#### Backend

- Migration `orders` và `order_items`.
- API tạo order.
- Backend tính lại giá.
- `DB::transaction()`.
- `lockForUpdate()`.
- Kiểm tra tồn kho.
- Trừ tồn kho.
- API customer xem order.
- API admin xem order.
- State transition validation.
- Hủy đơn và hoàn kho.

#### Frontend

- Checkout page.
- Form thông tin nhận hàng.
- Trang order success.
- Customer order history.
- Admin order list.
- Admin order detail.
- Admin cập nhật trạng thái.

#### Testing

- Test tạo order thành công.
- Test giỏ hàng rỗng.
- Test không đủ tồn.
- Test rollback.
- Test admin authorization.
- Test hoàn tồn kho.

### Deliverables

1. Luồng từ cart đến order hoạt động end-to-end.
2. Giá do backend tính.
3. Không thể tạo tồn kho âm.
4. Đơn lưu snapshot customer và product.
5. Admin cập nhật được trạng thái hợp lệ.
6. Hủy đơn hoàn tồn đúng.
7. Có ít nhất 5 automated tests cho order.
8. API documentation cho orders.

### Tiêu chí đánh giá tuần 4

| Tiêu chí                   | Tỷ trọng |
| -------------------------- | -------: |
| Transaction và rollback    |      25% |
| Kiểm soát tồn kho          |      25% |
| Snapshot dữ liệu           |      15% |
| State transition           |      15% |
| UI checkout và admin order |      10% |
| Automated tests            |      10% |

### Điều kiện không đạt

- Frontend gửi `total_amount` và backend tin trực tiếp.
- Tạo order và trừ kho không cùng transaction.
- Hủy đơn có thể hoàn kho nhiều lần.
- Order item không lưu giá snapshot.
- Customer xem được order của người khác.

---

## Tuần 5 — PC Builder và Compatibility Engine

### Mục tiêu

Hoàn thành PC Builder cho năm nhóm linh kiện với ba rule compatibility bắt buộc.

### Công việc bắt buộc

#### Dữ liệu

Chuẩn hóa attribute code:

- `cpu_socket`
- `ram_type`
- `cpu_power_draw`
- `gpu_power_draw`
- `psu_wattage`

Seed đủ sản phẩm cho:

- CPU.
- Mainboard.
- RAM.
- GPU.
- PSU.

#### Backend

- `BudgetAllocationService`.
- `ProductScoringService`.
- `CompatibilityService`.
- `PcBuildService`.
- API recommend.
- API check compatibility.
- Fallback khi không đủ ngân sách.
- Fallback khi không có sản phẩm.
- Unit test cho ba rule.

#### Frontend

- Form nhập budget.
- Chọn purpose.
- Hiển thị cấu hình.
- Hiển thị tổng tiền.
- Hiển thị từng compatibility check.
- Hiển thị cảnh báo.

### Deliverables

1. PC Builder trả đúng năm nhóm linh kiện.
2. Không trả sản phẩm hết hàng.
3. Tổng tiền do backend tính.
4. Socket CPU-mainboard đúng.
5. RAM-mainboard đúng.
6. PSU đủ công suất theo rule.
7. Có ít nhất 6 unit test compatibility.
8. Có response mẫu cho ba nhu cầu.
9. Có demo trường hợp hợp lệ và không hợp lệ.

### Tiêu chí đánh giá tuần 5

| Tiêu chí            | Tỷ trọng |
| ------------------- | -------: |
| Tách service đúng   |      15% |
| Budget allocation   |      15% |
| Candidate selection |      15% |
| Compatibility rules |      30% |
| Error và fallback   |      10% |
| Unit tests          |      15% |

### Điều kiện không đạt

- Đưa toàn bộ sản phẩm vào prompt rồi để AI tự chọn.
- Rule so sánh theo attribute name thay vì code.
- Không kiểm tra tồn kho.
- AI quyết định tổng giá.
- Không có test cho compatibility.

---

## Tuần 6 — AI Explanation, Hardening và Hoàn thiện tài liệu

### Mục tiêu

Tích hợp AI ở mức an toàn, hoàn thiện kiểm thử, UX và tài liệu bàn giao.

### Công việc bắt buộc

#### AI

- `AiAdvisorService`.
- Prompt chỉ chứa cấu hình đã được backend xác nhận.
- Timeout.
- Rate limit.
- Fallback explanation.
- Validate output.
- Log lỗi và thời gian gọi.

#### Hardening

- Rà soát authorization.
- Rà soát validation.
- Xử lý empty state.
- Xử lý loading state.
- Xử lý API error.
- Tối ưu query N+1.
- Bổ sung index.
- Kiểm tra responsive cơ bản.

#### Testing

- Feature test PC Builder.
- Test AI fallback.
- Regression test catalog.
- Regression test order.
- Test quyền customer và admin.

#### Documentation

- README.
- Setup guide.
- Environment variables.
- ERD cuối.
- API list cuối.
- Seed account.
- Demo scenarios.
- Known limitations.

### Deliverables

1. AI explanation hoạt động.
2. Hệ thống vẫn trả cấu hình khi AI lỗi.
3. Tất cả migration và seed chạy trên máy mới.
4. Test suite chạy thành công.
5. README đủ để người khác cài đặt.
6. ERD khớp migration.
7. API documentation khớp code.
8. Video demo hoặc demo trực tiếp toàn bộ hệ thống.
9. Danh sách hạn chế và backlog sau MVP.

### Tiêu chí đánh giá tuần 6

| Tiêu chí                      | Tỷ trọng |
| ----------------------------- | -------: |
| AI integration an toàn        |      15% |
| Fallback và rate limit        |      10% |
| Security và authorization     |      15% |
| Test coverage nghiệp vụ chính |      20% |
| UX và error handling          |      15% |
| Documentation                 |      15% |
| Demo end-to-end               |      10% |

### Điều kiện không đạt

- AI lỗi làm toàn bộ PC Builder lỗi.
- Secret key nằm trong source code.
- ERD không khớp migration.
- Không thể setup dự án trên môi trường mới.
- Không có test cho order hoặc compatibility.
- Chỉ demo bằng dữ liệu hard-code ở frontend.

---
