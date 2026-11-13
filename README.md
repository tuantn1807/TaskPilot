# TaskPilot — Enterprise Agile Project Management Platform

<p align="center">
  <img src="public/images/logo.png" alt="TaskPilot Logo" width="120" onerror="this.style.display='none'"/>
</p>

<p align="center">
  <strong>Next-Generation Project Management & Team Collaboration Platform inspired by Jira, powered by Laravel, Vue 3, and AI Intelligence.</strong>
</p>

<p align="center">
  <a href="#features"><img src="https://img.shields.io/badge/Laravel-13.x-red.svg?style=flat-square&logo=laravel" alt="Laravel 13"/></a>
  <a href="#requirements"><img src="https://img.shields.io/badge/PHP-8.4+-777bb4.svg?style=flat-square&logo=php" alt="PHP 8.4+"/></a>
  <a href="#database"><img src="https://img.shields.io/badge/Database-PostgreSQL_18_%7C_MySQL_8-blue.svg?style=flat-square&logo=postgresql" alt="PostgreSQL 18 | MySQL 8"/></a>
  <a href="#mobile-api"><img src="https://img.shields.io/badge/Auth-Laravel_Sanctum-orange.svg?style=flat-square" alt="Laravel Sanctum"/></a>
  <a href="#real-time--meetings"><img src="https://img.shields.io/badge/RTC-LiveKit_WebRTC-green.svg?style=flat-square" alt="LiveKit WebRTC"/></a>
  <a href="#license"><img src="https://img.shields.io/badge/License-MIT-black.svg?style=flat-square" alt="License MIT"/></a>
</p>

---

## 📌 Overview

**TaskPilot** is a comprehensive, enterprise-grade project and task management system designed to streamline agile workflows, sprint planning, and cross-functional team collaboration. Built with a high-performance **Modular Monolith** architecture, TaskPilot combines Jira-like issue tracking, interactive Kanban boards, multi-platform video conferencing, and AI-driven predictive analytics into a unified workplace hub.

---

## ✨ Key Features

### 1. 🚀 Agile & Sprint Lifecycle Management
- **Backlog & Sprints**: Plan, start, and close sprints with velocity tracking and story point estimation.
- **Interactive Kanban Board**: Real-time drag-and-drop task progression across customizable workflow statuses (To Do, In Progress, QA/Review, Done).
- **Task Hierarchy & Links**: Link related tasks (blocks, is blocked by, relates to) and manage parent-subtask relationships.
- **Time Tracking & Worklog**: Granular worklog logging with spent time and remaining estimate calculations.

### 2. 🤖 AI-Powered Task Assignment & Smart Suggestions
- **AI Assignee Recommendation**: Automatically suggests the most suitable team member for newly created issues based on historical workload, skills, and expertise.
- **Batch AI Assignment**: Optimize team capacity across multiple backlog items in a single click.

### 3. 📈 Time Series Analytics & Forecasting (Lab 1)
- **Time Series Bug Trend Analysis**: 730-day tracking of bug reports and resolution metrics.
- **Seasonality & Decomposition ($s=7$)**: Additive and Multiplicative seasonal decomposition identifying weekday variance.
- **Stationarity Verification**: In-depth ADF (Augmented Dickey-Fuller) and KPSS hypothesis testing ensuring readiness for autoregressive forecasting (SARIMAX).
- **Interactive Web Dashboards**: Built-in SVG chart visualizations and instant re-analysis triggers.

### 4. 📹 Multi-Platform Meetings & Real-Time Collaboration
- **Integrated LiveKit WebRTC**: Host high-definition video meetings directly within TaskPilot workspaces.
- **Multi-Provider Support**: Seamlessly schedule and launch meetings across **Google Meet**, **Zoom**, **Microsoft Teams**, and **Offline** meeting rooms.
- **Meeting Minutes & Recordings**: Real-time collaborative notes, attendance logs, and cloud recording attachments.

### 5. 🔍 TQL (Task Query Language) & Custom Fields
- **JQL-like Flexible Querying**: Filter tasks using intuitive query syntax (`status = "in_progress" AND priority = "high"`).
- **Saved Filters & Export**: Save complex queries and export datasets to Excel/PDF.
- **Extensible Custom Fields**: Define text, number, date, and dropdown fields tailored to each project's workflow.

### 6. 📱 Mobile-Ready RESTful API (v1)
- **Sanctum Bearer Token Authentication**: High-security, state-of-the-art token authentication tailored for iOS, Android, and Flutter clients.
- **Push Notification Support**: FCM (Firebase Cloud Messaging) device token registration and real-time alerts.

---

## 🏛️ Architecture & Clean Code Principles

TaskPilot enforces a **Modular Monolith** pattern with strict one-directional dependency flows:

$$\textbf{Controller} \longrightarrow \textbf{Handler} \longrightarrow \textbf{Repository / Service}$$

