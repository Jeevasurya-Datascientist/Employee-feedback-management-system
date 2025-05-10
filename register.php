<?php
include 'db.php'; // Make sure this path is correct
session_start();

// --- Mappings ---
// Keys MUST match the 'value' sent from index.php's company select dropdown
define('KEY_COMPANY_A', 'Company A');
define('KEY_COMPANY_B', 'Company B');
define('KEY_COMPANY_C', 'Company C');

// Map keys to actual display names
$company_display_names = [
    KEY_COMPANY_A => 'Accenture',
    KEY_COMPANY_B => 'Zoho',
    KEY_COMPANY_C => 'Capgemini',
];

// Map keys to expected email endings (lowercase)
$company_email_endings = [
    KEY_COMPANY_A => ['@accenture.com'],
    KEY_COMPANY_B => ['@zoho.com', '@zohocorp.com'],
    KEY_COMPANY_C => ['@capgemini.com'],
];
// --- End Mappings ---


// Retrieve the selected company key and role - prefer POST, then GET
$company_key = $_POST['company'] ?? $_GET['company'] ?? ''; // This holds "Company A", "Company B", etc.
$role = $_POST['role'] ?? $_GET['role'] ?? '';

// Redirect to index.php if company key or role is not set (on GET request)
if (($_SERVER['REQUEST_METHOD'] !== 'POST') && (empty($company_key) || empty($role))) {
    header("Location: index.php?error=Please select your company and role first.");
    exit;
}

// Get the display name for the selected company key
$company_display_name = $company_display_names[$company_key] ?? htmlspecialchars($company_key); // Fallback to key if not found

$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Re-fetch company key/role from POST
    $company_key = trim($_POST['company'] ?? '');
    $role = trim($_POST['role'] ?? '');

    // Re-fetch display name based on submitted key
    $company_display_name = $company_display_names[$company_key] ?? htmlspecialchars($company_key);

    // Validate company key/role again
    if (empty($company_key) || empty($role) || !isset($company_display_names[$company_key])) { // Also check if key is valid
        $error = "Company and Role selection is invalid. Please start over.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $passwordInput = $_POST['password'] ?? '';

        // 1. Basic Server-Side Validation
        if (empty($name) || empty($email) || empty($passwordInput)) {
            $error = "All fields (Name, Email, Password) are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (strlen($passwordInput) < 6) {
             $error = "Password must be at least 6 characters long.";
        } else {
            // 2. Company-Specific Email Validation
            $emailLower = strtolower($email);
            $isValidCompanyEmail = false;
            $expectedDomains = [];

            if (isset($company_email_endings[$company_key])) {
                $expectedDomains = $company_email_endings[$company_key];
                foreach ($expectedDomains as $domain) {
                    // Use str_ends_with (PHP 8+) or alternative for older PHP
                    if (function_exists('str_ends_with') ? str_ends_with($emailLower, $domain) : (substr($emailLower, -strlen($domain)) === $domain)) {
                        $isValidCompanyEmail = true;
                        break;
                    }
                }
            } else {
                 // This case should be caught by the earlier check, but defensive coding is good.
                 $error = "Invalid company selection received.";
                 $isValidCompanyEmail = false;
            }

            // Update error message if email is invalid for the specific company
            if (!$isValidCompanyEmail && empty($error)) {
                $domainsList = implode("</code> or <code>", $expectedDomains);
                // Use the actual display name in the error message
                $error = "Invalid email for " . $company_display_name . ". Email must end with <code>" . $domainsList . "</code>.";
            }

            // 3. Proceed only if NO errors so far
            if (empty($error)) {
                 // Check if email already exists
                try {
                    $checkEmail = "SELECT id FROM employees WHERE email = ?";
                    $stmtCheck = $conn->prepare($checkEmail);
                    if ($stmtCheck === false) throw new Exception("Prepare failed (check email): " . $conn->error);
                    $stmtCheck->bind_param("s", $email);
                    $stmtCheck->execute();
                    $result = $stmtCheck->get_result();

                    if ($result->num_rows > 0) {
                        $error = "This email address is already registered. Please <a href='login.php' class='alert-link'>log in</a> instead.";
                    } else {
                        // Hash password
                        $passwordHash = password_hash($passwordInput, PASSWORD_DEFAULT);

                        // Insert employee - Use the KEY ("Company A", etc.) for the database record
                        $sql = "INSERT INTO employees (name, email, password, company, role) VALUES (?, ?, ?, ?, ?)";
                        $stmtInsert = $conn->prepare($sql);
                        if ($stmtInsert === false) throw new Exception("Prepare failed (insert): " . $conn->error);
                        // IMPORTANT: Store the KEY ('Company A', 'Company B', etc.) in the DB, not the display name,
                        // unless your DB schema expects the full name. Storing the key is usually more robust.
                        $stmtInsert->bind_param("sssss", $name, $email, $passwordHash, $company_key, $role);

                        if ($stmtInsert->execute()) {
                            $success = "Registration successful! You can now <a href='login.php' class='alert-link'>log in</a>.";
                            $_POST = []; // Clear POST to prevent re-populating form on success
                        } else {
                            error_log("Registration failed: " . $stmtInsert->error);
                            $error = "An error occurred during registration. Please try again later.";
                        }
                        $stmtInsert->close();
                    }
                    $stmtCheck->close();

                } catch (Exception $e) {
                     error_log("Database error: " . $e->getMessage());
                     $error = "A database error occurred. Please try again later.";
                }
            } // End check if(empty($error))
        } // End basic validation else
    } // End company/role check else
} // End POST request handling

