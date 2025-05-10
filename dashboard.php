<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'], $_SESSION['user_name'])) {
    header("Location: login.php");
    exit;
}

// --- Company Name and Logo Mapping ---
$company_details = [
    'Company A' => [
        'name' => 'Accenture',
        'logo' => 'https://logo.clearbit.com/accenture.com' // More direct URL
        // Alternative: 'https://cdn.worldvectorlogo.com/logos/accenture-2.svg' (SVG)
    ],
    'Company B' => [
        'name' => 'Zoho',
        'logo' => 'https://logo.clearbit.com/zoho.com' // More direct URL
         // Alternative: 'https://cdn.worldvectorlogo.com/logos/zoho.svg' (SVG)
    ],
    'Company C' => [
        'name' => 'Capgemini',
        'logo' => 'https://logo.clearbit.com/capgemini.com' // More direct URL
        // Alternative: 'https://upload.wikimedia.org/wikipedia/commons/9/92/Capgemini_logo_2017.svg' (SVG)
    ],
    // Add other mappings if needed
];

$company_key = $_SESSION['user_company'] ?? ''; // Get company key from session ('Company A', etc.)

// Determine display name and logo URL
$display_company = isset($company_details[$company_key]) ? $company_details[$company_key]['name'] : htmlspecialchars($company_key);
$logo_url = isset($company_details[$company_key]) ? $company_details[$company_key]['logo'] : null; // Get logo URL or null

$display_role = htmlspecialchars($_SESSION['user_role'] ?? 'N/A');
// --- End Company Mapping ---


// --- (Optional) Fetch profile picture path ---
// ... (Your existing code to fetch profile pic path can remain here) ...
// $profilePicPath = ... ;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo $display_company; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom CSS -->
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.6s ease-out forwards; }
        .btn:focus { box-shadow: none !important; }
        /* Optional profile pic styles */
        .profile-pic-container { width: 100px; height: 100px; border-radius: 50%; overflow: hidden; margin: 0 auto 1rem auto; border: 3px solid #dee2e6; background-color: #f8f9fa; }
        .profile-pic-container img { width: 100%; height: 100%; object-fit: cover; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container d-flex align-items-center justify-content-center min-vh-100 py-5">
        <div class="bg-white p-6 md:p-8 rounded-lg shadow-xl max-w-lg w-full text-center animate-fade-in">

            <!-- Company Logo -->
            <?php if ($logo_url): ?>
                <img src="<?php echo htmlspecialchars($logo_url); ?>"
                     alt="<?php echo htmlspecialchars($display_company); ?> Logo"
                     class="max-h-12 md:max-h-16 w-auto mx-auto mb-5"> <!-- Adjusted max-h and mb -->
            <?php endif; ?>

            <!-- Optional: Profile Picture Display -->
            <!--
            <div class="profile-pic-container">
                <img src="<?php echo htmlspecialchars($profilePicPath ?? 'path/to/default/avatar.png'); ?>" alt="Profile Picture">
            </div>
            -->

            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
            </h1>
            <p class="text-gray-600 mb-6 text-sm">
                <?php echo $display_role; ?> at <?php echo $display_company; ?>
            </p>

            <div class="space-y-3"> <!-- Vertical spacing for buttons -->

                <!-- Provide Feedback Button -->
                <a href="feedback.php" class="btn btn-primary w-full py-2 px-4 text-lg rounded-md transition duration-300 ease-in-out transform hover:scale-[1.03] hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                   Provide Feedback
                </a>

                <!-- Manage Profile Button -->
                <a href="profile.php" class="btn btn-outline-secondary w-full py-2 px-4 text-lg rounded-md transition duration-300 ease-in-out transform hover:scale-[1.03] hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50">
                   Manage Profile / Photo
                </a>

                <!-- Report Bug Button -->
                <a href="report_bug.php" class="btn btn-outline-warning w-full py-2 px-4 text-lg rounded-md transition duration-300 ease-in-out transform hover:scale-[1.03] hover:bg-yellow-100 hover:text-yellow-800 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-opacity-50">
                   Report a Bug
                </a>

                <!-- Delete Account Button -->
                <a href="delete_account.php" onclick="return confirm('Are you absolutely sure you want to delete your account?\nThis action cannot be undone.');" class="btn btn-outline-danger w-full py-2 px-4 text-lg rounded-md transition duration-300 ease-in-out transform hover:scale-[1.03] hover:bg-red-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 mt-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block me-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 012 0v6a1 1 0 11-2 0V8z" clip-rule="evenodd" /></svg>
                   Delete Account
                </a>

                <!-- Logout Button -->
                <a href="logout.php" class="btn btn-outline-dark w-full py-2 px-4 text-lg rounded-md transition duration-300 ease-in-out transform hover:scale-[1.03] hover:bg-gray-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50 mt-4">
                   Logout
                </a>
            </div> <!-- End space-y -->

        </div> <!-- End Card -->
    </div> <!-- End Container -->

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>