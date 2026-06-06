# 🎓 ĐỒ ÁN TỐT NGHIỆP - HỆ THỐNG HỖ TRỢ GIẢNG DẠY TÍCH HỢP AI (RAG)

---

Hệ thống bao gồm **4 thành phần chính**:

1. **`backend`**: Laravel API (PHP 8.2) đóng vai trò điều phối chính, quản lý cơ sở dữ liệu (MySQL), xác thực người dùng, và xử lý hàng đợi (Queue Worker).
2. **`ai-service`**: FastAPI Python (Python >= 3.10) chịu trách nhiệm về các tác vụ AI: Nhúng vector (Embedding), tìm kiếm ngữ cảnh (RAG với ChromaDB), sinh slide, sinh câu hỏi trắc nghiệm và chấm bài viết tay qua mô hình LLM.
3. **`website`**: Ứng dụng Web dành cho Giáo viên (Vue 3 + Vite + TailwindCSS) dùng để quản lý lớp học, tài liệu, tạo bài giảng và theo dõi tiến độ học tập.
4. **`appStudent`**: Ứng dụng Di động dành cho Học sinh (React Native) dùng để tham gia lớp học, làm bài trắc nghiệm và gửi bài tập.

---

## 📋 Yêu cầu hệ thống (System Prerequisites)

Trước khi tiến hành cài đặt, hãy đảm bảo máy tính của bạn đã cài đặt đầy đủ các công cụ sau:

*   **PHP**: Phiên bản `8.2.x`
*   **Composer**: Phiên bản `2.x`.
*   **Node.js**: Phiên bản `>= 20.x` (đi kèm `npm`).
*   **Python**: Phiên bản `>= 3.10` và thư viện quản lý gói `pip`.
*   **MySQL**: Phiên bản `8.0` hoặc tương đương.
*   **Git**: Để quản lý mã nguồn.
*   *(Tùy chọn)* **Docker & Docker Compose**: Nếu bạn muốn chạy toàn bộ hệ thống bằng Docker.
*   *(Tùy chọn cho Mobile)* **Android Studio** (Android SDK) hoặc **Xcode & CocoaPods** (dành cho macOS) để build ứng dụng React Native.

---

## 🚀 Hướng dẫn cài đặt & Khởi chạy (Local Setup)

Thực hiện cài đặt theo trình tự dưới đây để đảm bảo các dịch vụ kết nối với nhau một cách chính xác.

### Bước 1: Clone mã nguồn
Mở terminal và clone dự án về máy:
```bash
git clone <url_repository_cua_ban>
cd DATN
```

---

### Bước 2: Khởi tạo Dịch vụ AI (`ai-service`)
Dịch vụ AI được viết bằng Python FastAPI và sử dụng ChromaDB để lưu trữ vector ngữ cảnh tài liệu.

1.  **Di chuyển vào thư mục `ai-service`**:
    ```bash
    cd ai-service
    ```
2.  **Tạo môi trường ảo Python (Virtual Environment)**:
    ```bash
    # Trên Windows
    python -m venv venv
    
    # Trên macOS/Linux
    python3 -m venv venv
    ```
3.  **Kích hoạt môi trường ảo**:
    ```bash
    # Trên Windows (Command Prompt)
    venv\Scripts\activate.bat
    # Trên Windows (PowerShell)
    .\venv\Scripts\activate.ps1
    
    # Trên macOS/Linux
    source venv/bin/activate
    ```
4.  **Cài đặt các thư viện cần thiết**:
    ```bash
    pip install -r requirements.txt
    ```
5.  **Cấu hình biến môi trường**:
    Tạo tệp `.env` bằng cách sao chép từ tệp mẫu:
    ```bash
    cp .env.example .env
    ```
    Mở tệp `.env` vừa tạo và điền khóa API OpenAI của bạn:
    ```env
    OPENAI_API_KEY=sk-your-openai-api-key-here
    PORT=8001
    LARAVEL_API_SECRET=your-internal-api-secret  # Phải trùng khớp với RAG_API_SECRET bên backend
    ```
6.  **Khởi chạy dịch vụ**:
    ```bash
    python main.py
    ```
    Dịch vụ AI sẽ chạy tại địa chỉ: `http://localhost:8001`

---

### Bước 3: Cấu hình Backend API (`backend`)
Backend được xây dựng bằng Laravel 10 và đóng vai trò làm API gateway chính.

1.  **Di chuyển vào thư mục `backend`**:
    ```bash
    cd ../backend
    ```
2.  **Cài đặt các gói phụ thuộc PHP**:
    ```bash
    composer install
    ```
