# BatchFund – Batch Fund & Committee Management System

Pure **HTML5 + CSS3 + vanilla JavaScript + PHP (PDO) + MySQL**. No frameworks, no Composer, no npm.

## 1. Required software
- XAMPP (PHP 8.0+ with `pdo_mysql` and `fileinfo` enabled – both are on by default) – or any Apache/Nginx + PHP + MySQL/MariaDB stack.
- A modern browser.

## 2. Installation (XAMPP)
1. Install XAMPP and start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy the whole `BatchFund` folder into `C:\xampp\htdocs\` (macOS/Linux: `/opt/lampp/htdocs/`).
3. Open **http://localhost/phpmyadmin** → **Import** → choose `database/schema.sql` → **Go** (it creates the `batchfund` database).
4. Import `database/sample_data.sql` the same way (select the `batchfund` database first).
5. Open `config.php` and check `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` (XAMPP default is `root` with an empty password).
6. Make sure the `uploads/` folder is writable by the web server (automatic on XAMPP/Windows; on Linux `chmod 775 uploads`).
7. Browse to **http://localhost/BatchFund/**.

## 3. Test accounts (sample data)
| Role | Email | Password |
|---|---|---|
| Coordinator | `coordinator@batchfund.test` | `Coord@123` |
| Student | `student1@batchfund.test` … `student8@batchfund.test` | `Student@123` |

Change these passwords (My Account) before any real use.

## 4. SMTP e-mail configuration
Edit the `SMTP_*` and `MAIL_*` constants in `config.php`:
```php
define('SMTP_HOST', 'smtp.gmail.com'); define('SMTP_PORT', 587); define('SMTP_SECURE', 'tls');
define('SMTP_USER', 'you@gmail.com');  define('SMTP_PASS', 'your-16-char-app-password');
define('MAIL_FROM', 'you@gmail.com');
```
- Gmail needs 2-step verification and an **App Password** (not your normal password). Port 465 uses `'ssl'`.
- If `SMTP_HOST` is empty, PHP's `mail()` is used (usually not configured on XAMPP, so sends will fail and be logged).
- Each student gets their **own** message, so no one sees other recipients' addresses.
- Every attempt (sent/failed + error text) appears in **Email & Audit Logs**.
- For safe testing use a local catcher such as MailHog/Mailpit (`SMTP_HOST=127.0.0.1`, port `1025`, `SMTP_SECURE=''`, empty user).

## 5. Architecture
```
Browser ──► index.php (login) ─► auth.php (session + role)
        ├─► coordinator.php?page=…   (guarded: coordinator only)
        ├─► student.php?page=…       (guarded: student only; student_id read from session)
        └─► actions.php?action=…     (every form/POST; role + CSRF + ownership checks)
