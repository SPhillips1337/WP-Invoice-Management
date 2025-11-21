# WP Invoice Management Plugin: Development Plan

This document outlines the development plan for creating a multi-user invoice management plugin for WordPress, similar in functionality to invoice-generator.com.

---

### Phase 1: Project Setup & Foundation

The goal of this phase is to ensure we have a stable development environment and a solid foundation for the plugin.

1.  **Docker Environment:** Set up and confirm a working Docker environment with WordPress and MySQL. (✓ Done)
2.  **Dependencies:** Install all PHP dependencies using Composer. (✓ Done)
3.  **Plugin Activation:** Log into the WordPress admin panel at `http://localhost:8000`, activate the plugin, and confirm it runs without fatal errors.
4.  **File Structure:** Create a logical directory structure within `src/` for different components like `CPT` (Custom Post Types), `Admin`, `Frontend`, `API`, and `Lib`.

---

### Phase 2: Core Invoice Functionality

This phase focuses on creating the central data structure for invoices.

1.  **Custom Post Type (CPT):**
    *   Register a new Custom Post Type named `wpinv_invoice` with a user-friendly name like "Invoices".
    *   Define the CPT's supports (e.g., title, editor, author, custom-fields).

2.  **Invoice Data (Meta Fields):**
    *   Add custom meta fields to the invoice CPT to store essential data:
        *   **Client Details:** Client Name, Address, Email.
        *   **Line Items:** A repeatable group of fields for Description, Quantity, and Price.
        *   **Dates:** Issue Date, Due Date.
        *   **Financials:** Subtotal, Tax, Total, Currency.
        *   **Status:** Draft, Sent, Paid, Overdue.
    *   *Recommendation:* We can build these meta boxes manually or use a library like Advanced Custom Fields (ACF) to accelerate this process.

3.  **Business Logic:**
    *   Implement PHP functions to automatically calculate the subtotal and total whenever an invoice is saved.
    *   Create a mechanism to update the invoice status.

---

### Phase 3: User Roles & Permissions

This phase ensures that the multi-user requirements are met securely.

1.  **Custom User Roles:**
    *   Consider creating a custom "Client" role or extending the capabilities of the default "Subscriber" role.
    *   An "Administrator" or "Editor" would serve as the invoice manager.

2.  **Access Control:**
    *   Implement strict permission checks to ensure users can only view and manage their own invoices.
    *   Administrators should be able to view and manage all invoices.
    *   This will be enforced on the backend for all data queries and on the frontend for all views.

---

### Phase 4: Frontend Interface & PDF Generation

This phase focuses on what the user sees and interacts with.

1.  **Invoice Dashboard:**
    *   Create a new page in WordPress and add a shortcode `[invoice_dashboard]` to it.
    *   The shortcode will render a dashboard showing a list of the current user's invoices, their statuses, and links to view each one.

2.  **Single Invoice View:**
    *   Create a template for displaying a single invoice. This template should be clean, professional, and easy to read, drawing inspiration from invoice-generator.com.
    *   This view will be protected, so only the assigned user or an admin can see it.

3.  **PDF Generation:**
    *   Integrate the `dompdf` library (already in `composer.json`).
    *   Add a "Download PDF" button to the single invoice view that generates a PDF version of the invoice.

---

### Phase 5: API & Advanced Features (Optional)

This phase adds advanced functionality for extensibility and better user experience.

1.  **REST API Endpoints:**
    *   Create custom REST API endpoints for securely creating, reading, updating, and deleting invoices (CRUD).
    *   This will allow for future integrations, such as a dedicated JavaScript-based frontend.

2.  **Email Notifications:**
    *   Implement email notifications for key events, such as when a new invoice is issued or when an invoice becomes overdue.

3.  **Payment Gateways:**
    *   Explore integrating with payment gateways like Stripe or PayPal to allow clients to pay their invoices directly.

---

### Phase 6: Testing & Deployment

The final phase is to ensure the plugin is robust and ready for use.

1.  **Unit & Integration Testing:**
    *   Set up a testing framework (e.g., PHPUnit).
    *   Write tests for critical business logic (e.g., invoice total calculations, permission checks).
    *   Test the API endpoints.

2.  **Deployment:**
    *   Document the final steps for deploying the plugin to a live server.
    *   Ensure all development-related files are excluded from the final plugin package.
