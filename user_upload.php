<?php
// user_upload.php - Robust CSV to PostgreSQL user uploader

$options = getopt("u:p:h:", [
    "file:",
    "create_table",
    "dry_run",
    "help"
]);

if (isset($options['help'])) {
    displayhelp();
    exit;
}
// Display help
function displayhelp() {
    echo "Usage:\n";
    echo "--file [filename] : CSV file to process\n";
    echo "--create_table    : Create the 'users' table\n";
    echo "--dry_run         : Validate CSV without DB insert\n";
    echo "-u [username]     : PostgreSQL username\n";
    echo "-p [password]     : PostgreSQL password\n";
    echo "-h [host]         : PostgreSQL host (default: localhost)\n";
    echo "--help            : Show this help message\n";
}

// DB connection
function getpdoconnection($host, $user, $pass) {
    try {
        $dsn = "pgsql:host={$host};dbname=users";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo "Database connection failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Create table
function createuserstable($dbuser, $dbpass, $dbhost) {
    if (!$dbuser || !$dbpass) {
        echo "Error: Credentials required to create table.\n";
        exit(1);
    }
    $pdo = getpdoconnection($dbhost, $dbuser, $dbpass);
    $sql = "DROP TABLE IF EXISTS users;
            CREATE TABLE users (
                name VARCHAR(255) NOT NULL,
                surname VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE
            );";
    try {
        $pdo->exec($sql);
        echo "Users table (re)created successfully.\n";
    } catch (PDOException $e) {
        echo "Error creating table: " . $e->getMessage() . "\n";
    }
}
