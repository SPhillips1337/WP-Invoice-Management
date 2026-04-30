# Patterns and Lessons learned

## Success Patterns

### 1. ClawTeam Agent Swarm Development
- **Context:** Using the ClawTeam Agent Swarm orchestration framework to build out the core CPTs, REST API endpoints, and PDF generation templates.
- **Pattern:** Using specialized agents (architect, backend developer, UI developer) running concurrently speeds up scaffold generation and feature implementation significantly.

### 2. AJAX-based Line Item Management
- **Context:** Building the invoice line item editor.
- **Pattern:** Using Vanilla JS for dynamic DOM manipulation (add/remove line items) and recalculating totals directly on the client, synced via a custom REST API endpoint (`/wp-json/wp-invoice/v1/invoices`), makes the app feel extremely responsive compared to standard WP form submissions.

## Failure Lessons (Drag)

### 1. Dompdf File Path Resolution
- **Context:** There was a critical error occurring during PDF generation via the `dompdf` library integration.
- **Lesson:** WordPress plugin file paths can be absolute constraints for `dompdf`. It is crucial to ensure that asset paths (like CSS or logos) supplied to `dompdf` point to absolute server file paths (`WP_PLUGIN_DIR`) rather than HTTP URLs, or else the `dompdf` engine may fail or time out trying to fetch external resources in local environments.
