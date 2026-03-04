# VGBM Property Manager (MVP + Portfolios)

This is a **starter WordPress plugin** for VGBM to manage:
- **Portfolios** (CPT: `vgbm_portfolio`) - client/account level
- **Properties** (CPT: `vgbm_property`) (linked to a portfolio)
- **Units** (CPT: `vgbm_unit`)
- **Renter tickets** (CPT: `vgbm_ticket`) - submit via front-end shortcode

## Install
1. Upload the plugin folder to `wp-content/plugins/` or install the ZIP via WP admin.
2. Activate **VGBM Property Manager (MVP + Portfolios)**.
3. Create a page "My request" and add shortcode: `[vgbm_ticket_form]`
4. Create a page "My tickets" and add shortcode: `[vgbm_my_tickets]`

## Roles
- `vgbm_manager` (manage properties/units/tickets)
- `vgbm_maintenance` (work on tickets)
- `vgbm_renter` (submit & view own tickets via shortcodes)

## Assign a unit to a renter
WP Admin → Users → Edit user → **VGBM Property Manager** → Assigned units.

## Roadmap ideas (next iterations)
- Contracts/leases, invoicing, SEPA exports
- Maintenance planning & vendor assignment
- Document vault per property/unit/tenant
- SLA timers, attachments, chat-like ticket updates
- Multi-language (WPML) and multi-company (multi-tenant)


## Portfolios (client/accounts)
Create a portfolio first, then link each property to a portfolio in the **Property details** metabox.


## Admin visibility note
WordPress **Administrators are super users** and will always see the **VGBM** admin menu and have full access.
VGBM roles (Manager/Maintenance) see only what they are allowed to access.

## Portfolio title auto-fill
If you leave the Portfolio title empty, the plugin will automatically set it to the **Company name** so it stays selectable.

## Listing UX tweaks
- CPTs are now registered under the **VGBM** menu slug, so after saving/updating you stay in the correct VGBM listing.
- All VGBM list screens always include the bulk-selection **checkbox** column.

## Row click selection (admin UX)
On VGBM list screens (Portfolios/Properties/Units/Tickets) you can now click anywhere on a row to toggle its checkbox selection.
Clicks on links/buttons/inputs behave normally (no toggling).

## Required title
All VGBM items (Portfolios/Properties/Units/Tickets) require a title to Publish.
If you try to publish without a title, the item will be saved as **Draft** and you will see an error message.
Portfolios auto-fill the title from **Company name** if the title is empty.

## Checkbox selection troubleshooting
v0.2.7 adds a late-priority list-table fix that re-injects the checkbox column if any other plugin/theme removes it.
Row-click selection was also made more robust by binding to table cells instead of the row element.

## Per-row checkbox visibility fix (v0.2.8)
If you only saw the header checkbox but not the per-row checkboxes, it was caused by missing post-specific capabilities
(e.g. edit_published_*, delete_private_*, etc.). v0.2.8 grants the full set of capabilities derived from each VGBM CPT's
capability object to Administrators and VGBM Manager, so row actions + row checkboxes work consistently.

# New module: Renters & Rental contracts (v0.3.0)

## Renters
Renters are **WordPress users** with the role **VGBM Renter**.
Under **VGBM → Renters** you can manage them (link to the Users screen filtered by role).

## Rental contracts
- CPT: `vgbm_contract`
- Link a contract to a **Unit**
- Link one or more **Renters** (WP users)
- Store start/end dates, rent amount, deposit, and status
- Upload/attach a contract document via the media library

## Secure document viewing
Contract documents are served via a **permission-checked** endpoint:
- Admins / VGBM managers always have access
- Renters only have access if they are linked to the contract

## Renter portal shortcode
- `[vgbm_my_contracts]` – shows the logged-in renter their contracts + secure document link.

## Change: Renters are not WordPress users by default (v0.3.1)
Renters are now stored as a separate entity (**CPT: vgbm_renter**).  
Linking a renter to a WordPress user is **optional** and only needed if you want a renter portal login.

