---
design-system:
  version: 1.0.0
  name: WP Invoice Management Design System
  tokens:
    colors:
      brand:
        primary: "#2563eb"
        primary-hover: "#1d4ed8"
        secondary: "#64748b"
      semantic:
        success: "#16a34a"
        danger: "#dc2626"
        danger-hover: "#b91c1c"
        warning: "#d97706"
      neutral:
        background: "#f8fafc"
        surface: "#ffffff"
        border: "#e2e8f0"
        text-primary: "#1e293b"
        text-secondary: "#64748b"
        text-muted: "#94a3b8"
    typography:
      fontFamily: "'Inter', -apple-system, BlinkMacSystemFont, sans-serif"
      sizes:
        h1: "30px"
        h2: "20px"
        h3: "14px"
        body: "14px"
        small: "12px"
    borders:
      radius:
        sm: "4px"
        md: "8px"
        lg: "12px"
    shadows:
      sm: "0 1px 2px 0 rgb(0 0 0 / 0.05)"
      md: "0 4px 6px -1px rgb(0 0 0 / 0.1)"
---

# Design System Specification (WP Invoice Management)

This specification defines the visual language, coding guidelines, and interface standards for the WP Invoice Management system. It acts as the machine-readable design authority for both developers and autonomous AI coding agents.

---

## 1. Product Overview & Tone
WP Invoice Management aims to provide a **Premium**, **Modern**, and **Highly Interactive** single-page application feel within the WordPress theme context. The interface relies on clean lines, a structured grid, glassmorphism overlays, and smooth transition feedback.

---

## 2. Interface Layout & Spacing
- **Responsive Fluid Layout:** All core dashboard views and editor elements must adjust fluidly to varying viewport widths.
- **Sidebar Panel:** The invoice list sidebar in the editor has a default width of `320px`, collapsing to `60px` on mobile viewports or when collapsed manually.
- **Grid Systems:** Use grid templates for multi-column inputs (e.g., 2-column header row in the editor, 4-column meta rows) with standardized gaps of `40px` and `16px`.

---

## 3. UI Component Standards

### 3.1 Buttons (`.btn`)
All buttons must use the base `.btn` class with standard padding (`8px 16px`), transition delays (`0.15s`), and border radii:
- **Primary Button (`.btn-primary`):** Filled with primary brand blue. Used for dominant page actions (Save, Register, Log In).
- **Secondary Button (`.btn-secondary`):** Bordered slate outline. Used for alternative page options (New Invoice, settings).
- **Danger Button (`.btn-danger`):** Red background. Restricted to critical destructive workflows (Delete Invoice).
- **Icon Button (`.btn-icon`):** Transparent background with hover transitions to primary blue and neutral background.

### 3.2 Status Badges (`.status-badge`)
Badges display the current billing status of invoices using soft background shades and strong typography contrast:
- **Draft:** Gray background (`#f1f5f9`), slate text (`#64748b`).
- **Open:** Light blue background (`#dbeafe`), blue text (`#2563eb`).
- **Sent:** Soft yellow background (`#fef3c7`), dark gold text (`#d97706`).
- **Paid:** Light green background (`#dcfce7`), green text (`#16a34a`).
- **Overdue:** Soft red background (`#fee2e2`), red text (`#dc2626`).

### 3.3 Textareas & Input Fields
- Inputs must use standard borders (`1px solid var(--border-color)`) and border-radius (`8px` / `var(--radius-md)`).
- On focus, inputs show a glowing transition with `border-color: var(--primary-color)` and a light primary shadow overlay.

### 3.4 Modals & Overlays
Modals must fade in gracefully using a background overlay (`rgba(30, 41, 59, 0.7)`) with a blur filter (`backdrop-filter: blur(16px)`).

---

## 4. Multi-Tenant Scope & Security Guardrails
- **Resource Ownership Validation:** The system is built around strict data boundaries. Non-admin users must only be able to view, query, modify, or export Invoices and Customers belonging to their own author ID.
- **Input Sanitization:** Any customer ID or parameters read from client-side deep links (hash parameters like `#customer-{id}`) must be validated via pattern checking (`/^\d+$/`) before parsing or executing REST calls.
- **XSS Prevention:** For client-side DOM injection in Javascript, never assign raw HTML variables to `innerHTML`. Use `textContent` or `innerText` to safely load text content.

---

## 5. Stitch / AI Guardrails (Do's and Don'ts)

- **DO** use the defined CSS variables (e.g., `var(--primary-color)`) instead of hardcoding raw hex values.
- **DO** ensure all buttons have proper hover, active, and disabled visual states.
- **DO** use CSS transitions (`transition: all 0.15s ease-in-out`) for micro-animations.
- **DON'T** use native browser dialog popups (`alert()`, `confirm()`, `prompt()`) in production workflows. Rely on custom built-in modal components.
- **DON'T** hardcode credential tokens, passwords, or fallback secrets in source code files.
