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
$success = "";
$submitted_url = '';
$submitted_description = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        include 'db.php'; // Include DB only when needed

        // Get data, trim inputs
        $reporter_user_id = $_SESSION['user_id'];
        $page_url = trim($_POST['page_url'] ?? ''); // Optional field
        $description = trim($_POST['description'] ?? '');

        $submitted_url = $page_url;
        $submitted_description = $description;

        // 2. Server-side Validation
        if (empty($description)) {
            $error = "Please provide a description of the bug.";
        } else if (!empty($page_url) && !filter_var($page_url, FILTER_VALIDATE_URL)) {
             $error = "The provided URL is not valid.";
        } else {
            // --- Backend Logic Placeholder: DB Insert ---
            // - Create a `bug_reports` table if it doesn't exist.
            // - Columns: id (PK, AI), reporter_user_id (FK to employees), page_url (VARCHAR, nullable), description (TEXT), status (VARCHAR, default 'New'), reported_at (TIMESTAMP, default CURRENT_TIMESTAMP)
            // - Prepare and execute an INSERT statement.
            // - Handle potential DB errors.
            // ---

            // Example Placeholder Logic:
             $sql = "INSERT INTO bug_reports (reporter_user_id, page_url, description, status) VALUES (?, ?, ?, 'New')";
             $stmt = $conn->prepare($sql);
             if ($stmt) {
                 $stmt->bind_param("iss", $reporter_user_id, $page_url, $description);
                 if ($stmt->execute()) {
                     $success = "Bug report submitted successfully. Thank you for helping us improve!";
                     $submitted_url = ''; // Clear form on success
                     $submitted_description = '';
                     // Regenerate CSRF token
                     $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                     $csrf_token = $_SESSION['csrf_token'];
                 } else {
                     error_log("Bug Report Insert Error: " . $stmt->error);
                     $error = "Failed to submit bug report due to a database error.";
                 }
                 $stmt->close();
             } else {
                 error_log("Bug Report Prepare Error: " . $conn->error);
                 $error = "Failed to prepare the bug report submission.";
             }
            // --- End Placeholder Logic ---

            $conn->close(); // Close connection
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report a Bug</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom CSS -->
    <style>
        @keyframes fadeInUp { from { opacity: 0; transform: translate3d(0, 20px, 0); } to { opacity: 1; transform: translate3d(0, 0, 0); } }
        .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; opacity: 0; }
        .btn:focus, .form-control:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); outline: none; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="d-flex align-items-center justify-content-center min-vh-100 px-3 py-5">
        <div class="card shadow-xl max-w-xl w-full rounded-lg animate-fade-in-up">
            <div class="card-body p-4 p-md-5">
                <h2 class="text-center text-3xl font-semibold text-gray-800 mb-4">Report a Bug</h2>
                 <p class="text-center text-muted mb-4">Spotted something wrong? Let us know!</p>

                <!-- Messages -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success text-center text-sm p-2 mb-4" role="alert"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center text-sm p-2 mb-4" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Bug Report Form -->
                <form method="POST" action="report_bug.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="mb-3">
                        <label for="page_url" class="form-label text-gray-700">Page URL (Optional):</label>
                        <input type="url" id="page_url" name="page_url" class="form-control form-control-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="e.g., https://example.com/dashboard" value="<?php echo htmlspecialchars($submitted_url); ?>">
                         <div class="form-text text-xs text-gray-500 mt-1">The web address where you noticed the bug, if applicable.</div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label text-gray-700">Bug Description:</label>
                        <textarea id="description" name="description" class="form-control form-control-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Please describe the issue in detail..." rows="6" required><?php echo htmlspecialchars($submitted_description); ?></textarea>
                         <div class="form-text text-xs text-gray-500 mt-1">What happened? What did you expect to happen? Any steps to reproduce it?</div>
                    </div>

                    <button type="submit"
                            class="btn btn-warning btn-lg w-full mt-3 py-2 px-4 rounded-md text-lg font-semibold text-gray-900 transition duration-300 ease-in-out transform hover:scale-[1.02] hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-opacity-50">
                        Submit Report
                    </button>
                </form>

                <!-- Back to Dashboard Link -->
                <div class="text-center mt-4">
                    <a href="dashboard.php" class="btn btn-outline-secondary rounded-md py-2 px-4 transition duration-300 ease-in-out transform hover:scale-[1.02] hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50">
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>