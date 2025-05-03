# PHP User Upload Script

A command-line PHP script to parse and import user data from a CSV file into a PostgreSQL database with validation, formatting, and logging support.

## Features

- Parses CSV files containing user data (Name, Surname, Email).
- Capitalizes names and converts emails to lowercase.
- Validates email format with proper error handling.
- Optionally inserts valid data into a PostgreSQL database.
- Automatically creates the `users` table when required.
- Supports a **dry-run** mode for safe testing (no DB changes).
- Displays a helpful usage guide with `--help`.

## Requirements

- **PHP 8.3+**
- **PostgreSQL 13+**
- PHP `pdo_pgsql` extension enabled

## Installation

```bash
# Clone the repository
git clone https://github.com/rajutummoji/user-upload-script.git
cd user-upload-script

# Make the script executable
chmod +x user_upload.php
```

## Usage

### 1. Show Help

```bash
php user_upload.php --help
```

### 2. Create the `users` Table in PostgreSQL

```bash
php user_upload.php --create_table -u dbuser -p dbpass -h dbhost
```

### 3. Perform a Dry Run (Validate Only)

```bash
php user_upload.php --file users.csv --dry_run
```

### 4. Insert Data into the Database

```bash
php user_upload.php --file users.csv -u dbuser -p dbpass -h dbhost
```
