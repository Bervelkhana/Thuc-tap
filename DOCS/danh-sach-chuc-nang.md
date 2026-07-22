# DANH SÁCH CHỨC NĂNG (FEATURE LIST)

Hệ thống được chia thành 3 phân hệ chính:

## A. Phân hệ Khách hàng (Customer UI - Vue.js)
* **Tìm kiếm & Lọc động (Dynamic Filtering):** Lọc linh kiện theo nhiều tiêu chí (Danh mục, Giá cả, Thông số kỹ thuật riêng biệt của từng linh kiện).
* **Quản lý Giỏ hàng (Cart Management):** Sử dụng Pinia để lưu trữ trạng thái giỏ hàng theo thời gian thực (Real-time).
* **Thanh toán Một trang (One-page Checkout):** Tích hợp điền thông tin và chọn phương thức thanh toán (COD hoặc chuyển khoản VietQR) trên cùng một trang để tăng tỷ lệ chuyển đổi.
* **Chi tiết sản phẩm:** Hiển thị thư viện ảnh, thông số kỹ thuật chi tiết và tải tài liệu Datasheet (PDF).

## B. Phân hệ AI Trợ lý & Tư vấn (Điểm nhấn dự án)
* **AI PC Builder:** Chatbot thông minh giúp người dùng tự động xây dựng cấu hình máy tính dựa trên: Ngân sách (Budget) và Nhu cầu (Gaming, Đồ họa, Văn phòng).
* **Kiểm tra tương thích phần cứng:** Trợ lý AI có khả năng đọc thông số EAV từ Database để đảm bảo tính tương thích (VD: CPU LGA1700 đi với Mainboard Socket 1700, Nguồn đủ công suất kéo VGA).

## C. Phân hệ Quản trị (Admin Dashboard - Laravel)
* **Quản lý Sản phẩm & Thuộc tính (CRUD):** Áp dụng chuẩn mô hình EAV để dễ dàng thêm/bớt thông số kỹ thuật mới mà không cần sửa cấu trúc Database.
* **Quản lý Đơn hàng:** Cập nhật trạng thái đơn hàng và trừ số lượng tồn kho qua `DB::transaction`.
* **Quản lý Quota API:** Sử dụng DevQuota để giới hạn và theo dõi lưu lượng gọi API AI, ngăn chặn rủi ro chi phí.