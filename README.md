# Warm Booking System 🏨

> A comprehensive booking system for a single hotel, designed to prevent overbooking and support real-world resort operations.

## 🌟 Vision & Goals
*   **Stability First**: Prioritizes data accuracy and system reliability above all.
*   **Single Source of Truth**: Utilizes a centralized Booking Engine to manage room availability across all channels.
*   **Localized Operations**: Supports specific requirements such as manual slip verification and instant notifications.
*   **Scalable Monolith**: Built with a clean architecture that is easy to maintain and expand.

## 🛠 Tech Stack
*   **Framework**: [Laravel 12](https://laravel.com/)
*   **Frontend**: [Livewire 3](https://livewire.laravel.com/) & [Alpine.js](https://alpinejs.dev/)
*   **Styling**: [Tailwind CSS](https://tailwindcss.com/)
*   **Development**: PHP 8.4+ & MySQL
*   **Build Tool**: [Vite](https://vitejs.dev/)
*   **Starter Kit**: Laravel Breeze (Livewire Volt Stack)

## 🚀 Key Features (Roadmap)
*   **Booking Engine**: Real-time availability checking and locking using Database Transactions.
*   **Public Booking**: Customer-facing portal for searching and booking rooms.
*   **Back Office**: Staff dashboard for managing reservations, check-ins, and check-outs.
*   **Member System**: Loyalty program with member-only rates and points.
*   **Payment Verification**: Manual slip upload and automated payment gateway integration status.

## 📋 Getting Started
1.  Clone the repository:
    ```bash
    git clone https://github.com/thnakon/warm-booking-system.git
    ```
2.  Install dependencies:
    ```bash
    composer install
    npm install
    ```
3.  Configure your `.env` file and database connection.
4.  Run migrations and start the development server:
    ```bash
    php artisan migrate
    php artisan serve
    npm run dev
    ```

---
*Developed with ❤️ for Warm Booking System*
