# 📚 Web_English_vocab - Hệ Thống Học Từ Vựng Tiếng Anh Thông Minh

> **Ứng dụng web học từ vựng tiếng Anh với các tính năng hiện đại: Flashcard, Quiz trắc nghiệm, Điền từ, Theo dõi tiến độ, Streak motivational, Cross-tab sync và nhiều hơn nữa!**

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat&logo=javascript&logoColor=black)
![License](https://img.shields.io/badge/License-MIT-green.svg)

---

## 📖 Mục lục

- [Giới thiệu](#-giới-thiệu)
- [Tính năng chính](#-tính-năng-chính)
- [Công nghệ sử dụng](#-công-nghệ-sử-dụng)
- [Cấu trúc thư mục](#-cấu-trúc-thư-mục)
- [Cài đặt](#-cài-đặt)
- [Hướng dẫn sử dụng](#-hướng-dẫn-sử-dụng)
- [API Documentation](#-api-documentation)
- [Screenshots](#-screenshots)
- [Đóng góp](#-đóng-góp)

---

## 🎯 Giới thiệu

**VOCAB** là một hệ thống học từ vựng tiếng Anh toàn diện được phát triển bằng PHP, MySQL và JavaScript. Ứng dụng cung cấp trải nghiệm học tập cá nhân hóa với nhiều phương pháp học khác nhau, theo dõi tiến độ chi tiết và hệ thống gamification để tăng động lực học tập.

### 🎓 Dành cho ai?

- **Học sinh, sinh viên** muốn cải thiện vốn từ vựng tiếng Anh
- **Người đi làm** cần nâng cao trình độ tiếng Anh chuyên ngành
- **Giáo viên** muốn tạo và quản lý khóa học cho học viên
- **Cộng đồng học tập** chia sẻ và tham gia các khóa học công khai

---

## ✨ Tính năng chính

### 👤 Dành cho Người dùng

#### 🎓 Học tập & Ôn tập
- **3 chế độ học từ vựng:**
  - 📇 **Flashcard**: Học từ theo thẻ ghi nhớ với hiệu ứng lật thẻ
  - ✅ **Trắc nghiệm**: Kiểm tra với 4 phương án lựa chọn
  - ✏️ **Điền từ**: Nhập đáp án, phát triển khả năng viết

- **Hệ thống phát âm:**
  - Nghe phát âm chuẩn IPA
  - Upload file audio tùy chỉnh
  - Hiển thị phiên âm quốc tế

#### 📊 Theo dõi tiến độ
- **Dashboard thống kê:**
  - Tổng số từ đã học
  - Điểm trung bình các bài kiểm tra
  - Biểu đồ kết quả tuần này
  - Streak days (số ngày học liên tục) 🔥

- **Mục tiêu hàng ngày:**
  - Đặt số từ mới cần học mỗi ngày
  - Thanh tiến độ trực quan
  - Thông báo hoàn thành mục tiêu

#### 📚 Quản lý khóa học
- Tạo khóa học riêng tư hoặc công khai
- Tham gia khóa học từ cộng đồng
- Tìm kiếm khóa học theo tags
- Xem chi tiết từng khóa học (số từ, học viên, tiến độ)
- Import/Export từ vựng

#### 🔔 Thông báo thông minh
- Thông báo hoàn thành quiz
- Nhắc nhở ôn tập từ cũ
- Cảnh báo mất streak
- Thông báo đạt milestone

### 👨‍💼 Dành cho Admin

- **Quản lý người dùng:**
  - Xem danh sách user
  - Khóa/Mở tài khoản
  - Cập nhật thông tin user

- **Quản lý khóa học:**
  - Duyệt khóa học công khai
  - Chỉnh sửa nội dung khóa học
  - Xóa khóa học vi phạm
  - Xem thống kê khóa học

- **Lịch sử hoạt động:**
  - Log mọi thao tác admin
  - Export log theo ngày
  - Theo dõi IP và User Agent

- **Dashboard Analytics:**
  - Tổng người dùng
  - Tổng khóa học
  - Hoạt động hôm nay
  - Biểu đồ người dùng mới theo tháng

### 🔒 Bảo mật & Hiệu năng

- **Xác thực đa nền tảng:**
  - Đăng ký/Đăng nhập bằng Email + Password
  - OAuth 2.0: Google Login
  - OAuth 2.0: Facebook Login
  - Xác thực email qua mã OTP

- **Bảo vệ API:**
  - Rate Limiting (60 requests/minute)
  - CSRF Token protection
  - SQL Injection prevention (Prepared Statements)
  - XSS protection (htmlspecialchars)
  - Password hashing (bcrypt)

- **Cross-tab Synchronization:**
  - Đồng bộ dữ liệu real-time giữa các tab
  - Không cần F5 khi có thay đổi
  - Sử dụng BroadcastChannel API

---

## 🛠 Công nghệ sử dụng

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL 8.0+** - Relational database
- **MySQLi & PDO** - Database drivers
- **Apache 2.4** - Web server (XAMPP)

### Frontend
- **HTML5 & CSS3** - Semantic markup & modern styling
- **JavaScript ES6+** - Vanilla JS, no frameworks
- **Chart.js** - Data visualization
- **Font Awesome 6.5** - Icons
- **Google Fonts** - Typography (Roboto)

### APIs & Libraries
- **Google OAuth 2.0** - Google Sign-In
- **Facebook Graph API** - Facebook Login
- **BroadcastChannel API** - Cross-tab sync
- **Fetch API** - AJAX requests

### Development Tools
- **XAMPP** - Local development environment
- **Git** - Version control
- **VS Code** - Code editor

---
