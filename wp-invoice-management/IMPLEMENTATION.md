# Phase 1 Implementation Log

## Features Being Built:

### 1. Custom Post Statuses ✗ DONE
- `draft` - Invoices in draft status won't be sent
- `sent` - Invoice has been sent to client  
- `viewed` - Client has viewed the invoice
- `paid` - Invoice is fully paid
- `overdue` - Invoice is past due date
- `cancelled` - Invoice has been cancelled

### 2. Invoice Number Sequences ✗ DONE
- Auto-increment invoice numbers
- Configurable prefix (e.g., "INV-2024-001")
- Year-based numbering option
- Custom invoice number override

### 3. AJAX Line Item Management ✗ DONE
- Add/remove line items without page reload
- AJAX-based field updates
- Real-time total calculation

### 4. Real-time Calculations ✗ DONE
- JavaScript auto-calc of item totals
- Running subtotal/tax/total as you type
- Discount application calculation

### 5. Settings Page ✗ DONE  
- Branding settings (company name, logo, address)
- Invoice defaults (currency, tax rate, due days)
- Email templates
- PDF settings

### 6. Tax Categories ✗ DONE
- Multiple tax rates
- Tax categories (Standard, Reduced, Zero, Exempt)
- Tax calculation modes (inclusive, exclusive)

### 7. Discount Types ✗ DONE
- Percentage vs fixed amount discounts
- Item-level vs invoice-level discounts
- Discount stacking rules

---

## Date Started: 2026-03-31 14:26:52

## Implementation Notes:
