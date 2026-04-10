<?php
// Update admin password in SQLite
$db = new PDO('sqlite:var/data.db');

$hash = '$2y$10$/upcVy8jRyHBKNJB0Zp3H.Tnc76JbuESusBW0hBzAzqahLBjgvjk6';

$stmt = $db->prepare("UPDATE user SET password = :password WHERE email = 'admin@onins.com'");
$stmt->execute([':password' => $hash]);

echo "Admin password updated successfully.\n";
echo "Email: admin@onins.com\n";
echo "Password: admin123\n";