3.  **Cấu hình biến môi trường**:
    Tạo tệp `.env` từ tệp mẫu:
    ```bash
    cp .env.example .env
    ```
    Mở `.env` lên và cấu hình thông tin cơ sở dữ liệu MySQL, JWT và kết nối AI Service:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=DATNAITECH
    DB_USERNAME=root
    DB_PASSWORD=your_mysql_password
    
    # Kết nối tới FastAPI AI Service
    AI_SERVICE_URL=http://localhost:8001
    RAG_API_SECRET=your-internal-api-secret    # Phải trùng khớp với LARAVEL_API_SECRET ở ai-service
    
    # Khóa OpenAI dùng cho một số logic trực tiếp ở backend (nếu có)
    OPENAI_API_KEY=sk-your-openai-api-key-here
    ```
4.  **Tạo cơ sở dữ liệu**:
    Tạo một cơ sở dữ liệu trống trong MySQL với tên đã cấu hình (ví dụ: `DATNAITECH`).
5.  **Khởi tạo App Key & Migrations**:
    ```bash
    php artisan key:generate
    php artisan jwt:secret
    php artisan migrate --seed
    ```
6.  **Tạo liên kết thư mục lưu trữ**:
    ```bash
    php artisan storage:link
    ```
7.  **Khởi chạy Server**:
    Mở hai tab terminal riêng biệt trong thư mục `backend`:
    *   **Tab 1 (Chạy server API chính)**:
        ```bash
        php artisan serve --port=8000
        ```
    *   **Tab 2 (Chạy hàng đợi xử lý ngầm - Slide, bài giảng, AI)**:
        ```bash
        php artisan queue:work
        ```

---

### Bước 4: Chạy Website Giáo viên (`website`)
Ứng dụng web được xây dựng bằng Vue 3 và Vite.

1.  **Di chuyển vào thư mục `website`**:
    ```bash
    cd ../website
    ```
2.  **Cài đặt các thư viện Javascript**:
    ```bash
    npm install
    ```
3.  **Cấu hình biến môi trường**:
    Mở tệp `.env` (hoặc tạo mới nếu chưa có) và cấu hình endpoint trỏ tới Laravel Backend:
    ```env
    VITE_API_ENDPOINT=http://localhost:8000/api
    VITE_STORAGE_ENDPOINT=http://localhost:8000/storage
    BASE_URL=http://localhost:5173
    ```
4.  **Khởi chạy dự án ở chế độ Phát triển (Development)**:
    ```bash
    npm run dev
    ```
    Website sẽ hoạt động tại địa chỉ mặc định: `http://localhost:5173`

---

### Bước 5: Chạy Ứng dụng Di động Học sinh (`appStudent`)
Ứng dụng React Native dành cho các thiết bị di động.

1.  **Di chuyển vào thư mục `appStudent`**:
    ```bash
    cd ../appStudent
    ```
2.  **Cài đặt các thư viện Javascript**:
    ```bash
    npm install
    ```
4.  **Khởi chạy Metro Bundler**:
    ```bash
    npm start
    ```
5.  **Khởi chạy trên Thiết bị mô phỏng hoặc thiết bị thật**:
    Mở thêm một tab terminal khác trong thư mục `appStudent` và chạy:
    ```bash
    npx react-native run-android
    ```

---

## 🐳 Cấu hình & Chạy dự án bằng Docker Compose (Khuyên dùng)

Nếu bạn không muốn cài đặt nhiều môi trường thủ công trên máy cục bộ, bạn có thể khởi chạy toàn bộ hệ thống bằng Docker chỉ với một câu lệnh đơn giản:

1.  Đảm bảo bạn đã điền các thông tin bảo mật cần thiết vào các file `.env` của `backend` và `ai-service`.
2.  Đứng tại thư mục gốc của dự án (nơi chứa file `docker-compose.yml`), chạy lệnh:
    ```bash
    docker-compose up -d --build
    ```
3.  Docker sẽ tự động tải các image, build mã nguồn và khởi chạy các dịch vụ:
    *   **Database (MySQL)**: Cổng `3306`
    *   **Backend (Laravel)**: Chạy qua Gateway Nginx tại cổng `80` (hoặc cổng cấu hình)
    *   **AI Service (FastAPI)**: Chổng `8001`
    *   **Teacher Website (Vue)**: Chạy qua Gateway Nginx tại cổng `80`
    *   **Queue Worker**: Tự động chạy ngầm trong container.

Để kiểm tra các container đang chạy:
```bash
docker ps
```
Để dừng hệ thống:
```bash
docker-compose down
```

---

## 📝 Lưu ý quan trọng khi chạy dự án
1.  **Đồng bộ Khóa Secret**: Đảm bảo biến `RAG_API_SECRET` ở `.env` của Laravel Backend trùng khớp với `LARAVEL_API_SECRET` ở `.env` của AI Service. Đây là cơ chế xác thực nội bộ giữa hai service.
2.  **Cài đặt queue worker**: Rất nhiều tác vụ tạo giáo án, xử lý tài liệu PDF/Docx nâng cao và sinh slide tốn thời gian nên được đẩy vào Queue. Hãy chắc chắn rằng lệnh `php artisan queue:work` đang chạy liên tục khi chạy kiểm thử các tính năng AI.
