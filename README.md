<p align="center">
  <img src="assets/logo.svg" alt="SocietyPro logo" width="120">
</p>

<h1 align="center">SocietyPro — Advanced Society Management System (PHP &amp; MySQL)</h1>

<p align="center">
  Flats &amp; Residents &bull; Maintenance Billing &amp; Receipts &bull; Complaints &bull; Visitor Gate Register &bull; Notices &amp; Events &bull; Facility Bookings &bull; Reports
</p>

A **complete, ready-to-use housing society management system** in **Core PHP + MySQL** — flats & residents, **one-click monthly maintenance billing** with payments and printable receipts, complaints, visitor gate register, notices, events, facility bookings, expenses and reports. Beautiful responsive UI (Warm Paper & Deep Teal), no framework.

## Demo Accounts

| Role | Email | Password |
|---|---|---|
| Admin (Society Office) | `admin@example.com` | `Admin@123` |
| Resident (Flat A-101) | `resident@example.com` | `Resident@123` |

The SQL seeds a demo society ("Green Valley Apartments"): 8 flats, the demo resident with family + a car, facilities (Community Hall, Club House, Guest Room, Swimming Pool), 2 notices, 2 events, one paid + one unpaid bill, a sample complaint and October expenses — delete/change them anytime. Change the demo passwords after setup.

## Features

### For the Society Office (Admin)
- **Dashboard** — flats occupied/vacant, residents, open complaints, today's visitors, and this month's collection with progress bar.
- **Flats** — block-wise flat grid (occupied shown in green), add/edit/delete with safety checks, flat page with residents, vehicles and bill history.
- **Residents** — create logins, assign flats, owner/tenant type, activate/deactivate, reset passwords; view family members, vehicles and dues.
- **Maintenance Billing** — set rates once (per BHK type + water + parking per vehicle), then **Generate Bills** creates a month's bills for every flat in one click. Per-bill adjustments (other charges), part-payments, automatic **late fee** when paying after the due date, and **printable receipts** for every payment. Full **dues list CSV export**.
- **Complaints** — track every resident complaint (Open → In Progress → Resolved → Closed) with office remarks.
- **Visitor Gate Register** — log visitors in/out with flat, vehicle and purpose.
- **Bookings** — approve/reject facility bookings (double-booking on the same date is blocked).
- **Expenses & Reports** — record society expenses; monthly collection report, top-dues list, expense-by-category and society summary.

### For Residents
- **Resident Portal** — personal dashboard with their flat, live dues, notices and events.
- **My Flat & Family** — manage family members and vehicles (parking billed per vehicle automatically).
- **My Bills** — every bill with breakdown, payment history and downloadable/printable receipts.
- **Complaints** — raise with category + priority, track status and office remarks.
- **Book Facility** — request the hall/clubhouse/guest room; see approval status; cancel pending requests.
- **Visitors** — see who visited their flat.

## Setup (5 minutes)

1. Copy the project into `htdocs` (XAMPP) / `www` (WAMP) / `public_html` (hosting).
2. Open **phpMyAdmin → Import** and import `database.sql` (creates database `society_system`, all tables and demo data).
3. If MySQL isn't `root` / empty password, edit `config/database.php`.
4. Open the project in your browser → login as **Admin** → **Settings**: set your society name, address and maintenance rates.
5. Add your **Flats**, then **Residents** (assign each resident their flat). Residents can also self-register — you just link their flat from the Residents page.
6. Every month: **Maintenance Bills → Generate Bills**, then record payments as residents pay — receipts print in one click.

## How Billing Works

```
Bill total = Maintenance (by flat type) + Water (flat rate)
           + Parking (rate × vehicles of that flat) + Other (manual)
           + Late fee (added once, when a payment is made after the due date)
```
Status is automatic: `unpaid → partial → paid` as payments are recorded. Rates, late fee and due day all live in **Settings** — changing them affects only newly generated bills.

## Project Structure

```
index.php login.php register.php logout.php    Landing + auth (admin/resident roles)
dashboard.php                                  Role-aware dashboard
flats.php flat-add/edit/view/delete.php        Flat management + occupancy grid
residents.php resident-add/edit/view/delete    Resident logins + flat assignment
my-flat.php                                    Resident: family members + vehicles
bills.php bill-view.php my-bills.php           Billing, payments, resident bills
receipt.php                                    Printable payment receipt
export-dues.php                                Dues list CSV (admin)
complaints.php visitors.php                    Complaints desk + gate register
notices.php events.php                         Notice board + events
facilities.php book-facility.php bookings.php  Facilities + booking approvals
expenses.php reports.php settings.php          Expenses, reports, society settings
config/ includes/ assets/                      Config, helpers, UI assets
database.sql                                   Complete schema + demo data
```

## Security

PDO prepared statements everywhere, `password_hash` logins, CSRF tokens on every form, session regeneration, XSS escaping, role checks on every page (residents can only ever see their own flat's data — enforced on bills and receipts too).

## Requirements

- PHP 8.0+ with `pdo_mysql`
- MySQL 5.7+ / MariaDB 10.3+
- Apache (XAMPP / WAMP / LAMP / shared hosting)

## Author

**Lavkush Kumar** — [Coding Cush](https://codingcush.com) · [YouTube](https://www.youtube.com/@CodingCush)

## License

Free to use for learning and your own projects. A product of Coding Cush.
