<?php
// Reset admin password using Symfony's password hasher
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

// Create Symfony's native password hasher (uses bcrypt with cost 12)
$hasher = new NativePasswordHasher(12);

// Generate hash
$plaintextPassword = 'admin123';
$hash = $hasher->hash($plaintextPassword);

echo "Generated hash: " . $hash . "\n";
echo "Verification test: " . ($hasher->verify($hash, $plaintextPassword) ? "PASS" : "FAIL") . "\n";

// Update the database
$db = new PDO('sqlite:var/data.db');
$stmt = $db->prepare("UPDATE user SET password = :password WHERE email = 'admin@onins.com'");
$stmt->execute([':password' => $hash]);
echo "Password updated in database\n";
echo "\nYou can now login with:\n";
echo "Email: admin@onins.com\n";
echo "Password: admin123\n";
