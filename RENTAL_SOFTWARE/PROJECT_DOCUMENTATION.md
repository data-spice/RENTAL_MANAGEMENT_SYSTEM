# PangaRms Project Documentation

This document provides a detailed overview of the PangaRms (Property Management System) project, covering its architecture, database schema, user roles, core functionalities, and inter-file interactions.

## 1. Project Structure

The project follows a modular structure with PHP files for different functionalities and dedicated directories for CSS and JavaScript assets.

-   `/` (Root Directory): Contains all primary PHP application files.
-   `/css/`: Stores Cascading Style Sheets for styling the application's user interface.
-   `/js/`: Contains JavaScript files for client-side interactivity.

**Key Files in Root Directory:**

-   `index.php`: The main entry point for the application, handling login and registration.
-   `login.php`: Processes user login and registration requests.
-   `logout.php`: Handles user logout.
-   `config.php`: Contains database connection details and other global configurations.
-   `sidebar.php`: Defines the navigation sidebar, included in most pages.
-   `dashboard.php`: Tenant's main dashboard, displaying rent status, notifications, issues, and latest announcement.
-   `caretaker_dashboard.php`: Caretaker's main dashboard, displaying revenue, tenants, issues, and past announcements, with a link to create new ones.
-   `tenant_settings.php`: Allows tenants to manage their profile settings.
-   `caretaker_settings.php`: Allows caretakers to manage their profile settings.
-   `issues.php`: Manages issue reporting and viewing for tenants, and resolution for caretakers.
-   `view_issue.php`: Displays details of a specific issue.
-   `rent.php`: Allows tenants to view and manage their rent payments.
-   `notifications.php`: Displays user notifications.
-   `register_tenant.php`: Allows caretakers to register new tenants.
-   `create_announcement.php`: Dedicated page for caretakers to create new announcements.
-   `database.sql`: SQL script for setting up the database schema.
-   `help.php`: Provides help documentation.

## 2. Database Schema

The `panga_rms` database consists of several tables designed to manage users, properties, rentals, issues, notifications, payments, and announcements.

### `users` Table

Stores user information and their roles within the system.

| Column             | Type                          | Attributes                         | Description                           |
| :----------------- | :---------------------------- | :--------------------------------- | :------------------------------------ |
| `id`               | `INT`                         | `AUTO_INCREMENT`, `PRIMARY KEY`    | Unique user identifier                |
| `fullname`         | `VARCHAR(100)`                | `NOT NULL`                         | User's full name                      |
| `email`            | `VARCHAR(100)`                | `NOT NULL`, `UNIQUE`, `INDEX`      | User's email address                  |
| `username`         | `VARCHAR(50)`                 | `NOT NULL`, `UNIQUE`, `INDEX`      | User's chosen username                |
| `password`         | `VARCHAR(255)`                | `NOT NULL`                         | Hashed password                       |
| `phone_number`     | `VARCHAR(20)`                 |                                    | User's phone number                   |
| `role`             | `ENUM('tenant', 'caretaker')` | `NOT NULL`, `DEFAULT 'tenant'`, `INDEX` | User's role in the system             |
| `created_at`       | `TIMESTAMP`                   | `DEFAULT CURRENT_TIMESTAMP`        | Timestamp of account creation         |
| `updated_at`       | `TIMESTAMP`                   | `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | Last update timestamp                 |
| `last_login`       | `TIMESTAMP`                   | `NULL`                             | Last login timestamp                  |
| `status`           | `ENUM('active', 'inactive', 'suspended')` | `DEFAULT 'active'`             | Account status                        |

### `properties` Table

(Currently without a direct landlord link) Stores property details.

| Column         | Type            | Attributes                      | Description                 |
| :------------- | :-------------- | :------------------------------ | :-------------------------- |
| `id`           | `INT`           | `AUTO_INCREMENT`, `PRIMARY KEY` | Unique property identifier  |
| `property_name`| `VARCHAR(200)`  | `NOT NULL`                      | Name of the property        |
| `address`      | `TEXT`          | `NOT NULL`                      | Full address of the property|
| `description`  | `TEXT`          |                                 | Optional property description|
| `created_at`   | `TIMESTAMP`     | `DEFAULT CURRENT_TIMESTAMP`     | Timestamp of property creation |

### `rentals` Table

Manages rental agreements between tenants and properties, with caretaker assignment.

| Column         | Type               | Attributes                      | Description                           |
| :------------- | :----------------- | :------------------------------ | :------------------------------------ |
| `id`           | `INT`              | `AUTO_INCREMENT`, `PRIMARY KEY` | Unique rental identifier              |
| `property_id`  | `INT`              | `NOT NULL`, `FOREIGN KEY`       | References `properties(id)`           |
| `tenant_id`    | `INT`              | `NOT NULL`, `FOREIGN KEY`       | References `users(id)` (tenant)       |
| `caretaker_id` | `INT`              | `FOREIGN KEY`                   | References `users(id)` (caretaker)    |
| `room_number`  | `VARCHAR(50)`      |                                 | Room number within the property       |
| `rent_amount`  | `DECIMAL(10, 2)`   | `NOT NULL`                      | Monthly rent amount                   |
| `start_date`   | `DATE`             | `NOT NULL`                      | Start date of the rental agreement    |
| `end_date`     | `DATE`             | `NULL`                          | End date of the rental agreement      |
| `status`       | `ENUM('active', 'ended', 'pending')` | `DEFAULT 'pending'`     | Status of the rental agreement        |
| `created_at`   | `TIMESTAMP`        | `DEFAULT CURRENT_TIMESTAMP`     | Timestamp of rental creation          |

### `issues` Table

Records reported issues by tenants.

| Column         | Type                               | Attributes                      | Description                   |
| :------------- | :--------------------------------- | :------------------------------ | :---------------------------- |
| `id`           | `INT`                              | `AUTO_INCREMENT`, `PRIMARY KEY` | Unique issue identifier       |
| `user_id`      | `INT`                              | `NOT NULL`, `FOREIGN KEY`       | References `users(id)` (tenant who reported) |
| `issue_type`   | `ENUM('repair', 'complaint', 'maintenance', 'other')` | `NOT NULL`             | Type of issue                 |
| `room_number`  | `VARCHAR(50)`                      |                                 | Room number where issue occurred |
| `description`  | `TEXT`                             | `NOT NULL`                      | Detailed description of the issue |
| `status`       | `ENUM('pending', 'in_progress', 'resolved', 'closed')` | `NOT NULL`, `DEFAULT 'pending'` | Current status of the issue   |
| `created_at`   | `TIMESTAMP`                        | `DEFAULT CURRENT_TIMESTAMP`     | Timestamp of issue creation   |
| `updated_at`   | `TIMESTAMP`                        | `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | Last update timestamp         |

