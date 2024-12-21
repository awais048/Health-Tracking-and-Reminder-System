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

// Handle AJAX medicine submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $response = ['status' => 'error', 'message' => 'An error occurred.'];

    $medicine_name = $_POST['medicineName'];
$dosage = $_POST['dosage'];
$medicine_date = $_POST['medicineDate'];
$medicine_time = $_POST['medicineTime'];

if (!empty($_POST['id'])) {
    // Update the medicine
    $medicine_id = $_POST['id'];

    // Combine medicine_date and medicine_time into a DateTime object
    $updated_datetime = DateTime::createFromFormat('Y-m-d H:i', $medicine_date . ' ' . $medicine_time);
    $current_datetime = new DateTime();  // Get current date and time

    // Add 3 hours to the current datetime
    $current_datetime_plus_3hrs = clone $current_datetime;  // Clone the current datetime to avoid modifying the original
    $current_datetime_plus_3hrs->add(new DateInterval('PT3H'));  // Add 3 hours to current datetime

    // Check if the updated datetime is greater than the current datetime + 3 hours
    if ($updated_datetime > $current_datetime_plus_3hrs) {
        $is_notified = 0;  // Set notification flag to 0 (future)
    } else {
        $is_notified = 1;  // Set notification flag to 1 (past or within the next 3 hours)
    }

    // Update query includes is_notified
    $updateQuery = "UPDATE medicines SET medicine_name = ?, dosage = ?, medicine_date = ?, medicine_time = ?, is_notified = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    if ($updateStmt->execute([$medicine_name, $dosage, $medicine_date, $medicine_time, $is_notified, $medicine_id])) {
        $response = ['status' => 'success', 'message' => 'Medicine updated successfully!'];
    }

    // Set a session variable for the update notification
    $_SESSION['update_notification'] = "Medicine updated successfully!";
}
 else {
        // Insert a new medicine

        // Combine medicine_date and medicine_time into a DateTime object
        $new_datetime = DateTime::createFromFormat('Y-m-d H:i', $medicine_date . ' ' . $medicine_time);
        $current_datetime = new DateTime();

        // Check if the new datetime is in the future
        if ($new_datetime > $current_datetime) {
            $is_notified = 0;
        } else {
            $is_notified = 1; // Optionally, set to 1 if not in the future
        }

        $insertQuery = "INSERT INTO medicines (user_id, medicine_name, dosage, medicine_date, medicine_time, is_notified) VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        if ($insertStmt->execute([$user_id, $medicine_name, $dosage, $medicine_date, $medicine_time, $is_notified])) {
            $response = ['status' => 'success', 'message' => 'Medicine added successfully!'];
        }
        // Set a session variable for the insert notification
        $_SESSION['insert_notification'] = "Medicine added successfully!";
    }

    echo json_encode($response);
    exit();
}

// Handle AJAX medicine deletion
if (isset($_GET['delete'])) {
    $medicine_id = $_GET['id'];
    $response = ['status' => 'error', 'message' => 'An error occurred.'];

    // Delete the medicine
    $deleteQuery = "DELETE FROM medicines WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    if ($deleteStmt->execute([$medicine_id])) {
        $response = ['status' => 'success', 'message' => 'Medicine deleted successfully!'];
    }

    echo json_encode($response);
    exit();
}

// Fetch medicines for the logged-in user
$medicinesQuery = "SELECT * FROM medicines WHERE user_id = ?";
$stmt = $conn->prepare($medicinesQuery);
$stmt->execute([$user_id]);
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicines</title>
    <link rel="stylesheet" href="css/styles2.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->
