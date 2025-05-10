<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'], $_SESSION['user_name'])) {
    header("Location: login.php");
    exit;
}

// Include DB connection
include 'db.php';

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Initialize variables
$error = "";
$success = "";
$currentProfilePic = 'path/to/default/avatar.png'; // Default image path

// --- Fetch current profile picture path ---
$sql = "SELECT profile_picture_path FROM employees WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['profile_picture_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $row['profile_picture_path'])) {
             // Assuming path like /uploads/profile_pics/user_1_abc.jpg stored relative to web root
            $currentProfilePic = $row['profile_picture_path'];
        }
    }
    $stmt->close();
} else {
    $error = "Error fetching profile data."; // Or log it
}
// --- End Fetch ---


// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    }
    // 2. Check if file was uploaded without errors
    elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB limit

        $file_type = $_FILES['profile_picture']['type'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_tmp_name = $_FILES['profile_picture']['tmp_name'];
        $file_name = $_FILES['profile_picture']['name']; // Original name

        // 3. Validate file type
        if (!in_array($file_type, $allowed_types)) {
            $error = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        }
        // 4. Validate file size
        elseif ($file_size > $max_size) {
            $error = "File size exceeds the 2MB limit.";
        } else {
            // --- Backend Logic Placeholder: File Handling & DB Update ---
            // - Generate a unique filename (e.g., using user ID and timestamp/hash)
            // - Define the upload directory (ensure it's writable by the web server)
            // - Move the uploaded file (`move_uploaded_file()`)
            // - If successful, update the `profile_picture_path` in the `employees` table for the user.
            // - Handle potential errors during file move or DB update.
            // ---

            // Example Placeholder Logic:
            $upload_dir = '/uploads/profile_pics/'; // Relative to web root (IMPORTANT: Create this directory and set permissions)
            $upload_path_root = $_SERVER['DOCUMENT_ROOT'] . $upload_dir;
            if (!is_dir($upload_path_root)) {
                 mkdir($upload_path_root, 0775, true); // Create if not exists (adjust permissions as needed)
            }

            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
            $destination = $upload_path_root . $unique_filename;
            $db_path = $upload_dir . $unique_filename; // Path to store in DB

            if (move_uploaded_file($file_tmp_name, $destination)) {
                // --- Update Database ---
                $updateSql = "UPDATE employees SET profile_picture_path = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                if ($updateStmt) {
                    $updateStmt->bind_param("si", $db_path, $_SESSION['user_id']);
                    if ($updateStmt->execute()) {
                        $success = "Profile picture updated successfully!";
                        $currentProfilePic = $db_path; // Update displayed picture
                        // Regenerate CSRF token
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        $csrf_token = $_SESSION['csrf_token'];
                    } else {
                        $error = "Database update failed: " . $updateStmt->error;
                        // Optionally delete the uploaded file if DB update fails: unlink($destination);
                    }
                    $updateStmt->close();
                } else {
                    $error = "Database prepare failed: " . $conn->error;
                     // Optionally delete the uploaded file if DB prepare fails: unlink($destination);
                }
                // --- End Update Database ---
            } else {
                $error = "Failed to move uploaded file. Check permissions.";
            }
            // --- End Placeholder Logic ---
        }
    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] != UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors (e.g., UPLOAD_ERR_INI_SIZE)
        $error = "An error occurred during file upload. Error code: " . $_FILES['profile_picture']['error'];
    } else {
        $error = "Please select a file to upload.";
    }
}

$conn->close(); // Close DB connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom CSS -->
    <style>
        @keyframes fadeInUp { from { opacity: 0; transform: translate3d(0, 20px, 0); } to { opacity: 1; transform: translate3d(0, 0, 0); } }
        .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; opacity: 0; }
        .btn:focus, .form-control:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); outline: none; }
        .profile-pic-display { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: auto; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="d-flex align-items-center justify-content-center min-vh-100 px-3 py-5">
        <div class="card shadow-xl max-w-lg w-full rounded-lg animate-fade-in-up">
            <div class="card-body p-4 p-md-5">
                <h2 class="text-center text-3xl font-semibold text-gray-800 mb-4">Manage Your Profile</h2>

                <!-- Display Current Profile Picture -->
                <img src="<?php echo htmlspecialchars($currentProfilePic) . '?t=' . time(); // Add timestamp to break cache ?>" alt="Current Profile Picture" class="profile-pic-display mb-4">

                <!-- Messages -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success text-center text-sm p-2 mb-4" role="alert"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center text-sm p-2 mb-4" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Profile Picture Upload Form -->
                <form method="POST" action="profile.php" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="mb-4">
                        <label for="profile_picture" class="form-label text-gray-700">Upload New Profile Picture:</label>
                        <input class="form-control form-control-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" type="file" id="profile_picture" name="profile_picture" accept="image/jpeg, image/png, image/gif" required>
                        <div class="form-text text-xs text-gray-500 mt-1">Max file size: 2MB. Allowed types: JPG, PNG, GIF.</div>
                    </div>

                    <button type="submit"
                            class="btn btn-success btn-lg w-full mt-3 py-2 px-4 rounded-md text-lg font-semibold transition duration-300 ease-in-out transform hover:scale-[1.02] hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                        Upload Picture
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