### `notifications` Table

Stores general notifications to be sent to users.

| Column       | Type            | Attributes                      | Description                      |
| :----------- | :-------------- | :------------------------------ | :------------------------------- |
| `id`         | `INT`           | `AUTO_INCREMENT`, `PRIMARY KEY` | Unique notification identifier   |
| `sender_id`  | `INT`           | `NOT NULL`, `FOREIGN KEY`       | References `users(id)` (sender)  |
| `title`      | `VARCHAR(255)`  | `NOT NULL`                      | Title of the notification        |
| `message`    | `TEXT`          | `NOT NULL`                      | Full content of the notification |
| `created_at` | `TIMESTAMP`     | `DEFAULT CURRENT_TIMESTAMP`     | Timestamp of notification creation |
| `issue_id`   | `INT`           | `FOREIGN KEY`                   | Optional: References `issues(id)` if notification is related to an issue |

### `notification_recipients` Table

Links notifications to their recipients and tracks read status.

| Column          | Type      | Attributes                      | Description                        |
| :-------------- | :-------- | :------------------------------ | :--------------------------------- |
| `id`            | `INT`     | `AUTO_INCREMENT`, `PRIMARY KEY` | Unique recipient entry identifier  |
| `notification_id`| `INT`     | `NOT NULL`, `FOREIGN KEY`       | References `notifications(id)`     |
| `recipient_id`  | `INT`     | `NOT NULL`, `FOREIGN KEY`       | References `users(id)` (recipient) |
| `is_read`       | `BOOLEAN` | `NOT NULL`, `DEFAULT FALSE`     | Flag indicating if notification is read |
| `read_at`       | `TIMESTAMP`| `NULL`                          | Timestamp when notification was read |

### `payments` Table

Records rent payment details.

