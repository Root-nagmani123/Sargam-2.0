# Sargam 2.0

[![Laravel](https://img.shields.io/badge/Laravel-9.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A comprehensive enterprise management system built with Laravel, designed to handle institutional operations, estate management, course administration, and more.

## 📋 Table of Contents

- [About](#about)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Testing](#testing)
- [Project Structure](#project-structure)
- [Contributing](#contributing)
- [License](#license)

## 🎯 About

Sargam 2.0 is a robust enterprise resource management system that provides comprehensive solutions for:

- **Estate Management**: Handle building floors, room mappings, blocks, and approval workflows
- **Academic Administration**: Manage courses, disciplines, and eligibility criteria
- **User Management**: Role-based access control with LDAP integration
- **Document Management**: PDF generation, Excel import/export capabilities
- **Real-time Data**: Interactive datatables with advanced filtering and export options

## ✨ Features

### Core Functionality
- 🏢 **Estate Management System**
  - Building and floor mapping
  - Room allocation and tracking
  - Estate change request workflow
  - Electric slab management
  - Block administration

- 📚 **Course & Discipline Management**
  - Course master data administration
  - Discipline categorization
  - Eligibility criteria configuration

- 👥 **User & Permission Management**
  - LDAP/Active Directory integration
  - Role-based access control (RBAC)
  - Fine-grained permission management

- 📊 **Data Management**
  - Interactive DataTables with server-side processing
  - Excel import/export functionality
  - Advanced filtering and search
  - Bulk operations support

- 📄 **Document Generation**
  - PDF generation (DOMPDF & MPDF)
  - Template-based document creation
  - Digital signature support

- 🔒 **Security Features**
  - CAPTCHA integration
  - CSRF protection
  - Secure authentication
  - Activity logging

## 🛠️ Technology Stack

### Backend
- **Framework**: Laravel 9.x
- **Language**: PHP 8.0+
- **Database**: MySQL/PostgreSQL
- **Authentication**: Laravel Sanctum

### Frontend
- **CSS Framework**: Bootstrap/Tailwind
- **JavaScript**: Laravel Mix, Vite
- **UI Components**: Livewire
- **DataTables**: Yajra DataTables

### Key Packages
- **LDAP**: Adldap2 Laravel
- **Permissions**: Spatie Laravel Permission
- **Excel**: Maatwebsite Excel
- **PDF**: DOMPDF, MPDF
- **Storage**: Azure Blob Storage
- **Logging**: Arcanedev Log Viewer
- **CAPTCHA**: Mews Captcha

## 📦 Requirements

- PHP >= 8.0
- Composer
- Node.js >= 14.x
- NPM or Yarn
- MySQL >= 5.7 or PostgreSQL >= 10
- Web Server (Apache/Nginx)
- LDAP Server (optional, for AD integration)

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd Sargam-2.0
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 3. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Setup
```bash
# Update .env with your database credentials
# Then run migrations
php artisan migrate

# Seed the database (optional)
php artisan db:seed
```

### 5. Storage Setup
```bash
# Create symbolic link for storage
php artisan storage:link
```

### 6. Build Assets
```bash
# For development
npm run dev

# For production
npm run build
```

### 7. Start Development Server
```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

## ⚙️ Configuration

### LDAP/Active Directory Setup
Update `.env` with your LDAP credentials:
```env
LDAP_LOGGING=true
LDAP_CONNECTION=default
LDAP_HOST=your-ldap-server
LDAP_USERNAME=your-username
LDAP_PASSWORD=your-password
LDAP_BASE_DN=dc=example,dc=com
```

### Azure Blob Storage
Configure Azure storage in `.env`:
```env
AZURE_STORAGE_NAME=your-storage-account
AZURE_STORAGE_KEY=your-storage-key
AZURE_STORAGE_CONTAINER=your-container
```

### Mail Configuration
```env
MAIL_MAILER=smtp
MAIL_HOST=your-mail-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

## 📖 Usage

### Default Routes
- **Admin Panel**: `/admin`
- **API Endpoints**: `/api/*`
- **Faculty Routes**: `/fc/*` (see `routes/fc_route.php`)
- **Master Data**: `/master/*` (see `routes/master.php`)

### Running Background Jobs
```bash
php artisan queue:work
```

### Clearing Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 🧪 Testing

### Unit & Feature Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### E2E Tests with Playwright
```bash
# Install Playwright browsers
npx playwright install

# Run E2E tests
npx playwright test

# View test report
npx playwright show-report
```

## 📁 Project Structure

```
Sargam-2.0/
├── app/
│   ├── Console/         # Artisan commands
│   ├── DataTables/      # DataTable classes
│   ├── Exports/         # Excel export classes
│   ├── Http/            # Controllers, Middleware
│   ├── Imports/         # Excel import classes
│   ├── Models/          # Eloquent models
│   ├── Services/        # Business logic services
│   └── helpers.php      # Helper functions
├── config/              # Configuration files
├── database/
│   ├── migrations/      # Database migrations
│   └── seeders/         # Database seeders
├── public/              # Public assets
├── resources/
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   └── views/           # Blade templates
├── routes/
│   ├── web.php          # Web routes
│   ├── api.php          # API routes
│   ├── master.php       # Master data routes
│   └── fc_route.php     # Faculty routes
├── storage/             # Storage & logs
└── tests/               # Test files
    ├── Unit/            # Unit tests
    ├── Feature/         # Feature tests
    └── e2e/             # E2E tests
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Coding Standards
- Follow PSR-12 coding standards
- Write meaningful commit messages
- Add tests for new features
- Update documentation as needed

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and queries:
- Create an issue in the repository
- Contact the development team
- Check the documentation

---

**Built with ❤️ using Laravel**
