<?php
include 'db.php'; // Ensure this path is correct
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = null;
$success_message = null;
$show_password_fields = false;
$user_id_to_reset = null;
$email_verified = ''; // Keep email in field
$company_verified = ''; // Keep selected company VALUE in dropdown

// --- Define Company Display Name Mapping ---
$company_display_map = [
    'Company A' => 'Accenture',
    'Company B' => 'Zoho',
    'Company C' => 'Capgemini', // Corrected spelling
    // Add more mappings here if needed
];
// -----------------------------------------


// --- Fetch Company List (Identifiers) from Database ---
$companies_db_values = []; // Initialize an empty array for company DB values
$company_error = null; // Error specific to fetching companies

try {
    // Get distinct non-empty company identifiers from 'employees' table
    $sql_companies = "SELECT DISTINCT company FROM employees WHERE company IS NOT NULL AND company != '' ORDER BY company ASC";

    $result_companies = $conn->query($sql_companies);

    if ($result_companies) {
        while ($row = $result_companies->fetch_assoc()) {
            $companies_db_values[] = $row['company'];
        }
    } else {
        error_log("Failed to fetch company list: " . $conn->error);
        $company_error = "Could not load company list.";
    }
} catch (Exception $e) {
     error_log("Database error fetching companies: " . $e->getMessage());
     $company_error = "An error occurred loading company data.";
}
// -----------------------------------------


// --- Stage 1: Verify Email and Company ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'verify') {
    $email = trim($_POST['email'] ?? '');
    $company = trim($_POST['company'] ?? ''); // Get selected company VALUE (e.g., "Company A")

    if (empty($email) || empty($company)) {
        $error = "Please enter your email and select your company.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if EXACTLY ONE user matches email AND company identifier
        $sql = "SELECT id FROM employees WHERE email = ? AND company = ?"; // Checks against DB identifier
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ss", $email, $company);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $user_id_to_reset = $row['id'];
                $show_password_fields = true;
                $email_verified = $email;
                $company_verified = $company; // Store the DB identifier for the next stage
                $success_message = "Verification successful. Please enter your new password below.";
            } else {
                $error = "The provided email and company combination was not found. Please try again or contact support.";
            }
            $stmt->close();
        } else {
            error_log("Password Reset Verify Prepare failed: " . $conn->error);
            $error = "An error occurred during verification. Please try again later.";
        }
    }
     // Keep submitted values in fields if verification failed
     if (!$show_password_fields) {
        $email_verified = $email;
        $company_verified = $company; // Store the selected DB identifier to re-select it
     }
}

// --- Stage 2: Reset Password ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reset') {
    $user_id = $_POST['user_id'] ?? null;
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    // Get email/company identifier again from hidden fields
    $email_verified = trim($_POST['email_verified'] ?? '');
    $company_verified = trim($_POST['company_verified'] ?? ''); // The DB identifier
    $user_id_to_reset = $user_id;
    $show_password_fields = true;


    if (empty($user_id) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in both new password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
         $error = "Password must be at least 8 characters long.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $sql_update = "UPDATE employees SET password = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update) {
            $stmt_update->bind_param("si", $hashed_password, $user_id);
            if ($stmt_update->execute()) {
                $_SESSION['reset_success'] = "Your password has been successfully reset. Please log in with your new password.";
                header("Location: login.php");
                exit;
            } else {
                error_log("Password Reset Update failed: " . $stmt_update->error);
                $error = "An error occurred while resetting your password. Please try again.";
            }
            $stmt_update->close();
        } else {
            error_log("Password Reset Update Prepare failed: " . $conn->error);
            $error = "An error occurred preparing the password reset. Please try again later.";
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
    <title>Reset Password - Employee Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .reset-container { max-width: 500px; margin: 5rem auto; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
        .btn:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.5); }
        .form-floating select.form-select {
             padding-top: 1.625rem;
             padding-bottom: 0.625rem;
        }
    </style>
</head>
<body>
    <div class="container reset-container">
        <div class="card p-4 p-md-5 shadow-sm">
            <div class="card-body">
                <h2 class="text-center h3 mb-4 fw-normal">Reset Your Password</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center p-2" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (!empty($company_error)): /* Optional display */ ?>
                    <!-- <div class="alert alert-warning text-center p-2" role="alert"><?php // echo htmlspecialchars($company_error); ?></div> -->
                <?php endif; ?>
                <?php if (!empty($success_message) && $show_password_fields): ?>
                     <div class="alert alert-success text-center p-2" role="alert"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <?php if (!$show_password_fields): ?>
                    <p class="text-center text-muted mb-4">Please enter your email and select your company to verify your identity.</p>
                    <form method="POST" action="reset_password_request.php" novalidate>
                        <input type="hidden" name="action" value="verify">
                        <div class="mb-3 form-floating">
                            <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" value="<?php echo htmlspecialchars($email_verified); ?>" required autofocus>
                            <label for="email">Email address</label>
                        </div>

                        <!-- Company Dropdown with Display Names -->
                        <div class="mb-4 form-floating">
                             <select class="form-select" name="company" id="company" required>
                                <option value="" disabled <?php echo empty($company_verified) ? 'selected' : ''; ?>>Select your company...</option>
                                <?php foreach ($companies_db_values as $db_value):
                                    // Get the display name from map, fallback to DB value if not found
                                    $display_name = $company_display_map[$db_value] ?? $db_value;
                                ?>
                                    <option value="<?php echo htmlspecialchars($db_value); ?>" <?php echo ($db_value === $company_verified) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($display_name); // Show friendly name ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if (empty($companies_db_values) && empty($company_error)): ?>
                                    <option value="" disabled>No companies available</option>
                                <?php elseif (empty($companies_db_values) && !empty($company_error)): ?>
                                     <option value="" disabled>Error loading companies</option>
                                <?php endif; ?>
                            </select>
                            <label for="company">Company Name</label>
                        </div>
                        <!-- End Company Dropdown -->

                        <button type="submit" class="btn btn-primary w-100">Verify Identity</button>
                    </form>
                <?php else: // Show Password Reset fields ?>
                    <p class="text-center text-muted mb-4">Enter your new password below.</p>
                    <form method="POST" action="reset_password_request.php" novalidate>
                        <input type="hidden" name="action" value="reset">
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id_to_reset); ?>">
                        <input type="hidden" name="email_verified" value="<?php echo htmlspecialchars($email_verified); ?>">
                        <input type="hidden" name="company_verified" value="<?php echo htmlspecialchars($company_verified); // Pass the original DB identifier ?>">

                        <div class="mb-3 form-floating">
                            <input type="password" name="new_password" id="new_password" class="form-control" placeholder="New Password" required>
                            <label for="new_password">New Password</label>
                            <small class="form-text text-muted">Minimum 8 characters.</small>
                        </div>
                        <div class="mb-4 form-floating">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm New Password" required>
                            <label for="confirm_password">Confirm New Password</label>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Set New Password</button>
                    </form>
                <?php endif; ?>

                <p class="text-center text-muted mt-4 mb-0">
                    Remembered your password? <a href="login.php" class="text-decoration-none">Login here</a>.
                </p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>