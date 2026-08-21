# Telebat API

<p align="center">
  <strong>Telebat API</strong> is the backend of a Laravel 12 multi-vendor e-commerce platform.
</p>

<p align="center">
  <a href="https://laravel.com/docs/12.x"><img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel 12"></a>
  <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.2+"></a>
  <a href="https://www.mysql.com/"><img src="https://img.shields.io/badge/Database-MySQL-4479A1?logo=mysql&logoColor=white" alt="MySQL"></a>
  <a href="https://laravel.com/docs/sanctum"><img src="https://img.shields.io/badge/Auth-Laravel%20Sanctum-FF2D20?logo=laravel&logoColor=white" alt="Laravel Sanctum"></a>
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-green.svg" alt="MIT License"></a>
</p>

> **Backend-only repository.** Telebat API contains the Laravel backend, business logic, database layer, authentication, authorization, payments, notifications, and API endpoints. There is **no Vue or Inertia frontend in this repository**.

---

## Overview

**Telebat API** is a Laravel 12 backend for a multi-vendor e-commerce platform.

The project is built around a deliberate **single-vendor cart and checkout rule**: a customer's active cart is associated with one vendor at a time, and checkout produces a vendor-specific order flow. The backend is responsible for enforcing these business rules regardless of which client consumes the API.

The application also includes authentication, email verification, password flows, role/permission management, product and store management, carts, orders, payments, notifications, translations, PDF generation, and supporting administrative functionality.

The frontend/client is intentionally separate from this repository and can be implemented as a web application, mobile application, or another API consumer.

---

## ✨ Features

- 🏪 **Multi-vendor commerce** with vendor-oriented products, stores, sections, and orders
- 🛒 **Single-vendor cart** to prevent mixing vendors inside one active checkout
- 📦 **Product and catalog management**
- 🧾 **Order and checkout workflows**
- 💳 **Stripe and PayPal integrations**
- 🔐 **Authentication and account security**
- ✉️ **Email verification and password recovery**
- 🛡️ **Roles and permissions** using Spatie Laravel Permission
- 🔔 **Application notifications and Firebase Cloud Messaging support**
- 🌍 **Translatable content** using Spatie Laravel Translatable
- 💰 **Currency handling**
- 📄 **PDF generation** using Dompdf
- 🔌 **API-first backend** designed for separate clients
- 🧪 **Automated testing** with Laravel's testing stack and Pest tooling
- 📚 **ERD, SRS, and use-case documentation** under `docs/`

---

## 🏗️ Architecture

Telebat API follows a backend-focused Laravel architecture:

```text
┌────────────────────────────────────┐
│      External Client / Frontend    │
│   Web App • Mobile App • Client    │
└──────────────────┬─────────────────┘
                   │
                   │ HTTP / API
                   ▼
┌────────────────────────────────────┐
│            Laravel 12              │
│                                    │
│  Routes → Controllers → Services   │
│           ↓          ↓             │
│     Validation   Authorization     │
│           ↓          ↓             │
│          Eloquent Models           │
└───────────────┬─────────────┬──────┘
                │             │
                ▼             ▼
        ┌──────────────┐  ┌──────────────────┐
        │    MySQL     │  │ External Services│
        │              │  │                  │
        │ Users        │  │ Stripe           │
        │ Vendors      │  │ PayPal           │
        │ Products     │  │ Firebase / FCM   │
        │ Carts/Orders │  │ Mail              │
        └──────────────┘  └──────────────────┘
```

The key idea is that **business rules live in the backend**, not in the client. Any future frontend must respect the same authorization, validation, and vendor constraints enforced by the API.

---

## 🧰 Tech Stack

| Technology | Purpose |
|---|---|
| **Laravel 12** | Backend framework and application architecture |
| **PHP 8.2+** | Server-side runtime |
| **MySQL** | Relational database |
| **Laravel Sanctum** | API authentication |
| **Spatie Laravel Permission** | Roles and permissions |
| **Spatie Translatable** | Multilingual/translatable model data |
| **Stripe PHP SDK** | Stripe payment integration |
| **PayPal Checkout SDK** | PayPal payment integration |
| **Firebase / FCM** | Push notification support |
| **Laravel Dompdf** | PDF generation |
| **Guzzle** | HTTP client and external API communication |
| **Laravel Currency** | Currency handling |
| **Pest / Laravel testing tools** | Automated tests |
| **Laravel Pint** | PHP formatting |
| **Docker / Laravel Sail** | Local containerized development |

