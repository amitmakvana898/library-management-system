# 📚 Cyber Emerald Luxury - Library Management System (LMS)

A next-generation, high-performance, clean luxury **Library Management System** built with native **PHP, MySQL, Bootstrap 5.3, and Vanilla JavaScript**. Designed with an emerald/neo-mint glassmorphic aesthetic, dark/light theme engine, automated circulation workflows, and student self-service capabilities.

---

## ✨ Key Features

### 👨‍🎓 Student Portal
- 📖 **Modern Catalog Exploration**: Search by title, author, category, or ISBN with live filters.
- 🔍 **Command Palette (`Ctrl + K`) & Voice Search**: Instant global search across books, sections, and shortcuts.
- 📥 **1-Click Book Reservations**: Request book loans with real-time quota tracking (max 3 active books).
- 📱 **QR Code Scanner**: Scan physical book QR codes or student ID cards for instant details.
- 💖 **Personal Wishlist & Waitlist Alerts**: Save favorite books and receive instant alerts when out-of-stock books are returned.
- 🪪 **Digital Student ID Card**: Downloadable identity badge with dynamic QR code.
- 📄 **In-Browser E-Book Reader**: Read digital PDF textbooks seamlessly.
- ⭐ **Book Reviews & Ratings**: Share ratings and feedback on read titles.

### 🛡️ Librarian / Admin Portal
- 📊 **Executive Analytics Dashboard**: Real-time stats on active loans, overdue returns, revenue, and collection growth.
- 🔄 **Circulation Desk**: Seamless book issue & return workflows with automated inventory restock.
- 💳 **Automated Fine Settlement**: Daily overdue penalty calculation with instant digital receipt generation.
- 🏷️ **Category & Book Management**: Full CRUD support for physical books and digital e-books.
- 👥 **Student Member Management**: Track borrowing history, status, and activity.
- 📢 **Broadcast Announcements & Messages**: Direct communication channel with students.
- 📊 **1-Click Excel / CSV Report Exports**: Instant circulation reports and data backups.

---

## 🛠️ Technology Stack

- **Backend**: Native PHP (8.x / 7.4 compatible)
- **Database**: MySQL / MariaDB (Prepared Statements & Indexed Relations)
- **Frontend**: HTML5, CSS3, Vanilla ES6+ JavaScript, Bootstrap 5.3
- **Icons & Fonts**: FontAwesome 6.5 Pro, Google Fonts (Plus Jakarta Sans, Space Grotesk, JetBrains Mono)
- **Security**: Strict Session Auth Guards, CSRF prevention, SQL Injection protection, XSS Sanitization.

---

## 🚀 Installation & Setup

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/YOUR_USERNAME/library-management-system.git
   ```

2. **Move to XAMPP / WAMP web root**:
   Place the project folder inside your `htdocs` directory (e.g. `C:/xampp/htdocs/lms` or `C:/xampp/htdocs/library_management_system`).

3. **Import the Database**:
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin/`).
   - Create a database named `library_management_system`.
   - Import `database.sql` into the database.

4. **Configure Database Connection**:
   - Verify `includes/config.php` (default: `localhost`, user: `root`, password: ``, db: `library_management_system`).

5. **Start Apache & MySQL**:
   - Launch your web browser and navigate to:
     ```
     http://localhost/lms/
     ```

---

## 🔑 Default Demo Accounts

| Role | Email | Password |
| :--- | :--- | :--- |
| **Librarian / Admin** | `admin@gmail.com` | `admin123` |
| **Student** | `amit@gmail.com` | `111111` |

*(You can also use the 1-Click "Fill Demo Credentials" buttons on the login screens)*

---

## 📄 License
This project is open-source and available under the [MIT License](LICENSE).
