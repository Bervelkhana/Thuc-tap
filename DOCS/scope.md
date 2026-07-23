# TÀI LIỆU PHÂN TÍCH PHẠM VI HỆ THỐNG (MVP SCOPE)

## 1. Thông tin chung

**Tên dự án:** Hệ thống thương mại điện tử linh kiện PC và trợ lý AI PC Builder  
**Thời lượng thực tập:** 6 tuần
**Backend:** Laravel  
**Frontend:** Vue 3, Composition API, Pinia, Tailwind CSS  
**Database:** MySQL  
**AI Provider:** Google Gemini API
**Kiến trúc giao tiếp:** RESTful API

---

## 2. Mục tiêu MVP

MVP phải hoàn thành một luồng nghiệp vụ xuyên suốt từ quản lý dữ liệu sản phẩm đến tư vấn cấu hình và tạo đơn hàng.

Hệ thống cần đáp ứng được các mục tiêu sau:

1. Admin có thể quản lý danh mục, sản phẩm, thuộc tính động và tồn kho.
2. Người dùng có thể xem, tìm kiếm và lọc sản phẩm.
3. Người dùng có thể thêm sản phẩm vào giỏ hàng và tạo đơn hàng COD.
4. Admin có thể xem và cập nhật trạng thái đơn hàng.
5. Hệ thống có thể đề xuất một cấu hình PC từ sản phẩm đang có trong kho.
6. Hệ thống có thể kiểm tra ba nhóm tương thích cốt lõi:
    - Socket CPU và mainboard.
    - Loại RAM và loại RAM được mainboard hỗ trợ.
    - Công suất PSU so với công suất ước tính của cấu hình.
7. AI chỉ thực hiện vai trò giải thích và trình bày kết quả. Quyết định lựa chọn sản phẩm và kiểm tra tương thích phải do backend kiểm soát.

---

## 3. Phạm vi MVP

### 3.1. Trong phạm vi

#### Phân hệ khách hàng

- Xem danh sách sản phẩm.
- Xem chi tiết sản phẩm.
- Lọc sản phẩm theo:
    - Danh mục.
    - Khoảng giá.
    - Một số thuộc tính được đánh dấu là có thể lọc.
- Thêm, sửa số lượng và xóa sản phẩm trong giỏ hàng.
- Lưu giỏ hàng bằng Pinia và `localStorage`.
- Checkout COD.
- Xem kết quả tạo đơn hàng.
- Nhập ngân sách và nhu cầu sử dụng để nhận đề xuất cấu hình PC.
- Xem cảnh báo tương thích và phần giải thích của AI.

#### Phân hệ quản trị

- Đăng nhập admin.
- CRUD danh mục.
- CRUD thuộc tính.
- Gán thuộc tính cho danh mục.
- CRUD sản phẩm.
- Gán giá trị thuộc tính cho sản phẩm.
- Quản lý số lượng tồn kho.
- Xem danh sách và chi tiết đơn hàng.
- Cập nhật trạng thái đơn hàng.
- Hủy đơn và hoàn tồn kho khi thỏa điều kiện nghiệp vụ.

#### Phân hệ AI PC Builder

- Nhận đầu vào:
    - Ngân sách.
    - Nhu cầu: gaming, đồ họa hoặc văn phòng.
- Truy vấn sản phẩm đang hoạt động và còn hàng.
- Phân bổ ngân sách theo nhóm linh kiện.
- Chọn ứng viên theo rule và điểm số.
- Kiểm tra tương thích.
- Trả về danh sách sản phẩm hợp lệ.
- Gọi AI để sinh phần giải thích ngắn gọn.
- Kiểm tra lại toàn bộ ID sản phẩm, tổng giá và tồn kho trước khi trả response.

### 3.2. Ngoài phạm vi

Các chức năng sau không thuộc cam kết bắt buộc của MVP:

- Thanh toán online có xác nhận tự động.
- Webhook ngân hàng.
- Flash Sale.
- Voucher và khuyến mãi.
- Wishlist.
- Đánh giá sản phẩm.
- Chatbot hội thoại nhiều vòng phức tạp.
- Vector database.
- RAG dùng embedding.
- Theo dõi quota AI bằng dashboard riêng.
- Đồng bộ giỏ hàng đa thiết bị theo thời gian thực.
- WebSocket.
- Compatibility đầy đủ cho BIOS, kích thước case, chiều cao tản nhiệt hoặc số cổng mở rộng.
- Mobile application.
- Phân quyền nhiều cấp ngoài `admin` và `customer`.