---

## 🛒 Core Business Rule: Single-Vendor Cart

One of the defining rules of Telebat is that a cart cannot contain products from multiple vendors at the same time.

```text
Customer
   │
   ▼
Add Product
   │
   ▼
┌────────────────────────────┐
│ Does cart belong to same   │
│ vendor as the new product? │
└──────────────┬─────────────┘
               │
        ┌──────┴──────┐
        │             │
       YES            NO
        │             │
        ▼             ▼
 Add item       Resolve existing
                vendor/cart context
        │             │
        └──────┬──────┘
               ▼
            Checkout
               │
               ▼
        Vendor-specific Order
               │
               ▼
             Payment
```

This is enforced at the backend level so the rule remains consistent for every client consuming the API.

---

## 📦 Main Backend Domains

### Authentication

Registration, login, logout, email verification, password reset, password confirmation, and account management.

### Users & Authorization

Role-based and permission-based access control using Spatie Laravel Permission.

### Vendors & Stores

Vendor-oriented store and catalog workflows.

### Products

Product creation, management, browsing, and catalog organization.

### Cart

Vendor-aware cart operations and checkout preparation.

### Orders

Order creation, order data, lifecycle handling, and vendor-specific transactions.

### Payments

Dedicated backend logic for Stripe and PayPal integrations.

### Notifications

Application notifications and Firebase Cloud Messaging support.

### Documents

PDF generation for supported application documents.

---

## 📁 Project Structure

```text
Telebat_API/
├── app/
│   ├── Console/               # Artisan commands
│   ├── Helpers/               # Shared helper functions
│   ├── Http/
│   │   ├── Controllers/       # HTTP/API controllers
│   │   ├── Middleware/        # Middleware
│   │   └── Requests/          # Request validation
│   ├── Models/                # Eloquent models
│   ├── Notifications/         # Notification classes
│   ├── Policies/              # Authorization policies
│   ├── Providers/             # Service providers
│   └── Services/              # Business/service logic
│
├── bootstrap/                 # Laravel bootstrap configuration
├── config/                    # Application configuration
├── database/
│   ├── factories/             # Model factories
│   ├── migrations/            # Database migrations
│   └── seeders/               # Database seeders
│
├── docs/
│   ├── erd/                   # Entity Relationship documentation
│   └── srs/                   # SRS and use-case documentation
│
├── public/                    # Public entry point
├── resources/                 # Backend resources/views/assets
├── routes/                    # Application and API routes
├── storage/                   # Logs/cache/generated files
├── tests/                     # Automated tests
├── composer.json              # PHP dependencies and scripts
├── package.json               # Supporting development tooling
└── README.md
```

---

## 🔌 API & Backend Responsibilities

Telebat API is intended to be consumed by a separate client. The backend owns:

- Request validation
- Authentication
- Authorization
- Business rules
- Database access
- Cart calculations and constraints
- Order creation
- Payment processing/integration
- Notification delivery
- File/document generation
- Error handling

The current route surface is defined under `routes/`, with controllers and services implementing the corresponding application behavior.

---

## 🔐 Authentication & Authorization

The backend includes authentication flows such as:

- Registration
- Login / logout
- Email verification
- Password reset
- Password confirmation
- Verification-code flows
- Profile and account settings

Laravel Sanctum is included for API authentication, while **Spatie Laravel Permission** provides roles and permissions.

Authorization must be enforced by the backend for sensitive operations; clients should never be trusted to enforce permissions themselves.

---

## 💳 Payments

Telebat integrates two payment providers at the backend level.

### Stripe

Stripe support is included through the Stripe PHP SDK.

### PayPal

PayPal Checkout support is included through the PayPal Checkout SDK.

Use test/sandbox credentials during development and store provider credentials in environment variables.

**Never commit API keys, access tokens, or payment secrets.**

---

## 🔔 Notifications

The project includes Laravel notification functionality and Firebase Cloud Messaging support.

This allows an external web/mobile client to receive notification data without coupling the backend to a particular frontend framework.

