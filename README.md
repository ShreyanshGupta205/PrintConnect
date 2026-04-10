# PrintConnect – Online Cyber Cafe Service Booking System

PrintConnect is a full-stack web application built using PHP, MySQL, and Bootstrap 5. It connects customers with local cyber cafes to upload documents for printing, scanning, and binding services online.

## Project Structure
The project uses a standard 3-tier architecture:
- **Presentation Layer**: HTML5, CSS3, Bootstrap 5, Javascript
- **Business Logic Layer**: PHP 8+
- **Data Layer**: MySQL Database

## Features
- **3 User Roles**: Admin, Cafe Owner, Customer
- **Customer**: Upload files (PDF, DOCX, JPG - max 10MB), choose print settings, track active orders.
- **Cafe Owner**: Subscribe to the platform, manage shop profile, view incoming orders, and update statuses (Pending -> Printing -> Ready -> Completed).
- **Admin**: View platform statistics, manage all users, and approve/activate new shop profiles.

## Installation Instructions (for XAMPP/WAMP)

1. **Install XAMPP**
   - Download and install [XAMPP](https://www.apachefriends.org/index.html).
   - Start the **Apache** and **MySQL** modules from the XAMPP Control Panel.

2. **Clone/Copy Project Files**
   - Copy the entire `PrintConnect` folder into the XAMPP `htdocs` directory (usually located at `C:\xampp\htdocs\PrintConnect`).

3. **Database Setup**
   - Open your browser and navigate to `http://localhost/phpmyadmin/`.
   - The system uses a database named `printconnect`.
   - Import the `database.sql` file provided in the root of the project to automatically create the database structure and insert sample data.
   - *Alternatively, copy the contents of `database.sql` and run it in the SQL tab in phpMyAdmin.*

4. **Directory Permissions**
   - Ensure the `uploads/` directory exists in the root folder and has write permissions so users can upload files successfully.

5. **Start the Application**
   - Open your browser and navigate to: [http://localhost/PrintConnect](http://localhost/PrintConnect)

## Login Credentials (Sample Data)
The system comes with 3 pre-configured accounts (one for each role).

**Admin Account:**
- **Email:** `admin@printconnect.com`
- **Password:** `password123`

**Cafe Owner Account:**
- **Email:** `owner1@printconnect.com`
- **Password:** `password123`

**Customer Account:**
- **Email:** `customer1@printconnect.com`
- **Password:** `password123`

## Testing
- **Login:** Use the credentials above to verify role-based redirects.
- **File Upload:** Login as a customer, click 'New Print Job', select the newly created cafe, upload a test PDF (under 10MB), and submit.
- **Order Processing:** Login as the owner, go to incoming orders, and change the status of the newly created order.

## Notes
- To test the registration flow securely, passwords are hashed using bcrypt (`password_hash()`).
- The user dashboard provides visual statistics dynamically generated from the MySQL database.
- Files uploaded by customers go into the `uploads/` directory and are renamed to a secure hash.

---
*Created by Antigravity*
