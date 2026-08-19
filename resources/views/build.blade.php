<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Xây dựng cấu hình PC theo từng danh mục linh kiện.">
        <title>Xây dựng cấu hình - PC Store</title>
        <style>
            :root {
                color-scheme: light;
            }
            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #f3f4f6;
                color: #111827;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 24px 16px 48px;
            }
            .hero {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 20px;
                padding: 24px;
                margin-bottom: 20px;
            }
            .section {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 20px;
                padding: 20px;
                margin-bottom: 18px;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            }
            .section-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                margin-bottom: 16px;
            }
            .grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 16px;
            }
            .card {
                overflow: hidden;
                border: 1px solid #e5e7eb;
                border-radius: 16px;
                background: #f9fafb;
            }
            .thumb {
                aspect-ratio: 1 / 1;
                background: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .thumb img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .card-body {
                padding: 14px;
            }
            .muted {
                color: #6b7280;
                font-size: 14px;
            }
            .empty {
                border: 1px dashed #d1d5db;
                border-radius: 16px;
                padding: 20px;
                color: #6b7280;
                background: #f9fafb;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="hero">
                <a href="/browse" style="text-decoration:none;color:#6b7280;font-size:12px;letter-spacing:.3em;text-transform:uppercase;">TechGear</a>
                <h1 style="margin:12px 0 8px;font-size:32px;">Xây dựng cấu hình</h1>
                <p style="margin:0;color:#6b7280;">Chọn linh kiện theo từng danh mục để bắt đầu build máy.</p>
            </div>

            @foreach ($targetCategories as $categoryName)
                <section class="section">
                    <div class="section-header">
                        <h2 style="margin:0;font-size:20px;">{{ $categoryName }}</h2>
                        <span class="muted">{{ $groupedProducts[$categoryName]->count() }} sản phẩm</span>
                    </div>

                    @if ($groupedProducts[$categoryName]->isNotEmpty())
                        <div class="grid">
                            @foreach ($groupedProducts[$categoryName] as $product)
                                <article class="card">
                                    <div class="thumb">
                                        @if ($product->thumbnail_url)
                                            <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}">
                                        @else
                                            <span style="font-size:48px;color:#d1d5db;">🖥️</span>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <h3 style="margin:0 0 8px;font-size:15px;line-height:1.4;">{{ $product->name }}</h3>
                                        <p style="margin:0;font-size:16px;font-weight:700;">{{ number_format((float) $product->price, 0, ',', '.') }} VND</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="empty">Đang cập nhật sản phẩm</div>
                    @endif
                </section>
            @endforeach
        </div>
    </body>
</html>



