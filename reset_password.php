<?php
$hash = password_hash('admin123', PASSWORD_BCRYPT);
echo "Hash: " . $hash . "\n";
echo "Verify: " . (password_verify('admin123', $hash) ? 'true' : 'false') . "\n";

// Update the database
$db = new PDO('sqlite:var/data.db');
$stmt = $db->prepare("UPDATE user SET password = :password WHERE email = 'admin@onins.com'");
$stmt->execute([':password' => $hash]);
echo "Password updated for admin@onins.com\n";
