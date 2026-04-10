<?php
// Check database user
$db = new PDO('sqlite:var/data.db');
$stmt = $db->query('SELECT id, username, email, full_name, roles, password, is_active, is_archived FROM user');
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "User found:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Full Name: " . $user['full_name'] . "\n";
    echo "Roles: " . $user['roles'] . "\n";
    echo "Password hash: " . substr($user['password'], 0, 60) . "...\n";
    echo "Is Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
    echo "Is Archived: " . ($user['is_archived'] ? 'Yes' : 'No') . "\n";
    
    // Test password verification
    echo "\nTesting password verification:\n";
    $test = password_verify('admin123', $user['password']);
    echo "password_verify('admin123', hash): " . ($test ? 'true' : 'false') . "\n";
} else {
    echo "No user found in database!\n";
}
