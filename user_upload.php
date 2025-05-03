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