```text
app/
├── Modules/
│   ├── Auth/           # Mobile & Web Authentication, Session & Sanctum tokens
│   ├── Projects/       # Project Management, Workflows, Roles, AI Forecast
│   ├── Tasks/          # Tasks, Sprints, Backlog, Kanban Board, AI Assignee
│   ├── Meetings/       # LiveKit WebRTC, Zoom/Meet integrations, Minutes
│   ├── Comments/       # Discussion threads & @mentions
│   ├── Companies/      # Multi-tenant organization structure
│   ├── Notifications/  # In-app notification center & push dispatches
│   └── Profile/        # User profiles, skills matrix, preferences
├── Services/           # Reusable external integrations (Cloudflare R2, AI, LiveKit)
└── Http/               # Core routing, middleware & legacy controllers
```

### Architectural Guarantees:
- **Thin Controllers**: Controllers contain no business logic or database queries; they delegate strictly to dedicated Use Case Handlers.
- **Single Responsibility Handlers**: Each handler executes exactly one business use case (`ApiLoginHandler`, `StoreTaskHandler`, etc.).
- **Strict Constructor Injection**: All dependencies are injected via `private readonly` properties without dynamic container lookups (`app()`, `resolve()`).
- **Standardized Envelope Responses**: Predictable JSON response envelopes across all API endpoints.

---

## 💻 Tech Stack

| Layer | Technologies |
| :--- | :--- |
| **Backend Framework** | Laravel 13.x, PHP 8.4+ |
| **API & Auth** | Laravel Sanctum (Token Auth), RESTful JSON API v1 |
| **Database** | PostgreSQL 18 (default) / MySQL 8.0+ |
| **Frontend UI** | Vue 3, Vite, Blade Templates, Tailwind CSS / Custom Luxury Theme |
| **Realtime & Media** | LiveKit WebRTC Server, Pusher / Laravel Echo |
| **Cloud Storage** | Cloudflare R2 / AWS S3 compatible object storage |
| **Data Science / AI** | Python 3.12, Pandas, Statsmodels, Scipy, Seaborn |

---

## 🚀 Getting Started

### Prerequisites

Ensure you have the following installed on your host machine:
- **PHP 8.4** or higher (with `pdo_pgsql`, `pdo_mysql`, `mbstring`, `bcmath`, `curl`, `intl`)
- **Composer 2.x**
- **Node.js 18+** & **npm**
- **PostgreSQL 18** or **MySQL 8.0+**
- **Git**

### Installation Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/tuantn1807/TaskPilot.git
   cd TaskPilot
   ```

2. **Install PHP & Node dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Configure the environment file:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Update database credentials in `.env`:**
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=taskpilot
   DB_USERNAME=postgres
   DB_PASSWORD=your_password
   ```

5. **Run migrations and seeds:**
   ```bash
   php artisan migrate
   ```

6. **Build frontend assets:**
   ```bash
   npm run build
   ```

7. **Start the local development server:**
   ```bash
   php artisan serve
   ```
   Access the web dashboard at: [http://localhost:8000](http://localhost:8000)

---

## 📱 Mobile API Reference (v1)

All mobile API endpoints are versioned under `/api/v1/`. Protected endpoints require an `Authorization: Bearer <TOKEN>` header.

### Authentication Endpoints

#### 1. Đăng nhập (Login)
```http
POST /api/v1/auth/login
Content-Type: application/json
Accept: application/json

{
  "email": "user@example.com",
  "password": "secret_password",
  "device_name": "iPhone 15 Pro"
}
```
**Response (200 OK):**
```json
{
  "success": true,
  "message": "Đăng nhập thành công.",
  "data": {
    "token": "1|AbCdEf123456...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Alex Mercer",
      "email": "user@example.com",
      "job_title": "Senior Engineer"
    }
  }
}
```

#### 2. Đăng ký (Register)
```http
POST /api/v1/auth/register
Content-Type: application/json
Accept: application/json

{
  "name": "Jane Doe",
  "email": "jane@company.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "device_name": "Pixel 8"
}
```

#### 3. Thông tin tài khoản hiện tại (Current User Profile)
```http
GET /api/v1/auth/me
Authorization: Bearer <TOKEN>
Accept: application/json
```

#### 4. Cập nhật hồ sơ cá nhân (Update Profile)
```http
PUT /api/v1/auth/profile
Authorization: Bearer <TOKEN>
Content-Type: application/json

{
  "full_name": "Jane Q. Doe",
  "job_title": "Lead Product Designer",
  "department": "Product",
  "city": "Da Nang",
  "timezone": "Asia/Ho_Chi_Minh"
}
```

#### 5. Đăng ký nhận thông báo đẩy (Update FCM Token)
```http
POST /api/v1/auth/fcm-token
Authorization: Bearer <TOKEN>
Content-Type: application/json

{
  "fcm_token": "fXyZ_example_device_token_..."
}
```

#### 6. Đăng xuất (Logout)
```http
POST /api/v1/auth/logout
Authorization: Bearer <TOKEN>
```

---

## 🧪 Testing & Quality Assurance

TaskPilot comes with an extensive automated test suite covering unit tests, feature flows, and browser interactions.

```bash
# Run all Unit tests
php artisan test --testsuite=Unit

# Run specific Auth Module test suites
php artisan test --filter=ApiAuthModuleTest
php artisan test --filter=AuthModuleTest

# Check code formatting & static analysis
php artisan route:list
```

---

## 📄 License

The TaskPilot platform is open-source software licensed under the [MIT License](LICENSE).
