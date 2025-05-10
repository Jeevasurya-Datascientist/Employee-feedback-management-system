<?php
session_start();

// Check login status
if (!isset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'])) {
    header("Location: login.php?error=Session expired or invalid. Please log in again.");
    exit;
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Initialize variables
$success = "";
$error = "";
$submitted_feedback = '';
$show_success_animation = false; // Flag for animation/redirect trigger

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid form submission. Please try again.";
    } else {
        include 'db.php';

        $name = $_SESSION['user_name'];
        $email = $_SESSION['user_email'];
        $feedback = trim($_POST['feedback'] ?? '');

        $submitted_feedback = $feedback; // Keep for repopulation on error

        if (empty($feedback)) {
            $error = "Feedback field cannot be empty.";
        } else {
            $sql = "INSERT INTO feedback (name, email, feedback) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("sss", $name, $email, $feedback);
                if ($stmt->execute()) {
                    $success = "Feedback submitted successfully! Thank you.";
                    $submitted_feedback = ''; // Clear field content
                    $show_success_animation = true; // *** SET FLAG FOR SUCCESS ***
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $csrf_token = $_SESSION['csrf_token'];
                } else {
                    error_log("Feedback Submit Error: " . $stmt->error);
                    $error = "An error occurred while submitting your feedback.";
                }
                $stmt->close();
            } else {
                error_log("Feedback Prepare Error: " . $conn->error);
                $error = "An error occurred preparing the feedback submission.";
            }
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provide Feedback</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom CSS -->
    <style>
        @keyframes fadeInUp { from { opacity: 0; transform: translate3d(0, 20px, 0); } to { opacity: 1; transform: translate3d(0, 0, 0); } }
        .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; opacity: 0; }
        .form-control:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
        .btn:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.5); }
        .form-control:read-only { background-color: #e9ecef; opacity: 1; cursor: not-allowed; }

         /* Make success message more prominent when form is hidden */
        .success-container {
            min-height: 300px; /* Give it some height */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="d-flex align-items-center justify-content-center min-vh-100 px-3 py-5">
        <div class="card shadow-lg max-w-xl w-full rounded-lg animate-fade-in-up">
            <div class="card-body p-4 p-md-5">

                <!-- Conditionally display header based on success -->
                <?php if (!$show_success_animation): ?>
                    <h2 class="text-center text-3xl font-semibold text-gray-800 mb-4">We Value Your Feedback!</h2>
                    <p class="text-center text-muted mb-4">Let us know what you think.</p>
                <?php endif; ?>

                <!-- Error Message Display (Always show if error exists) -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center text-sm p-2 mb-4" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Success Message Display -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success text-center p-4 mb-4 <?php echo $show_success_animation ? 'success-container' : ''; ?>" role="alert">
                        <h4 class="alert-heading text-xl font-semibold"><?php echo htmlspecialchars($success); ?></h4>
                        <?php if ($show_success_animation): ?>
                            <p class="mt-2">Redirecting you back to the dashboard shortly...</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>


                <!-- Hide form and Back button on success -->
                <?php if (!$show_success_animation): ?>

                    <!-- Feedback Form -->
                    <form method="POST" action="feedback.php" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="mb-3">
                            <label for="name" class="form-label text-gray-700">Your Name:</label>
                            <input type="text" id="name" name="name" class="form-control form-control-lg" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label text-gray-700">Your Email:</label>
                            <input type="email" id="email" name="email" class="form-control form-control-lg" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" readonly>
                             <div class="form-text text-xs text-gray-500">This is your registered email address.</div>
                        </div>

                        <div class="mb-3">
                            <label for="feedback" class="form-label text-gray-700">Your Feedback:</label>
                            <textarea id="feedback" name="feedback" class="form-control form-control-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Write your feedback here..." rows="5" required><?php echo htmlspecialchars($submitted_feedback); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-full mt-4 py-2 px-4 rounded-md text-lg font-semibold transition duration-300 ease-in-out transform hover:scale-[1.02] hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                            Submit Feedback
                        </button>
                    </form>

                    <!-- Back to Dashboard Link -->
                    <div class="text-center mt-4">
                        <a href="dashboard.php" class="btn btn-outline-secondary rounded-md py-2 px-4 transition duration-300 ease-in-out transform hover:scale-[1.02] hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50">
                            Back to Dashboard
                        </a>
                    </div>

                <?php endif; // End hiding form ?>

            </div> <!-- end card-body -->
        </div> <!-- end card -->
    </div> <!-- end container -->

    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Canvas Confetti Library -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <!-- Custom JS for Animation & Redirect -->
    <script>
        // Check if PHP flag is set to true
        <?php if ($show_success_animation): ?>

            // Trigger Confetti Animation
            confetti({
                particleCount: 150, // Increase particle count
                spread: 90,        // Widen the spread
                origin: { y: 0.6 }, // Start confetti slightly lower than top
                colors: ['#bb0000', '#ffffff', '#0000ff', '#00ff00', '#ffff00'] // Example color palette
            });

             // Add a small delay before starting the redirect timer, looks smoother
            setTimeout(() => {
                // Redirect to dashboard after 4 seconds (4000 milliseconds)
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 4000);
             }, 200); // 200ms delay after confetti starts

        <?php endif; ?>
    </script>

</body>
</html>