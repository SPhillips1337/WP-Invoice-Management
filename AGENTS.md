# Agent Context: WP Invoice Management

This file provides critical context for AI coding assistants and autonomous agents working on this repository.

## 🧠 Core Architecture Insights

### 1. The Frontend "Shadow" Route
The primary frontend UI is **not** a standard WordPress admin page. It is triggered by the query parameter `?invoice_editor=1`.
- **Reasoning**: This bypasses heavy WP Admin CSS/JS to provide a clean, modern, application-like experience.
- **Related Files**: 
    - `templates/invoice-editor.php` (The template loader)
    - `assets/js/invoice-editor.js` (The core logic)

### 2. REST API Integration
The frontend editor is tightly coupled with custom REST endpoints.
- **Base Namespace**: `wp-invoice/v1`
- **Endpoints**: `/invoices` (GET/POST/PUT/DELETE)
- **Logic**: All calculations happen in JS on the frontend and are validated/stored via the REST API in PHP.

### 3. Data Persistence
- **Custom Post Types**: Uses `wp_invoice` for invoices and `wp_customer` for clients.
- **Line Items**: Stored as serialized JSON in the `_invoice_items` post meta field. Do not try to find a separate database table for line items.
- **PDFs**: Generated on-the-fly via `dompdf` using the `?wp_invoice_pdf={id}` route.

## 🛠 Working Guidelines for Agents

- **Hook Order**: Ensure you are hooking into `init` or `template_redirect` when dealing with the custom routes (`?invoice_editor` or `?wp_invoice_pdf`).
- **Styles**: Use the project's CSS tokens. The design aim is "Premium" and "Modern". Avoid default browser or generic WP styling.
- **Memory**: Always check `.antigravity/memories/` for historical architectural decisions and learned patterns.
- **Plan Reference**: Consult `project.json` for the current development phase and upcoming tasks.

## 📁 Key Directories
- `wp-invoice-management/src/`: PSR-4 compliant PHP source code.
- `wp-invoice-management/templates/`: UI templates.
- `wp-invoice-management/assets/`: Compiled/source JS and CSS.