---

## 🌍 Localization & Currency

The backend includes support for:

- Translatable model attributes through Spatie Laravel Translatable
- Currency-related functionality through the Laravel Currency package

This allows commerce-related data to support multiple languages/currencies according to the configured application behavior.

---

## 📄 PDF Generation

Dompdf is included for generating PDF documents from the backend where required, such as order/invoice-style documents.

---

## 📚 Project Documentation

The repository includes supporting engineering documentation in `docs/`.

### ERD

`docs/erd/` contains Entity Relationship Diagram documentation describing the application's database relationships.

### SRS / Use Cases

`docs/srs/` contains Software Requirements and use-case documentation describing the intended system behavior.

These documents are useful when extending the backend or implementing a separate client.

---

## 🚀 Getting Started

### Requirements

Install the following before starting:

- PHP **8.2+**
- Composer
- MySQL or another supported relational database
- Node.js + npm for project tooling where required
- Git

External integrations additionally require their own credentials, for example Stripe, PayPal, or Firebase.

### 1. Clone the repository

```bash
git clone https://github.com/Muhammad-S-Gh/Telebat_API.git
cd Telebat_API
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install supporting Node dependencies

```bash
npm install
```

### 4. Create the environment file

```bash
cp .env.example .env
```

### 5. Generate the application key

```bash
php artisan key:generate
```

### 6. Configure the database

Update the database variables in `.env`, then run:

```bash
php artisan migrate
```

Run project seeders when needed for your local environment:

```bash
php artisan db:seed
```

### 7. Start the backend

```bash
php artisan serve
```

Run a queue worker separately if the feature being tested uses queued jobs:

```bash
php artisan queue:listen --tries=1
```

---

## 🐳 Docker / Laravel Sail

Laravel Sail can be used as the local containerized development environment.

Typical commands:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test
```

Use the project's `.env` configuration for database and service credentials.

---

## ⚙️ Environment Configuration

Start from `.env.example` and configure the services required by your environment.

Common configuration areas include:

- `APP_*` Laravel application settings
- Database connection variables
- Cache/session/queue configuration
- Mail configuration
- Sanctum/authentication configuration
- Stripe credentials
- PayPal credentials
- Firebase/FCM credentials
- Currency/localization settings

Keep `.env` private.

---

## 🧪 Testing

Telebat uses Laravel's testing infrastructure with Pest available in development dependencies.

Run the test suite with:

```bash
php artisan test
```

For a focused test:

```bash
php artisan test --filter=ExampleTest
```

Run the full test suite before merging significant backend changes.

---

## 🧹 Code Quality

Format PHP files with Laravel Pint:

```bash
./vendor/bin/pint
```

Inspect routes:

```bash
php artisan route:list
```

Clear Laravel caches during development when configuration changes are not being picked up:

```bash
php artisan optimize:clear
```

---

## 🔒 Security Notes

Because the backend handles authentication, permissions, payments, and user data:

- Never commit `.env`
- Never commit Stripe or PayPal secrets
- Never commit Firebase private credentials
- Use HTTPS in production
- Validate all incoming requests server-side
- Enforce authorization in backend policies/middleware
- Use sandbox/test credentials during development
- Rotate credentials immediately if they are exposed

---

## 🗺️ Roadmap

Potential future improvements include:

- More complete automated API/integration coverage
- Expanded API documentation and endpoint examples
- CI/CD workflows for tests and static checks
- Production deployment documentation
- Improved observability and application metrics
- More comprehensive payment/webhook testing
- Additional API hardening and rate-limiting

---

## 🤝 Contributing

Contributions are welcome.

```bash
git checkout -b feature/my-change
# make your changes
php artisan test
./vendor/bin/pint
git add .
git commit -m "feat: describe the change"
git push -u origin feature/my-change
```

Then open a pull request with a clear description of the change and testing performed.

---

## 📄 License

Telebat API is open-sourced under the **MIT License** as declared by the project's Composer configuration.

---

## 👤 Author

**Muhammad S. Ghunaim**

GitHub: [@Muhammad-S-Gh](https://github.com/Muhammad-S-Gh)

---

<p align="center">Backend-focused Laravel e-commerce API built with ❤️</p>
