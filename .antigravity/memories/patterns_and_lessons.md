# Patterns and Lessons learned

## Success Patterns

### 1. ClawTeam Agent Swarm Development
- **Context:** Using the ClawTeam Agent Swarm orchestration framework to build out the core CPTs, REST API endpoints, and PDF generation templates.
- **Pattern:** Using specialized agents (architect, backend developer, UI developer) running concurrently speeds up scaffold generation and feature implementation significantly.

### 2. AJAX-based Line Item Management
- **Context:** Building the invoice line item editor.
- **Pattern:** Using Vanilla JS for dynamic DOM manipulation (add/remove line items) and recalculating totals directly on the client, synced via a custom REST API endpoint (`/wp-json/wp-invoice/v1/invoices`), makes the app feel extremely responsive compared to standard WP form submissions.

### 3. Base64 for PDF Logos
- **Context:** `Dompdf` often fails to resolve image paths or URLs in complex environments (Docker, non-standard ports).
- **Pattern:** Convert local image files to Base64 data URIs directly in the PHP template for PDFs. This makes the PDF self-contained and avoids all networking/permission issues during generation.

## Failure Lessons (Drag)

### 1. Dompdf File Path Resolution
- **Context:** There was a critical error occurring during PDF generation via the `dompdf` library integration.
- **Lesson:** Absolute server file paths are better than URLs for `dompdf`, but Base64 encoding the image data is the most robust solution for local development environments.

### 2. REST API Mapping & Sanitization
- **Context:** Data was failing to save because of naming mismatches between JS (`from`) and PHP (`_invoice_from`).
- **Lesson:** Using an explicit mapping array in `save_invoice_meta` and specific sanitization (`sanitize_textarea_field` for addresses/notes) is much safer than dynamic string replacement. It prevents silent data loss and preserves formatting.
