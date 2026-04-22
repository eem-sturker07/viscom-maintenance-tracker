# Viscom Maintenance Tracking System v2.0

A web-based maintenance tracking system for Viscom machines with periodic scheduling, reporting, and print-ready outputs.

---

## 🚀 Features

* Separate maintenance tracking for 5 (or more) machines
* Tasks identical to the ODS table (Monthly / 3-Monthly / 6-Monthly / Yearly)
* Yearly overview and progress tracking per machine
* Period-based records (date + technician + notes)
* Printing: single machine or all machines (same format as ODS table)
* Add / Edit / Delete machines via Settings panel

---

## ⚙️ Installation (XAMPP / Linux PHP + MySQL)

### 1. Create Database

```sql
SOURCE /path/to/viscom_maintenance/db/schema.sql;
```

---

### 2. Configure Database Connection

Edit `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'viscom_maintenance');
define('DB_USER', 'root');
define('DB_PASS', '');
```

---

### 3. Copy Project Files

**XAMPP:**

```
htdocs/viscom_maintenance/
```

**Linux (Apache):**

```
/var/www/html/viscom_maintenance/
```

---

### 4. Run in Browser

```
http://localhost/viscom_maintenance/
```

---

## 📁 Project Structure

```
viscom_maintenance/
├── config.php
├── index.php
├── machine.php
├── print_machine.php
├── print_all.php
├── settings.php
├── css/
│   └── style.css
└── db/
    └── schema.sql
```

---

## ⚙️ Machine Management

From the **Settings (⚙)** page you can:

* Add new machines
* Edit machine names
* Delete machines

---

## 🛠 Maintenance Tasks

All tasks are stored in the `maintenance_tasks` table.

---

### 📅 Monthly (12x/year)

* Database Backup Taken
* Camera and Motor Health Check
* PCB Rails Cleaning
* Calibration Verification
* Compressed Air Check (4–6 Bar)

---

### 📅 3-Monthly (Mar / Jun / Sep / Dec)

* Grayscale Calibration
* Transport System Check
* Fan Filter Cleaning

---

### 📅 6-Monthly (Jun / Dec)

* Geometric Calibration
* 3D Camera Software Check
* Cable Reel Wear Check
* Positioning Unit Lubrication
* Screw Torque Check
* PCB Stopper Check
* Conveyor Belt Check

---

### 📅 Yearly

* Electrical Cable Inspection
* Sensors & Switches Check
* Magnet Check
* Positioning Unit Lubrication

---

## 📄 License

This project is for internal/company use. Modify as needed.

---

## 👨‍💻 Author

Developed for Viscom machine maintenance tracking.
