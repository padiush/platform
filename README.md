# Padiush

## About the Project
Padiush is a web-based bioinformatics tool designed to simplify the collection, processing, and export of data in ethnobotanical research. The system aims to streamline workflows, making data handling more efficient for researchers in this field.

## Features
- **Form Designer**: A feature that allows users to create and customize data collection forms.
- **Cross-Platform Accessibility**: Accessible through any modern web browser.

## Technologies
The project utilizes the following technologies:

### Backend
- **Laravel Framework**: `^12.0` - A PHP framework for robust and scalable server-side operations.
- **Laravel Sanctum**: `^4.0` - For API authentication.
- **Inertia.js (Laravel Adapter)**: `^2.0` - For server-side rendering and SPA support.
- **Doctrine DBAL**: `^4.0` - Database abstraction layer.
- **Maatwebsite Excel**: `^3.1` - For spreadsheet handling.
- **Spatie SEO Tools**: `^1.2` - For SEO optimization.
- **Spatie Sitemap**: `^7.2.0` - For sitemap generation.

### Frontend
- **React**: `^19.1.0` - A JavaScript library for building user interfaces.
- **Inertia.js (React)**: `^2.0.8` - For React integration with Laravel.
- **TailwindCSS**: `^4.1.5` - A utility-first CSS framework.
- **DaisyUI**: `^5.0.35` - Components for TailwindCSS enhancement.
- **Vite**: `^6.3.4` - For fast and efficient frontend builds.

### Additional Tools
- **Testing Tools**:
  - PHPUnit: `^11.0`
  - Mockery: `^1.4.4`
- **Development Tools**:
  - Laravel Breeze: `^2.0` - For scaffolding authentication.
  - Laravel Pint: `^1.0` - For code formatting.

## Getting Started
To set up the project locally, follow these steps:

### Prerequisites
- PHP 8.2 or higher
- Node.js and npm
- Composer

### Installation
1. Clone the repository:
    ```bash
    git clone https://github.com/raarevalo96/padiush.git
    ```
2. Navigate to the project directory:
    ```bash
    cd padiush
    ```
3. Install dependencies:
    ```bash
    composer install
    npm install
    ```
4. Run database migrations:
    ```bash
    php artisan migrate
    ```
5. Start the development server:
    ```bash
    php artisan serve
    ```
