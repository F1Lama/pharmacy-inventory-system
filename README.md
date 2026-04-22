# Pharmacy Inventory System

A full-stack web application built to help hospitals and pharmacies manage medicine inventory efficiently.

## Features

- Secure User Login / Logout
- Session Authentication
- Role-Based Access Control
  - Owner
  - Admin
  - Pharmacist
- Add Medicines
- Edit Medicines
- Delete Medicines
- Search Medicines
- Expiry Status Detection
  - Valid
  - Near Expiry
  - Expired
- Hospital-Based Data Separation
- Owner Panel for Creating Hospitals & Users
- Responsive Dashboard Design

## Technologies Used

- PHP
- MySQL
- HTML5
- CSS3
- Bootstrap 5
- JavaScript

## User Roles

### Owner
- Create hospitals
- Create system users
- Manage access levels

### Admin
- Add pharmacists/admins inside hospital
- Manage medicines

### Pharmacist
- Manage medicine inventory

## Security Features

- Password Hashing
- Prepared Statements
- Session Protection
- Role Authorization

## Project Structure

```bash
pharmacy-inventory-system/
│── index.php
│── dashboard.php
│── owner.php
│── logout.php
│── db.php
│── validation.js
│── CSS/
│   ├── base.css
│   ├── dash.css
│   ├── owner.css
│── bootstrap/
```

## How to Run

1. Install XAMPP

2. Move project folder into:

```bash
htdocs/
```

3. Start:
- Apache
- MySQL

4. Import database using phpMyAdmin

5. Open browser:

```bash
http://localhost/pharmacy-inventory-system
```

## Future Improvements

- Charts & Statistics
- Email Alerts for Expiry Medicines
- Export Reports PDF / Excel
- Better UI/UX
- Audit Logs

## Author

Developed by Lama
