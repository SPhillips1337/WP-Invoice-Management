# WP Invoice Management

A multi-user invoicing plugin for WordPress, designed to provide a seamless experience similar to [invoice-generator.com](https://invoice-generator.com).

## 🚀 Overview

WP Invoice Management allows users to create, manage, and download professional invoices directly from a WordPress site. It features a custom frontend editor for a reactive, application-like feel while maintaining the robustness of the WordPress backend.

## 🛠 Tech Stack

- **PHP**: Core logic and WordPress integration.
- **WordPress**: Content management and user authentication.
- **MySQL**: Database for invoices and customer data.
- **Composer**: Dependency management.
- **dompdf**: High-quality PDF generation.
- **Vanilla JS**: Interactive frontend editor.

## 📦 Installation (Development)

This project includes a Docker Compose setup for easy local development.

1.  **Clone the repository**:
    ```bash
    git clone https://github.com/SPhillips1337/wp-invoice-management.git
    cd wp-invoice-management
    ```

2.  **Start the environment**:
    ```bash
    docker-compose up -d
    ```

3.  **Install dependencies**:
    ```bash
    docker run --rm --interactive --tty --volume "$(pwd)/wp-invoice-management:/app" composer install
    ```

4.  **Activate Plugin**:
    Log in to the WordPress admin panel at `http://localhost:9992` and activate the **WP Invoice Management** plugin.

## 📄 License

GPLv2 or later.
