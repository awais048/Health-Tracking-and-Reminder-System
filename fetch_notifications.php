<?php
session_start();
include 'connection.php'; // Include your DB connection file

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode([]); // Return an empty JSON array if not logged in
    exit();
}

// Fetch user_id from the database based on email
$userEmail = $_SESSION['email'];
$query = "SELECT id FROM users WHERE email = :email";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Prepare failed: " . $conn->error); // Display error if prepare fails
}

// Bind email to the query
$stmt->bindValue(':email', $userEmail);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

// If no result is found, return an empty JSON array
if (!$result) {
    echo json_encode([]); // Return an empty array if user not found
    exit();
}

$user_id = $result['id']; // Fetch the user ID

// Function to fetch notifications from the database
function fetchNotifications($conn, $user_id) {
    $notifications = [];

    // Get the current date and time as a DateTime object
    $currentDateTime = new DateTime(); // Current date and time
    $currentDateTimePlusOneHour = clone $currentDateTime; // Clone to add one hour
    $currentDateTimePlusOneHour->modify('+3 hour'); // Add three hours

    // Queries for notifications, fetch only records with is_notified = 0
    $tableQueries = [
        "appointments" => "SELECT id, appointment_title AS title, appointment_date, appointment_time FROM appointments WHERE user_id = :user_id AND is_notified = 0",
        "exercises" => "SELECT id, exercise_name AS title, exercise_date, exercise_time FROM exercises WHERE user_id = :user_id AND is_notified = 0",
        "medicines" => "SELECT id, medicine_name AS title, medicine_date, medicine_time FROM medicines WHERE user_id = :user_id AND is_notified = 0"
    ];

    // Loop through the queries and fetch notifications
    foreach ($tableQueries as $table => $query) {
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error); // Display error if prepare fails
        }
        // Bind parameters to the query
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Store IDs to update after fetching
        $idsToUpdate = [];

        // Process results to filter notifications based on date and time
        foreach ($results as $row) {
            // Create a DateTime object for the notification
            if ($table === "appointments") {
                $notificationDateTime = DateTime::createFromFormat('Y-m-d H:i:s', 
                    $row['appointment_date'] . ' ' . $row['appointment_time']);
            } elseif ($table === "exercises") {
                $notificationDateTime = DateTime::createFromFormat('Y-m-d H:i:s', 
                    $row['exercise_date'] . ' ' . $row['exercise_time']);
            } elseif ($table === "medicines") {
                $notificationDateTime = DateTime::createFromFormat('Y-m-d H:i:s', 
                    $row['medicine_date'] . ' ' . $row['medicine_time']);
            }

            // Compare the notification time with the current time
            if ($notificationDateTime <= $currentDateTimePlusOneHour) {
                $notifications[] = [
                    'type' => ucfirst($table), // Capitalize the first letter of the type
                    'title' => $row['title'],
                ];
                // Store the ID to update the `is_notified` column
                $idsToUpdate[] = $row['id'];
            }
        }

        // Update the is_notified column for fetched notifications
        if (count($idsToUpdate) > 0) {
            $updateQuery = "UPDATE $table SET is_notified = 1 WHERE id IN (" . implode(',', $idsToUpdate) . ")";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->execute();
        }
    }

    return $notifications;
}

// Fetch notifications and return as JSON
$notifications = fetchNotifications($conn, $user_id);
header('Content-Type: application/json');
echo json_encode($notifications);
?>
