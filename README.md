# IDMA Student Management System (SMS) & Learning Management System (LMS)

## Overview
A comprehensive, secure web-based platform combining Student Management and Learning Management functionalities.

**Institution:** Institute of Development Management - Eswatini  
**Technology Stack:** PHP, MySQL, HTML5, CSS3, JavaScript  
**Deployment:** XAMPP (Apache + MySQL)

## Features

### User Roles
- **Admin:** System administration and oversight
- **Lecturer:** Grade management and course content
- **Student:** Academic portal and results access
- **Finance:** Payment tracking and billing
- **Admissions:** Student registration management

### Core Functionalities

#### Authentication & Security
- ✅ Role-based access control (RBAC)
- ✅ Student login with 9-character Student ID
- ✅ Admin/Lecturer email/username authentication
- ✅ Password hashing (bcrypt)
- ✅ Session management
- ✅ CSRF token protection
- ✅ SQL injection prevention

#### Student Portal
- ✅ Self-registration (after 40% deposit payment)
- ✅ View academic results
- ✅ Track GPA and credits
- ✅ Download printable transcripts
- ✅ Receive notifications

#### Lecturer Portal
- ✅ Grade management interface
- ✅ Set weightings:
  - Assignments (Individual & Group): 40%
  - Test: 40%
  - Exam: 60%
- ✅ View student performance
- ✅ Generate grade reports

#### Finance Portal
- ✅ Payment tracking
- ✅ Fee schedules
- ✅ Payment verification
- ✅ Financial reports

#### Admin Dashboard
- ✅ User management
- ✅ System configuration
- ✅ Audit trails
- ✅ Report generation

### Database Features
- ✅ Secure MySQL schema
- ✅ Normalized tables
- ✅ Audit logging
- ✅ Referential integrity

### Design
- ✅ Premium UI with green and white color scheme (IDMA branding)
- ✅ Responsive layout
- ✅ IDMA logo integration
- ✅ Professional dashboard

## Installation

### Prerequisites
- XAMPP (PHP 7.4+, MySQL 5.7+)
- Modern web browser
- Administrator access

### Setup Steps

1. **Install XAMPP**
   - Download from https://www.apachefriends.org/
   - Install with Apache and MySQL modules

2. **Clone Repository**
   ```bash
   cd C:\xampp\htdocs  # Windows
   # or
   cd /opt/lampp/htdocs  # Linux
   
   git clone https://github.com/PetrosMnisi/System_Repo.git idma-sms-lms
   cd idma-sms-lms
   ```

3. **Database Setup**
   - Start XAMPP (Apache & MySQL)
   - Open http://localhost/phpmyadmin
   - Import `database/idma_sms_lms.sql`
   - Database name: `idma_sms_lms`

4. **Configuration**
   - Edit `config/database.php`
   - Set database credentials

5. **Access Application**
   - Navigate to http://localhost/idma-sms-lms
   - Login with default credentials

## Default Login Credentials

### Admin
- **Email:** admin@idma.sz
- **Password:** Admin@123

### Lecturer
- **Username:** lecturer1
- **Password:** Lecturer@123

### Student (Example)
- **Student ID:** STU000001
- **Password:** Student@123

### Finance
- **Username:** finance1
- **Password:** Finance@123

### Admissions
- **Username:** admissions1
- **Password:** Admissions@123

## Project Structure

```
idma-sms-lms/
├── config/               # Configuration files
├── database/             # SQL schema
├── public/               # Publicly accessible files
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   ├── images/           # Images & logo
│   └── uploads/          # User uploads
├── src/
│   ├── controllers/      # Business logic
│   ├── models/           # Database models
│   ├── views/            # HTML templates
│   ├── helpers/          # Utility functions
│   └── middleware/       # Authentication & validation
├── includes/             # Reusable components
├── admin/                # Admin panel
├── lecturer/             # Lecturer dashboard
├── student/              # Student portal
├── finance/              # Finance module
├── admissions/           # Admissions module
└── index.php             # Entry point
```

## Security Features

- ✅ **Password Security:** bcrypt hashing with salt
- ✅ **Session Management:** Secure session handling
- ✅ **Input Validation:** Server-side validation
- ✅ **SQL Injection Prevention:** Prepared statements
- ✅ **CSRF Protection:** Token-based validation
- ✅ **Audit Logging:** Track all user activities
- ✅ **Role-Based Access:** Enforce permissions
- ✅ **Secure Headers:** XSS and clickjacking prevention

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `POST /api/auth/register` - Student registration
- `POST /api/auth/refresh-token` - Refresh session

### Student Module
- `GET /api/student/profile` - Get student profile
- `GET /api/student/results` - Get academic results
- `GET /api/student/gpa` - Calculate GPA
- `GET /api/student/transcript` - Download transcript
- `GET /api/student/notifications` - Get notifications

### Lecturer Module
- `POST /api/lecturer/grades` - Submit grades
- `GET /api/lecturer/courses` - Get assigned courses
- `PUT /api/lecturer/weightings` - Update grade weightings
- `GET /api/lecturer/reports` - Generate reports

### Finance Module
- `GET /api/finance/payments` - Get payment records
- `POST /api/finance/verify-payment` - Verify payment
- `GET /api/finance/reports` - Financial reports

### Admin Module
- `GET /api/admin/users` - List all users
- `POST /api/admin/users` - Create user
- `PUT /api/admin/users/:id` - Update user
- `DELETE /api/admin/users/:id` - Delete user
- `GET /api/admin/audit-log` - View audit logs

## Database Schema

Key tables:
- `users` - User accounts
- `students` - Student records
- `lecturers` - Lecturer information
- `courses` - Course definitions
- `modules` - Module details
- `grades` - Student grades
- `payments` - Payment records
- `audit_logs` - Activity tracking
- `notifications` - System notifications

## Reporting

Printable reports include:
- Academic transcripts
- Grade reports
- GPA calculations
- Payment summaries
- Audit logs
- Performance analytics

## Support

For issues or feature requests, contact the development team.

## License

Institute of Development Management - Eswatini © 2026

---

**Last Updated:** June 13, 2026