shared: config.php (DB, SMTP, settings) · functions.php (helpers, calculations, uploads, mail, layout)
```
Uploaded files live in `uploads/` (blocked by `.htaccess`, random names) and are only served by `actions.php` after an authorisation check. (`uploads/.htaccess` is the one extra file – it stops direct URL access to receipts. `uploads/sample_receipt.png` is only sample data.)

## 6. Database & relationships
`users 1─1 students` · `students 1─N payments N─1 collections` · `payments 1─N payment_receipts` · `collections 1─N expenses (optional link)` · `expenses 1─N expense_documents` · `users 1─N notifications` · `students 1─N email_logs` · `users 1─N audit_logs / announcements`.

Deliberate choices:
- `events` is merged into `collections` (`kind = 'monthly' | 'event'`); one FK serves payments, reports and reminders.
- `payments` has `UNIQUE(student_id, collection_id)` and `UNIQUE(reference_no)`. "Unpaid" = no row. A rejected payment is **resubmitted** by updating the same row (`attempt_no + 1`) and adding a new receipt – history of receipts is kept.
- Payment amount must equal the collection's contribution amount (validated in browser and PHP).
- **Available Balance = Σ approved payments − Σ approved expenses.** Only those two statuses ever enter `fund_totals()`.
- Approvals use a transaction + `SELECT … FOR UPDATE`, so a payment cannot be approved/rejected twice.

## 7. Action reference (`actions.php`)
All are POST + `csrf` token unless marked GET. `return` (optional) = page to go back to.

| Action | Role | Parameters | Owner |
|---|---|---|---|
| `change_password` | any | current_password, new_password, confirm_password | M1 |
| `add_student` | coord | full_name, reg_no, email, contact_no, district, password (blank = generate) | M1 |
| `update_student` | coord | student_id + same fields | M1 |
| `toggle_student` / `reset_password` / `delete_student` | coord | student_id | M1 |
| `create_collection` | coord | title, description, amount, collection_month (YYYY-MM), deadline | M2 |
| `create_event` | coord | title, description, amount, target_amount?, event_date?, deadline | M2 |
| `update_collection` | coord | collection_id + fields | M2 |
| `close_collection` / `reopen_collection` | coord | collection_id | M2 |
| `submit_payment` | student | collection_id, amount, reference_no, receipt (file) – also used for resubmission | M3 |
| `start_review` / `approve_payment` | coord | payment_id | M3 |
| `reject_payment` | coord | payment_id, reason | M3 |
| `download_receipt` (GET) | any (students: own only) | id = receipt_id | M3 |
| `add_expense` / `update_expense` | coord | title, description, category, amount, expense_date, collection_id?, document (file)?, doc_public? (+expense_id) | M4 |
| `approve_expense` / `reject_expense` / `delete_expense` | coord | expense_id | M4 |
| `delete_expense_doc` | coord | document_id | M4 |
| `download_expense_doc` (GET) | any (students: public + approved only) | id = document_id | M4 |
| `export_csv` (GET) | coord | type = payments / expenses / collections, from?, to?, collection_id? | M4 |
| `send_reminder` | coord | student_id, collection_id, mode (unpaid/overdue/deadline) | M5 |
| `send_bulk_reminders` | coord | collection_id (blank = all open), mode, days | M5 |
| `create_announcement` | coord | title, body, publish_date, publish (0 draft / 1 publish) | M5 |
| `update_announcement` / `delete_announcement` / `publish_announcement` / `unpublish_announcement` | coord | announcement_id (+fields) | M5 |
| `send_notification` | coord | message | M5 |
| `mark_notifications_read` / `clear_notifications` | any | – | M5 |

## 8. Five-member split
| Member | Files / sections | Tables |
|---|---|---|
| **1 – Auth & Students** | `index.php`, `auth.php`, `config.php`, layout + helpers in `functions.php`; `page_students`, `page_student_view`, `page_account` in `coordinator.php`; `act_*student*`, `act_change_password` | users, students |
| **2 – Collections & Events** | `page_monthly`, `page_events`, `page_collection_view`; `act_create_collection/event`, `update/close/reopen`; `student_collections`, `collection_roster` | collections |
| **3 – Payments** | `page_payments`; student `pay`, `history`, `collections`; `act_submit/approve/reject_payment`, `handle_upload`, `serve_file` | payments, payment_receipts |
| **4 – Expenses & Reports** | `page_dashboard`, `page_expenses`, `page_expense_edit`, `page_reports`; student `finances`; `fund_totals`, `total_outstanding`, `report_*`, `act_export_csv`, `act_*expense*`; charts in `script.js` | expenses, expense_documents, audit_logs |
| **5 – Communication** | `page_reminders`, `page_announcements`, `page_notifications`, `page_logs`; student notifications/announcements; `send_mail`, `reminder_message`, `notify*`, `act_send_*`, `act_*announcement*` | notifications, announcements, email_logs |

## 9. Security summary
`password_hash/verify`; session ID regeneration at login, HttpOnly + SameSite cookies, idle timeout, login throttling; role guard on every page and action; ownership checks (student IDs always come from the session); PDO prepared statements; output escaped with `h()`; CSRF token on every POST (and on logout); uploads validated by extension + real MIME + size + image check, renamed randomly, stored outside direct access; CSV export neutralises formula injection; important actions go to `audit_logs`.

## 10. Feature test checklist
1. **Login/logout** as coordinator and as student; try a wrong password; try opening `coordinator.php` as a student (403) and `student.php` when logged out (redirect).
2. **Students**: add (leave password blank – note the temp password), edit, search/filter by district, deactivate, reset password; duplicate reg. no/email must be rejected.
3. **Collections**: create a monthly collection and a special event; see students get notifications; open *Manage* to view paid/unpaid/overdue lists; close/reopen.
4. **Payments (student)**: *Submit Payment* with a JPG/PNG/PDF; try a `.php` or oversized file (rejected); check *Payment History*.
5. **Verification (coordinator)**: *Payments* → Review → Approve; reject another with a reason; as the student, resubmit the rejected one and confirm attempt 2.
6. **Expenses**: add one with an invoice (tick "visible to students"), approve it, and confirm the balance on the dashboard drops; a pending expense must not change it.
7. **Reports**: filter by date/collection, export all three CSVs, use *Print report*.
8. **Reminders**: send an individual and a bulk reminder, then check the Email Log (failures show the SMTP error).
9. **Announcements**: create a draft, publish it, view it as a student; check the notification.
10. **Privacy**: as student A, edit the URL `actions.php?action=download_receipt&id=<receipt of student B>` → 403.
