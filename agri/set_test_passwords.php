<?php
// Update test user passwords to 'password123'
require_once __DIR__ . '/config/bootstrap.php';
$db = $GLOBALS['conn'] ?? null;
$conn = $db;

$emails = [
    'farmer@test.com',
    'consumer@test.com',
    'admin@test.com'
];

$newPassword = 'password123';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

foreach ($emails as $email) {
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    if (!$stmt) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error . "\n";
        continue;
    }
    $stmt->bind_param('ss', $hash, $email);
    if ($stmt->execute()) {
        echo "Updated password for: $email\n";
    } else {
        echo "Failed to update $email: (" . $stmt->errno . ") " . $stmt->error . "\n";
    }
    $stmt->close();
}

echo "Done. You can now log in with password: $newPassword\n";

$conn->close();

?>