</head>
<body>
    <div class="g"><?php include 'navbar.php'; ?></div>
    <div class="contain">
        <header class="header">
            <h1>Medicines</h1>
        </header>

        <div class="content">
            <div class="add-button"><span onclick="openForm()">+</span></div>
            <div id="medicinesList">
                <?php foreach ($medicines as $medicine): ?>
                    <div class="tile" id="medicine_<?php echo $medicine['id']; ?>">
                        <span>
                            <strong>Medicine Name: </strong> <?php echo htmlspecialchars($medicine['medicine_name']); ?> <br>
                            <strong>Dosage: </strong><?php echo htmlspecialchars($medicine['dosage']); ?> <br>
                            <strong>Date: </strong><?php echo htmlspecialchars($medicine['medicine_date']);  ?><br>
                            <strong>Time: </strong><?php echo htmlspecialchars($medicine['medicine_time']); ?>
                        </span>
                        <div>
                            <button class="edit-button" type="button" onclick="openEditForm(<?php echo htmlspecialchars(json_encode($medicine)); ?>)">Edit</button>
                            <button type="button" onclick="openConfirmationModal(<?php echo htmlspecialchars($medicine['id']); ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Notification for deletion and updates -->
        <div class="notification" id="notification"></div>

        <!-- Overlay and Modal for Form -->
        <div class="overlay" id="overlay" style="display:none;"></div>

        <div class="modal" id="modal" style="display:none;">
            <h2 id="formTitle">Add New Medicine</h2>
            <form id="medicineForm">
                <input type="hidden" id="medicineId" name="id"> <!-- Hidden input for the medicine ID -->
                <label for="medicineName">Medicine Name</label>
                <input type="text" id="medicineName" name="medicineName" required>

                <label for="dosage">Dosage</label>
                <input type="number" id="dosage" name="dosage" required>

                <label for="medicineDate">Date</label>
                <input type="date" id="medicineDate" name="medicineDate" required>

                <label for="medicineTime">Time</label>
                <input type="time" id="medicineTime" name="medicineTime" required>

                <button type="submit">Submit</button>
            </form>
        </div>

        <!-- Confirmation Modal for Deletion -->
        <div class="confirmation-overlay" id="confirmationOverlay" style="display:none;"></div>
        <div class="confirmation-modal" id="confirmationModal" style="display:none;">
            <h2>Are you sure you want to delete this medicine?</h2>
            <div>
                <button id="confirmDelete">Yes, Delete</button>
                <button onclick="closeConfirmationModal()">No, Cancel</button>
            </div>
        </div>

        <!-- Bar Chart for Medicines -->
        <div class="h">
            <h1 class="headd">Medicine Bar Chart</h1>
            <canvas id="medicineChart"></canvas>
        </div>
    </div>

    <script>
        // Fetch medicines for the chart
        const medicines = <?php echo json_encode($medicines); ?>;

        const labels = medicines.map(medicine => medicine.medicine_name);
        const dosages = medicines.map(medicine => parseInt(medicine.dosage));

        const ctx = document.getElementById('medicineChart').getContext('2d');
        const medicineChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Dosage',
                    data: dosages,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                        display: true,
                        text: 'Dosage',
                        font: {
                            size: 16
                        }
                    }
                    },
                    x: {
                    title: {
                        display: true,
                        text: 'Medicines',
                        font: {
                            size: 16
                        }
                    },
                }
                    
                }
            }
        });

        // Function to show notification
        function showNotification(message) {
            const notification = document.getElementById('notification');
            notification.innerText = message;
            notification.style.display = 'block';
            notification.style.opacity = 1;
            setTimeout(() => {
                notification.style.opacity = 0;
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 500); // Match this timeout to the CSS transition
            }, 3000); // Display for 3 seconds
        }

        // Function to open the form for adding a new medicine
        function openForm() {
            document.getElementById('formTitle').innerText = 'Add New Medicine';
            document.getElementById('medicineForm').reset();
            document.getElementById('modal').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        // Function to open the form for editing an existing medicine
        function openEditForm(medicine) {
            document.getElementById('formTitle').innerText = 'Edit Medicine';
            document.getElementById('medicineId').value = medicine.id;
            document.getElementById('medicineName').value = medicine.medicine_name;
            document.getElementById('dosage').value = medicine.dosage;
            document.getElementById('medicineDate').value = medicine.medicine_date;
            document.getElementById('medicineTime').value = medicine.medicine_time;

            // Open the modal for editing
            document.getElementById('modal').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        // Function to close the form modal
        function closeForm() {
            document.getElementById('modal').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }

        // Function to submit the form via AJAX
        document.getElementById('medicineForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            formData.append('ajax', true); // Indicate an AJAX request

            fetch('', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification(data.message);
                    location.reload(); // Reload the page to show the updated list of medicines
                } else {
                    showNotification('An error occurred. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.');
            });
        });

        // Function to open confirmation modal for deletion
        function openConfirmationModal(medicineId) {
            document.getElementById('confirmationModal').style.display = 'block';
            document.getElementById('confirmationOverlay').style.display = 'block';

            // Set the onclick function for the confirmation button
            document.getElementById('confirmDelete').onclick = function() {
                // Use AJAX for deletion
                fetch('?delete=true&id=' + medicineId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showNotification(data.message);
                            document.getElementById('medicine_' + medicineId).remove(); // Remove the medicine from the DOM
                        } else {
                            showNotification('An error occurred. Please try again.');
                        }
                        closeConfirmationModal(); // Close modal after deletion
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.');
                        closeConfirmationModal();
                    });
            };
        }

        // Function to close confirmation modal
        function closeConfirmationModal() {
            document.getElementById('confirmationModal').style.display = 'none';
            document.getElementById('confirmationOverlay').style.display = 'none';
        }

        // Close form when clicking on the overlay
        document.getElementById('overlay').addEventListener('click', closeForm);

        // Close confirmation modal when clicking on the overlay
        document.getElementById('confirmationOverlay').addEventListener('click', closeConfirmationModal);

        // Display notifications from session variables on page load
        window.onload = function() {
            <?php if (isset($_SESSION['insert_notification'])): ?>
                showNotification('<?php echo $_SESSION['insert_notification']; unset($_SESSION['insert_notification']); ?>');
            <?php endif; ?>
            <?php if (isset($_SESSION['update_notification'])): ?>
                showNotification('<?php echo $_SESSION['update_notification']; unset($_SESSION['update_notification']); ?>');
            <?php endif; ?>
        };

        // Prevent auto resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
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
