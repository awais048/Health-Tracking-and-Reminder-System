<?php
session_start();
include 'connection.php'; // Include your database connection file


// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Get the current user's ID based on the email
$email = $_SESSION['email'];
$query = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_id = $user['id'];

// Handle blood pressure reading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $systolic = $_POST['systolic'];
    $diastolic = $_POST['diastolic'];
    $reading_date = $_POST['reading_date'];
    $reading_time = $_POST['reading_time'];

    // Determine the alert based on systolic and diastolic values
    $alert = '';
    if ($systolic < 110 || $diastolic < 70) {
        $alert = 'Low';
        
    }
 
    elseif ($systolic > 130 || $diastolic > 90) {
        $alert = 'High';
    }
    

    // Check if we are updating a reading
    if (!empty($_POST['id'])) {
        $reading_id = $_POST['id'];

        // Update the blood pressure reading in the database
        $updateQuery = "UPDATE blood_pressure_readings SET systolic = ?, diastolic = ?, reading_date = ?, reading_time = ?, alert = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$systolic, $diastolic, $reading_date, $reading_time, $alert, $reading_id]);

        // Set a session variable for the update notification
        $_SESSION['update_notification'] = "Blood pressure readings updated successfully!";
        echo json_encode(['status' => 'success', 'message' => 'Blood pressure reading updated successfully']);
        exit(); // Stop execution after returning a response
    } else {
        // If not updating, insert a new reading
        $insertQuery = "INSERT INTO blood_pressure_readings (user_id, systolic, diastolic, reading_date, reading_time, alert) VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->execute([$user_id, $systolic, $diastolic, $reading_date, $reading_time, $alert]);

        // Set a session variable to show a notification
        $_SESSION['insert_notification'] = "Blood pressure readings added successfully!";
        echo json_encode(['status' => 'success', 'message' => 'Blood pressure reading added successfully']);
        exit(); // Stop execution after returning a response
    }
}

