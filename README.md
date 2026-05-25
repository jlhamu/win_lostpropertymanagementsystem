# WIN Lost Property Management System
## Wentworth Lost and Found Management System

A full-stack PHP 8 and MySQL web application for **Wentworth Higher Institute of Education**.  
Students and staff can report lost items, report found items, browse listings, submit ownership claims, and receive email notifications when potential matches are found.

---

## Features

- **User Registration & Login** – Students and staff register with hashed passwords; role-based sessions
- **Report Lost Items** – Full form with image upload, category, location, and date
- **Report Found Items** – Auto-match against open lost items after submission; sends email notifications
- **Browse Listings** – Paginated grid with live-search, type/category/status filters
- **Item Detail Page** – Full item view with ownership claim button
- **Ownership Claims** – Logged-in users submit proof-of-ownership descriptions; admin reviews
- **My Reports** – Tabbed view of a user's items, submitted claims, and unread notifications
- **In-App Notifications** – Automatic notification when a possible match is found
- **Admin Dashboard** – Summary stats (users, items, claims by status)
- **Admin Item Management** – Filter, search, update status, delete items
- **Admin Claim Management** – Approve/reject claims; approving auto-marks item as returned and rejects other pending claims
- **Email Notifications** – PHPMailer integration; notifies lost-item reporters of potential matches
- **Security** – PDO prepared statements, `password_hash()`, session protection, XSS escaping, file-type validation

---

## Technology Stack

| Layer        | Technology            |
|--------------|-----------------------|
| Frontend     | HTML5, CSS3, JavaScript (vanilla) |
| Backend      | PHP 8                 |
| Database     | MySQL (via PDO)       |
| Local Server | XAMPP / Apache        |
| Email        | PHPMailer             |
| IDE          | VS Code               |
| Version Control | GitHub             |

---

## Folder Structure

```
lost-found-system/
│
├── index.php              – Home page
├── login.php              – Login page
├── register.php           – Register page
├── logout.php             – Logout script
├── browse.php             – Browse all listings (paginated + filtered)
├── item-detail.php        – Full item detail view
├── claim-item.php         – Submit ownership claim
├── report-lost.php        – Report a lost item
├── report-found.php       – Report a found item
├── my-reports.php         – User's items, claims, notifications
├── setup.php              – Run once to create admin account (DELETE after use)
│
├── admin/
│   ├── dashboard.php      – Admin stats dashboard
│   ├── items.php          – Manage all items
│   ├── claims.php         – Manage all claims
│   └── update-status.php  – Handle item status updates
│
├── includes/
│   ├── db.php             – PDO database connection + BASE_URL definition
│   ├── auth.php           – isLoggedIn(), requireLogin(), requireAdmin(), currentUser()
│   ├── functions.php      – sanitize(), uploadItemImage(), findPotentialMatches(), etc.
│   ├── email.php          – sendMatchNotification() using PHPMailer
│   ├── header.php         – Shared HTML header + navigation bar
│   ├── footer.php         – Shared HTML footer
│   └── admin-sidebar.php  – Admin sidebar navigation
│
├── assets/
│   ├── css/style.css      – Full responsive stylesheet
│   └── js/main.js         – Live search, image preview, form validation, tabs
│
├── uploads/
│   └── items/             – Uploaded item images (auto-created)
│
├── sql/
│   └── database.sql       – All CREATE TABLE statements
│
├── vendor/                – PHPMailer (install via Composer)
│
└── README.md
```

---

## Installation Guide

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) with Apache and MySQL running
- PHP 8.0+
- [Composer](https://getcomposer.org/) (for PHPMailer)

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/group-project.git
   ```

2. **Copy to XAMPP htdocs**
   ```
   Copy the project folder into:  C:\xampp\htdocs\   (Windows)
                                   /Applications/XAMPP/htdocs/  (macOS)
   ```

3. **Start XAMPP** – Start Apache and MySQL from the XAMPP Control Panel.

4. **Create the database**
   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Click **Import**
   - Select the file `sql/database.sql` and click **Go**

5. **Install PHPMailer** (optional – required only for email notifications)
   ```bash
   cd path/to/your/project
   composer require phpmailer/phpmailer
   ```

6. **Configure database credentials**  
   Open `includes/db.php` and update if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'wentworth_lost_found');
   define('DB_USER', 'root');
   define('DB_PASS', '');   // Leave empty for default XAMPP
   ```

7. **Create the admin account**  
   Open your browser and go to:
   ```
   http://localhost/<your-folder-name>/setup.php
   ```
   Click **Create Admin Account**, then **delete `setup.php`** immediately.

8. **Open the application**
   ```
   http://localhost/<your-folder-name>/
   ```

---

## Database Setup

```sql
CREATE DATABASE wentworth_lost_found;
-- Then import sql/database.sql
```

Tables created by `sql/database.sql`:

| Table           | Purpose                          |
|-----------------|----------------------------------|
| `users`         | All registered users             |
| `items`         | Lost and found item reports      |
| `claims`        | Ownership claims on found items  |
| `notifications` | In-app match notifications       |

---

## Default Admin Account

| Field    | Value                 |
|----------|-----------------------|
| Name     | System Admin          |
| Email    | admin@win.edu.au      |
| Password | admin123              |
| Role     | admin                 |

> **Created by running `setup.php`** — never stored as plain text.

---

## Email Configuration

Edit `includes/email.php` to configure PHPMailer for your SMTP server:

```php
$mail->Host     = 'smtp.gmail.com';
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';   // Gmail App Password
$mail->Port     = 587;
```

For Gmail, enable 2FA and generate an **App Password** at  
https://myaccount.google.com/apppasswords

---

## How to Run in XAMPP

1. Place the project folder in `htdocs/`
2. Start **Apache** and **MySQL** in XAMPP Control Panel
3. Import `sql/database.sql` via phpMyAdmin
4. Run `setup.php` to create the admin user
5. Navigate to `http://localhost/<folder-name>/`

---

## Security Features

- PDO prepared statements (no raw SQL from user input)
- `password_hash()` + `password_verify()` — passwords never stored in plain text
- PHP sessions with `session_regenerate_id()` on login
- `requireLogin()` / `requireAdmin()` guards on every protected page
- `htmlspecialchars()` on all output
- Uploaded files: type checked, size limited (5 MB), renamed with `uniqid()`
- `getimagesize()` validation to reject disguised non-images

---

## Future Enhancements

- Forgot password with email reset link
- User profile page with editable details
- QR code tagging for tracked items
- CSV export of item/claim reports
- Soft delete (archive) instead of permanent delete
- Item expiry / auto-disposal workflow
- Real-time notifications via WebSockets or SSE

---

## Team Members

- *(Add your name and student ID here)*

---

## Screenshots

*(Add screenshots of Home, Browse, Admin Dashboard, etc. here)*
Tested and verified local setup by tseyr
Tested and verified local setup by Tashi
