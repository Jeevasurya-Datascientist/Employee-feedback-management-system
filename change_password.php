<?php
include 'db.php'; // Ensure this path is correct
session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit;
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID
$error = null;
$success_message = null;

// 2. Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 3. Basic Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) { // Example: Enforce minimum length
        $error = "New password must be at least 8 characters long.";
    } else {
        // 4. Verify Current Password (Fetch HASHED password from DB)
        $sql_get_current = "SELECT password FROM employees WHERE id = ?";
        $stmt_get_current = $conn->prepare($sql_get_current);

        if ($stmt_get_current) {
            $stmt_get_current->bind_param("i", $user_id);
            $stmt_get_current->execute();
            $result = $stmt_get_current->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $db_password_hash = $row['password']; // This MUST be the HASHED password from DB

                // ======================================================
                // == SECURE HASH CHECK (RECOMMENDED & ENABLED) ==
                // ======================================================
                // Verify the submitted current password against the stored hash
                $is_current_password_valid = password_verify($current_password, $db_password_hash);
                // ======================================================


                // ======================================================
                // == !!! INSECURE PLAIN TEXT CHECK (FOR REFERENCE ONLY - DELETE THIS) !!! ==
                /*
                $is_current_password_valid = ($current_password === $db_password_hash); // This comparison is wrong if db stores hash
                */
                // ======================================================


                if ($is_current_password_valid) {
                    // Prevent setting the same password again (compare plain text new pass with plain text current pass)
                    if ($current_password === $new_password) {
                         $error = "New password cannot be the same as the current password.";
                    } else {
                        // 5. Hash the NEW password (CRITICAL!)
                        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

                        // 6. Update the password in the database
                        $sql_update = "UPDATE employees SET password = ? WHERE id = ?";
                        $stmt_update = $conn->prepare($sql_update);
                        if ($stmt_update) {
                            $stmt_update->bind_param("si", $hashed_new_password, $user_id);
                            if ($stmt_update->execute()) {
                                $success_message = "Your password has been successfully updated.";
                                // Optional: Force re-login?
                                // session_destroy();
                                // header("Location: login.php?message=password_changed_relogin");
                                // exit;
                            } else {
                                error_log("Password Update failed for user ID $user_id: " . $stmt_update->error);
                                $error = "An error occurred while updating your password. Please try again.";
                            }
                            $stmt_update->close();
                        } else {
                            error_log("Password Update Prepare failed: " . $conn->error);
                            $error = "An error occurred. Please try again later.";
                        }
                    }
                } else {
                    // Current password verification failed
                    $error = "Incorrect current password.";
                }
            } else {
                // User ID not found
                error_log("User ID $user_id not found during password change attempt.");
                $error = "An error occurred. User not found.";
                // Optional: Destroy session if user seems invalid
                // session_destroy();
                // header("Location: login.php");
                // exit;
            }
            $stmt_get_current->close();
        } else {
            error_log("Password Get Current Prepare failed: " . $conn->error);
            $error = "An error occurred. Please try again later.";
        }
    }
}

// $conn->close(); // Optional
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Employee Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .change-password-container { max-width: 500px; margin: 5rem auto; }
        .form-control:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
        .btn:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.5); }
    </style>
</head>
<body>
    <?php
        // You should have a navigation bar for logged-in users
        // Include it here if it exists (e.g., navbar.php or header.php)
        // Example: include 'navbar_logged_in.php';
    ?>
    <div class="container change-password-container">
        <div class="card p-4 p-md-5 shadow-sm">
            <div class="card-body">
                <h2 class="text-center h3 mb-4 fw-normal">Change Your Password</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center p-2" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success text-center p-2" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php // Keep form visible even after success unless you redirect
                // if (empty($success_message)): ?>
                <form method="POST" action="change_password.php" novalidate>
                    <div class="mb-3 form-floating">
                        <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Current Password" required>
                        <label for="current_password">Current Password</label>
                    </div>
                    <hr class="my-4">
                    <div class="mb-3 form-floating">
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="New Password" required>
                        <label for="new_password">New Password</label>
                         <small class="form-text text-muted">Minimum 8 characters.</small>
                    </div>
                    <div class="mb-4 form-floating">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm New Password" required>
                        <label for="confirm_password">Confirm New Password</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3">Update Password</button>
                </form>
                <?php // endif; ?>

                <p class="text-center mt-4">
                    <a href="dashboard.php" class="text-decoration-none">Back to Dashboard</a>
                </p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>