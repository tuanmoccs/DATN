# Hướng Dẫn Deploy Hệ Thống (Frontend, Laravel Backend, Python AI Service, Laravel Queue)

Tài liệu này hướng dẫn chi tiết cách deploy toàn bộ hệ thống của bạn lên môi trường production. Chúng ta sẽ so sánh 2 phương pháp: **Sử dụng Docker (Khuyên Dùng)** và **Deploy Thủ Công (Sử dụng Nginx + PM2/Supervisor)**.

---

## 🛠️ PHƯƠNG PHÁP 1: Deploy Sử Dụng Docker (Khuyên Dùng & Dễ Nhất)

Khi sử dụng Docker, bạn **không cần** cài đặt PHP, Node.js, Python hay MySQL trực tiếp trên VPS. Docker sẽ đóng gói tất cả các dịch vụ vào các container độc lập, tự động liên kết và chạy mượt mà.

### 1. Kiến Trúc Docker Hệ Thống

Toàn bộ hệ thống được quản lý qua `docker-compose.yml` gồm các service sau:
1. `db`: Hệ quản trị cơ sở dữ liệu MySQL 8.0 (dữ liệu được lưu trữ an toàn trong volume `mysql_data`).
2. `backend`: Ứng dụng Laravel PHP-FPM 8.2 xử lý toàn bộ logic nghiệp vụ và API.
3. `queue-worker`: Hộp chứa chạy hàng đợi Laravel (`php artisan queue:work`) liên tục, tự động restart nếu có lỗi. **Không cần cài thêm PM2 hay Supervisor**.
4. `ai-service`: Ứng dụng Python FastAPI xử lý RAG, slide generation, quiz, v.v. (dữ liệu ChromaDB được lưu trữ trong volume `chroma_data`).
5. `frontend`: Website giáo viên (Vue.js + Vite) đã build thành file tĩnh và deploy trên một container Nginx độc lập.
6. `nginx`: Nginx Gateway chính làm nhiệm vụ tiếp nhận traffic từ bên ngoài (Port 80) và định tuyến:
   - Các request `/api/` và `/storage/` -> Chuyển qua Laravel `backend`.
   - Các request khác (`/`) -> Chuyển qua Vue `frontend`.
   - Lưu ý bảo mật: `ai-service` chạy nội bộ trong mạng ảo Docker (`datn-network`), không cần mở port ra ngoài Internet giúp tránh rủi ro bảo mật!

### 2. Các File Docker Đã Được Tạo Tự Động
Hệ thống AI đã tự động tạo cho bạn cấu trúc file Docker cực kỳ chuẩn hóa bao gồm:
* `website/Dockerfile` & `website/nginx.conf` & `website/.dockerignore`
* `ai-service/Dockerfile` & `ai-service/.dockerignore`
* `backend/Dockerfile` & `backend/.dockerignore`
* `docker/nginx/default.conf` (Nginx Gateway Routing)
* `docker-compose.yml` (Tổ hợp kết nối toàn bộ hệ thống)

### 3. Quy Trình Các Bước Deploy Bằng Docker Trên VPS

#### **Bước 1: Cài đặt Docker & Docker Compose trên VPS**
Nếu VPS của bạn chưa có Docker, hãy chạy các lệnh sau (hệ điều hành Ubuntu/Debian):
```bash
sudo apt update
sudo apt install -y docker.io docker-compose
sudo systemctl start docker
sudo systemctl enable docker
```

#### **Bước 2: Chuẩn bị mã nguồn và cấu hình `.env`**
1. Clone dự án về VPS thông qua Git.
2. Cấu hình file `.env` cho từng dịch vụ:
   - **Laravel Backend (`backend/.env`)**:
     ```env
     APP_ENV=production
     APP_DEBUG=false
     APP_URL=http://yourdomain.com  # Thay bằng tên miền hoặc IP VPS của bạn

     DB_CONNECTION=mysql
     DB_HOST=db                     # BẮT BUỘC để trùng với tên service trong docker-compose
     DB_PORT=3306
     DB_DATABASE=DATNAITECH
     DB_USERNAME=root
     DB_PASSWORD=your_secure_password # Mật khẩu database của bạn

     QUEUE_CONNECTION=database       # Sử dụng driver database cho queue

     # Kết nối AI Service chạy Docker nội bộ
     AI_SERVICE_URL=http://ai-service:8001
     ```
   - **Vue Frontend (`website/.env`)**:
     ```env
     VITE_API_ENDPOINT=http://yourdomain.com/api
     VITE_STORAGE_ENDPOINT=http://yourdomain.com/storage
     ```
   - **Python AI Service (`ai-service/.env`)**:
     Cấu hình các API key của OpenAI, Gemini giống như trên local của bạn.

#### **Bước 3: Khởi chạy hệ thống bằng Docker Compose**
Tại thư mục gốc chứa file `docker-compose.yml`, hãy chạy lệnh:
```bash
# Build các image và chạy ngầm (detach mode)
docker-compose up -d --build
```
Docker sẽ tự động tải các base image, cài đặt composer dependencies cho Laravel, npm packages cho Vue, pip packages cho Python, sau đó build code và khởi động toàn bộ các container lên.

#### **Bước 4: Chạy Migration và Seed Dữ Liệu (Chỉ chạy lần đầu)**
Chạy migration database trong container Laravel backend:
```bash
docker-compose exec backend php artisan migrate --seed
docker-compose exec backend php artisan jwt:secret
```

---

