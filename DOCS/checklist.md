# CHECKLIST ĐÁNH GIÁ CUỐI KỲ

## Kiến trúc

- [x] Laravel và Vue giao tiếp qua API rõ ràng.
- [x] Controller mỏng.
- [x] Business logic nằm trong service.
- [x] Compatibility tách khỏi AI.
- [x] Có validation và authorization.

## Database

- [x] ERD khớp migration.
- [x] Có unique và foreign key cần thiết.
- [x] EAV hỗ trợ text và number.
- [x] Snapshot đơn hàng đầy đủ.
- [x] Tồn kho không âm.

## Chức năng

- [x] Catalog hoạt động.
- [x] Filter hoạt động.
- [x] Cart lưu được.
- [x] Checkout COD hoạt động.
- [x] Admin quản lý order.
- [x] PC Builder hoạt động.

## Testing

- [x] Test order transaction.
- [x] Test insufficient stock.
- [x] Test authorization.
- [x] Test compatibility.
- [x] Test AI fallback.

## Tài liệu

- [x] README.
- [x] ERD.
- [x] API list.
- [x] Data dictionary.
- [x] Acceptance criteria.
- [x] Known limitations.

---

## Backlog sau MVP

Sau khi MVP đạt yêu cầu mới xem xét:

1. Thêm SSD, case và cooler vào PC Builder.
2. Kiểm tra kích thước GPU và case.
3. Kiểm tra cooler socket.
4. VietQR.
5. Payment webhook.
6. Product images.
7. Datasheet.
8. Wishlist.
9. Review.
10. Promotion.
11. Full-text search.
12. Vector retrieval.
13. AI conversation history.
14. Admin analytics.
15. Inventory movement log.

---

## Tiêu chuẩn hoàn thành dự án

Dự án được xem là hoàn thành khi:

- [x] Luồng catalog, cart, order và PC Builder chạy end-to-end.
- [x] Không có lỗi nghiêm trọng làm sai tồn kho hoặc tổng tiền.
- [x] AI không phải điểm phụ thuộc bắt buộc của PC Builder.
- [x] ERD, migration và tài liệu nhất quán.
- [x] Có test cho nghiệp vụ rủi ro cao.
- [x] Một lớp trình viên khác có thể clone, cấu hình và chạy dự án từ README.
- [x] Toàn bộ chức năng demo sử dụng API và database thật, không dùng dữ liệu hard-code.