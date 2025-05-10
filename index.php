<?php
session_start();
// No session check needed here usually, as this is often the entry point before login/registration
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Feedback System - Welcome</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <!-- Custom CSS for Animation and Minor Adjustments -->
    <style>
        body {
            background-color: #f8f9fa; /* Bootstrap's bg-light color */
        }

        .centered-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem; /* Add some padding for smaller screens */
        }

        .welcome-card {
            max-width: 500px;
            width: 100%;
        }

        /* Simple fade-in-up animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate3d(0, 30px, 0); /* Start slightly below */
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0); /* End at original position */
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0; /* Start hidden before animation begins */
        }

         /* Ensure labels in text-center card are aligned left */
        .form-label {
             /* text-align: left; */ /* Usually handled by text-start on parent */
        }

    </style>
</head>
<body>

    <div class="centered-container">
        <div class="card p-4 p-md-5 shadow welcome-card text-center animate-fade-in-up">
             <div class="card-body">
                <h1 class="h3 mb-4 fw-normal">Welcome to the Employee Feedback System</h1>
                <p class="mb-4 text-muted">Please select your company and role to get started:</p>

                <form method="POST" action="register.php">
                    <!-- Company Selection -->
                    <div class="mb-3 text-start"> <!-- text-start aligns label/select left -->
                        <label for="company" class="form-label">Select Your Company:</label>
                        <select name="company" id="company" class="form-select form-select-lg" required>
                            <option value="" disabled selected>-- Choose Company --</option>
                            <option value="Company A">Accenture</option>
                            <option value="Company B">ZOHO</option>
                            <option value="Company C">Capegemini</option>
                            <!-- Add more companies as needed -->
                        </select>
                    </div>

                    <!-- Role Selection -->
                    <div class="mb-4 text-start"> <!-- Increased bottom margin slightly before button -->
                        <label for="role" class="form-label">Select Your Role:</label>
                        <select name="role" id="role" class="form-select form-select-lg" required>
                            <option value="" disabled selected>-- Choose Role --</option>
                            <option value="Manager">Manager</option>
                            <option value="Team Leader">Team Leader</option>
                            <option value="Developer">Developer</option>
                            <option value="Designer">Designer</option>
                            <option value="QA Tester">QA Tester</option>
                            <option value="Intern">Intern</option>
                            <!-- Add more roles as needed -->
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">Continue</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

</body>
</html>