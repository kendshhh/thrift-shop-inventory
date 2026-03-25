# Implementation Matrix

This matrix maps the requested system specification to current codebase status.

## Legend
- Implemented: Available end-to-end in current app
- Partial: Scaffold or backend support exists, but flow/UI is incomplete
- Pending: Not yet implemented

## Features

| Requirement Area | Status | Notes |
|---|---|---|
| Role-based access (Admin/Customer) | Implemented | Spatie role and permission setup, route guards, seeded roles |
| Customer account registration/login | Implemented | Laravel auth with customer role assignment |
| Browse/search/filter inventory | Implemented | Search + category + condition filters, price sorting |
| Reservation creation flow | Implemented | Creates reservation + line item + reserved stock updates |
| 48-hour reservation validity | Implemented | expires_at default + expire command + scheduler |
| Reservation statuses (Pending/Completed/Overdue/Expired) | Implemented | Enum + transitions + admin update flow |
| Payment tracking (Pending/Completed/Overdue) | Implemented | Tracked in reservation, updated by admin |
| In-person payment policy | Implemented | System tracks only; no online gateway integration |
| Reservation history | Implemented | Customer reservation index/detail pages |
| Admin inventory CRUD | Implemented | Create/read/update/archive (soft delete) |
| Archive inventory instead of hard delete | Implemented | Archive status + soft delete used |
| Admin reservation management | Implemented | List/filter/show/update status/payment |
| Admin user management | Implemented | List/search + role/status updates |
| Profile management | Implemented | Edit profile, password update, account delete |
| Email notifications (reservation created/updated) | Implemented | Notification classes wired in controllers |
| SMS notifications | Partial | SmsChannel scaffold exists; no delivery integration yet |
| Category CRUD (admin) | Implemented | Admin routes, controller logic, and views are available |
| Inventory image upload | Partial | image_path field exists; file upload pipeline pending |
| Bulk inventory update | Pending | No bulk update UI/action yet |
| Restore archived items | Partial | Archived model state exists; restore flow not exposed |
| Popularity sorting | Pending | Current sorting: latest/price asc/desc |
| Loyalty/rewards/communication preferences | Partial | communication_preferences field exists; no full feature |

## Priority Backlog

### High Priority
1. Add inventory image upload (storage disk + validation + preview)
2. Add archived inventory restore action
3. Add dashboard-level category analytics (optional enhancement)

### Medium Priority
1. Add bulk inventory quantity/status update tool
2. Add popularity-based sorting (e.g., by reservations count)
3. Add SMS provider integration for reservation notifications

### Low Priority
1. Add loyalty/reward points module
2. Expand communication preferences UI and per-channel notification controls

## Suggested Next Sprint

1. Inventory image upload
2. Archived restore action
3. Bulk inventory update flow
4. End-to-end tests for reservation expiry and stock release