// Close DB connection if needed
// if ($conn) $conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Registration - <?php echo $company_display_name; ?></title> <!-- Title shows actual company -->

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <!-- Tailwind CSS (Play CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom CSS (animations, styles) -->
    <style>
        @keyframes fadeInUp { from { opacity: 0; transform: translate3d(0, 20px, 0); } to { opacity: 1; transform: translate3d(0, 0, 0); } }
        .animate-fade-in-up { animation: fadeInUp 0.5s ease-out forwards; }
        .form-control:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
        .btn:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.5); }
        .alert-link { font-weight: bold; text-decoration: underline; }
        .alert-link:hover { opacity: 0.8; }
        .alert code { font-size: 0.85em; padding: 0.1em 0.4em; background-color: rgba(0,0,0,0.05); border-radius: 3px; }
        .badge { font-size: 0.9em; } /* Slightly adjust badge size if needed */
    </style>
</head>
<body class="bg-gray-100">

    <div class="d-flex align-items-center justify-content-center min-vh-100 px-3">
        <div class="card shadow-xl max-w-lg w-full rounded-lg animate-fade-in-up">
            <div class="card-body p-4 p-md-5">
                <h2 class="text-center text-3xl font-semibold text-gray-800 mb-4">Employee Registration</h2>

                <!-- Display Company and Role -->
                <div class="text-center mb-4 text-base text-gray-600">
                    Registering for:
                    <span class="badge bg-info text-dark rounded-pill px-3 py-1 me-1">
                        <?php echo $company_display_name; // Display ACTUAL company name ?>
                    </span>
                     as
                    <span class="badge bg-secondary text-white rounded-pill px-3 py-1 ms-1">
                        <?php echo htmlspecialchars($role); ?>
                    </span>
                </div>

                <!-- Error or Success Message -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger mt-4 mb-4 text-sm" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success mt-4 mb-4 text-sm" role="alert">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <?php if (empty($success)): ?>
                <form method="POST" action="register.php" novalidate>
                    <!-- Hidden fields still use the KEY ('Company A', etc.) -->
                    <input type="hidden" name="company" value="<?php echo htmlspecialchars($company_key); ?>">
                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">

                    <div class="mb-3">
                        <label for="name" class="form-label visually-hidden">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control form-control-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Your Full Name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                         <label for="email" class="form-label visually-hidden">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control form-control-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Your Company Email Address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label visually-hidden">Password</label>
                        <input type="password" name="password" id="password" class="form-control form-control-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Create a Password (min. 8 chars)" required>
                    </div>
                    <button type="submit"
                            class="btn btn-primary btn-lg w-full mt-4 py-2 px-4 rounded-md text-lg font-semibold transition duration-300 ease-in-out transform hover:scale-[1.02] hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        Register
                    </button>
                </form>
                <p class="text-center text-sm text-gray-600 mt-4">
                    Already registered? <a href="login.php" class="text-blue-600 hover:text-blue-800 hover:underline font-medium">Log in here</a>.
                </p>
                <?php endif; ?>

                <!-- Login Link/Button on Success -->
                 <?php if (!empty($success)): ?>
                    <p class="text-center mt-4">
                         <a href="login.php" class="btn btn-success btn-lg">Proceed to Login</a>
                    </p>
                 <?php endif; ?>

            </div> <!-- End card-body -->
        </div> <!-- End card -->
    </div> <!-- End flex container -->

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

</body>
</html>