# Meeting Management System (MMS)

[![Laravel Version](https://img.shields.io/badge/Laravel-13.x-red.svg)](https://laravel.com)
[![Database](https://img.shields.io/badge/PostgreSQL-18-blue.svg)](https://www.postgresql.org)
[![Frontend](https://img.shields.io/badge/Bootstrap-5.3-purple.svg)](https://getbootstrap.com)
[![Network](https://img.shields.io/badge/Internal%20Network-100%25%20Offline%20Ready-success.svg)](#offline-ready-internal-network-architecture)

A modern, production-ready, and sophisticated enterprise **Meeting Management System (MMS)** built with **Laravel 13** and architected for **PostgreSQL 18**. It empowers organizations to seamlessly manage corporate meeting rooms, schedule in-person, hybrid, and virtual meetings, prevent room double-booking conflicts, build structured agenda timelines, record attendance roll-calls, draft and sign official Minutes of Meeting (MoM), track actionable deliverables, and perform administrative bulk CSV imports.

The entire frontend is bundled locally using **Bootstrap 5.3**, **Bootstrap Icons**, **FontAwesome 6**, and **Feather Icons**, allowing the system to operate flawlessly in restricted **internal networks / corporate intranets without requiring internet connectivity**.

---

## 🌟 Key Features

### 1. Conflict Prevention & Smart Room Booking
- **Real-Time Overlap Detection:** Automatic validation prevents room double-booking across date and time windows.
- **Facility & Equipment Directory:** Meeting rooms cataloged with floor locations, seating capacities, and equipment tags (4K Laser Projector, Polycom Video Conferencing, Smart Podiums, Digital Whiteboards, etc.).
- **Live Conflict Check API:** As organizers select date and time slots on the booking screen, an asynchronous live checker verifies availability instantly before form submission.
- **Visual Timetable Calendar:** Daily room-by-room timetable view with color-coded status blocks (8:00 AM &ndash; 6:00 PM).

### 2. End-to-End Meeting Lifecycle
- **Meeting Modes:** In-Person, Hybrid (with meeting room + online conferencing link), and Virtual Only.
- **Dynamic Agenda Builder:** Organize meetings into time-boxed agenda topics with dedicated presenters and discussion notes.
- **Status Workflow:** Transition through `Scheduled` &rarr; `In Progress` &rarr; `Completed` or `Cancelled` (with mandatory cancellation rationale audit).
- **Confidentiality Toggles:** Sensitive meetings restricted to invited attendees only.

### 3. Attendee Management & Attendance Roll-Call
- **Multi-Role Invites:** Specify attendee roles as `Host`, `Presenter`, `Required`, or `Optional`.
- **Live RSVP Tracking:** Attendees can mark their response as `Accepted`, `Declined`, or `Tentative` with custom notes.
- **Official Roll Call:** Meeting organizers can record attendance as `Present`, `Absent`, `Late`, or `Excused` with automatic check-in timestamps.

### 4. Official Minutes of Meeting (MoM) & Print Export
- **Structured Minutes:** Dedicated fields for Executive Discussion Summaries, Agreed Resolutions, and Key Takeaways.
- **Approval Workflow:** Tracks preparation author and chairperson approval timestamp.
- **Print / PDF Ready:** Clean `@media print` layout generating official corporate minutes with metadata, roll-call, decisions, action item matrices, and signature sign-off lines.

### 5. Action Items & Deliverables Tracker
- **Accountability Matrix:** Assign actionable deliverables to specific users with priority levels (`Low`, `Medium`, `High`, `Urgent`) and due dates.
- **Overdue Detection:** Automated flags and badges highlighting overdue commitments.
- **Personal & Global Views:** Filter tasks by "Assigned to Me" or "All Corporate Tasks" with instant completion toggles.

### 6. Admin Bulk Data Import Hub
- **Batch CSV Ingestion:** Centralized administrative import hub supporting:
  - **Users Directory:** Bulk create employee accounts, assign departments, roles, and default credentials.
  - **Meeting Rooms:** Bulk register physical facilities, capacities, floor locations, and equipment.
  - **Scheduled Meetings:** Bulk schedule meeting series with automated organizer resolution and room conflict checking.
- **Smart Parsing Engine:** Auto-detects delimiters (comma `,`, semicolon `;`, tab `\t`), strips UTF-8 BOM, and performs transactional database insertions.
- **Line-by-Line Error Reporting:** Detailed audit history log tracking successful versus failed rows with specific validation failure reasons (e.g., duplicate email, invalid room code, conflicting time slot).
- **1-Click Sample Templates:** Download pre-formatted `.csv` template files for instant onboarding.

### 7. Role-Based Access Control (RBAC)
- **Admin:** Full system control, department setup, user management, and bulk import hub.
- **Organizer:** Schedule meetings, book rooms, manage agendas, conduct roll-calls, record MoM, and assign action items.
- **Participant:** View assigned meetings, update RSVP responses, download attachments, and mark assigned tasks as complete.

---

## 🔒 Offline-Ready / Internal Network Architecture

This application is specifically engineered for internal corporate networks, secure enterprise intranets, and air-gapped environments:
- **No External CDNs:** All stylesheets, JavaScript bundles, vector icons, and font files are downloaded and served directly from `public/vendor/`.
- **Asset Directory Structure:**
  - `public/vendor/bootstrap/` &mdash; Bootstrap 5.3.3 CSS & JS Bundle.
  - `public/vendor/bootstrap-icons/` &mdash; Bootstrap Icons CSS and local `.woff2` font files.
  - `public/vendor/fontawesome/` &mdash; FontAwesome 6 Free CSS and local `.woff2` webfonts.
  - `public/vendor/feather/` &mdash; Feather Icons vector script.
  - `public/vendor/chartjs/` &mdash; Local Chart.js engine for dashboard analytics.

---

## 🛠️ Technology Stack

| Layer | Technology | Version |
|---|---|---|
| **Backend Framework** | Laravel | 13.x (`laravel/framework v13.36.0`) |
| **PHP Runtime** | PHP | 8.3 / 8.4 (Tested with Laragon) |
| **Database Engine** | PostgreSQL | 18 (Target) / SQLite (Local dev test fallback) |
| **Styling & Components** | Bootstrap | 5.3.3 |
| **Icons Library** | Bootstrap Icons & FontAwesome 6 | Latest (Bundled locally) |
| **Data Visualization** | Chart.js | Latest (Bundled locally) |
| **Testing** | PHPUnit | 12.x |

---

## 🚀 Environment Setup & Installation

### Prerequisites
1. **PHP:** Version 8.3 or 8.4 (available in Laragon: `C:\laragon\bin\php\php-8.3.33...` or `php-8.4.12...`).
2. **Extensions Enabled in `php.ini`:**
   - `pdo_pgsql`
   - `pgsql`
   - `mbstring`
   - `openssl`
   - `curl`
   - `fileinfo`
3. **PostgreSQL 18** installed on your server or integrated into Laragon.

---

### Step 1: Clone or Navigate to the Project
```bash
cd c:\xampp\htdocs\meeting-management-system
```

---

### Step 2: Configure PostgreSQL 18
Create a PostgreSQL database:
```sql
CREATE DATABASE meeting_db;
CREATE USER postgres WITH ENCRYPTED PASSWORD 'postgres';
GRANT ALL PRIVILEGES ON DATABASE meeting_db TO postgres;
```

In `.env`, configure the PostgreSQL 18 credentials:
```ini
APP_NAME="Meeting Management System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# PostgreSQL 18 Database Configuration
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=meeting_db
DB_USERNAME=postgres
DB_PASSWORD=postgres
DB_SCHEMA=public
DB_SSLMODE=prefer
```

> **Note on Local Testing without PostgreSQL 18:**
> If PostgreSQL 18 is not yet installed on your development workstation, you can temporarily test the entire application using SQLite by uncommenting:
> ```ini
> DB_CONNECTION=sqlite
> ```
> The system has zero raw dialect dependencies and executes ANSI SQL compliant queries across both engines.

---

### Step 3: Run Database Migrations & Seeders
Execute the migrations and populate the database with realistic corporate demo data:
```bash
# Using Laragon PHP 8.3/8.4
php artisan migrate:fresh --seed
```

This will automatically create:
- 6 Organizational Departments (Executive Management, Engineering, Product & Design, HR, Finance, Operations).
- 8 Realistic Employee Profiles across Admin, Organizer, and Participant roles.
- 6 Meeting Facilities with capacities, floor locations, and equipment tags.
- Sample Scheduled, In-Progress, and Completed Meetings with full agendas, roll-calls, official MoM, and action items.

---

### Step 4: Link Storage Disk
Enable file upload access for meeting attachments and presentations:
```bash
php artisan storage:link
```

---

### Step 5: Start the Application Server
Run the local development server:
```bash
php artisan serve
```
Or serve via Laragon / Apache VirtualHost:
```
http://localhost:8000
```

---

## 🔑 Default Demo Accounts

The login screen includes **1-Click Fill Buttons** for instant evaluation without typing:

| Role | Email | Password | Access Privileges |
|---|---|---|---|
| **System Administrator** | `admin@company.local` | `password` | Full system access, Bulk Import Hub, User Management, Department configuration. |
| **Meeting Organizer** | `organizer@company.local` | `password` | Schedule meetings, book rooms, manage agendas, mark attendance, record MoM. |
| **Staff Participant** | `participant@company.local` | `password` | View scheduled meetings, submit RSVPs, complete assigned action items. |

---

## 📥 Admin Bulk Data Import Guide

Access the Hub under **Administration &rarr; Bulk Data Import** (`/admin/bulk-import`):

### 1. Users Import (`users`)
Download sample template: `GET /admin/bulk-import/template/users`
```csv
name,email,role,department,phone,job_title,status,password
Sarah Jenkins,sarah.j@company.local,organizer,Engineering,+1-555-0101,Lead Architect,active,password123
Marcus Chen,marcus.c@company.local,participant,Product,+1-555-0102,Senior Product Manager,active,password123
```
- **Roles:** `admin`, `organizer`, `participant`.
- **Department:** Automatically resolved by code (e.g. `ENG`) or name (e.g. `Engineering`). If not found, it is automatically created.
- **Password:** Defaults to `password123` if left blank.

### 2. Meeting Rooms Import (`rooms`)
Download sample template: `GET /admin/bulk-import/template/rooms`
```csv
name,code,floor_location,capacity,equipment,hourly_rate,status,notes
Executive Boardroom Alpha,BOARD-01,Executive Floor 5,24,"Projector, Video Conference, Whiteboard",50.00,available,Catering permitted
Innovation Lab,LAB-02,East Wing 2nd Floor,16,"Smart TV 75 inch, Dual Whiteboard",0.00,available,Configurable layout
```
- **Code:** Unique alphanumeric room identifier.
- **Equipment:** Comma-separated list enclosed in quotes.
- **Status:** `available`, `maintenance`, `inactive`.

### 3. Meetings Import (`meetings`)
Download sample template: `GET /admin/bulk-import/template/meetings`
```csv
title,meeting_code,room_code,organizer_email,meeting_date,start_time,end_time,meeting_type,virtual_link,department_code,description
Quarterly Strategic Review,MTG-STRAT-01,BOARD-01,admin@company.local,2026-10-20,09:00,11:00,hybrid,https://meet.company.local/strat-q4,EXEC,Review Q3 performance and Q4 goals
```
- **Conflict Engine:** Automatically checks if `room_code` is already booked for that date and time slot.
- **Organizer:** Resolved by `organizer_email`.

---

## 🧪 Automated Test Suite

The system includes feature tests verifying the authentication workflow, dashboard statistics, room conflict engine, authorization middleware, template downloads, and end-to-end CSV imports:

```bash
php artisan test
```

Expected result:
```
Tests:    10 passed (25 assertions)
Duration: 0.81s
```

---

## 🗄️ Database Schema Overview

```
departments
 └── users
      ├── meetings (as organizer)
      │    ├── meeting_rooms (room_id)
      │    ├── meeting_agendas
      │    ├── meeting_attendees (with users)
      │    ├── meeting_minutes
      │    ├── action_items (assigned_to users)
      │    └── meeting_attachments
      └── import_logs
```

---

## 📄 License & Attribution
Open-sourced enterprise software licensed under the [MIT license](https://opensource.org/licenses/MIT).
Designed with care for high-reliability organizational meeting management.
