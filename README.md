# Thrift Shop Inventory and Reservation System

Laravel-based web application for thrift store inventory visibility and online item reservations.

## 1. System Overview

The Thrift Shop Inventory and Reservation System helps thrift stores manage inventory while allowing customers to browse and reserve items online.

The platform follows a role-based model:
- Admin for operational management
- Customer for browsing and reservations

The system tracks reservations and payment status, while payment itself is completed in person at the thrift shop.

## 2. User Roles

### Admin
Administrators manage inventory, reservations, categories, and users.

Permissions:
- Manage inventory items
- Manage categories
- View and update reservations
- Manage user accounts
- Update stock quantities
- Archive inventory items

### Customer
Customers interact with public shopping and reservation pages.

Permissions:
- Create account
- Browse/search/filter inventory
- Reserve items
- View reservation history
- Manage own profile

## 3. Core Features

### 3.1 Reservation System
Reservation flow:
1. Customer browses inventory
2. Customer selects item
3. Customer submits reservation
4. System records reservation and reserves stock
5. Reservation is time-limited

Reservation deadline:
- 48-hour validity window
- If unpaid after deadline, reservation expires

Reservation statuses:
- Pending
- Completed
- Overdue
- Expired

Reservation confirmation:
- Email notifications supported
- SMS channel scaffold exists for future integration

### 3.2 Payment Handling
- Payment is outside the platform (in-person at shop)
- System tracks payment state only

Payment statuses:
- Pending
- Completed
- Overdue

Admins update payment status after in-person confirmation.

### 3.3 Inventory Management (Admin CRUD)
Create/Edit fields:
- Name
- Category
- Price
- Quantity
- Description
- Condition (New, Gently Used, Worn)
- Tags
- Status (Active, Out of Stock, Archived)

Read features:
- Search
- Filter by category/condition/status
- Listing and stock visibility

Delete behavior:
- Archive-style via soft deletes and archived status

### 3.4 User Management (Admin)
- View/search users
- Update role (admin/customer)
- Activate/suspend accounts
- Customers can register themselves
- Users can manage profile and delete account

## 4. Website Pages

### Admin Pages
- Admin dashboard
- Inventory management
- Reservation management
- User management
- Admin profile

### Customer Pages
- Home page
- Browse items page
- Item details page
- Reservation pages
- Profile page
- Login/registration pages

## 5. Workflow Summary

### Customer Side
1. Customer creates account
2. Customer browses inventory
3. Customer reserves item
4. System starts a 48-hour reservation timer
5. Customer pays in person at thrift shop

### Admin Side
1. Admin logs in
2. Admin manages inventory
3. Admin reviews reservations
4. Admin confirms in-person payment
5. Admin updates reservation/payment status

## 6. Current Implementation Notes

Implemented now:
- Role-based access (admin/customer)
- Inventory CRUD with archive behavior
- Category CRUD for admin management
- Reservation creation with 48-hour expiration
- Automatic expiry command and scheduled execution
- Overdue marking command and scheduled execution
- Reservation and payment status tracking
- Modernized UI across admin/customer/auth pages
- Email notifications for reservation creation and status updates

Partially implemented or pending:
- Inventory image upload workflow (field exists, upload flow pending)
- Bulk inventory update tools
- Archived item restoration flow
- Optional SMS notification integration
- Popularity-based sorting and loyalty features

For an actionable checklist, see docs/implementation-matrix.md.

## 7. Tech Stack

- Laravel (PHP)
- Blade templates
- MySQL
- Bootstrap 5
- Vite
- Spatie Laravel Permission

## 8. Local Setup

1. Install dependencies:
	- composer install
	- npm install

2. Prepare environment:
	- copy .env.example to .env
	- configure DB credentials
	- php artisan key:generate

3. Run migrations and seeds:
	- php artisan migrate --seed

4. Build assets:
	- npm run build
	- or npm run dev for watch mode

5. Start application:
	- php artisan serve

6. Run scheduler in development:
	- php artisan schedule:work

## 9. Default Seeded Accounts

Configured in .env defaults:
- Admin: admin@thriftshop.local / 123
- Customer: customer@thriftshop.local / 123

## 10. Scheduled Jobs

- reservations:expire -> every 5 minutes
- reservations:mark-overdue -> daily at 01:00
