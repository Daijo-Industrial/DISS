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
- **Livewire Components & Views**:
  - `app/Livewire/Ticketing/TicketList.php` & `resources/views/livewire/ticketing/ticket-list.blade.php`: Control center with 2-row layout (scope filter tabs row 1, search + status pills row 2), claim ticket action.
  - `app/Livewire/Ticketing/TicketDetail.php` & `resources/views/livewire/ticketing/ticket-detail.blade.php`: Ticket inspection, quick SLA presets, reassignment, reporter WhatsApp/phone card, chat-style activity log (earliest top, latest bottom, auto-scroll to bottom).
  - `app/Livewire/Ticketing/TicketDashboard.php` & `resources/views/livewire/ticketing/ticket-dashboard.blade.php`: Personal IT PIC performance metrics.
  - `app/Livewire/Ticketing/Admin/KpiDashboard.php` & `resources/views/livewire/ticketing/admin/kpi-dashboard.blade.php`: Department-wide aggregated IT performance across all staff.
  - `app/Livewire/Ticketing/SupportBubble.php` & `resources/views/livewire/ticketing/support-bubble.blade.php`: Inside-app floating IT concierge bubble at `bottom-6 right-6`.
  - `app/Livewire/Ticketing/TicketStatusModal.php`: Modal for status transitions with contextual hold reason / resolution note cards.
- **Guest Support & Tracking**:
  - `app/Http/Controllers/Auth/LoginAssistanceController.php`: Handles guest ticket submission and status tracking.
  - `resources/views/auth/login.blade.php`: Guest IT Concierge bubble at `bottom-6 right-6` with 2 tabs (`New Request` and `Track Status`).
  - `resources/views/support/track.blade.php`: Standalone guest ticket tracking page.
- **Localization**:
  - `lang/en/ticketing.php` and `lang/id/ticketing.php` with 100% key parity across `concierge`, `login_help`, `track`, `detail`, `nav`, `list`, `dashboard`, `admin_kpi`, `sla`, `modal`, `status`.

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
