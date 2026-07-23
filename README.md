# 3ilajak Backend API

A healthcare platform backend built with Laravel, providing REST APIs for patient and doctor registration, login, and authentication using Sanctum.

## Tech Stack

- Laravel 12.x
- PHP 8.2+
- Laravel Sanctum (Bearer Token Auth)
- MySQL

## Roles

- Patient
- Doctor
- Clinic Admin
- System Admin

## Setup

```bash
git clone https://github.com/your-username/3ilajak-backend.git
cd 3ilajak-backend
composer install
cp .env.example .env
php artisan key:generate
```

Update your `.env` with your database info:

```env
DB_CONNECTION=mysql
DB_DATABASE=3ilajak_db
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations:

```bash
php artisan migrate
```

Start the server:

```bash
php artisan serve
```

API base URL: `http://127.0.0.1:8000/api`

## Authentication

- Register/login to get a Bearer token.
- Send the token in the header for protected routes:

```
Authorization: Bearer {token}
```

## Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | /api/register | No | Register patient |
| POST | /api/login | No | Login patient |
| POST | /api/doctor/register | No | Register doctor |
| POST | /api/doctor/login | No | Login doctor |
| GET | /api/me | Yes | Get logged-in user |
| POST | /api/logout | Yes | Logout |

## Testing with Postman

1. Set `base_url` = `http://127.0.0.1:8000/api`
2. Login using `/login` or `/doctor/login`
3. Copy the returned token
4. In protected requests, go to **Authorization > Bearer Token** and paste it

## Status

🚧 Project just started — currently only auth (register/login/logout) is implemented.

Next up: clinics, appointments, medical records.
