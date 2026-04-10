<?php
/**
 * Database Setup Script
 * Creates MySQL database, user, runs migrations, and loads fixtures
 */

// Database configuration
$dbHost = '127.0.0.1';
$dbPort = '3309';
$rootUser = 'root';
$rootPassword = 'cabajon'; // MySQL root password from .env

$newDbName = 'nino';
$newUser = 'ninocabajon';
$newUserPassword = 'cabajon123';

echo "=== MySQL Database Setup ===\n\n";

try {
    // Connect as root
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort", $rootUser, $rootPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "[1/5] Connected to MySQL as root\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$newDbName` 
                CHARACTER SET utf8mb4 
                COLLATE utf8mb4_unicode_ci");
    echo "[2/5] Database '$newDbName' created/verified\n";
    
    // Create user and grant privileges
    $pdo->exec("CREATE USER IF NOT EXISTS '$newUser'@'%' IDENTIFIED BY '$newUserPassword'");
    $pdo->exec("GRANT ALL PRIVILEGES ON `$newDbName`.* TO '$newUser'@'%'");
    $pdo->exec("FLUSH PRIVILEGES");
    echo "[3/5] User '$newUser' created with privileges\n";
    
    echo "\n=== Database Setup Complete ===\n";
    echo "Database: $newDbName\n";
    echo "User: $newUser\n";
    echo "Password: $newUserPassword\n";
    echo "\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "\nManual setup required:\n";
    echo "1. Open phpMyAdmin: http://localhost/phpmyadmin\n";
    echo "2. Login as root (password: cabajon)\n";
    echo "3. Create database: nino\n";
    echo "4. Create user: ninocabajon / cabajon123\n";
    echo "5. Grant all privileges to user on database\n";
    exit(1);
}

// Now run Symfony commands
echo "[4/5] Running database migrations...\n";
chdir(__DIR__);
$migrateOutput = [];
$migrateReturn = 0;
exec('php bin/console doctrine:migrations:migrate --no-interaction 2>&1', $migrateOutput, $migrateReturn);
if ($migrateReturn === 0) {
    echo "Migrations completed successfully!\n";
} else {
    echo "Migration output:\n" . implode("\n", $migrateOutput) . "\n";
}

echo "\n[5/5] Loading data fixtures...\n";
$fixtureOutput = [];
$fixtureReturn = 0;
exec('php bin/console doctrine:fixtures:load --no-interaction --append 2>&1', $fixtureOutput, $fixtureReturn);
if ($fixtureReturn === 0) {
    echo "Fixtures loaded successfully!\n";
} else {
    echo "Fixture output:\n" . implode("\n", $fixtureOutput) . "\n";
}

echo "\n=== Setup Complete ===\n";
echo "Your system is now connected to MySQL and ready to use!\n";
echo "Access phpMyAdmin at: http://localhost/phpmyadmin\n";
echo "Database: $newDbName | User: $newUser | Password: $newUserPassword\n";
