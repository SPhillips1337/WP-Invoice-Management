# WP Invoice Management Core Components

## Overview
The WP Invoice Management plugin is designed to replicate an invoice-generator.com experience within WordPress.

## Hidden Knowledge & Non-Obvious Dependencies
- **Frontend Editor Route:** The frontend UI is triggered via a query parameter `?invoice_editor=1`, rather than a standard WP admin page. This allows for a clean, vanilla JS, React-like reactive experience without WP Admin overhead.
- **REST API Tight Coupling:** The frontend editor `assets/js/invoice-editor.js` relies strictly on custom REST endpoints at `/wp-json/wp-invoice/v1/invoices` for AJAX-based seamless saving.
- **Data Structure:** Instead of custom database tables, the system heavily leverages Custom Post Types (`wp_invoice`, `wp_customer`) and Post Meta. Invoice line items (`_invoice_items`) are stored as serialized JSON.
- **PDF Generation Dependency:** The PDF output relies on `dompdf`, injected via Composer in `wp-invoice-management/composer.json`. It is accessed via `?wp_invoice_pdf={id}`.

## Related Files
- `src/CPT/Invoice.php`
- `src/API/REST_API.php`
- `assets/js/invoice-editor.js` 
- `templates/invoice-editor.php`
