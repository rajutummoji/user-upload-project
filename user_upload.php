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

$dbuser = $options['u'] ?? null;
$dbpass = $options['p'] ?? null;
$dbhost = $options['h'] ?? 'localhost';
$csvfile = $options['file'] ?? null;

// Globals for logging and summary
$errorlog = [];
$summary = [
    'total_rows' => 0,
    'valid_rows' => 0,
    'invalid_rows' => 0,
    'duplicates_in_csv' => 0,
    'duplicates_in_db' => 0
];

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

// Format name/surname
function formatname($value) {
    return ucfirst(strtolower(trim(preg_replace('/\s+/', ' ', $value))));
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

// Insert user
function insertuser($pdo, $name, $surname, $email, $row) {
    global $errorlog;
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, surname, email) VALUES (?, ?, ?)");
        $stmt->execute([$name, $surname, $email]);
        return true;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate key') !== false) {
            $errorlog[] = "Row {$row}: Duplicate email in DB - '{$email}'";
        } else {
            $errorlog[] = "Row {$row}: DB Error - " . $e->getMessage();
        }
        return false;
    }
}

// Process CSV
function processcsvfile($csvfile, $dbuser, $dbpass, $dbhost, $dryrun) {
    global $errorlog, $summary;

    if (!file_exists($csvfile)) {
        echo "Error: File '{$csvfile}' not found.\n";
        exit(1);
    }

    if (($handle = fopen($csvfile, "r")) !== false) {
        $row = 0;
        $pdo = null;
        $seenemails = [];

        if (!$dryrun) {
            if (!$dbuser || !$dbpass) {
                echo "Error: Database credentials required.\n";
                exit(1);
            }
            $pdo = getpdoconnection($dbhost, $dbuser, $dbpass);
        }

        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if ($row === 1) continue; // Skip header

            $summary['total_rows']++;

            if (empty(trim(implode('', $data)))) {
                $errorlog[] = "Row {$row}: Empty row skipped.";
                $summary['invalid_rows']++;
                continue;
            }

            if (empty(trim($data[0]))) {
                $errorlog[] = "Row {$row}: Missing name. Skipped.";
                $summary['invalid_rows']++;
                continue;
            }

            if (empty(trim($data[1]))) {
                $errorlog[] = "Row {$row}: Missing surname. Skipped.";
                $summary['invalid_rows']++;
                continue;
            }

            if (empty(trim($data[2]))) {
                $errorlog[] = "Row {$row}: Missing email. Skipped.";
                $summary['invalid_rows']++;
                continue;
            }

            $name = formatname($data[0]);
            $surname = formatname($data[1]);
            $email = strtolower(trim($data[2]));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || substr_count($email, '@') !== 1) {
                $errorlog[] = "Row {$row}: Invalid email - '{$email}'";
                $summary['invalid_rows']++;
                continue;
            }

            if (in_array($email, $seenemails)) {
                $errorlog[] = "Row {$row}: Duplicate in CSV - '{$email}'";
                $summary['duplicates_in_csv']++;
                continue;
            }
            $seenemails[] = $email;

            if (!$dryrun && $pdo) {
                $inserted = insertuser($pdo, $name, $surname, $email, $row);
                if (!$inserted) {
                    $summary['duplicates_in_db']++;
                    continue;
                }
            }

            $summary['valid_rows']++;
        }

        fclose($handle);
        echo "Processing completed.\n";
        displaysummaryreport();
    } else {
        echo "Failed to open file: {$csvfile}\n";
        exit(1);
    }
}

// Summary Report
function displaysummaryreport() {
    global $summary, $errorlog;

    echo "\n=== Summary Report ===\n";
    echo "Total Rows            : {$summary['total_rows']}\n";
    echo "Valid Rows Inserted   : {$summary['valid_rows']}\n";
    echo "Invalid Rows Skipped  : {$summary['invalid_rows']}\n";
    echo "CSV Duplicates Skipped: {$summary['duplicates_in_csv']}\n";
    echo "DB Duplicates Skipped : {$summary['duplicates_in_db']}\n";

    if (!empty($errorlog)) {
        echo "\n=== Error Log ===\n";
        foreach ($errorlog as $error) {
            echo "- {$error}\n";
        }
    }
}

// Execution flow
if (isset($options['create_table'])) {
    createuserstable($dbuser, $dbpass, $dbhost);
    exit;
}

if (!$csvfile) {
    echo "Error: No CSV file provided. Use --file=filename.csv\n";
    exit(1);
}

processcsvfile($csvfile, $dbuser, $dbpass, $dbhost, isset($options['dry_run']));