## 🏃‍♂️ PHƯƠNG PHÁP 2: Deploy Thủ Công (Nginx + PM2 + Supervisor)

Nếu bạn không muốn sử dụng Docker mà muốn deploy trực tiếp lên hệ điều hành VPS (không khuyên dùng vì cài đặt môi trường rất phức tạp), đây là cách thực hiện:

### 1. Phân chia nhiệm vụ các Service:
* **Frontend (Vue.js)**: Build thành thư mục static (`dist`) rồi cấu hình Nginx để serve trực tiếp các file tĩnh này.
* **Backend (Laravel)**: Chạy bằng PHP-FPM (hoặc Laravel Octane) phối hợp với Nginx.
* **AI Service (Python FastAPI)**: Chạy bằng `uvicorn` và được quản lý bằng **PM2**.
* **Laravel Queue Worker**: Chạy bằng `php artisan queue:work` và được quản lý liên tục bằng **Supervisor** (hoặc **PM2**).

### 2. Cách chạy và quản lý các tác vụ liên tục (Queue và FastAPI) bằng PM2

Đúng như bạn đã dự đoán, **PM2** là một công cụ quản lý process cực mạnh, có thể dùng để chạy cả queue của Laravel lẫn AI Service của Python liên tục 24/7.

#### **Cài đặt PM2 trên VPS**:
PM2 yêu cầu cài đặt Node.js:
```bash
sudo apt install -y nodejs npm
sudo npm install -y -g pm2
```

#### **Cấu hình file `ecosystem.config.js` để chạy tự động toàn bộ dịch vụ**:
Tạo file `ecosystem.config.js` tại thư mục gốc dự án:

```javascript
module.exports = {
  apps: [
    // 1. Chạy AI Service (FastAPI)
    {
      name: "ai-service",
      script: "venv/bin/uvicorn",
      args: "main:app --host 127.0.0.1 --port 8001 --workers 4",
      cwd: "./ai-service",
      interpreter: "python3",
      restart_delay: 3000,
      autorestart: true,
      watch: false,
      env: {
        NODE_ENV: "production"
      }
    },
    // 2. Chạy Laravel Queue Worker (Chạy liên tục)
    {
      name: "laravel-queue-worker",
      script: "artisan",
      args: "queue:work --tries=3 --timeout=120",
      cwd: "./backend",
      interpreter: "php",
      restart_delay: 2000,
      autorestart: true,
      watch: false
    }
  ]
};
```

#### **Các lệnh quản lý PM2**:
* Khởi chạy toàn bộ hệ thống (Queue + FastAPI):
  ```bash
  pm2 start ecosystem.config.js
  ```
* Xem trạng thái các dịch vụ đang chạy:
  ```bash
  pm2 status
  ```
* Xem log real-time của Queue và AI Service:
  ```bash
  pm2 logs
  ```
* Bật chế độ tự động chạy lại PM2 khi VPS bị restart:
  ```bash
  pm2 startup
  # Chạy lệnh sinh ra trên terminal để lưu cấu hình
  pm2 save
  ```

---

## 📊 Bảng so sánh 2 phương pháp Deploy

| Tiêu chí | Deploy bằng Docker (Khuyên dùng) | Deploy Thủ Công (Nginx + PM2) |
| :--- | :--- | :--- |
| **Độ dễ cài đặt** | ⭐⭐⭐⭐⭐ (Chỉ cần cài Docker, gõ 1 lệnh chạy ngay) | ⭐⭐ (Cần cài thủ công PHP 8.2, Python 3.11, MySQL, Node, PM2) |
| **Độ ổn định** | Cực kỳ cao, độc lập, không sợ lỗi xung đột phiên bản hệ điều hành. | Trung bình, dễ bị lỗi thư viện hệ thống khi cập nhật VPS. |
| **Quản lý Hàng Đợi (Queue)** | Tự động chạy trong container `queue-worker` chuyên biệt. | Phải quản lý bằng PM2 hoặc cấu hình Supervisor của Linux. |
| **Quản lý AI Service** | Tự động chạy trong container `ai-service`, lưu dữ liệu riêng. | Phải dùng PM2 để quản lý uvicorn process, tự tạo venv. |
| **Bảo mật mạng** | Rất cao, các service giao tiếp nội bộ trong mạng Docker ẩn. | Phải cấu hình Firewall cẩn thận để tránh lộ Port 8001, 3306 ra ngoài. |

---

## 💡 Đề xuất tốt nhất cho Graduation Project (Đồ án tốt nghiệp) của bạn
Bạn nên **sử dụng Docker**.
1. **Lý do**: Khi viết Docker, bạn chỉ cần nộp kèm file `docker-compose.yml` và các `Dockerfile` này. Hội đồng phản biện hoặc bất kỳ ai muốn chạy thử dự án của bạn chỉ cần tải Docker về, gõ lệnh `docker-compose up -d` là toàn bộ web giáo viên, web học sinh, AI service, và queue chạy mượt mà chỉ sau 5 phút mà **không cần cài cắm bất kỳ môi trường phức tạp nào**. Điều này sẽ giúp bạn ghi điểm tuyệt đối về sự chuyên nghiệp và chỉn chu trong đồ án!
2. **Hàng đợi chạy liên tục**: Với Docker, service `queue-worker` được khai báo trong `docker-compose` sẽ hoạt động như một daemon độc lập chạy ngầm mãi mãi. Bạn không cần lo lắng về việc nó bị dừng hay crash.