// Fetch blood pressure readings for the logged-in user
$bpReadingsQuery = "SELECT * FROM blood_pressure_readings WHERE user_id = ?";
$stmt = $conn->prepare($bpReadingsQuery);
$stmt->execute([$user_id]);
$bpReadings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle deletion of blood pressure readings
if (isset($_GET['delete'])) {
    $reading_id = $_GET['id'];

    // Delete the blood pressure reading from the database
    $deleteQuery = "DELETE FROM blood_pressure_readings WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->execute([$reading_id]);

    // Respond with JSON without setting session variable
    echo json_encode(['status' => 'success', 'message' => 'Blood pressure reading deleted successfully']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Blood Pressure</title>
    <link rel="stylesheet" href="css/styles2.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="contain">
        <header class="header">
            <h1>Blood Pressure</h1>
        </header>

        <div class="content">
            <div class="add-button" id="addButton">+</div>
            <div id="bpList">
                <?php foreach ($bpReadings as $reading): ?>
                    <div class="tile" id="reading-<?php echo htmlspecialchars($reading['id']); ?>">
                        <span>
                            <strong>Systolic:</strong> <?php echo htmlspecialchars($reading['systolic']); ?> mm Hg <br>
                            <strong>Diastolic:</strong> <?php echo htmlspecialchars($reading['diastolic']); ?> mm Hg <br>
                            <strong>Date:</strong> <?php echo htmlspecialchars($reading['reading_date']); ?> <br>
                            <strong>Time:</strong> <?php echo htmlspecialchars($reading['reading_time']); ?> <br>
                            <?php if ($reading['alert']): ?>
                                <strong style="color: red;"><?php echo htmlspecialchars($reading['alert']); ?> Blood Pressure</strong>
                            <?php endif; ?>
                        </span>
                        <div>
                            <button class="edit-button" onclick='editBpReading(<?php echo json_encode($reading, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>Edit</button>
                            <button onclick="openConfirmationModal(<?php echo htmlspecialchars($reading['id']); ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Chart Section -->
            <div class="h">
            <h1 class="headd">Blood Pressure Bar Chart</h1>
                <canvas id="bpChart" width="400" height="200"></canvas> <!-- Canvas for Chart -->
            </div>
        </div>

        <!-- Notification for deletion and updates -->
        <div class="notification" id="notification"></div>

        <!-- Overlay and Modal for Form -->
        <div class="overlay" id="overlay" style="display:none;"></div>

        <div class="modal" id="modal" style="display:none;">
            <h2 id="formTitle">Add New Blood Pressure Reading</h2>
            <form id="bpForm">
                <input type="hidden" id="bpId" name="id"> <!-- Hidden input for the reading ID -->
                <label for="systolic">Systolic (mm Hg)</label>
                <input type="number" id="systolic" name="systolic" required>

                <label for="diastolic">Diastolic (mm Hg)</label>
                <input type="number" id="diastolic" name="diastolic" required>

                <label for="bpDate">Date</label>
                <input type="date" id="bpDate" name="reading_date" required>

                <label for="bpTime">Time</label>
                <input type="time" id="bpTime" name="reading_time" required>

                <button type="submit">Submit</button>
            </form>
        </div>

        <!-- Confirmation Modal for Deletion -->
        <div class="confirmation-overlay" id="confirmationOverlay" style="display:none;"></div>
        <div class="confirmation-modal" id="confirmationModal" style="display:none;">
            <h2>Are you sure you want to delete this blood pressure reading?</h2>
            <div>
                <button id="confirmDelete">Yes, Delete</button>
                <button onclick="closeConfirmationModal()">No, Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let systolicReadings = []; // Initialize systolic readings array
        let diastolicReadings = []; // Initialize diastolic readings array
        let readingDates = []; // Initialize reading dates array

        <?php foreach ($bpReadings as $reading): ?>
            systolicReadings.push(<?php echo htmlspecialchars($reading['systolic']); ?>);
            diastolicReadings.push(<?php echo htmlspecialchars($reading['diastolic']); ?>);
            readingDates.push('<?php echo htmlspecialchars($reading['reading_date']); ?>');
        <?php endforeach; ?>

        // Initialize Chart.js
        const ctx = document.getElementById('bpChart').getContext('2d');
        const bpChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: readingDates, // X-axis labels
                datasets: [
                    {
                        label: 'Systolic (mm Hg)',
                        data: systolicReadings, // Data for systolic readings
                        backgroundColor: 'rgba(255, 99, 132, 0.5)', // Adjust opacity
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Diastolic (mm Hg)',
                        data: diastolicReadings, // Data for diastolic readings
                        backgroundColor: 'rgba(54, 162, 235, 0.5)', // Adjust opacity
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true, // Start y-axis at 0
                        title: {
                            display: true,
                            text: 'Blood Pressure (mm Hg)' // Y-axis label
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Dates' // X-axis label
                        }
                    }
                }
            }
        });

        // Other JavaScript functions for handling the form and notifications
        let editIndex = null;

        function openForm() {
            document.getElementById('formTitle').innerText = 'Add New Blood Pressure Reading';
            document.getElementById('bpForm').reset();
            document.getElementById('modal').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
            editIndex = null;
        }

        function closeForm() {
            document.getElementById('modal').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }

        function editBpReading(reading) {
            document.getElementById('bpId').value = reading.id;
            document.getElementById('systolic').value = reading.systolic;
            document.getElementById('diastolic').value = reading.diastolic;
            document.getElementById('bpDate').value = reading.reading_date;
            document.getElementById('bpTime').value = reading.reading_time;

            document.getElementById('formTitle').innerText = 'Edit Blood Pressure Reading';
            document.getElementById('modal').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function openConfirmationModal(readingId) {
            document.getElementById('confirmationModal').style.display = 'block';
            document.getElementById('confirmationOverlay').style.display = 'block';

            document.getElementById('confirmDelete').onclick = function() {
                fetch(`?delete=true&id=${readingId}`, { method: 'GET' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showNotification(data.message);
                            document.getElementById(`reading-${readingId}`).remove();
                        } else {
                            showNotification('Failed to delete the reading.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred while deleting the reading.');
                    });
                closeConfirmationModal();
            };
        }

        function closeConfirmationModal() {
            document.getElementById('confirmationModal').style.display = 'none';
            document.getElementById('confirmationOverlay').style.display = 'none';
        }

        document.getElementById('addButton').addEventListener('click', openForm);

        document.getElementById('bpForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            formData.append('ajax', true);

            fetch('', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                showNotification(data.message);
                if (data.status === 'success') {
                    closeForm();
                    location.reload(); // Reload the page to see updated readings
                }
            })
            .catch(error => console.error('Error:', error));
        });

        function showNotification(message) {
            const notification = document.getElementById('notification');
            notification.innerText = message;
            notification.style.display = 'block';
            notification.style.opacity = 1;
            setTimeout(() => {
                notification.style.opacity = 0;
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 500);
            }, 3000);
        }
        window.onload = function() {
            <?php if (isset($_SESSION['insert_notification'])): ?>
                showNotification('<?php echo $_SESSION['insert_notification']; unset($_SESSION['insert_notification']); ?>');
            <?php endif; ?>
            <?php if (isset($_SESSION['update_notification'])): ?>
                showNotification('<?php echo $_SESSION['update_notification']; unset($_SESSION['update_notification']); ?>');
            <?php endif; ?>
        };

        // Close form when clicking on the overlay
        document.getElementById('overlay').addEventListener('click', closeForm);
    </script>
    <style>
    .h{
    margin-top: 40px;
    width: 100%;
    height: 450px;
    display: flex;
    flex-direction: column;
    align-items: center;
}
        canvas {
            max-width: 90%;

            margin-top: 20px;
        }
        .h h1 {
    font-size: 2.5em;
    color: #113768;
    text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
    margin: 20px 0;
    font-weight: bold;
    letter-spacing: 1.5px;
}
    </style>
</body>
</html>
