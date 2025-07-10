# User Data Management API

A Symfony backend service for user management featuring CSV upload, asynchronous email notifications, Twitter OAuth authentication, and MySQL database backup/restore.

---

## 🚀 Features

- ✅ User import via CSV
- 📬 Async email notifications using Symfony Messenger
- 🐦 Twitter OAuth 1.0a authentication
- 💾 MySQL database backup and restore via API

---

## 🧾 Data Model

**User**
- `id`: integer, primary key
- `name`: string
- `email`: string (unique)
- `username`: string (unique)
- `address`: string (nullable)
- `role`: string

---

## 📡 API Endpoints

### 🔐 AuthController

- `GET /auth/twitter`  
  Start Twitter OAuth login

- `GET /auth/twitter/callback`  
  Handle Twitter callback, store user, redirect to app

### 📤 UploadController

- `POST /api/upload`  
  Upload CSV (field: `file`), create users, send async emails

- `GET /api/users`  
  List all users in JSON format

- `GET /api/backup`  
  Download MySQL database backup (`.sql` file)

- `POST /api/restore`  
  Restore database from uploaded `.sql` file

---

## ✉️ Async Email Notification

- On user upload, a message is dispatched for each user.
- `SendNotificationHandler` sends a welcome email asynchronously.
- Uses Symfony Messenger for queue-based background processing.

---

## 🐦 Twitter OAuth

- Integrated with `league/oauth1-client`
- On successful login:
  - Retrieves user info from Twitter
  - Stores or updates user record in database

---

## ⚙️ Setup Instructions

### 1. Install dependencies

```bash
composer install
