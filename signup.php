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
    die("Connection failed: " . $conn->connect_error);
}

// Initialize error message variables
$response = [
    'success' => false,
    'errors' => []
];

// Check if the form is submitted via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirmPassword = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    
    // Basic input validation
    if (empty($name)) {
        $response['errors']['name'] = "Name is required!";
    }

    if (empty($email)) {
        $response['errors']['email'] = "Email is required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = "Invalid email format!";
    }

    if (empty($password)) {
        $response['errors']['password'] = "Password is required!";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/", $password)) {
        $response['errors']['password'] = "Password must be at least 8 characters long, include at least one uppercase letter, one lowercase letter, one number, and one special character.";
    }

    if (empty($confirmPassword)) {
        $response['errors']['confirm_password'] = "Confirm password is required!";
    } elseif ($password !== $confirmPassword) {
        $response['errors']['confirm_password'] = "Passwords do not match!";
    }
    

    // If no errors, check if the email already exists
    if (empty($response['errors'])) {
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $response['errors']['email'] = "Already have an account with this email!";
        } else {
            // Hash the password for security
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Prepare the SQL statement to insert the new user
            $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hashedPassword')";
            if ($conn->query($sql) === TRUE) {
                $response['success'] = true;
                $response['message'] = "Registration successful! You can now log in.";
            } else {
                $response['errors']['database'] = "Error: " . $conn->error;
            }
        }
    }

    // Close the database connection
    $conn->close();

    // Return the response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit; // Prevent further processing
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #e9ecef;
            font-family: 'Poppins', sans-serif;
        }
        .signup-container {
            max-width: 400px;
            margin: 20px auto;
            padding: 30px;
            background: rgb(205,212,232);
            background: linear-gradient(90deg, rgba(205,212,232,1) 0%, rgba(200,196,215,1) 40%, rgba(182,179,223,1) 100%);
            border-radius: 10px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
        }
        .signup-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
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
        }
        .success {
            color: green;
            text-align: center;
        }
        .login-prompt {
            text-align: center;
            margin-top: 20px;
        }
        .login-prompt a {
            color: #5a67d8;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="signup-container">
            <h2>Create an Account</h2>
            <div id="success-message" class="success" style="display: none;"></div>
            <form id="signup-form">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" id="name" placeholder="Enter your name">
                    <small class="error" id="nameError"></small>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="text" name="email" class="form-control" id="email" placeholder="Enter your email">
                    <small class="error" id="emailError"></small>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" id="password" placeholder="Enter your password">
                    <small class="error" id="passwordError"></small>
                </div>
                <div class="mb-3">
                <div class="mb-3">
    <label for="confirm_password" class="form-label">Confirm Password</label>
    <input type="password" name="confirm_password" class="form-control" id="confirm_password" placeholder="Confirm your password">
    <small class="error" id="confirm_passwordError"></small> <!-- Changed here -->
</div>

                </div>
                <div class="mb-3 text-center">
                    <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                </div>
            </form>
            <div class="login-prompt">
                <p>Already have an account? <a href="index.php">Log in here</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#signup-form').on('submit', function(e) {
                e.preventDefault(); // Prevent the default form submission
                
                // Clear previous error messages
                $('.error').text('');
                $('#success-message').hide();

                // Gather form data
                var formData = $(this).serialize();

                // AJAX request
                $.ajax({
                    type: 'POST',
                    url: 'signup.php', // The same page
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#success-message').text(response.message).show();
                            $('#signup-form')[0].reset(); // Reset form
                        } else {
                            // Display errors
                            $.each(response.errors, function(field, message) {
                                $('#' + field + 'Error').text(message);
                            });
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing your request. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>