| Column            | Type               | Attributes                      | Description                       |
| :---------------- | :----------------- | :------------------------------ | :-------------------------------- |
| `id`              | `INT`              | `AUTO_INCREMENT`, `PRIMARY KEY` | Unique payment identifier         |
| `rental_id`       | `INT`              | `NOT NULL`, `FOREIGN KEY`, `UNIQUE KEY (rental_id, payment_for_month)` | References `rentals(id)`          |
| `payment_for_month`| `DATE`             | `NOT NULL`                      | The month the payment is for      |
| `amount_due`      | `DECIMAL(10, 2)`   | `NOT NULL`                      | Total amount expected             |
| `amount_paid`     | `DECIMAL(10, 2)`   | `DEFAULT 0.00`                  | Amount actually paid              |
| `status`          | `ENUM('paid', 'not_paid', 'partially_paid', 'pending_confirmation')` | `NOT NULL`, `DEFAULT 'not_paid'` | Current status of the payment     |
| `payment_date`    | `TIMESTAMP`        | `NULL`                          | Timestamp of payment              |
| `transaction_code`| `VARCHAR(100)`     |                                 | Payment transaction code          |
| `payment_method`  | `VARCHAR(50)`      |                                 | Method of payment                 |
| `created_at`      | `TIMESTAMP`        | `DEFAULT CURRENT_TIMESTAMP`     | Timestamp of record creation      |
| `updated_at`      | `TIMESTAMP`        | `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | Last update timestamp             |

### `announcements` Table

Stores announcements made by caretakers.

| Column         | Type            | Attributes                      | Description                   |
| :------------- | :-------------- | :------------------------------ | :---------------------------- |
| `id`           | `INT`           | `AUTO_INCREMENT`, `PRIMARY KEY` | Unique announcement identifier |
| `caretaker_id` | `INT`           | `NOT NULL`, `FOREIGN KEY`       | References `users(id)` (caretaker who made announcement) |
| `title`        | `VARCHAR(255)`  | `NOT NULL`                      | Title of the announcement     |
| `message`      | `TEXT`          | `NOT NULL`                      | Full content of the announcement |
| `created_at`   | `TIMESTAMP`     | `DEFAULT CURRENT_TIMESTAMP`     | Timestamp of announcement creation |

## 3. User Roles and Authentication

The system supports two primary user roles: `tenant` and `caretaker`.

-   **Roles:** Defined in the `users.role` ENUM as `'tenant', 'caretaker'`.
-   **Authentication (`login.php`):** Users log in using their username/email and password. The system verifies credentials and sets `$_SESSION` variables (`user_id`, `fullname`, `username`, `email`, `role`, `logged_in`).
-   **Registration (`login.php`):** New users (tenants) can register. Caretakers are likely created by an admin or another caretaker.
-   **Authorization:** Access to certain pages and features is controlled based on the `$_SESSION['role']` variable. For example, `register_tenant.php` and `create_announcement.php` are restricted to caretakers.
-   **Password Hashing:** Passwords are hashed using `password_hash()` for secure storage.
-   **Prepared Statements:** The application consistently uses prepared statements (`mysqli_stmt`) to prevent SQL injection vulnerabilities.

## 4. Core Functionalities

### User Management
-   **Registration (`login.php`):** Allows new tenants to register. Caretakers can register new tenants via `register_tenant.php`.
-   **Login (`index.php`, `login.php`):** Provides a login interface with role selection.
-   **Settings (`tenant_settings.php`, `caretaker_settings.php`):** Users can update their profile details (fullname, phone, email) and change their password.

### Issue Management
-   **Reporting (`issues.php`):** Tenants can file new issues, which are stored in the `issues` table.
-   **Viewing (`issues.php`, `view_issue.php`):** Tenants can view their own pending issues. Caretakers can view all pending issues under their management.
-   **Resolution (`issues.php`):** Caretakers can mark issues as 'resolved', triggering a notification to the tenant. Tenants can mark resolved issues as 'closed'.

### Rent Management
-   **Viewing Status (`dashboard.php`, `rent.php`):** Tenants can view their current month's rent status and rent amount due.
-   **Payment (`rent.php`):** Tenants can simulate checking a payment, which updates their rent status in the `payments` table.

### Notification System
-   **Creation (`issues.php`, `caretaker_dashboard.php` indirectly):** Notifications are generated for events like issue resolution.
-   **Display (`notifications.php`, `dashboard.php`):** Users can view unread notifications. The dashboard shows a count of unread notifications.

### Announcement System
-   **Creation (`create_announcement.php`):** Caretakers can create and post new announcements, stored in the `announcements` table.
-   **Caretaker View (`caretaker_dashboard.php`):** Caretakers can view a list of all announcements they have posted.
-   **Tenant View (`dashboard.php`):** The latest announcement posted by any caretaker is displayed on the tenant's dashboard.

## 5. Inter-file Interactions

-   **`config.php`:** Included in almost every PHP file to establish database connection (`getDBConnection()`) and manage session.
-   **`sidebar.php`:** Included in all main dashboard and functionality pages to provide consistent navigation based on the user's role.
-   **Session Management:** `$_SESSION` superglobal is extensively used for user authentication state (`logged_in`, `user_id`, `role`, etc.) and for passing messages (success/error).
-   **Database Interactions:** All database operations are centralized through `getDBConnection()` from `config.php` and use `mysqli` prepared statements.
-   **Redirections:** `header('Location: ...')` is used for navigation and redirecting after form submissions or unauthorized access attempts.

## 6. Security Considerations

-   **Password Hashing:** User passwords are not stored in plain text but are hashed using `password_hash()` before being saved to the database. `password_verify()` is used for authentication.
-   **Prepared Statements:** All SQL queries that involve user-provided data utilize `mysqli` prepared statements with parameter binding, effectively preventing SQL injection attacks.
-   **Session Management:** Relies on PHP's built-in session management for tracking user login state.
-   **Role-Based Access Control:** Access to certain pages and actions is restricted based on the user's assigned role, preventing unauthorized users from accessing sensitive functionalities.
-   **Input Validation:** Basic input validation (e.g., `empty()`, `trim()`, `filter_var()`) is performed on user inputs, though more comprehensive validation could be implemented.
