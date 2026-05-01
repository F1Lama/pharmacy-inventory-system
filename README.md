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

## Screenshots

### Dashboard
<img width="1916" height="822" alt="لقطة شاشة 2026-05-01 164019" src="https://github.com/user-attachments/assets/28b11c1e-62f1-4496-a7ee-3e8d23b94195" />

### Medicines Page
<img width="1918" height="824" alt="لقطة شاشة 2026-05-01 164539" src="https://github.com/user-attachments/assets/a0da56b9-c369-4b7e-8df2-59799155a303" />

### Users Management
<img width="1902" height="813" alt="لقطة شاشة 2026-05-01 164704" src="https://github.com/user-attachments/assets/a0b7cbe7-c353-45fc-85d1-a92001f62782" />

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

Developed by Lama Aldhafyan
