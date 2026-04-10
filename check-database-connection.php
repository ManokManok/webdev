<?php
/**
 * Database Connection Test Script
 * Run this to test your database connection
 */

echo "Testing Database Connection...\n\n";

// Test different connection strings
$connections = [
    '127.0.0.1' => 'mysql:host=127.0.0.1;port=3306',
    'localhost' => 'mysql:host=localhost;port=3306',
    'IPv6 localhost' => 'mysql:host=[::1];port=3306',
];

$credentials = [
    ['root', ''],
    ['root', 'root'],
    ['symfony', 'symfony'],
];

foreach ($connections as $name => $dsn) {
    echo "Testing connection to: $name\n";
    foreach ($credentials as $cred) {
        list($user, $pass) = $cred;
        try {
            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "  ✓ SUCCESS with user: $user, password: " . ($pass ?: 'empty') . "\n";
            
            // Try to list databases
            $stmt = $pdo->query("SHOW DATABASES");
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "  Available databases: " . implode(', ', $databases) . "\n";
            break 2;
        } catch (PDOException $e) {
            // Continue to next credential
        }
    }
    echo "  ✗ Failed with all credentials\n\n";
}

echo "\nIf no connection worked, make sure:\n";
echo "1. Docker Desktop is running\n";
echo "2. MySQL container is started: docker-compose up -d database\n";
echo "3. Or MySQL service is running locally\n";



