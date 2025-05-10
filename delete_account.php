<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'], $_SESSION['user_name'])) {
    header("Location: login.php");
    exit;
}

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Initialize variables
$error = "";
$fatal_error = false; // Flag to hide form if deletion happens or critical error occurs

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid request. Please refresh and try again.";
        $fatal_error = true; // Don't show form again
    } else {
        include 'db.php'; // Include DB only when needed

        $user_id_to_delete = $_SESSION['user_id'];

        // --- Backend Logic Placeholder: Account Deletion ---
        // - !! IMPORTANT !! Consider requiring password re-authentication here for security.
        // - Begin Transaction (if deleting from multiple related tables).
        // - DELETE records related to the user (e.g., from `feedback`, `bug_reports`, potentially profile pics).
        // - DELETE the user record from the `employees` table: `DELETE FROM employees WHERE id = ?`
        // - Commit Transaction if all successful, Rollback otherwise.
        // - Handle potential DB errors.
        // ---

        // Example Placeholder Logic (Simplified):
        $conn->begin_transaction(); // Start transaction
        try {
            // Add deletions for related data first (feedback, bugs, etc.) if necessary
            // $conn->query("DELETE FROM feedback WHERE user_id = $user_id_to_delete"); // Example - Use prepared statements ideally

            // Delete the main user record
            $sql = "DELETE FROM employees WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if(!$stmt) throw new Exception("Prepare failed: " . $conn->error);

            $stmt->bind_param("i", $user_id_to_delete);
            if (!$stmt->execute()) {
                 throw new Exception("Deletion failed: " . $stmt->error);
            }
            $stmt->close();

            $conn->commit(); // Commit changes

            // --- Log user out and destroy session ---
            session_unset(); // Remove all session variables
            session_destroy(); // Destroy the session

            // Redirect to a logged-out page (e.g., login page with a success message)
            header("Location: login.php?message=Account deleted successfully.");
            exit; // Stop script execution

        } catch (Exception $e) {
            $conn->rollback(); // Rollback changes on error
            error_log("Account Deletion Error for User ID $user_id_to_delete: " . $e->getMessage());
            $error = "Could not delete account due to a server error. Please contact support.";
            $fatal_error = true; // Hide form on critical error
        }
        // --- End Placeholder Logic ---

        $conn->close(); // Close connection
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Account Deletion</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
     <!-- Custom CSS -->
    <style>
        @keyframes fadeInUp { from { opacity: 0; transform: translate3d(0, 20px, 0); } to { opacity: 1; transform: translate3d(0, 0, 0); } }
        .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; opacity: 0; }
        .btn:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); outline: none; }
        .alert-danger strong { color: inherit; } /* Ensure strong tag inherits alert color */
    </style>
</head>
<body class="bg-gray-100">

    <div class="d-flex align-items-center justify-content-center min-vh-100 px-3 py-5">
        <div class="card shadow-xl max-w-lg w-full rounded-lg animate-fade-in-up">
            <div class="card-body p-4 p-md-5">
                <h2 class="text-center text-3xl font-semibold text-red-700 mb-4">Delete Your Account</h2>

                <!-- Messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center text-sm p-2 mb-4" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Confirmation Form (Hide if fatal error occurred) -->
                <?php if (!$fatal_error): ?>
                <div class="alert alert-warning border-danger border-start border-5 p-4 mb-4" role="alert">
                    <h4 class="alert-heading text-danger font-bold">Warning!</h4>
                    <p>You are about to permanently delete your account, including any associated data like feedback or reports.</p>
                    <hr>
                    <p class="mb-0 text-danger"><strong>This action cannot be undone.</strong> Are you absolutely sure you want to proceed?</p>
                    <p class="mt-2 text-xs text-gray-600">For security, we recommend implementing password re-authentication before allowing deletion in a production environment.</p>
                </div>

                <form method="POST" action="delete_account.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <button type="submit"
                            class="btn btn-danger btn-lg w-full mt-3 py-2 px-4 rounded-md text-lg font-semibold transition duration-300 ease-in-out transform hover:scale-[1.02] hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">
                        Yes, Permanently Delete My Account
                    </button>
                </form>
                <?php endif; ?>

                <!-- Back to Dashboard Link -->
                <div class="text-center mt-4">
                    <a href="dashboard.php" class="btn btn-outline-secondary rounded-md py-2 px-4 transition duration-300 ease-in-out transform hover:scale-[1.02] hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50">
                        No, Take Me Back to Safety!
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>