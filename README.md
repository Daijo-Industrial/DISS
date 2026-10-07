# Daijo Industrial Support System (DISS)

**Daijo Industrial Support System (DISS)** is an enterprise manufacturing and operations support platform developed for **PT Daijo Industrial**. It centralizes factory floor operations, quality assurance, vehicle fleet tracking, procurement workflows, SAP ERP synchronization, and internal employee services into a cohesive, responsive web platform.

---

## Table of Contents

- [Core Modules](#core-modules)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Getting Started](#getting-started)
- [Docker / Laravel Sail Setup](#docker--laravel-sail-setup)
- [Background Services & Realtime](#background-services--realtime)
- [Essential Artisan Commands](#essential-artisan-commands)
- [Code Quality & Testing](#code-quality--testing)
- [Contributing](#contributing)
- [License](#license)

---

## Core Modules

- **Production & Operations**: Daily manufacturing reports, work-in-progress (WIP) tracking, line status updates, defect entries, and downtime logging.
- **Quality Assurance (QA/QC)**: Incoming material inspection workflows, CPAR (Corrective & Preventive Action Requests), vendor claim response tracking, and calibration management.
- **Fleet Management & P2H**: Master vehicle fleet directory, digital pre-trip/post-trip P2H (*Pemeriksaan Pemeliharaan Harian*) checklists, Indonesian TNKB license plate validation, legal compliance tracking (KIR and STNK annual/5-year expirations), and physical QR code vehicle stickers.
- **Procurement & Purchasing**: 8-criteria supplier evaluation scoring, SAP export streaming parser (UTF-16LE tab-delimited files), automated BOM explosion, and predictive material demand forecasting.
- **HR, Locker & Employee Services**: Employee master profiles, locker assignments, company asset allocation, and internal IT/maintenance ticketing.
- **Approvals & Telemetry**: Multi-tier hierarchical document approval engine, real-time WebSocket notifications via Laravel Reverb, asynchronous job progress bars, and user activity telemetry.

---

## Tech Stack

- **Backend**: [Laravel 10](https://laravel.com), [PHP 8.1 - 8.3](https://php.net), [Livewire 3](https://livewire.laravel.com)
- **Frontend**: Blade Templates, [Bootstrap 5.3](https://getbootstrap.com), [Tailwind CSS 3.4](https://tailwindcss.com), [Vite 6](https://vitejs.dev), [Chart.js](https://www.chartjs.org/)
- **Database & Cache**: MySQL 8.0+ / MariaDB 10.5+, Redis
- **Realtime / WebSockets**: [Laravel Reverb](https://reverb.laravel.com), [Laravel Echo](https://laravel.com/docs/broadcasting)
- **Data Tables**: [Yajra Laravel DataTables](https://yajrabox.com/)
- **Testing & Quality Assurance**: [Pest PHP](https://pestphp.com), [PHPUnit](https://phpunit.de), [PHPStan / Larastan](https://github.com/larastan/larastan), [Laravel Pint](https://laravel.com/docs/pint)

---

## Prerequisites

Before running the project locally, ensure you have the following installed on your machine:

- **PHP**: `^8.1` or `8.3` (recommended)
- **Required PHP Extensions**: `bcmath`, `curl`, `gd`, `iconv`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`
- **Composer**: `^2.2`
- **Node.js**: `^18.x` or `^20.x` & **npm**: `^9.x`
- **MySQL / MariaDB**: `^8.0` / `^10.5`
- **Redis**: Optional for basic local testing, recommended for queues and Reverb broadcasting

---

## Getting Started

### 1. Clone the Repository

```bash
git clone https://github.com/your-org/DISS.git
cd DISS
```

### 2. Install Dependencies

Install Composer dependencies and Node packages:

```bash
composer install
npm install
```

### 3. Environment Configuration

Copy the example environment configuration and generate the application key:

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` in your editor and configure your database and application details:

```env
APP_NAME=DaijoIndustrialSupportSystem
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=diss_database
DB_USERNAME=your_mysql_user
DB_PASSWORD=your_mysql_password

BROADCAST_DRIVER=reverb
QUEUE_CONNECTION=sync
CACHE_DRIVER=file
```

### 4. Run Database Migrations & Seeds

```bash
php artisan migrate

# Optional: seed initial/demo master data if required
# php artisan db:seed
```

### 5. Link Storage Directory

Create the symbolic link from `public/storage` to `storage/app/public`:

```bash
php artisan storage:link
```

### 6. Build Frontend Assets

For development with Hot Module Replacement (HMR):

```bash
npm run dev
```

Or compile minified assets for production:

```bash
npm run build
```

### 7. Run the Application

```bash
php artisan serve
```

Access the application in your browser at `http://localhost:8000`.

---

## Docker / Laravel Sail Setup

If you prefer containerized development without installing PHP and MySQL on your host:

1. Copy the environment file:
   ```bash
   cp .env.example .env
   ```
2. Start the Sail Docker containers:
   ```bash
   ./vendor/bin/sail up -d
   # or: docker compose up -d
   ```
3. Run migrations inside the container:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```
4. Access the web server at `http://localhost` and the Mailpit email catcher at `http://localhost:8025`.

---

## Background Services & Realtime

DISS utilizes background jobs and WebSockets for long-running batch operations (such as SAP synchronization) and real-time alerts.

### Start the Reverb WebSocket Server

```bash
php artisan reverb:start
```

### Start the Queue Worker

```bash
php artisan queue:work
```

---

## Essential Artisan Commands

| Command | Description |
| :--- | :--- |
| `php artisan sap:sync` | Synchronize production, BOM, and demand data from SAP endpoints |
| `php artisan sap:sync --queue` | Run SAP sync asynchronously using batched queue jobs |
| `php artisan fleet:check-reminders` | Check KIR & STNK expiration deadlines and dispatch alert notifications |
| `php artisan verification:cleanup-drafts` | Clean up expired draft verification records |
| `php artisan employee-dashboard:update-from-api` | Sync employee records and dashboard data from external HR API |

---

## Code Quality & Testing

We enforce clean code, type safety, and automated test coverage:

```bash
# Run entire test suite (Pest / PHPUnit)
composer test

# Run PHPStan static analysis (Level 4+)
composer stan

# Check code styling without making changes
composer pint:test

# Auto-fix PHP code style with Laravel Pint
composer pint

# Format Blade templates, JS/SCSS, and PHP in one go
npm run format

# Run full quality check (Pint + PHPStan + Test Suite)
composer quality
```

---

## Contributing

We welcome contributions from team members and collaborators. Please see [CONTRIBUTING.md](CONTRIBUTING.md) for branch naming conventions, PR guidelines, and development standards.

---

## License

This project is open-sourced under the [MIT License](LICENSE) for PT Daijo Industrial internal and authorized partner use.
