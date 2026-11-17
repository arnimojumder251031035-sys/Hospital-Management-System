# Hospital Management System

A PHP + MySQL academic project for managing patients, doctors, departments, appointments and bills.

## Project files
- `config.php` — PDO connection to MySQL.
- `database.sql` — creates all six tables and inserts fictional demo records.
- `install.php` — creates any missing tables and creates the initial admin account.
- `index.php` — PHP application with prepared SQL queries and CRUD operations.
- `style.css` — page styling.

## Configure the MySQL connection
1. Open `config.php`.
2. Enter the MySQL hostname, database name, username and password shown in your InfinityFree control panel.
3. Save the file. Never publish your database password in a public GitHub repository.

## Import database.sql on InfinityFree
1. Open InfinityFree Control Panel > MySQL Databases.
2. Find your hospital database and open its phpMyAdmin link.
3. Select the hospital database in the left sidebar.
4. Click the **Import** tab.
5. Choose `database.sql` from this project folder.
6. Click **Import** or **Go** and wait for the success message.
7. Visit your website's `install.php` once. It will create the initial admin user if no user exists.
8. Sign in at `index.php` using `admin` / `ChangeMe123!` only if this is a fresh installation. Change the password immediately.
9. Delete `install.php` from hosting after installation.

If the tables already exist, the SQL file uses `CREATE TABLE IF NOT EXISTS` and will not drop or replace them. The demo inserts check for existing demo identifiers to reduce duplicates.

## Modules and database tables
- `patients` — patient details.
- `doctors` — doctor details and department link.
- `departments` — department names and descriptions.
- `appointments` — patient/doctor appointment records.
- `bills` — patient billing records.
- `users` — administrator login.

## Local XAMPP setup
1. Copy the project folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL.
3. Create a database in phpMyAdmin.
4. Set the matching local database details in `config.php`.
5. Import `database.sql` into that database, or visit `install.php` to create the tables.
6. Open `http://localhost/hospital_management/`.

## Important
- This is an educational demonstration, not a production hospital system.
- All included patient, doctor, appointment and billing entries are fictional demo data.
- Do not enter real patient medical information or sensitive personal data.
- The original application includes administrator login. Do not expose public create/edit/delete access to real records.
