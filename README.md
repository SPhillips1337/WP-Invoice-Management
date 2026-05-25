# WP Invoice Management

A multi-user invoicing plugin for WordPress, designed to provide a seamless experience.

## 🚀 Overview

WP Invoice Management allows users to create, manage, and download professional invoices directly from a WordPress site. It features a custom frontend editor for a reactive, application-like feel while maintaining the robustness of the WordPress backend.

## Demo
A demo is available at https://invoices.happymonkey.ai/ (Expect bugs though as still in Beta testing)

## 🛠 Tech Stack

- **PHP**: Core logic and WordPress integration.
- **WordPress**: Content management and user authentication.
- **MySQL**: Database for invoices and customer data.
- **Composer**: Dependency management.
- **dompdf**: High-quality PDF generation.
- **Vanilla JS**: Interactive frontend editor.

## 📦 Installation

### Installer script

A hardened installer is available for fresh checkouts or existing local copies. It validates an existing directory before modifying it, installs Composer dependencies, and supports production or development dependency sets.

Inspect the script before running it:

```bash
curl -fsSL https://raw.githubusercontent.com/SPhillips1337/WP-Invoice-Management/main/install.sh -o install.sh
less install.sh
bash install.sh --dir ./WP-Invoice-Management
```

For a trusted shell, you can run the downloaded script directly:

```bash
curl -fsSL https://raw.githubusercontent.com/SPhillips1337/WP-Invoice-Management/main/install.sh | bash -s -- --dir ./WP-Invoice-Management
```

Installer options:

```bash
./install.sh --help
./install.sh --dir ~/src/WP-Invoice-Management --dev      # include PHPUnit/WP_Mock dev packages
./install.sh --dir ./WP-Invoice-Management --no-composer # clone/validate only
```

After installation, copy or symlink `WP-Invoice-Management/wp-invoice-management` into your WordPress `wp-content/plugins/` directory and activate **WP Invoice Management** in the WordPress admin panel.

### Manual development setup

1. **Clone the repository**:
   ```bash
   git clone https://github.com/SPhillips1337/WP-Invoice-Management.git
   cd WP-Invoice-Management
   ```

2. **Install dependencies**:
   ```bash
   composer install --working-dir=wp-invoice-management
   # or, if Composer is not installed locally:
   docker run --rm --interactive --tty --volume "$(pwd)/wp-invoice-management:/app" composer:2 install
   ```

3. **Activate Plugin**:
   Copy or symlink `wp-invoice-management` into `wp-content/plugins/`, then activate the **WP Invoice Management** plugin from your WordPress admin panel.

## 📜 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
