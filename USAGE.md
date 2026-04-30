# Usage Guide

This document explains how to use the WP Invoice Management plugin.

## 🏁 Getting Started

### Prerequisites
- Docker and Docker Compose installed.
- Access to a web browser.

### Initial Setup
1.  Run `docker-compose up -d`.
2.  Access WordPress at `http://localhost:9992`.
3.  Login:
    - **Username**: `admin`
    - **Password**: `password123`
4.  Navigate to **Plugins** and ensure **WP Invoice Management** is active.

## 📝 Managing Invoices

### The Frontend Editor
One of the core features is the reactive frontend editor.
- **URL**: `http://localhost:9992/?invoice_editor=1`
- **Features**: 
    - Real-time calculation of subtotals and totals.
    - Seamless saving via REST API.
    - Professional layout inspired by modern invoicing tools.

### Dashboard
Users can view their invoices via the `[invoice_dashboard]` shortcode. Place this shortcode on any WordPress page to create a user-facing dashboard.

## 👥 Customer Management
Customers are managed as a separate entity to allow for reusable billing details.
- Go to the WordPress Admin > Invoices > Customers to add or edit client details.

## 📥 PDF Generation
Once an invoice is created, you can generate a PDF version:
- **Direct Link**: `http://localhost:9992/?wp_invoice_pdf={invoice_id}`
- Look for the **Download PDF** button on the single invoice view or within the dashboard.

## 🔐 Permissions
- **Administrators**: Can view and edit all invoices across all users.
- **Subscribers/Clients**: Can only see and manage invoices they created or that were assigned to them.
