<?php
require_once __DIR__ . '/../config/bootstrap.php';

// Require consumer role
AuthMiddleware::requireRole(ROLE_CONSUMER);

$db = $GLOBALS['conn'] ?? null;
$userModel = new User($db);
$consumerModel = new ConsumerProfile($db);

$user_id = $_SESSION['user_id'];
$user = $userModel->findById($user_id);
$consumerProfile = $consumerModel->getByUserId($user_id);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update user info
    $userData = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'location' => $_POST['location'] ?? '',
        'bio' => $_POST['bio'] ?? ''
    ];

    if (empty($userData['first_name']) || empty($userData['last_name'])) {
        $error = 'First name and last name are required';
    } else {
        if ($userModel->update($user_id, $userData)) {
            // Update consumer profile
            $consumerData = [
                'preferences' => $_POST['preferences'] ?? '',
                'dietary_restrictions' => $_POST['dietary_restrictions'] ?? '',
                'address' => $_POST['address'] ?? '',
                'postal_code' => $_POST['postal_code'] ?? ''
            ];

            if ($consumerModel->update($user_id, $consumerData)) {
                $success = 'Profile updated successfully!';
                // Refresh data
                $user = $userModel->findById($user_id);
                $consumerProfile = $consumerModel->getByUserId($user_id);
            } else {
                $error = 'Failed to update consumer profile';
            }
        } else {
            $error = 'Failed to update user profile';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Consumer Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>
    <div class="dashboard-header">
        <h1>Edit Profile</h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>consumer/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div style="padding: 40px; max-width: 600px; margin: 0 auto;">
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0;">Personal Information</h2>
            
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled style="background: #f5f5f5; cursor: not-allowed;">
                <small style="color: #999;">Email cannot be changed</small>
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" placeholder="City, State">
            </div>

            <div class="form-group">
                <label for="bio">Bio</label>
                <textarea id="bio" name="bio" rows="3" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>

            <h2>Preferences</h2>

            <div class="form-group">
                <label for="preferences">Food Preferences</label>
                <textarea id="preferences" name="preferences" rows="2" placeholder="e.g., Organic, Local, Seasonal..."><?php echo htmlspecialchars($consumerProfile['preferences'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="dietary_restrictions">Dietary Restrictions</label>
                <textarea id="dietary_restrictions" name="dietary_restrictions" rows="2" placeholder="e.g., Vegan, Gluten-free, Allergies..."><?php echo htmlspecialchars($consumerProfile['dietary_restrictions'] ?? ''); ?></textarea>
            </div>

            <h2>Delivery Information</h2>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="2" placeholder="Street address"><?php echo htmlspecialchars($consumerProfile['address'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="postal_code">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($consumerProfile['postal_code'] ?? ''); ?>" placeholder="ZIP/Postal code">
            </div>

            <div style="display: flex; gap: 10px; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?php echo BASE_URL; ?>consumer/dashboard.php" class="btn btn-secondary" style="background: #666; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block;">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
