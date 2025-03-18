# Record Tracking System

## 📌 Project Overview

The Record Tracking System is a web-based application designed to streamline the background check process for applicants. It enables administrators to efficiently track, verify, and manage applicant records while ensuring secure role-based access control.

## 🚀 Features

- **Applicant Management** – Add, update, and track applicant records.
- **Real-time Updates (SSE)** – Live updates for new applicants using Server-Sent Events (SSE).
- **Role-Based Access Control**:
  - **Admin**: Full control over applicant records and user management.
  - **Officer**: Limited access to add documents but not change status.
- **Status Tracking** – Admins can set an applicant’s status (Cleared / Not Cleared).
- **Secure Authentication** – User accounts with different access levels.
- **Notifications** – Toast notifications for new applicants.
- **Pagination & Filtering** – Easily navigate and search applicant records.

## 🛠 Tech Stack

- **Backend**: Laravel (PHP)
- **Frontend**: Vue.js (with Inertia.js & TailwindCSS)
- **Database**: SQLite / MySQL
- **Real-time Communication**: SSE (Server-Sent Events)
- **Authentication**: Laravel Breeze / Sanctum

## 📂 Setup Instructions

1. Clone the repository:
    ```bash
    git clone https://github.com/yourusername/record-tracking-system.git
    cd record-tracking-system
    ```

2. Install dependencies:
    ```bash
    composer install
    npm install
    ```

3. Set up the environment file:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. Run database migrations:
    ```bash
    php artisan migrate --seed
    ```

5. Start the development server:
    ```bash
    php artisan serve
    npm run dev
    ```

## 📌 Future Enhancements

- **QR Code Scanning** – Faster Login Authentication via QR CODE scanning.
- **Export Reports** – Generate reports in PDF or Excel format.
- **Multi-Tenant Support** – Extend the system for multiple organizations (SaasS).

## 📜 License

This project is open-source and licensed under the MIT License.