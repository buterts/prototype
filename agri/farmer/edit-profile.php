<?php
require_once __DIR__ . '/../config/bootstrap.php';

// Require farmer role
AuthMiddleware::requireRole(ROLE_FARMER);

$db = $GLOBALS['conn'] ?? null;
$userModel = new User($db);
$farmerModel = new FarmerProfile($db);

$user_id = $_SESSION['user_id'];
$user = $userModel->findById($user_id);
$farmerProfile = $farmerModel->getByUserId($user_id);

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
            // Update farmer profile
            $farmerData = [
                'farm_name' => $_POST['farm_name'] ?? '',
                'farm_size' => $_POST['farm_size'] ?? null,
                'crops_grown' => $_POST['crops_grown'] ?? '',
                'farming_practices' => $_POST['farming_practices'] ?? '',
                'certification' => $_POST['certification'] ?? ''
            ];

            if (empty($farmerData['farm_name'])) {
                $error = 'Farm name is required';
            } else {
                if ($farmerModel->update($user_id, $farmerData)) {
                    $success = 'Profile updated successfully!';
                    // Refresh data
                    $user = $userModel->findById($user_id);
                    $farmerProfile = $farmerModel->getByUserId($user_id);
                } else {
                    $error = 'Failed to update farm profile';
                }
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
    <title>Edit Profile - Farmer Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>
    <div class="dashboard-header">
        <h1>Edit Profile</h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>farmer/dashboard.php">Dashboard</a>
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

            <h2>Farm Information</h2>

            <div class="form-group">
                <label for="farm_name">Farm Name *</label>
                <input type="text" id="farm_name" name="farm_name" value="<?php echo htmlspecialchars($farmerProfile['farm_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="farm_size">Farm Size (acres)</label>
                <input type="number" id="farm_size" name="farm_size" step="0.01" value="<?php echo htmlspecialchars($farmerProfile['farm_size'] ?? ''); ?>" placeholder="e.g., 50">
            </div>

            <div class="form-group">
                <label for="crops_grown">Crops Grown</label>
                <textarea id="crops_grown" name="crops_grown" rows="2" placeholder="e.g., Tomatoes, Lettuce, Carrots"><?php echo htmlspecialchars($farmerProfile['crops_grown'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="farming_practices">Farming Practices</label>
                <textarea id="farming_practices" name="farming_practices" rows="2" placeholder="e.g., Organic, Sustainable, Conventional"><?php echo htmlspecialchars($farmerProfile['farming_practices'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="certification">Certification</label>
                <input type="text" id="certification" name="certification" value="<?php echo htmlspecialchars($farmerProfile['certification'] ?? ''); ?>" placeholder="e.g., USDA Organic">
            </div>

            <div style="display: flex; gap: 10px; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?php echo BASE_URL; ?>farmer/dashboard.php" class="btn btn-secondary" style="background: #666; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block;">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
