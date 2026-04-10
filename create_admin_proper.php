<?php
// Create admin user properly
$db = new PDO('sqlite:var/data.db');

// First check if user exists
$stmt = $db->query("SELECT COUNT(*) FROM user WHERE email = 'admin@onins.com'");
$count = $stmt->fetchColumn();

// Generate password hash using Symfony's NativePasswordHasher (bcrypt cost 12)
$hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);

if ($count == 0) {
    // Insert new user
    $stmt = $db->prepare("INSERT INTO user (username, email, full_name, roles, password, is_active, is_archived, created_at) VALUES (:username, :email, :full_name, :roles, :password, :is_active, :is_archived, :created_at)");
    $stmt->execute([
        ':username' => 'admin',
        ':email' => 'admin@onins.com',
        ':full_name' => 'Administrator',
        ':roles' => '["ROLE_ADMIN"]',
        ':password' => $hash,
        ':is_active' => 1,
        ':is_archived' => 0,
        ':created_at' => date('Y-m-d H:i:s')
    ]);
    echo "Admin user CREATED successfully!\n";
} else {
    // Update existing user
    $stmt = $db->prepare("UPDATE user SET password = :password WHERE email = 'admin@onins.com'");
    $stmt->execute([':password' => $hash]);
    echo "Admin user UPDATED successfully!\n";
}

echo "Email: admin@onins.com\n";
echo "Password: admin123\n";
echo "Hash: " . $hash . "\n";

// Verify
$stmt = $db->query("SELECT * FROM user WHERE email = 'admin@onins.com'");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nVerification: password_verify = " . (password_verify('admin123', $user['password']) ? 'true' : 'false') . "\n";
