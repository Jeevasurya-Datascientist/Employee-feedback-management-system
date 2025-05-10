<?php
include 'db.php'; // Ensure this path is correct

// Stricter session settings (BEFORE session_start) - uncomment if using HTTPS
/*
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // REQUIRES HTTPS
ini_set('session.cookie_samesite', 'Lax');
*/

session_start();

$error = null; // Initialize error variable
$success_message = null; // Initialize success message for potential redirects

// Check for success message from password reset (coming from reset_password_request.php)
if (isset($_SESSION['reset_success'])) {
    $success_message = $_SESSION['reset_success'];
    unset($_SESSION['reset_success']); // Clear the message after displaying
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Trim whitespace from inputs
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Don't trim password initially

    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please fill in both email and password.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if the email exists using PREPARED STATEMENTS
        $sql = "SELECT id, name, email, password, company, role FROM employees WHERE email = ?"; // Select needed columns
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result(); // Get result set

            if ($result->num_rows === 1) { // Check if EXACTLY ONE user was found
                $row = $result->fetch_assoc(); // Email exists, fetch the user data

                // ==================================================
                // == SECURE PASSWORD CHECK (RECOMMENDED & ENABLED) ==
                // ==================================================
                // Your database 'password' column MUST store hashed passwords for this to work.
                // Use password_hash() when registering users or updating passwords.
                if (password_verify($password, $row['password'])) {
                    // Login successful - Regenerate session ID
                    session_regenerate_id(true);

                    // Store user data in session
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_name'] = $row['name'];
                    $_SESSION['user_company'] = $row['company'];
                    $_SESSION['user_role'] = $row['role'];
                    $_SESSION['user_email'] = $row['email']; // Store the user's email

                    // Redirect to the dashboard
                    header("Location: dashboard.php");
                    exit; // Important: exit after redirect
                } else {
                    // Password verification failed
                    $error = "Invalid email or password.";
                }
                // ==================================================


                // ==================================================
                // == !!! INSECURE PLAIN TEXT CHECK (FOR REFERENCE ONLY - DELETE THIS) !!! ==
                /*
                if ($password === $row['password']) { // Direct string comparison (INSECURE!)
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_name'] = $row['name'];
                    $_SESSION['user_company'] = $row['company'];
                    $_SESSION['user_role'] = $row['role'];
                    $_SESSION['user_email'] = $row['email'];
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = "Invalid email or password.";
                }
                */
                // ==================================================


            } else {
                // No account found or multiple accounts
                $error = "Invalid email or password."; // Generic message for security
            }
            $stmt->close(); // Close the statement
        } else {
            // Database prepare statement error
            error_log("Login Prepare failed: " . $conn->error);
            $error = "An error occurred during login. Please try again later.";
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
    <title>Login - Employee Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .login-container { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem; }
        .login-card { max-width: 450px; width: 100%; }
        @keyframes fadeInUp { from { opacity: 0; transform: translate3d(0, 30px, 0); } to { opacity: 1; transform: translate3d(0, 0, 0); } }
        .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; opacity: 0; }
        .form-control:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
        .btn:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.5); }
        .forgot-password-link { font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card p-4 p-md-5 shadow-lg login-card animate-fade-in-up">
            <div class="card-body">
                <h2 class="text-center h3 mb-4 fw-normal">Login to Your Account</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center text-sm p-2" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success text-center text-sm p-2" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" novalidate>
                    <div class="mb-3 form-floating">
                        <input type="email" name="email" id="floatingInput" class="form-control form-control-lg" placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        <label for="floatingInput">Email address</label>
                    </div>
                    <div class="mb-3 form-floating">
                        <input type="password" name="password" id="floatingPassword" class="form-control form-control-lg" placeholder="Password" required>
                        <label for="floatingPassword">Password</label>
                    </div>
                    <!-- CORRECTED Forgot Password Link -->
                    <div class="text-end mb-4">
                         <a href="reset_password_request.php" class="text-decoration-none forgot-password-link">Forgot Password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Login</button>
                </form>
                <p class="text-center text-muted mt-4 mb-0">
                    Don't have an account? <a href="index.php" class="text-decoration-none">Register here</a>.
                </p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>