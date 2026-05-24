# Project Tasks: WP Invoice Management

A task-based roadmap for building the WP Invoice Management plugin.

## Phase 1: Project Setup & Foundation
- [x] Initialize WordPress Plugin structure.
- [x] Setup Composer and install `dompdf`.
- [x] Register Custom Post Types: `wp_invoice` and `wp_customer`.
- [x] Implement Custom Post Meta fields for both CPTs.

## Phase 2: Backend Logic & REST API
- [x] Create REST API endpoints for Invoice and Customer management.
- [x] Implement Invoice status toggling logic (Mark as Paid/Open).
- [ ] Add Invoice duplication functionality.
- [ ] Setup Global Settings page in WordPress Admin.

## Phase 3: Invoice Editor UI
- [x] Build the Editor UI (Vanilla JS).
- [x] Implement real-time calculations for subtotal, tax, discounts, and total.
- [x] Add dynamic line item management (Add/Remove items).
- [x] Integrate WordPress Media Uploader for logo uploading.
- [ ] Build recipient/customer selection from the `wp_customer` CPT.

## Phase 4: Dashboard & Listing
- [x] Customize the Invoice List View (WP List Table).
- [x] Add status badges (Open, Paid).
- [x] Implement Quick Actions in the table rows.
- [ ] Add search and filtering capabilities.

## Phase 5: PDF Generation & Templates
- [x] Create the PDF template (HTML/CSS).
- [x] Integrate `dompdf` for one-click PDF generation.
- [x] Build the PDF download/stream logic.

## Phase 6: Themes & Polish
- [ ] Implement "Classic" and "Slate" themes for the editor and PDFs.
- [ ] Add micro-animations and styling for a premium feel.
- [ ] Final UI/UX review and bug fixes.
- [ ] Test on different devices and browsers.

---

## Progress Summary (as of March 30, 2026)

### Completed:
- Plugin structure with CPTs for Invoices and Customers
- Meta fields for invoice details (logo, from/to, dates, line items, totals, status)
- REST API endpoints at `/wp-json/wp-invoice/v1/invoices`
- Frontend invoice editor at `?invoice_editor=1` (similar to invoice-generator.com)
- Real-time line item calculations
- WordPress Media Uploader integration for logo
- PDF generation using dompdf
- Invoice view template at `?wp_invoice={id}`

### Files Created:
- `src/CPT/Invoice.php` - Invoice CPT registration and meta boxes
- `src/CPT/Customer.php` - Customer CPT registration and meta boxes
- `src/API/REST_API.php` - REST API endpoints
- `src/Plugin.php` - Main plugin class
- `templates/invoice-editor.php` - Frontend editor template
- `assets/css/invoice-editor.css` - Editor styles
- `assets/js/invoice-editor.js` - Editor JavaScript

### Access Points:
- Frontend Editor: your-site.com/?invoice_editor=1
- Invoice View: your-site.com/?wp_invoice={id}
- PDF Download: your-site.com/?wp_invoice_pdf={id}
