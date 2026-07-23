# TÀI LIỆU PHÂN TÍCH THIẾT KẾ CHỨC NĂNG AI

## 1. AI PC Builder

### 1.1. Nguyên tắc thiết kế

AI không trực tiếp quyết định tính hợp lệ của cấu hình.

Backend phải chịu trách nhiệm:

- Lấy sản phẩm từ database.
- Kiểm tra tồn kho.
- Tính ngân sách.
- Chọn ứng viên.
- Kiểm tra compatibility.
- Tính tổng giá.
- Xác minh output cuối.

AI chỉ chịu trách nhiệm:

- Giải thích lựa chọn.
- Mô tả ưu điểm.
- Nêu hạn chế.
- Đưa ra khuyến nghị nâng cấp.

### 1.2. Nhóm linh kiện trong MVP

- CPU.
- Mainboard.
- RAM.
- GPU.
- PSU.

Không bắt buộc trong PC Builder MVP:

- Case.
- Cooler.
- SSD.
- HDD.
- Fan.
- Monitor.
- Peripheral.

Các nhóm ngoài MVP có thể được bổ sung sau khi năm nhóm chính ổn định.

### 1.3. Input

```json
{
    "budget": 25000000,
    "purpose": "gaming"
}
```

Validation:

- `budget`: số nguyên dương.
- Mức tối thiểu đề xuất: cấu hình do dự án quy định.
- `purpose`: `gaming`, `graphics`, `office`.

### 1.4. Luồng xử lý

```text
Validate input
  → Allocate budget
  → Retrieve candidates
  → Score candidates
  → Build initial configuration
  → Validate compatibility
  → Replace incompatible component
  → Calculate total
  → Call AI for explanation
  → Validate final response
```

### 1.5. Phân bổ ngân sách tham khảo

### Gaming

| Nhóm      | Tỷ lệ tham khảo |
| --------- | --------------: |
| GPU       |             40% |
| CPU       |             22% |
| Mainboard |             13% |
| RAM       |             10% |
| PSU       |             15% |

### Graphics

| Nhóm      | Tỷ lệ tham khảo |
| --------- | --------------: |
| GPU       |             35% |
| CPU       |             28% |
| Mainboard |             12% |
| RAM       |             13% |
| PSU       |             12% |

### Office

| Nhóm      | Tỷ lệ tham khảo |
| --------- | --------------: |
| CPU       |             32% |
| Mainboard |             22% |
| RAM       |             18% |
| GPU       |             10% |
| PSU       |             18% |

Các tỷ lệ này là heuristic cho MVP, không phải chuẩn phần cứng tuyệt đối.

### 1.6. Compatibility rules

### Rule 1 — CPU và Mainboard

```text
CPU.cpu_socket == MAINBOARD.cpu_socket
```

### Rule 2 — RAM và Mainboard

```text
RAM.ram_type == MAINBOARD.ram_type
```

### Rule 3 — PSU

```text
estimated_power =
    CPU.cpu_power_draw
  + GPU.gpu_power_draw
  + base_system_power
```

```text
required_psu = estimated_power * safety_factor
```

Giá trị tham khảo:

```text
base_system_power = 100W
safety_factor = 1.25
```

Điều kiện:

```text
PSU.psu_wattage >= required_psu
```

### 1.7. Cơ chế chấm điểm sản phẩm

Ví dụ:

```text
score =
    budget_fit_score
  + purpose_score
  + stock_score
  + compatibility_score
```

Trong MVP có thể triển khai đơn giản:

- Ưu tiên sản phẩm nằm gần ngân sách phân bổ.
- Loại sản phẩm hết hàng.
- Loại sản phẩm không active.
- Ưu tiên sản phẩm có đầy đủ thuộc tính bắt buộc.
- Không cần machine learning.

### 1.8. Output

```json
{
    "success": true,
    "data": {
        "budget": 25000000,
        "purpose": "gaming",
        "total_price": 24300000,
        "items": [
            {
                "type": "cpu",
                "product_id": 12,
                "sku": "CPU-001",
                "name": "Example CPU",
                "price": 5000000
            }
        ],
        "compatibility": {
            "is_compatible": true,
            "checks": [
                {
                    "rule": "CPU_MAINBOARD_SOCKET",
                    "passed": true,
                    "message": "CPU và mainboard cùng socket."
                }
            ]
        },
        "explanation": "Cấu hình ưu tiên GPU cho nhu cầu gaming."
    }
}
```

### 1.9. Fallback khi AI lỗi

Nếu AI timeout hoặc trả dữ liệu không hợp lệ:

```text
explanation =
"Hệ thống đã tạo cấu hình dựa trên ngân sách, tồn kho và các quy tắc tương thích."
```

PC Builder vẫn phải hoạt động khi AI Provider không khả dụng.
