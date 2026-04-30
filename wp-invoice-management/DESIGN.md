# Design Specifications: WP Invoice Management

This document outlines the visual and architectural design for the WP Invoice Management plugin.

## Visual Design

### 1. Primary Colors & Typography
- **Core Font**: Inter or Roboto (Modern, clean, legible).
- **Primary Color**: Deep Blue/Dark Gray (Classic Theme) or Slate/Blue (Slate Theme).
- **Secondary Color**: Vibrant Green (for "Paid" status) or Orange (for "Open" status).
- **Backgrounds**: Light Gray (#f9f9f9) for the main dashboard and a white paper-like contrast for the editor.

### 2. High-Fidelity UI Components
- **Dashboard Table**: Modern, responsive table with large, readable fonts and status badges.
- **Invoice Editor**: Card-based layout with a "Paper" feel. Input fields should be minimal, only appearing as inputs on focus/hover (similar to invoice-generator.com).
- **Action Buttons**: Stylized with subtle micro-animations (hover effects, transitions).

### 3. Responsive Layout
- Full desktop width for intensive invoice editing.
- Stacked mobile view for the dashboard and line item listing.

## Architectural Design

### 1. Data Schema (WordPress CPT)
**Custom Post Type: `wp_invoice`**
- **Title**: Invoice Reference Number.
- **Meta Fields**:
  - `_invoice_logo_id`: Media ID for company logo.
  - `_invoice_from`: Text block for sender info.
  - `_invoice_to`: Text block for recipient info.
  - `_invoice_ship_to`: Text block for shipping info.
  - `_invoice_date`: Invoice issuance date.
  - `_invoice_due_date`: Invoice due date.
  - `_invoice_po_number`: Reference for Purchase Order.
  - `_invoice_items`: Serialized JSON for line items (Description, Quantity, Rate, Amount).
  - `_invoice_notes`: Footer notes.
  - `_invoice_terms`: Footer terms.
  - `_invoice_tax`: Tax value.
  - `_invoice_discount`: Discount value.
  - `_invoice_shipping`: Shipping value.
  - `_invoice_amount_paid`: Amount already paid.
  - `_invoice_status`: Status ('open', 'paid').

**Custom Post Type: `wp_customer`**
- **Title**: Customer Name.
- **Meta Fields**:
  - `_customer_address`: Full address.
  - `_customer_email`: Billing email.

### 2. Service Layer (PDF Engine)
- **Engine**: dompdf.
- **Template System**: HTML/CSS based templates, ensuring easy customization of branding.
- **Caching**: Optional caching of generated PDFs for performance.

### 3. REST API Integration
- For the real-time editor, a custom REST API endpoint (`/wp-json/wp-invoice/v1/invoices`) will handle:
  - Saving invoice drafts.
  - Fetching customer details.
  - Triggering PDF generation.

## User Experience (UX)
- **Auto-Calculations**: Totals MUST update instantly as line items or tax rates are modified.
- **Seamless Saving**: AJAX-based saving in the editor to prevent data loss.
- **Quick Actions**: Inline "Mark as Paid" and "Download PDF" links in the dashboard.
