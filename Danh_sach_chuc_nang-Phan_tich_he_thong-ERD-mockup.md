<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trình chỉnh sửa và tải xuống tệp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; margin: 0; height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .editor-container {
            display: flex;
            flex: 1;
            overflow: hidden;
            background: #fff;
            margin: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }

        .pane {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .pane.hidden {
            display: none;
        }

        .pane-header {
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #334155;
            flex-shrink: 0;
        }

        .pane-content {
            flex: 1;
            overflow: auto;
            position: relative;
        }

        textarea.editor {
            width: 100%;
            height: 100%;
            padding: 1rem;
            border: none;
            resize: none;
            outline: none;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 14px;
            line-height: 1.5;
            color: #1e293b;
            background: #ffffff;
            tab-size: 4;
            box-sizing: border-box;
        }

        .preview {
            padding: 1.5rem;
            color: #334155;
            line-height: 1.6;
        }

        /* Markdown styling inside preview */
        .preview h1, .preview h2, .preview h3, .preview h4 { color: #0f172a; margin-top: 1.5em; margin-bottom: 0.5em; font-weight: bold; }
        .preview h1 { font-size: 2em; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.3em; }
        .preview h2 { font-size: 1.5em; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.3em; }
        .preview p { margin-bottom: 1em; }
        .preview ul, .preview ol { margin-bottom: 1em; padding-left: 2em; }
        .preview li { margin-bottom: 0.25em; }
        .preview code { background: #f1f5f9; padding: 0.2em 0.4em; border-radius: 3px; font-family: monospace; font-size: 0.9em; color: #ef4444; }
        .preview pre { background: #1e293b; color: #f8fafc; padding: 1em; border-radius: 6px; overflow-x: auto; margin-bottom: 1em; }
        .preview pre code { background: transparent; color: inherit; padding: 0; }
        .preview blockquote { border-left: 4px solid #cbd5e1; padding-left: 1em; color: #64748b; margin-left: 0; margin-bottom: 1em; }
        .preview img { max-width: 100%; height: auto; border-radius: 4px; border: 1px solid #e2e8f0; margin: 1em 0; }

        /* Iframe preview for HTML */
        iframe.html-preview {
            width: 100%;
            height: 100%;
            border: none;
            background: #fff;
            display: block; /* Important to remove bottom gap */
        }

        .resizer {
            width: 4px;
            background: #e2e8f0;
            cursor: col-resize;
            transition: background 0.2s;
            flex-shrink: 0;
        }

        .resizer:hover, .resizer.dragging {
            background: #3b82f6;
        }

        /* Utility classes */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2563eb;
        }

        .btn-secondary {
            background-color: white;
            color: #475569;
            border-color: #cbd5e1;
        }

        .btn-secondary:hover {
            background-color: #f8fafc;
            color: #0f172a;
        }

        .btn-icon {
            padding: 0.4rem;
            border-radius: 0.375rem;
            color: #64748b;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-icon:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 50;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            background: #1e293b;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(120%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast-success { border-left: 4px solid #10b981; }
        .toast-error { border-left: 4px solid #ef4444; }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s;
            backdrop-filter: blur(2px);
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: white;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: translateY(20px);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }

        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }
    </style>

</head>
<body>

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center shadow-sm shrink-0">
        <div class="flex items-center gap-3">
            <div class="bg-blue-600 text-white p-2 rounded-lg">
                <i class="fas fa-file-code text-lg"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold text-gray-800 leading-tight">File Manager & Editor</h1>
                <p class="text-xs text-gray-500" id="file-count-label">Đang tải...</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <select id="file-selector" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2 outline-none w-64">
                <!-- Options populated by JS -->
            </select>

            <div class="h-6 w-px bg-gray-300 mx-1"></div>

            <button id="btn-download" class="btn btn-primary" title="Tải xuống tệp hiện tại">
                <i class="fas fa-download"></i> Tải tệp này
            </button>
            <button id="btn-download-all" class="btn btn-secondary" title="Tải xuống tất cả tệp dưới dạng ZIP">
                <i class="fas fa-file-archive"></i> Tải tất cả (ZIP)
            </button>
        </div>
    </header>

    <!-- Main Workspace -->
    <div class="editor-container relative">

        <!-- Code Editor Pane -->
        <div class="pane" id="editor-pane" style="flex: 1 1 50%;">
            <div class="pane-header">
                <div class="flex items-center gap-2">
                    <i class="fas fa-code text-blue-500"></i>
                    <span id="editor-title">Mã nguồn</span>
                </div>
                <div class="flex gap-1">
                    <button id="btn-copy" class="btn-icon" title="Sao chép mã">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button id="btn-toggle-preview" class="btn-icon lg:hidden" title="Chuyển sang xem trước">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="pane-content">
                <textarea id="editor" class="editor" spellcheck="false" placeholder="Đang tải nội dung..."></textarea>
            </div>
        </div>

        <!-- Resizer -->
        <div class="resizer hidden lg:block" id="resizer"></div>

        <!-- Preview Pane -->
        <div class="pane" id="preview-pane" style="flex: 1 1 50%;">
            <div class="pane-header">
                <div class="flex items-center gap-2">
                    <i class="fas fa-eye text-green-500"></i>
                    <span>Xem trước</span>
                    <span id="preview-badge" class="ml-2 px-2 py-0.5 text-xs bg-gray-200 text-gray-700 rounded-full"></span>
                </div>
                <div class="flex gap-1">
                    <button id="btn-refresh-preview" class="btn-icon" title="Làm mới xem trước">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button id="btn-toggle-editor" class="btn-icon lg:hidden" title="Chuyển sang chỉnh sửa">
                        <i class="fas fa-code"></i>
                    </button>
                </div>
            </div>
            <div class="pane-content bg-white" id="preview-container">
                <!-- Preview content generated by JS -->
            </div>
        </div>

    </div>

    <!-- Status Bar -->
    <footer class="bg-white border-t border-gray-200 px-4 py-1 flex justify-between items-center text-xs text-gray-500 shrink-0">
        <div class="flex items-center gap-4">
            <span id="status-mode"><i class="fas fa-check-circle text-green-500 mr-1"></i> Sẵn sàng</span>
            <span id="status-file-type">Loại tệp: N/A</span>
        </div>
        <div>
            <span id="status-stats">Độ dài: 0 ký tự</span>
        </div>
    </footer>

    <!-- Toast Notifications -->
    <div class="toast-container" id="toast-container"></div>

    <!-- JSZip Library for downloading multiple files -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <!-- Embedded File Data (Mocking the state of artifacts) -->
    <script>
        // Define the files embedded in this session
        const filesData = [
            {
                filename: "baocao_thuctap_pcstore.md",
                title: "Báo cáo Thực tập Dự án PC Store",
                type: "markdown",
                content: `# BÁO CÁO DỰ ÁN THỰC TẬP: HỆ THỐNG THƯƠNG MẠI ĐIỆN TỬ LINH KIỆN PC & TRỢ LÝ AI

## 1. DANH SÁCH CHỨC NĂNG (FEATURE LIST)

Hệ thống được chia thành 3 phân hệ chính:

**A. Phân hệ Khách hàng (Customer UI - Vue.js)**

- **Tìm kiếm & Lọc động (Dynamic Filtering):** Lọc linh kiện theo nhiều tiêu chí (Danh mục, Giá cả, Thông số kỹ thuật riêng biệt của từng linh kiện).

- **Quản lý Giỏ hàng (Cart Management):** Sử dụng Pinia để lưu trữ trạng thái giỏ hàng theo thời gian thực (Real-time).

- **Thanh toán Một trang (One-page Checkout):** Tích hợp điền thông tin và chọn phương thức thanh toán (COD hoặc chuyển khoản VietQR) trên cùng một trang để tăng tỷ lệ chuyển đổi.

- **Chi tiết sản phẩm:** Hiển thị thư viện ảnh, thông số kỹ thuật chi tiết và tải tài liệu Datasheet (PDF).

**B. Phân hệ AI Trợ lý & Tư vấn (Điểm nhấn dự án)**

- **AI PC Builder:** Chatbot thông minh giúp người dùng tự động xây dựng cấu hình máy tính dựa trên: Ngân sách (Budget) và Nhu cầu (Gaming, Đồ họa, Văn phòng).

- **Kiểm tra tương thích phần cứng:** Trợ lý AI có khả năng đọc thông số EAV từ Database để đảm bảo tính tương thích (VD: CPU LGA1700 đi với Mainboard Socket 1700, Nguồn đủ công suất kéo VGA).

**C. Phân hệ Quản trị (Admin Dashboard - Laravel)**

- **Quản lý Sản phẩm & Thuộc tính (CRUD):** Áp dụng chuẩn mô hình EAV để dễ dàng thêm/bớt thông số kỹ thuật mới mà không cần sửa cấu trúc Database.

- **Quản lý Đơn hàng:** Cập nhật trạng thái đơn hàng và trừ số lượng tồn kho qua \`DB::transaction\`.

- **Quản lý Quota API:** Sử dụng DevQuota để giới hạn và theo dõi lưu lượng gọi API AI, ngăn chặn rủi ro chi phí.

## 2. PHÂN TÍCH HỆ THỐNG (SYSTEM ANALYSIS)

**Công nghệ sử dụng:**

- **Backend:** PHP / Laravel Framework.

- **Frontend:** Vue 3 (Composition API) / Tailwind CSS / Pinia.

- **Database:** MySQL.

- **Third-party / AI:** Google Gemini API (hoặc OpenAI), DevQuota, thư viện Carbon xử lý thời gian.

**Kiến trúc cốt lõi:**

1. **Kiến trúc RESTful API:** Phân tách hoàn toàn Backend (cung cấp JSON APIs) và Frontend (Render giao diện), giúp hệ thống dễ bảo trì và mở rộng lên Mobile App sau này.

2. **Mô hình Dữ liệu EAV (Entity-Attribute-Value):**
    - _Vấn đề:_ Linh kiện PC có thuộc tính quá đa dạng (RAM cần thông số Bus, CPU cần thông số Core/Thread). Nếu dùng bảng (table) phẳng thông thường sẽ tạo ra vô số cột rác, lãng phí bộ nhớ.

    - _Giải pháp:_ Áp dụng EAV. Tách thuộc tính ra thành bảng riêng. Điều này giúp hệ thống linh hoạt tuyệt đối, thêm loại linh kiện mới mà không cần can thiệp cấu trúc DB. Hơn nữa, nó giúp AI dễ dàng query dữ liệu theo cặp \`Key-Value\` để so sánh tính tương thích.

3. **Kiến trúc RAG (Retrieval-Augmented Generation) cho AI:**
    - AI không tự bịa ra linh kiện, mà Backend sẽ query các linh kiện có sẵn trong kho (đáp ứng điều kiện giá), sau đó đóng gói thành "Context" gửi cho AI để phân tích và trả về cấu hình tối ưu.

## 3. BẢNG ERD (SƠ ĐỒ THỰC THỂ LIÊN KẾT)

> _(Ghi chú cho sinh viên: Hãy chèn hình ảnh chụp Sơ đồ ERD từ bản thiết kế Blueprint vào đây)_
> \`![Sơ đồ ERD](duong-dan-anh-erd.png)\`

**Cấu trúc các bảng cốt lõi (Table Schema):**

- **Nhóm Quản lý (Users & Orders):**
    - \`users\` (id, name, email, role...)

    - \`orders\` (id, user_id, total_amount, status, payment_method)

    - \`order_items\` (id, order_id, product_id, quantity, price)

- **Nhóm Thực thể (Entity):**
    - \`categories\` (id, name, parent_id)

    - \`products\` (id, category_id, sku, name, price, stock_quantity...)

- **Nhóm EAV (Quản lý Thuộc tính Động):**
    - \`attributes\` (id, name - VD: "Socket", "Dung lượng")

    - \`category_attribute\` (category_id, attribute_id)

    - \`product_attribute_values\` (product_id, attribute_id, value - VD: "LGA 1700")

## 4. MOCKUP (PHÁC THẢO GIAO DIỆN)

> _(Ghi chú cho sinh viên: Hãy chèn hình ảnh chụp các trang Mockup từ bản thiết kế Blueprint vào đây)_
> \`![Mockup Trang chủ](duong-dan-anh-trang-chu.png)\`

**Mô tả các màn hình chính:**

- **Trang Chủ (Homepage):** Thiết kế Modern E-commerce. Có thanh điều hướng trung tâm, khu vực Hero Banner nổi bật với nút Gọi AI (AI PC Builder), phía dưới là lưới danh mục sản phẩm (Category Grid) và khu vực Flash Sale hiển thị Card sản phẩm.

- **Chi tiết sản phẩm (Product Detail):** Layout phân bổ tối ưu trải nghiệm. Cột trái là Gallery hình ảnh, Cột phải là thông tin giá, tồn kho và các CTA (Add to cart/Buy Now). Bên dưới tích hợp Tabs để hiển thị Bảng thông số kỹ thuật (truy xuất từ EAV).

- **Thanh toán (One-page Checkout):** Tối ưu hóa tỷ lệ chuyển đổi bằng cách gộp chung màn hình. Khách hàng có thể vừa xem lại list sản phẩm bên trái, vừa điền thông tin nhận hàng và chọn thanh toán (Hỗ trợ QR Code động) ở bên phải mà không cần chuyển trang.`            },
            {
                filename: "project-blueprint.html",
                title: "Bản thiết kế ERD & UI/UX",
                type: "html",
                content:`<!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bản thiết kế ERD & UI/UX Dự án PC Store</title>
        <script src="https://cdn.tailwindcss.com"><\/script>
        <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"><\/script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
            .tab-content { display: none; }
            .tab-content.active { display: block; animation: fadeIn 0.3s ease-in-out; }
            @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
            /* Scrollbar custom */
            ::-webkit-scrollbar { width: 8px; }
            ::-webkit-scrollbar-track { background: #f1f1f1; }
            ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
            ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        </style>
    </head>
    <body class="h-screen flex flex-col overflow-hidden text-gray-800">

        <header class="bg-slate-900 text-white shadow-md z-10 shrink-0">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-microchip text-blue-400 text-2xl"></i>
                        <span class="font-bold text-xl tracking-tight">TechGear <span class="text-blue-400">Blueprint</span></span>
                    </div>
                    <nav class="flex space-x-1 bg-slate-800 p-1 rounded-lg">
                        <button onclick="switchTab('erd')" id="btn-erd" class="tab-btn px-4 py-2 rounded-md text-sm font-medium bg-blue-500 text-white transition-colors">
                            <i class="fas fa-database mr-2"></i>Sơ đồ ERD (EAV)
                        </button>
                        <button onclick="switchTab('home')" id="btn-home" class="tab-btn px-4 py-2 rounded-md text-sm font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-colors">
                            <i class="fas fa-home mr-2"></i>Trang chủ
                        </button>
                        <button onclick="switchTab('detail')" id="btn-detail" class="tab-btn px-4 py-2 rounded-md text-sm font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-colors">
                            <i class="fas fa-info-circle mr-2"></i>Chi tiết SP
                        </button>
                        <button onclick="switchTab('checkout')" id="btn-checkout" class="tab-btn px-4 py-2 rounded-md text-sm font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-colors">
                            <i class="fas fa-shopping-cart mr-2"></i>Thanh toán
                        </button>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Khu vực nội dung chính -->
        <main class="flex-1 overflow-y-auto bg-gray-100 p-6">
            <div class="max-w-7xl mx-auto">

                <section id="erd" class="tab-content active bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Sơ đồ Thực thể - Mối quan hệ (ERD)</h2>
                        <p class="text-gray-500 mt-2">Áp dụng kiến trúc EAV (Entity-Attribute-Value) tối ưu hóa cho AI PC Builder.</p>
                    </div>
                    <div class="flex justify-center bg-gray-50 rounded-lg p-4 border border-gray-100 overflow-x-auto">
                        <pre class="mermaid">
                            erDiagram
                                USERS {
                                    bigint id PK
                                    string name
                                    string email
                                    string password
                                    string role "admin/customer"
                                    string phone
                                    string address
                                    datetime created_at
                                }
                                ORDERS {
                                    bigint id PK
                                    bigint user_id FK
                                    decimal total_amount
                                    string status "pending/completed..."
                                    string payment_method
                                    datetime created_at
                                }
                                ORDER_ITEMS {
                                    bigint id PK
                                    bigint order_id FK
                                    bigint product_id FK
                                    int quantity
                                    decimal price
                                }
                                CATEGORIES {
                                    bigint id PK
                                    bigint parent_id FK
                                    string name
                                }
                                PRODUCTS {
                                    bigint id PK
                                    bigint category_id FK
                                    string sku
                                    string name
                                    decimal price
                                    int stock_quantity
                                    string description
                                    string thumbnail_url
                                }
                                ATTRIBUTES {
                                    bigint id PK
                                    string name "e.g., Socket, VRAM"
                                }
                                CATEGORY_ATTRIBUTE {
                                    bigint category_id FK
                                    bigint attribute_id FK
                                }
                                PRODUCT_ATTRIBUTE_VALUES {
                                    bigint product_id FK
                                    bigint attribute_id FK
                                    string value "e.g., LGA 1700"
                                }

                                USERS ||--o{ ORDERS : "tạo"
                                ORDERS ||--|{ ORDER_ITEMS : "bao gồm"
                                PRODUCTS ||--o{ ORDER_ITEMS : "nằm trong"
                                CATEGORIES ||--o{ CATEGORIES : "danh mục con"
                                CATEGORIES ||--o{ PRODUCTS : "chứa"
                                CATEGORIES ||--|{ CATEGORY_ATTRIBUTE : "có thuộc tính"
                                ATTRIBUTES ||--|{ CATEGORY_ATTRIBUTE : "thuộc danh mục"
                                PRODUCTS ||--o{ PRODUCT_ATTRIBUTE_VALUES : "có giá trị"
                                ATTRIBUTES ||--o{ PRODUCT_ATTRIBUTE_VALUES : "định nghĩa"
                        </pre>
                    </div>
                </section>

                <section id="home" class="tab-content">
                    <div class="bg-white border border-gray-300 rounded-lg overflow-hidden shadow-2xl mockup-window relative">
                        <!-- Mockup Header -->
                        <div class="bg-blue-600 text-white p-4 flex justify-between items-center">
                            <div class="text-xl font-bold flex items-center gap-2"><i class="fas fa-desktop"></i> TECHGEAR</div>
                            <div class="flex-1 max-w-2xl mx-8 relative">
                                <input type="text" placeholder="Nhập tên linh kiện, mã SKU..." class="w-full py-2 pl-4 pr-10 rounded-full text-gray-800 focus:outline-none">
                                <i class="fas fa-search absolute right-4 top-3 text-gray-400"></i>
                            </div>
                            <div class="flex gap-6 text-xl">
                                <div class="relative cursor-pointer">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span class="absolute -top-2 -right-2 bg-red-500 text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold">2</span>
                                </div>
                                <i class="fas fa-user-circle cursor-pointer"></i>
                            </div>
                        </div>

                        <!-- Mockup Hero & AI Banner -->
                        <div class="relative bg-slate-800 h-80 flex items-center justify-center overflow-hidden">
                            <img src="https://placehold.co/1200x400/1e293b/ffffff?text=RTX+40+Series+-+Sẵn+Hàng" alt="Banner" class="absolute inset-0 w-full h-full object-cover opacity-50">
                            <div class="relative z-10 text-center">
                                <h1 class="text-4xl font-bold text-white mb-4">Nâng Cấp Hiệu Năng Vượt Trội</h1>
                                <p class="text-gray-300 mb-8 text-lg">Hàng ngàn linh kiện chính hãng đang chờ bạn.</p>
                                <button class="bg-gradient-to-r from-purple-600 to-blue-500 hover:from-purple-500 hover:to-blue-400 text-white font-bold py-3 px-8 rounded-full shadow-lg transform transition hover:scale-105 flex items-center gap-2 mx-auto border border-white/20">
                                    <i class="fas fa-robot text-2xl"></i>
                                    <span>Trải nghiệm AI PC Builder<br><span class="text-xs font-normal opacity-90">Build PC chuẩn kỹ sư trong 5 giây</span></span>
                                </button>
                            </div>
                        </div>

                        <!-- Mockup Categories -->
                        <div class="p-8">
                            <h2 class="text-xl font-bold text-gray-800 mb-6">Danh mục linh kiện</h2>
                            <div class="grid grid-cols-6 gap-4 text-center">
                                <div class="p-4 border rounded-lg hover:border-blue-500 cursor-pointer transition"><i class="fas fa-microchip text-4xl text-gray-600 mb-2"></i><div class="font-medium">CPU</div></div>
                                <div class="p-4 border rounded-lg hover:border-blue-500 cursor-pointer transition"><i class="fas fa-chess-board text-4xl text-gray-600 mb-2"></i><div class="font-medium">Mainboard</div></div>
                                <div class="p-4 border rounded-lg hover:border-blue-500 cursor-pointer transition"><i class="fas fa-memory text-4xl text-gray-600 mb-2"></i><div class="font-medium">RAM</div></div>
                                <div class="p-4 border rounded-lg hover:border-blue-500 cursor-pointer transition"><i class="fas fa-tv text-4xl text-gray-600 mb-2"></i><div class="font-medium">VGA</div></div>
                                <div class="p-4 border rounded-lg hover:border-blue-500 cursor-pointer transition"><i class="fas fa-hdd text-4xl text-gray-600 mb-2"></i><div class="font-medium">SSD/HDD</div></div>
                                <div class="p-4 border rounded-lg hover:border-blue-500 cursor-pointer transition"><i class="fas fa-plug text-4xl text-gray-600 mb-2"></i><div class="font-medium">Nguồn (PSU)</div></div>
                            </div>
                        </div>

                        <!-- Mockup Flash Sale -->
                        <div class="bg-gray-50 p-8 border-t">
                            <h2 class="text-xl font-bold text-red-600 mb-6 flex items-center gap-2"><i class="fas fa-bolt"></i> Giá Tốt Hôm Nay</h2>
                            <div class="grid grid-cols-4 gap-6">
                                <!-- Product Card 1 -->
                                <div class="bg-white border rounded-lg p-4 hover:shadow-lg transition relative">
                                    <span class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">-15%</span>
                                    <img src="https://placehold.co/200x200/e2e8f0/475569?text=CPU+Intel" alt="CPU" class="w-full mb-4 rounded">
                                    <h3 class="font-medium text-gray-800 line-clamp-2 h-12">CPU Intel Core i5-12400F (6 Nhân / 12 Luồng)</h3>
                                    <div class="mt-2">
                                        <span class="text-red-600 font-bold text-lg">2.890.000 đ</span>
                                        <span class="text-gray-400 line-through text-sm ml-2">3.400.000 đ</span>
                                    </div>
                                    <button class="w-full mt-4 border border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white py-2 rounded font-medium transition">Thêm vào giỏ</button>
                                </div>
                                <!-- Product Card 2 -->
                                <div class="bg-white border rounded-lg p-4 hover:shadow-lg transition">
                                    <img src="https://placehold.co/200x200/e2e8f0/475569?text=RAM+16GB" alt="RAM" class="w-full mb-4 rounded">
                                    <h3 class="font-medium text-gray-800 line-clamp-2 h-12">RAM Corsair Vengeance LPX 16GB (2x8) DDR4 3200MHz</h3>
                                    <div class="mt-2"><span class="text-red-600 font-bold text-lg">950.000 đ</span></div>
                                    <button class="w-full mt-4 border border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white py-2 rounded font-medium transition">Thêm vào giỏ</button>
                                </div>
                                <!-- Product Card 3 & 4 Placeholders -->
                                <div class="bg-white border rounded-lg p-4 opacity-70"><img src="https://placehold.co/200x200/e2e8f0/475569?text=VGA" class="w-full mb-4 rounded"><div class="h-4 bg-gray-200 rounded mb-2"></div><div class="h-4 bg-gray-200 rounded w-2/3"></div></div>
                                <div class="bg-white border rounded-lg p-4 opacity-70"><img src="https://placehold.co/200x200/e2e8f0/475569?text=Mainboard" class="w-full mb-4 rounded"><div class="h-4 bg-gray-200 rounded mb-2"></div><div class="h-4 bg-gray-200 rounded w-2/3"></div></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="detail" class="tab-content">
                     <div class="bg-white border border-gray-300 rounded-lg shadow-2xl p-6">
                        <!-- Breadcrumb -->
                        <div class="text-sm text-gray-500 mb-6 flex gap-2 items-center">
                            <i class="fas fa-home"></i> Trang chủ <i class="fas fa-chevron-right text-xs"></i> Linh kiện PC <i class="fas fa-chevron-right text-xs"></i> CPU <i class="fas fa-chevron-right text-xs"></i> <span class="text-blue-600 font-medium">Intel Core i5-12400F</span>
                        </div>

                        <!-- Top Section -->
                        <div class="flex gap-8 mb-10">
                            <!-- Left: Gallery -->
                            <div class="w-2/5">
                                <div class="border rounded-lg p-4 mb-4 flex justify-center items-center bg-gray-50">
                                    <img src="https://placehold.co/400x400/ffffff/475569?text=Intel+i5+Box" alt="Main image" class="w-full max-w-sm rounded">
                                </div>
                                <div class="flex gap-2">
                                    <div class="border-2 border-blue-500 rounded p-1 w-1/4 cursor-pointer"><img src="https://placehold.co/100x100/ffffff/475569?text=img1" class="w-full"></div>
                                    <div class="border rounded p-1 w-1/4 cursor-pointer hover:border-gray-400"><img src="https://placehold.co/100x100/ffffff/475569?text=img2" class="w-full"></div>
                                    <div class="border rounded p-1 w-1/4 cursor-pointer hover:border-gray-400"><img src="https://placehold.co/100x100/ffffff/475569?text=img3" class="w-full"></div>
                                </div>
                            </div>

                            <!-- Right: Info -->
                            <div class="w-3/5">
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">CPU Intel Core i5-12400F (Up To 4.40GHz, 6 Nhân 12 Luồng, 18MB Cache, Socket 1700, Alder Lake)</h1>
                                <div class="flex items-center gap-6 text-sm text-gray-600 mb-4 pb-4 border-b">
                                    <span>Mã SKU: <span class="font-medium">CPU-INT-0012</span></span>
                                    <span>Tình trạng: <span class="text-green-600 font-bold"><i class="fas fa-check-circle"></i> Còn hàng (15)</span></span>
                                    <span>Thương hiệu: <span class="text-blue-600 font-medium">Intel</span></span>
                                </div>
                                <div class="text-4xl font-bold text-red-600 mb-6">2.890.000 đ <span class="text-lg text-gray-400 line-through font-normal ml-2">3.400.000 đ</span></div>

                                <div class="flex items-center gap-4 mb-8">
                                    <span class="font-medium text-gray-700">Số lượng:</span>
                                    <div class="flex border rounded-md overflow-hidden w-32">
                                        <button class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 transition">-</button>
                                        <input type="text" value="1" class="w-full text-center border-x focus:outline-none font-medium">
                                        <button class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 transition">+</button>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <button class="flex-1 border-2 border-blue-600 text-blue-600 hover:bg-blue-50 py-3 rounded-lg font-bold text-lg transition flex items-center justify-center gap-2">
                                        <i class="fas fa-cart-plus"></i> THÊM VÀO GIỎ HÀNG
                                    </button>
                                    <button class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-bold text-lg transition flex flex-col items-center justify-center">
                                        <span>MUA NGAY</span>
                                        <span class="text-xs font-normal">Giao hàng tận nơi nhanh chóng</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Section (Tabs) -->
                        <div class="border-t">
                            <div class="flex gap-8 border-b">
                                <button class="py-4 text-gray-500 font-medium hover:text-gray-900 border-b-2 border-transparent">Mô tả chi tiết</button>
                                <button class="py-4 text-blue-600 font-bold border-b-2 border-blue-600">Thông số kỹ thuật</button>
                                <button class="py-4 text-gray-500 font-medium hover:text-gray-900 border-b-2 border-transparent">Tài liệu kỹ thuật</button>
                            </div>
                            <div class="py-6">
                                <!-- EAV Table Mockup -->
                                <p class="text-sm text-gray-500 mb-4"><i class="fas fa-info-circle"></i> Bảng thông số được kết xuất tự động từ hệ thống thuộc tính động (EAV).</p>
                                <table class="w-full border-collapse border border-gray-200 rounded-lg overflow-hidden">
                                    <tbody>
                                        <tr class="bg-gray-50">
                                            <td class="w-1/3 py-3 px-4 border border-gray-200 font-medium text-gray-700">Dòng CPU</td>
                                            <td class="w-2/3 py-3 px-4 border border-gray-200 text-gray-900">Core i5</td>
                                        </tr>
                                        <tr class="bg-white">
                                            <td class="py-3 px-4 border border-gray-200 font-medium text-gray-700">Socket</td>
                                            <td class="py-3 px-4 border border-gray-200 text-gray-900 font-semibold text-blue-700">LGA 1700 <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">AI Tag</span></td>
                                        </tr>
                                        <tr class="bg-gray-50">
                                            <td class="py-3 px-4 border border-gray-200 font-medium text-gray-700">Số nhân / Số luồng</td>
                                            <td class="py-3 px-4 border border-gray-200 text-gray-900">6 Cores / 12 Threads</td>
                                        </tr>
                                        <tr class="bg-white">
                                            <td class="py-3 px-4 border border-gray-200 font-medium text-gray-700">Xung nhịp cơ bản / Boost</td>
                                            <td class="py-3 px-4 border border-gray-200 text-gray-900">2.50 GHz / 4.40 GHz</td>
                                        </tr>
                                        <tr class="bg-gray-50">
                                            <td class="py-3 px-4 border border-gray-200 font-medium text-gray-700">Điện năng tiêu thụ (TDP)</td>
                                            <td class="py-3 px-4 border border-gray-200 text-gray-900 font-semibold text-orange-600">65W <span class="ml-2 text-xs bg-orange-100 text-orange-800 px-2 py-0.5 rounded-full">AI Tag</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                     </div>
                </section>

                <section id="checkout" class="tab-content">
                    <div class="flex gap-6 items-start">
                        <!-- Left: Cart Items -->
                        <div class="w-7/12 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                            <div class="bg-gray-50 px-6 py-4 border-b font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-shopping-bag text-blue-600"></i> Giỏ hàng của bạn (2 sản phẩm)
                            </div>
                            <div class="p-6 flex flex-col gap-6">
                                <!-- Item 1 -->
                                <div class="flex items-center gap-4 pb-6 border-b border-gray-100">
                                    <img src="https://placehold.co/80x80/e2e8f0/475569?text=i5" alt="Item" class="rounded border w-20 h-20 object-cover">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-800">CPU Intel Core i5-12400F</h4>
                                        <p class="text-red-600 font-bold mt-1">2.890.000 đ</p>
                                    </div>
                                    <div class="flex border rounded overflow-hidden">
                                        <button class="px-2 py-1 bg-gray-50 hover:bg-gray-200">-</button>
                                        <input type="text" value="1" class="w-10 text-center border-x text-sm">
                                        <button class="px-2 py-1 bg-gray-50 hover:bg-gray-200">+</button>
                                    </div>
                                    <div class="w-28 text-right font-bold text-gray-800">2.890.000 đ</div>
                                    <button class="text-gray-400 hover:text-red-500 ml-4 transition" title="Xóa"><i class="fas fa-trash-alt"></i></button>
                                </div>
                                <!-- Item 2 -->
                                <div class="flex items-center gap-4">
                                    <img src="https://placehold.co/80x80/e2e8f0/475569?text=RAM" alt="Item" class="rounded border w-20 h-20 object-cover">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-800">RAM Corsair Vengeance 16GB</h4>
                                        <p class="text-red-600 font-bold mt-1">950.000 đ</p>
                                    </div>
                                    <div class="flex border rounded overflow-hidden">
                                        <button class="px-2 py-1 bg-gray-50 hover:bg-gray-200">-</button>
                                        <input type="text" value="2" class="w-10 text-center border-x text-sm">
                                        <button class="px-2 py-1 bg-gray-50 hover:bg-gray-200">+</button>
                                    </div>
                                    <div class="w-28 text-right font-bold text-gray-800">1.900.000 đ</div>
                                    <button class="text-gray-400 hover:text-red-500 ml-4 transition" title="Xóa"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Form & Summary -->
                        <div class="w-5/12 flex flex-col gap-6">
                            <!-- Giao hàng Form -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Thông tin nhận hàng</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Họ và tên</label>
                                        <input type="text" value="Nguyễn Văn A" class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                                        <input type="text" value="0987654321" class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ giao hàng chi tiết</label>
                                        <textarea rows="2" class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">Số 1 Đại Cồ Việt, Hai Bà Trưng, Hà Nội</textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Phương thức thanh toán & Tóm tắt -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Thanh toán & Đặt hàng</h3>

                                <!-- Payment Methods -->
                                <div class="space-y-3 mb-6">
                                    <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                        <input type="radio" name="payment" class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                                        <i class="fas fa-truck text-gray-500"></i>
                                        <span class="font-medium text-gray-700">Thanh toán khi nhận hàng (COD)</span>
                                    </label>
                                    <label class="flex items-start gap-3 p-3 border border-blue-500 bg-blue-50 rounded-lg cursor-pointer transition">
                                        <input type="radio" name="payment" checked class="w-4 h-4 text-blue-600 focus:ring-blue-500 mt-1">
                                        <div class="flex-1">
                                            <div class="font-medium text-blue-900 flex items-center gap-2">
                                                <i class="fas fa-qrcode"></i> Chuyển khoản VietQR
                                            </div>
                                            <!-- Mockup QR -->
                                            <div class="mt-3 p-3 bg-white rounded border flex gap-4 items-center">
                                                <img src="https://placehold.co/100x100/ffffff/000000?text=QR+Code" alt="QR" class="w-20 h-20 border rounded">
                                                <div class="text-sm">
                                                    <p class="text-gray-500">Quét mã để thanh toán tự động</p>
                                                    <p class="font-bold text-gray-800 mt-1">TECHGEAR VN</p>
                                                    <p class="font-mono text-xs text-blue-600 bg-blue-50 p-1 mt-1 inline-block rounded">TG-889922</p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <!-- Summary -->
                                <div class="space-y-2 text-sm text-gray-600 mb-6">
                                    <div class="flex justify-between"><span>Tạm tính:</span> <span class="font-medium text-gray-900">4.790.000 đ</span></div>
                                    <div class="flex justify-between"><span>Phí giao hàng:</span> <span class="font-medium text-gray-900">Miễn phí</span></div>
                                    <div class="flex justify-between pt-2 border-t mt-2">
                                        <span class="font-bold text-gray-800 text-base">TỔNG CỘNG:</span>
                                        <span class="font-bold text-red-600 text-xl">4.790.000 đ</span>
                                    </div>
                                </div>

                                <button class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 rounded-lg text-lg transition flex justify-center items-center gap-2 shadow-lg">
                                    ĐẶT HÀNG NGAY <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <script>
            // Khởi tạo thư viện vẽ ERD
            mermaid.initialize({ startOnLoad: true, theme: 'default' });

            // Xử lý chuyển đổi qua lại giữa các Tab
            function switchTab(tabId) {
                // 1. Ẩn tất cả nội dung
                document.querySelectorAll('.tab-content').forEach(el => {
                    el.classList.remove('active');
                });
                // 2. Hiện nội dung được chọn
                document.getElementById(tabId).classList.add('active');

                // 3. Reset style tất cả nút
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.className = 'tab-btn px-4 py-2 rounded-md text-sm font-medium text-gray-300 hover:text-white hover:bg-slate-700 transition-colors';
                });
                // 4. Highlight nút được chọn
                document.getElementById('btn-' + tabId).className = 'tab-btn px-4 py-2 rounded-md text-sm font-medium bg-blue-500 text-white transition-colors shadow-sm';
            }
        <\/script>

    </body>
    </html>`
                }
            ];

            // State variables
            let currentFileIndex = 0;
            let isResizing = false;

            // DOM Elements
            const fileSelector = document.getElementById('file-selector');
            const editor = document.getElementById('editor');
            const previewContainer = document.getElementById('preview-container');
            const editorTitle = document.getElementById('editor-title');
            const previewBadge = document.getElementById('preview-badge');
            const statusMode = document.getElementById('status-mode');
            const statusFileType = document.getElementById('status-file-type');
            const statusStats = document.getElementById('status-stats');
            const fileCountLabel = document.getElementById('file-count-label');

            // Buttons
            const btnDownload = document.getElementById('btn-download');
            const btnDownloadAll = document.getElementById('btn-download-all');
            const btnCopy = document.getElementById('btn-copy');
            const btnRefreshPreview = document.getElementById('btn-refresh-preview');
            const btnTogglePreview = document.getElementById('btn-toggle-preview');
            const btnToggleEditor = document.getElementById('btn-toggle-editor');

            // Panes
            const editorPane = document.getElementById('editor-pane');
            const previewPane = document.getElementById('preview-pane');
            const resizer = document.getElementById('resizer');

            // Initialization
            function init() {
                // Populate file selector
                filesData.forEach((file, index) => {
                    const option = document.createElement('option');
                    option.value = index;
                    option.textContent = `${file.filename} - ${file.title}`;
                    fileSelector.appendChild(option);
                });

                fileCountLabel.textContent = `${filesData.length} tệp được tạo`;

                // Setup event listeners
                fileSelector.addEventListener('change', (e) => loadFile(e.target.value));
                editor.addEventListener('input', handleEditorChange);

                // Setup buttons
                btnDownload.addEventListener('click', downloadCurrentFile);
                btnDownloadAll.addEventListener('click', downloadAllFiles);
                btnCopy.addEventListener('click', copyCode);
                btnRefreshPreview.addEventListener('click', () => updatePreview(filesData[currentFileIndex]));

                // Mobile toggle buttons
                btnTogglePreview.addEventListener('click', () => {
                    editorPane.classList.add('hidden');
                    previewPane.classList.remove('hidden');
                    updatePreview(filesData[currentFileIndex]);
                });

                btnToggleEditor.addEventListener('click', () => {
                    previewPane.classList.add('hidden');
                    editorPane.classList.remove('hidden');
                });

                // Resizer logic
                resizer.addEventListener('mousedown', (e) => {
                    isResizing = true;
                    resizer.classList.add('dragging');
                    document.body.style.cursor = 'col-resize';
                    e.preventDefault();
                });

                document.addEventListener('mousemove', (e) => {
                    if (!isResizing) return;

                    const container = document.querySelector('.editor-container');
                    const containerRect = container.getBoundingClientRect();

                    // Calculate percentage, keeping boundaries
                    let newFlexBasis = ((e.clientX - containerRect.left) / containerRect.width) * 100;
                    newFlexBasis = Math.max(10, Math.min(newFlexBasis, 90));

                    editorPane.style.flex = `1 1 ${newFlexBasis}%`;
                    previewPane.style.flex = `1 1 ${100 - newFlexBasis}%`;
                });

                document.addEventListener('mouseup', () => {
                    if (isResizing) {
                        isResizing = false;
                        resizer.classList.remove('dragging');
                        document.body.style.cursor = 'default';
                    }
                });

                // Load first file
                loadFile(0);

                // Handle window resize for mobile view
                window.addEventListener('resize', handleResize);
                handleResize();
            }

            function handleResize() {
                if (window.innerWidth < 1024) {
                    // Mobile view: show editor, hide preview initially
                    editorPane.style.flex = "1";
                    previewPane.style.flex = "1";
                    previewPane.classList.add('hidden');
                    editorPane.classList.remove('hidden');
                } else {
                    // Desktop view: show both
                    editorPane.classList.remove('hidden');
                    previewPane.classList.remove('hidden');
                    editorPane.style.flex = "1 1 50%";
                    previewPane.style.flex = "1 1 50%";
                }
            }

            // Load a specific file
            function loadFile(index) {
                currentFileIndex = parseInt(index);
                const file = filesData[currentFileIndex];

                editor.value = file.content;
                editorTitle.textContent = file.filename;

                statusFileType.textContent = `Loại tệp: ${file.type.toUpperCase()}`;
                previewBadge.textContent = file.type.toUpperCase();

                updateStats();
                updatePreview(file);
            }

            // Handle typing in editor
            function handleEditorChange() {
                const file = filesData[currentFileIndex];
                file.content = editor.value; // Save changes back to our object

                updateStats();
                statusMode.innerHTML = `<i class="fas fa-edit text-yellow-500 mr-1"></i> Đã chỉnh sửa`;

                // Debounce preview update (could add a real debounce here for performance)
                updatePreview(file);
            }

            function updateStats() {
                const lines = editor.value.split('\n').length;
                const chars = editor.value.length;
                statusStats.textContent = `${lines} dòng, ${chars} ký tự`;
            }

            // Update the preview pane based on file type
            function updatePreview(file) {
                previewContainer.innerHTML = '';

                if (file.type === 'markdown') {
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'preview markdown-body';
                    previewDiv.innerHTML = marked.parse(file.content);
                    previewContainer.appendChild(previewDiv);
                }
                else if (file.type === 'html') {
                    const iframe = document.createElement('iframe');
                    iframe.className = 'html-preview';
                    previewContainer.appendChild(iframe);

                    // Write content to iframe
                    const doc = iframe.contentWindow.document;
                    doc.open();
                    doc.write(file.content);
                    doc.close();
                }
                else {
                    const pre = document.createElement('pre');
                    pre.style.padding = '1.5rem';
                    pre.style.margin = '0';
                    pre.textContent = file.content;
                    previewContainer.appendChild(pre);
                }
            }

            // Download functionality
            function downloadCurrentFile() {
                const file = filesData[currentFileIndex];
                downloadBlob(file.content, file.filename, getMimeType(file.type));
                showToast(`Đang tải xuống ${file.filename}`, 'success');
            }

            async function downloadAllFiles() {
                if (filesData.length === 0) return;

                if (filesData.length === 1) {
                    // If only one file, just download it directly
                    downloadCurrentFile();
                    return;
                }

                try {
                    showToast('Đang tạo tệp ZIP...', 'success');

                    const zip = new JSZip();

                    filesData.forEach(file => {
                        zip.file(file.filename, file.content);
                    });

                    const content = await zip.generateAsync({ type: "blob" });
                    downloadBlob(content, "project-files.zip", "application/zip");

                    showToast('Đã tải xuống tất cả tệp thành công!', 'success');
                } catch (error) {
                    console.error("Error creating ZIP:", error);
                    showToast('Có lỗi xảy ra khi tạo tệp ZIP.', 'error');
                }
            }

            // Helper to trigger browser download
            function downloadBlob(content, filename, contentType) {
                const blob = content instanceof Blob ? content : new Blob([content], { type: contentType });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                setTimeout(() => {
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                }, 0);
            }

            // Helper to get mime type
            function getMimeType(type) {
                const mimeTypes = {
                    'html': 'text/html',
                    'css': 'text/css',
                    'javascript': 'text/javascript',
                    'js': 'text/javascript',
                    'json': 'application/json',
                    'markdown': 'text/markdown',
                    'md': 'text/markdown',
                    'text': 'text/plain',
                    'txt': 'text/plain'
                };
                return mimeTypes[type] || 'text/plain';
            }

            // Copy to clipboard
            function copyCode() {
                navigator.clipboard.writeText(editor.value).then(() => {
                    showToast('Đã sao chép mã vào clipboard!', 'success');
                }).catch(err => {
                    console.error('Không thể sao chép:', err);
                    showToast('Lỗi khi sao chép mã.', 'error');
                });
            }

            // Toast notification system
            function showToast(message, type = 'success') {
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');
                toast.className = `toast toast-${type}`;

                const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
                const colorClass = type === 'success' ? 'text-green-400' : 'text-red-400';

                toast.innerHTML = `
                    <i class="fas ${icon} ${colorClass} text-xl"></i>
                    <span class="text-sm font-medium">${message}</span>
                `;

                container.appendChild(toast);

                // Trigger animation
                setTimeout(() => toast.classList.add('show'), 10);

                // Remove after delay
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        if (container.contains(toast)) {
                            container.removeChild(toast);
                        }
                    }, 300); // Wait for transition
                }, 3000);
            }

            // Run initialization
            document.addEventListener('DOMContentLoaded', init);
        </script>

    </body>
    </html>