# New module: Rent elements & yearly indexation (v0.3.2)

## Rent elements
Each rental contract can now contain multiple rent elements (monthly), for example:
- Base rent (kale huur)
- Service costs (servicekosten)
- Utilities (gas/water/electricity)

Each element can be marked as **Index yearly**.

## Indexation (batch)
Go to **VGBM → Indexation**:
- Choose the **Year** and **Rate (%)**
- Select one or more contracts
- Run **Test run** (preview) or **Apply** (saves results per contract)

Indexation results are stored per contract in the indexation history.
Later versions can add batch selection by property and portfolio.

# New module: Rent collection (v0.4.0)

This module adds a **best-practice rent collection workflow**:
- Generate monthly rent **charges** per active contract
- Record **payments** per charge (manual/bank transfer/etc.)
- Track statuses: unpaid / partial / overdue / paid
- Send and log **reminder emails** (with IBAN + payment reference)

## Where to find it
- **VGBM → Rent collection**
  - Generate: create monthly charges
  - Charges: track status, record payments, send reminders
  - Settings: configure IBAN, account name, reference template

## Notes / next steps
- Later: SEPA (incasso) exports, automatic bank matching, and batch selection per property/portfolio.

# Contract-specific billing rules (v0.4.1)

Each contract now supports:
- **Due day** (1-28) for monthly rent charges
- **Prorate partial months** (start/end mid-month)

## How it works
- When generating monthly charges, each contract uses its own due day if set; otherwise the default due day is used.
- If proration is enabled, the monthly rent is multiplied by (active days / days in month) for the first and last month.

## Yearly correction invoices
In Rent collection → Generate you can create a separate **Correction** charge (e.g. yearly settlement service costs).
It behaves like rent charges: payments, overdue status, and reminders.


## Admin list selection fix (v0.4.2)
Clicking the post title now opens the edit screen as expected. Row selection toggling only happens when clicking non-link areas.

# Utilities, meters and allocations (v0.5.1)

## Key concept
In Dutch property management, a *meter/connection* (EAN + meter number) often belongs to a building, but can be **shared** across multiple units and even multiple contracts.

This plugin models that using:

- **Utility (meter/connection)**: stores EAN, meter number, unit of measure, parent meter etc.
- **Meter reading**: registers readings + optional photo.
- **Utility allocation**: defines *who uses a meter* (exclusive/shared rules) across **one or more contracts**.

## Contracts and utilities
Contracts can cover **multiple units**. Utilities on a contract are **derived automatically** from Utility Allocations (recommended model).


## v0.5.2
- Fixed fatal parse error in MyContracts shortcode.


## v0.5.3
- Fixed WP warning: allocation post type slug exceeded 20 characters; added automatic migration for existing allocation posts.


## v0.5.4
- Serve contract documents earlier (init priority 0) to prevent 'headers already sent' warnings.

# Document management (v0.6.0)

## Overview
A new **Document** module was added to manage many kinds of files (PDF, images, office docs, etc.) across:
- Portfolios
- Properties
- Units
- Renters
- Contracts
- Utilities
- Meter readings

## Privacy labels
Document privacy labels are configurable (e.g. tenant-visible vs GDPR/PII). Downloads use a **secure endpoint** that enforces access:
- Staff (admins/VGBM managers) always allowed
- Tenant-visible documents are accessible to linked renters (via contract or renter profile)
- Public documents may be accessible without login

## Versioning
Documents keep a version history by storing each uploaded file as an attachment under the document record.
The **current version** is stored in `_vgbm_current_attachment_id`, while older versions remain accessible in the history table.

## Settings
Go to **VGBM → Document settings** to manage:
- Document types
- Allowed extensions per type
- Privacy labels


## v0.6.1
- Added attach existing documents + detach (AJAX) in entity Documents box.
- A document can be linked to multiple records; utilities on each record are derived from those links.
