---
name: it-support-ticketing
description: Domain knowledge, architecture, SLA calculations, dual concierge floating bubble, and bilingual localization for IT Support & Ticketing in DISS.
---

# IT Support & Ticketing Module

A full-lifecycle IT support, service triage, and operational KPI platform supporting internal authenticated staff and external/guest login assistance.

## Architecture & Directory Layout

- **Entities & Enums**: `app/Domains/Ticketing/`
  - `Entities/Ticket`: Core ticket entity with SLA status helpers (`getSlaStatus()`, `getEffectiveSlaMinutes()`, `getFormattedHoldTime()`), phone WhatsApp link accessors (`whatsapp_url`), and clean descriptions.
  - `Entities/TicketCategory`: Generalized IT categories with dynamic fallback mapping.
  - `Entities/TicketActivity`: Activity audit log (comments, status transitions, assignments).
  - `Enums/TicketStatus`: `Open`, `In Progress`, `On Hold`, `Resolved`, `Closed` with `getBadgeColor()`.
  - `Enums/TicketPriority`: `Low`, `Medium`, `High`, `Critical` with `getBadgeColor()`.
- **Domain Actions**: `app/Domains/Ticketing/Actions/`
  - `CalculateKpiMetrics`: Computes Throughput, SLA compliance %, Re-open rate %, and Utilization (WIP).
  - `TransitionTicketStatus`: State transitions, pauses/resumes SLA timer, records hold reason and resolution notes, sends resolution notification email.
  - `AssignTicketToPIC`: Assigns technician and dispatches `TicketAssigned` event (queued `TicketAssignedMail` notification to assignee).
- **Livewire Components & Views**:
  - `app/Livewire/Ticketing/TicketList.php` & `resources/views/livewire/ticketing/ticket-list.blade.php`: Dual-mode portal (Self-Service ~80px cards for employees vs. Action-focused Queue defaulting to Unassigned active tickets with collapsible Advanced Filters for IT staff).
  - `app/Livewire/Ticketing/TicketDetail.php` & `resources/views/livewire/ticketing/ticket-detail.blade.php`: 2-column layout (Left: pre-wrap description & discussion; Right: reporter contact card, assignment/SLA properties, and collapsible technical diagnostics disclosure).
  - `app/Livewire/Ticketing/TicketDashboard.php` & `resources/views/livewire/ticketing/ticket-dashboard.blade.php`: Personal IT PIC performance metrics.
  - `app/Livewire/Ticketing/Admin/KpiDashboard.php` & `resources/views/livewire/ticketing/admin/kpi-dashboard.blade.php`: Department-wide aggregated IT performance across all staff.
  - `app/Livewire/Ticketing/SupportBubble.php` & `resources/views/livewire/ticketing/support-bubble.blade.php`: Inside-app floating IT concierge bubble at `bottom-6 right-6` with 7 issue categories aligned with login assistance.
  - `app/Livewire/Ticketing/TicketStatusModal.php`: Modal for status transitions with contextual hold reason / resolution note cards.
- **Guest Support & Tracking**:
  - `app/Http/Controllers/Auth/LoginAssistanceController.php`: Handles guest ticket submission and status tracking.
  - `resources/views/auth/login.blade.php`: Guest IT Concierge bubble at `bottom-6 right-6` with 2 tabs (`New Request` and `Track Status`).
  - `resources/views/support/track.blade.php`: Standalone guest ticket tracking page.
- **Localization**:
  - `lang/en/ticketing.php` and `lang/id/ticketing.php` with 100% key parity across `concierge`, `login_help`, `track`, `detail`, `nav`, `list`, `dashboard`, `admin_kpi`, `sla`, `modal`, `status`.

## UI & Operational Conventions

1. **Persona & Terminology Governance**:
   - **No Technical Jargon for Requesters**: Employees must never see "Triage" in any language. Standardize on **"Awaiting IT Review"** (EN) and **"Menunggu Peninjauan IT"** (ID).
   - **Information Gating**: Requesters do not see `page_url`, `device_info`, or direct contact action buttons. They see an "Assigned IT Specialist" card.
2. **MVP Priority Discipline**:
   - Priority is completely hidden from user-facing views (no badges, dropdowns, or filters). Backend/database defaults (`TicketPriority::MEDIUM`) remain active.
   - When refactoring views and controllers, always run `php artisan view:cache` to catch residual view references before shipping.
3. **IT Helpdesk Action Queue Defaults**:
   - IT Queue lands on **Unassigned Active Tickets** (`scopeFilter = 'unassigned'`, status is not closed/resolved) by default.
   - Primary bar has 3 action tabs (`Unassigned`, `Assigned to Me`, `All Active`).
   - Secondary filters (Category, Status history including Closed/Resolved) are tucked inside a collapsible "Filters" drawer.
4. **Multi-line Log Preservation**:
   - Problem description containers must use `whitespace-pre-wrap font-sans` to prevent multi-line logs, error messages, and reproduction steps from collapsing.
5. **In-Page Image Lightbox**:
   - Never open ticket screenshots or chat attachments in raw browser tabs. Wire them to `$dispatch('open-lightbox', { src, title, subtitle })`, which natively invokes the global Fancybox (`@fancyapps/ui` v5) lightbox handler in `resources/js/app.js`.
6. **Role-Asymmetric Chat Notifications**:
   - When IT technicians reply: immediate email notification is dispatched to the requester (office/plant staff do not keep DISS open constantly).
   - When requesters reply: per-message chat emails to IT specialists are suppressed to prevent inbox flooding during rapid back-and-forth discussions.
7. **07:30 AM (Asia/Jakarta) Morning Operations Digest**:
   - Artisan command `ticketing:daily-digest` runs Monday through Saturday at 07:30 WIB (prior to 08:00 WIB plant operations).
   - Dual distribution: Team overview to `config('mail.support_address')` / IT admins, plus personal digest to each specialist with active assigned tickets.
   - Fatigue suppression: completely skips sending if there are 0 active tickets in the queue.
8. **No Decorative Emojis**:
   - Avoid emojis (e.g. 🌅, 📊, 🚨, 📋, ⏳) in email templates and system communications. Keep all automated text, headers, and tables concise, professional, and direct.

## Key Governance & Security Rules

1. **ISO 27001 Anti-Enumeration**:
   - Never expose public user verification endpoints. Guest ticket tracking strictly requires matching Ticket Number + Employee NIK. Mismatches return generic "not found" without revealing whether the ticket or NIK exists.
2. **Dual Concierge Consistency**:
   - Guest bubble on the login page and authenticated bubble inside the app share identical visual design: slate-900 circular floating button at `bottom-6 right-6`, smooth tab transition, click-away and ESC key dismiss.
3. **SLA Calculation Formula**:
   $$\text{TTR} = (\text{resolved\_at} - \text{created\_at}) - \text{total\_hold\_time\_minutes}$$
   - SLA timer is paused while ticket is in `On Hold` status.
4. **GitHub Escalation Security**:
   - GitHub Issue creation and issue URL linking are strictly gated to authorized IT staff (`canManageTicket` / `ticketing.manage` / `super-admin`). Standard reporters never see GitHub cards or fields.

## Testing & Quality Assurance

```bash
# Run ticketing feature test suite
./vendor/bin/sail test tests/Feature/Ticketing
```
