# Architectural Decision Record: Core Tech Stack

**Status:** Active
**Date:** 2026-03-31

## Context
We needed a way to build a robust, multi-user invoice management application on top of WordPress, while ensuring it feels like a modern SaaS product (e.g. invoice-generator.com) with real-time editing and PDF exporting.

## Decisions

### 1. Storage: WordPress Custom Post Types (CPTs)
We chose to use `wp_invoice` and `wp_customer` CPTs instead of custom DB tables.
**Tradeoffs:**
- *Pros:* Takes advantage of built-in WP querying, user permissions (assigning posts to authors), and meta data APIs. Simplifies the REST API creation.
- *Cons:* Serialized JSON for line items (`_invoice_items`) makes it slightly harder to query globally by specific items, but this isn't a current requirement.

### 2. PDF Engine: Dompdf
We selected `dompdf` via Composer for generating PDF invoices.
**Tradeoffs:**
- *Pros:* Native PHP, works well with standard HTML/CSS templates, easy to bundle in a WordPress plugin.
- *Cons:* Rendering engines can be finicky with modern flexbox/grid layouts and file paths constraints within WP environments.

### 3. Editor UI: Vanilla JS & Custom Endpoints
We opted for a custom frontend interface over extending the Gutenberg block editor or using standard meta boxes.
**Tradeoffs:**
- *Pros:* Allows for real-time calculations (tax, total, discounts) natively on the client side without constant page reloads, providing a premium feel.
- *Cons:* Disconnects the UI slightly from standard WordPress theming, requiring standalone responsive CSS.
