# PHÂN TÍCH HỆ THỐNG (SYSTEM ANALYSIS)

Dự án: Hệ thống thương mại điện tử linh kiện PC & Trợ lý AI

## 1. Công nghệ sử dụng
* **Backend:** PHP / Laravel Framework.
* **Frontend:** Vue 3 (Composition API) / Tailwind CSS / Pinia.
* **Database:** MySQL.
* **Third-party / AI:** Google Gemini API (hoặc OpenAI), DevQuota, thư viện Carbon xử lý thời gian.

## 2. Kiến trúc cốt lõi
* **Kiến trúc RESTful API:** Phân tách hoàn toàn Backend (cung cấp JSON APIs) và Frontend (Render giao diện), giúp hệ thống dễ bảo trì và mở rộng lên Mobile App sau này.
* **Mô hình Dữ liệu EAV (Entity-Attribute-Value):**
    * *Vấn đề:* Linh kiện PC có thuộc tính quá đa dạng. Bảng phẳng tạo vô số cột rác lãng phí bộ nhớ.
    * *Giải pháp:* Tách thuộc tính ra thành bảng riêng. Điều này giúp hệ thống linh hoạt, thêm loại linh kiện mới mà không cần can thiệp cấu trúc DB. Đồng thời giúp AI dễ dàng query dữ liệu theo cặp `Key-Value` để so sánh tính tương thích.
* **Kiến trúc RAG (Retrieval-Augmented Generation) cho AI:**
    * AI không tự tạo ra linh kiện, Backend sẽ query linh kiện có sẵn trong kho (theo mức giá) đóng gói thành "Context" gửi cho AI phân tích trả về cấu hình.

## 3. Mockup (Phác thảo giao diện)
* **Trang Chủ (Homepage):** Modern E-commerce với AI PC Builder, Category Grid, Flash Sale.
* **Chi tiết sản phẩm (Product Detail):** Layout phân bổ tối ưu với Gallery, thông tin tồn kho, và Tabs hiển thị Bảng thông số kỹ thuật (truy xuất từ hệ thống EAV).
* **Thanh toán (One-page Checkout):** Tối ưu tỷ lệ chuyển đổi bằng cách gộp chung màn hình giỏ hàng và điền thông tin (Hỗ trợ thanh toán QR Code động VietQR).