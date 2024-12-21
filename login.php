<?php
// Start the session
session_start();

// Database connection details
$servername = "localhost";
$username = "root"; // Change this if needed
$password = ""; // Change this if needed
$dbname = "healthcare";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

// Initialize error messages
$emailError = $passwordError = "";

// Check if the request is an AJAX POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; // Do not escape the password

    // Basic input validation
    if (empty($email)) {
        $emailError = "Email is required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = "Invalid email format!";
    }

    if (empty($password)) {
        $passwordError = "Password is required!";
    }

    // If no errors, proceed with login
    if (empty($emailError) && empty($passwordError)) {
        // Prepare the SQL statement to select the user
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Fetch user data
            $user = $result->fetch_assoc();
            
            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Store user information in the session
                $_SESSION['email'] = $user['email'];
                
                // Return success response
                echo json_encode(["success" => true, "message" => "Login successful!"]);
                exit();
            } else {
                $passwordError = "Invalid password!";
            }
        } else {
            $emailError = "Invalid email!";
        }
    }

    // Return error response if any
    echo json_encode([
        "success" => false,
        "message" => "Please fix the errors below.",
        "emailError" => $emailError,
        "passwordError" => $passwordError
    ]);
    exit();
}

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: azure;
            font-family: 'Poppins', sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: auto;
            padding: 30px;
            background: linear-gradient(90deg, rgba(205,212,232,1) 0%, rgba(200,196,215,1) 40%, rgba(182,179,223,1) 100%);
            border-radius: 10px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
            margin-top: 100px;
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: black;
        }
        label {
            color: black;
        }
        .form-control {
            border-radius: 30px;
            border: 1px solid #ced4da;
        }
        .btn-primary {
            border-radius: 30px;
            background-color: #5a67d8;
            border: none;
        }
        .btn-primary:hover {
            background-color: #434190;
        }
        .error {
            color: red;
            font-size: 0.8rem;
            text-align: center;
        }
        .signup-prompt {
            text-align: center;
            margin-top: 20px;
        }
        .signup-prompt a {
            color: #5a67d8;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2>Welcome Back!</h2>
            <form id="login-form">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control" id="email" placeholder="Enter your email">
                    <small id="email-error" class="error"></small> <!-- Email error will be displayed here -->
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" id="password" placeholder="Enter your password">
                    <small id="password-error" class="error"></small> <!-- Password error will be displayed here -->
                </div>
                <div class="mb-3 text-center">
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </div>
            </form>
            <div class="signup-prompt">
                <p>Don't have an account? <a href="#" id="signup-link">Sign up here</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('login-form').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            // Clear previous error messages
            document.getElementById('email-error').innerText = '';
            document.getElementById('password-error').innerText = '';

            // Make the AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "login.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Redirect to dashboard on successful login
                        window.location.href = 'dashboard.php';
                    } else {
                        // Display error messages below respective fields
                        document.getElementById('email-error').innerText = response.emailError;
                        document.getElementById('password-error').innerText = response.passwordError;
                    }
                }
            };
            xhr.send("ajax=true&email=" + encodeURIComponent(email) + "&password=" + encodeURIComponent(password));
        });

        document.getElementById('signup-link').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default anchor behavior
            window.location.href = 'signup.php'; // Redirect to signup page
        });

        // Prevent auto resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
