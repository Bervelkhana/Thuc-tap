# CHECKLIST ĐÁNH GIÁ CUỐI KỲ

## Kiến trúc

- [ ] Laravel và Vue giao tiếp qua API rõ ràng.
- [ ] Controller mỏng.
- [ ] Business logic nằm trong service.
- [ ] Compatibility tách khỏi AI.
- [ ] Có validation và authorization.

## Database

- [ ] ERD khớp migration.
- [ ] Có unique và foreign key cần thiết.
- [ ] EAV hỗ trợ text và number.
- [ ] Snapshot đơn hàng đầy đủ.
- [ ] Tồn kho không âm.

## Chức năng

- [ ] Catalog hoạt động.
- [ ] Filter hoạt động.
- [ ] Cart lưu được.
- [ ] Checkout COD hoạt động.
- [ ] Admin quản lý order.
- [ ] PC Builder hoạt động.

## Testing

- [ ] Test order transaction.
- [ ] Test insufficient stock.
- [ ] Test authorization.
- [ ] Test compatibility.
- [ ] Test AI fallback.

## Tài liệu

- [ ] README.
- [ ] ERD.
- [ ] API list.
- [ ] Data dictionary.
- [ ] Acceptance criteria.
- [ ] Known limitations.

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

- Luồng catalog, cart, order và PC Builder chạy end-to-end.
- Không có lỗi nghiêm trọng làm sai tồn kho hoặc tổng tiền.
- AI không phải điểm phụ thuộc bắt buộc của PC Builder.
- ERD, migration và tài liệu nhất quán.
- Có test cho nghiệp vụ rủi ro cao.
- Một lập trình viên khác có thể clone, cấu hình và chạy dự án từ README.
- Toàn bộ chức năng demo sử dụng API và database thật, không dùng dữ liệu hard-code để thay thế nghiệp vụ.
