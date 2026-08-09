# 3ilajak Backend API 

**3ilajak** (علاجك) is a healthcare platform backend built with **Laravel**, providing a robust set of RESTful APIs for multi-role user management, doctor schedule management, appointment booking, medical records, and prescriptions. The API is secured using **Laravel Sanctum** token-based authentication.

---

##  Table of Contents

- [Tech Stack](#-tech-stack)
- [User Roles & Access](#-user-roles--access)
- [Local Setup Instructions](#-local-setup-instructions)
- [Seeder Credentials](#-pre-populated-seeder-credentials)
- [Authentication Headers](#-headers-required-for-authenticated-endpoints)
- [API Endpoints](#-api-route-endpoints-summary)
- [Postman Testing Workflow](#-postman-testing-workflow)
- [Contributing](#-contributing)

---

## 🛠 Tech Stack

| Component          | Technology                        |
|---------------------|------------------------------------|
| Framework           | Laravel 12.x                       |
| Language            | PHP 8.2+                           |
| Authentication      | Laravel Sanctum (Bearer Tokens)    |
| Database            | MySQL                              |

---

##  User Roles & Access

3ilajak implements a multi-role access system. Each role is granted a distinct set of permissions across the platform:

| Role              | Permissions                                                                 |
|-------------------|-------------------------------------------------------------------------------|
| **Patient**       | Book appointments, view own medical records and prescriptions.                |
| **Doctor**        | Manage working schedules/slots, view assigned appointments, upload medical records and prescriptions. |
| **Clinic Admin**  | Manage clinic doctors and day-to-day clinic operations.                       |
| **System Admin**  | Full platform-wide administration and oversight.                              |

---

##  Local Setup Instructions

Follow these steps to get the project running on your local machine.

### 1. Clone the repository

```bash
git clone https://github.com/mennaabdelelhady/ilajak-backend.git
```

### 2. Change directory

```bash
cd ilajak-backend
```

### 3. Install dependencies

```bash
composer install
```

### 4. Copy the environment file

```bash
cp .env.example .env
```

### 5. Generate the application key

```bash
php artisan key:generate
```

### 6. Configure the database

Open your `.env` file and set the following database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=3ilajak_db
DB_USERNAME=root
DB_PASSWORD=
```

> Make sure a MySQL database named `3ilajak_db` exists before proceeding, or that your MySQL user has permission to create it.

### 7. Run migrations and seed the database

```bash
php artisan migrate:fresh --seed
```

This command will build the schema from scratch and populate it with demo accounts (see [Seeder Credentials](#-pre-populated-seeder-credentials) below).

### 8. Start the local development server

```bash
php artisan serve
```

The API will now be available at:

```
http://127.0.0.1:8000/api
```

---

##  Pre-populated Seeder Credentials

Running `php artisan migrate:fresh --seed` will create the following demo accounts, ready for immediate testing:

| Role                     | Email                     | Password      |
|--------------------------|----------------------------|----------------|
| System Admin             | `admin@3ilajak.com`        | `password123`  |
| Clinic Admin             | `clinicadmin@3ilajak.com`  | `password123`  |
| Doctor (Cardiology)      | `drahmed@3ilajak.com`      | `password123`  |
| Doctor (Pediatrics)      | `drsarah@3ilajak.com`      | `password123`  |
| Patient                  | `patient@3ilajak.com`      | `password123`  |

>  **Security Note:** These credentials are for local development and testing purposes only. Never use default seeded passwords in a staging or production environment.

---

##  Headers Required for Authenticated Endpoints

All protected routes require the following headers to be sent with each request:

| Header            | Value                  | Required For             |
|--------------------|-------------------------|----------------------------|
| `Authorization`     | `Bearer {token}`        | All protected endpoints    |
| `Accept`            | `application/json`      | All requests (recommended) |

Example:

```
Authorization: Bearer 1|a3F9kLmZpQeR7tYvUiOpAsDfGhJkLzXcVbNm
Accept: application/json
```

---

##  API Route Endpoints Summary

### 1. Authentication

| Method | Endpoint               | Access               | Description                              |
|--------|--------------------------|------------------------|--------------------------------------------|
| POST   | `/api/register`          | Public                 | Register a new patient account             |
| POST   | `/api/login`              | Public                 | Login for patients and doctors             |
| POST   | `/api/doctor/register`   | Public                 | Register a new doctor account              |
| POST   | `/api/me`                 | Protected              | Get the currently authenticated user's profile |
| POST   | `/api/logout`             | Protected              | Revoke the current access token (logout)   |

### 2. Doctor Schedules & Slots

| Method | Endpoint                                       | Access        | Description                                                                 |
|--------|--------------------------------------------------|-----------------|---------------------------------------------------------------------------------|
| POST   | `/api/doctor-schedules`                         | Doctor Only     | Create or update a schedule. Supports recurring availability via `day_of_week` or one-off overrides via `specific_date`. |
| GET    | `/api/doctors/{doctorId}/available-slots`        | Public / Optional Auth | Fetch calculated available slots for a doctor. Filterable by `specific_date`, `day_of_week`, or `clinic_id`. |

### 3. Appointments

| Method | Endpoint             | Access         | Description                                   |
|--------|------------------------|------------------|--------------------------------------------------|
| POST   | `/api/appointments`   | Patient Only     | Book an available appointment slot                |
| GET    | `/api/appointments`   | Protected        | List appointments for the current user (patient view) or doctor (schedule view) |

---

##  Postman Testing Workflow

Follow these steps to test the 3ilajak API using Postman.

### Step 1 — Set up the base URL

Create a Postman **Environment** (or Collection Variable) named `base_url` with the following value:

```
http://127.0.0.1:8000/api
```

Use `{{base_url}}` in all your request URLs (e.g. `{{base_url}}/login`) so you can easily switch environments later (local, staging, production).

### Step 2 — Obtain a Bearer token via login

Send a `POST` request to authenticate and retrieve your access token:

**Request**

```
POST {{base_url}}/login
```

**Headers**

```
Accept: application/json
Content-Type: application/json
```

**Body (raw JSON)** — using a seeded account, e.g. the patient:

```json
{
  "email": "patient@3ilajak.com",
  "password": "password123"
}
```

**Response** — copy the `token` value from the JSON response body.

### Step 3 — Store the token as a Postman variable

In your Postman Environment, create a variable named `token` and paste the value returned from the login response. This lets you reference it as `{{token}}` in subsequent requests.

### Step 4 — Set headers for protected requests

For every protected endpoint (e.g. `/me`, `/appointments`, `/doctor-schedules`), add the following headers to your request:

```
Authorization: Bearer {{token}}
Accept: application/json
```

### Step 5 — Test the flow

A typical end-to-end testing sequence:

1. `POST {{base_url}}/login` → obtain and store `{{token}}`
2. `POST {{base_url}}/me` → verify the authenticated user's profile
3. `GET {{base_url}}/doctors/{doctorId}/available-slots` → check open slots for a doctor
4. `POST {{base_url}}/appointments` → book a slot as the patient
5. `GET {{base_url}}/appointments` → confirm the appointment appears in the list
6. `POST {{base_url}}/logout` → revoke the token when finished testing

>  **Tip:** Save these requests in a Postman Collection with the `Authorization` header set at the collection level (using `{{token}}`) so you don't need to re-add it to every individual request.

---

##  Contributing

Contributions, issues, and feature requests are welcome. Please open an issue first to discuss what you would like to change, or submit a pull request with a clear description of your changes.

---

##  License

This project is proprietary/unlicensed unless otherwise stated by the repository owner. Please contact the maintainer for usage terms.
