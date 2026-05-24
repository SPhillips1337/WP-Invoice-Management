# Design System: WP Invoice Management

This document defines the visual language, design tokens, and components for the WP Invoice Management plugin.

## Design Principles
- **Premium**: High-quality interactions and visual polish.
- **Modern**: Clean, spacious, and state-of-the-art aesthetics.
- **Intuitive**: Contextual tools and clear hierarchies.

## Visual Tokens

### Colors
| Name | Token | Value |
| :--- | :--- | :--- |
| Primary | `--primary-color` | `#2563eb` |
| Primary Hover | `--primary-hover` | `#1d4ed8` |
| Secondary | `--secondary-color` | `#64748b` |
| Success | `--success-color` | `#16a34a` |
| Danger | `--danger-color` | `#dc2626` |
| Warning | `--warning-color` | `#d97706` |
| Background | `--bg-color` | `#f8fafc` |
| Surface | `--surface-color` | `#ffffff` |
| Border | `--border-color` | `#e2e8f0` |
| Text Primary | `--text-primary` | `#1e293b` |
| Text Secondary | `--text-secondary` | `#64748b` |
| Text Muted | `--text-muted` | `#94a3b8` |

### Typography
- **Primary Font**: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif
- **Scale**:
  - `h1`: 30px (Dashboard Title), 20px (Editor Header)
  - `h3`: 14px (Section Headers, Uppercase, 0.05em tracking)
  - `body`: 14px (Default text, inputs)
  - `small`: 12px (Meta text, utility buttons)

### Layout & Spacing
- **Container Max-Width**: 1200px
- **Standard Padding**: 24px
- **Grid Gaps**: 40px (Header Columns), 32px (Dashboard Rows)
- **Border Radius**: 
  - `sm`: 4px
  - `md`: 8px
  - `lg`: 12px

### Shadows
- `shadow-sm`: 0 1px 2px 0 rgb(0 0 0 / 0.05)
- `shadow-md`: 0 4px 6px -1px rgb(0 0 0 / 0.1)

## UI Components

### 1. Address Sections (From/Bill To)
- **Header**: Flex layout with `justify-content: flex-start` and `gap: 15px`.
- **Textarea**: `min-height: 120px`, `14px` font, `1.5` line-height.
- **Helpers**: "Use Default" button and Customer selection dropdown placed inline with the header title.

### 2. Invoice List Items
- **Hover State**: Subtle background transition with increased shadow.
- **Active State**: Primary color border or background highlight.
- **Meta Row**: Status badge on the left, date on the right.

### 3. Status Badges
- **Draft**: Neutral gray.
- **Sent**: Primary blue.
- **Paid**: Success green.
- **Overdue**: Danger red.

## Implementation Guidelines
- **Responsive**: Use `grid` and `flex` with appropriate gaps to ensure fluidity.
- **Interactions**: All buttons and interactive elements must have `0.2s` transitions for `all` or `background/color`.
- **PDF Assets**: Use Base64 encoding for logos in PDF templates to bypass filesystem/URL resolution issues.

## Architectural Design

### 1. Data Schema (WordPress CPT)
**Custom Post Type: `wp_invoice`**
- **Meta Fields**: `_invoice_logo_id`, `_invoice_from`, `_invoice_to`, `_invoice_date`, `_invoice_due_date`, `_invoice_items` (JSON), `_invoice_tax`, `_invoice_status`, etc.

**Custom Post Type: `wp_customer`**
- **Meta Fields**: `_customer_name`, `_customer_company`, `_customer_address`, `_customer_email`, `_customer_phone`, `_customer_url`.

### 2. REST API Integration
- **Namespace**: `wp-invoice/v1`
- **Endpoints**:
  - `/invoices` (GET/POST/PUT/DELETE)
  - `/customers` (GET/POST/PUT/DELETE)
  - `/settings` (GET/POST)

## User Experience (UX)
- **Auto-Calculations**: Totals update instantly via JS logic.
- **Seamless Saving**: AJAX-based saving in the editor to prevent data loss.
- **Quick Actions**: Mark as Paid, Download PDF, and Edit available from the Dashboard.
