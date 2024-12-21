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

// Handle AJAX sugar level submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $sugar_level = $_POST['sugarLevel'];
    $sugar_type = $_POST['sugarType'];
    $reading_date = $_POST['readingDate'];
    $reading_time = $_POST['readingTime'];
    $alert = '';

    // Determine the alert based on sugar level and type
    if ($sugar_type === 'fasting') {
        if ($sugar_level < 70) {
            $alert = 'Low Sugar Level';
        } elseif ($sugar_level > 110) {
            $alert = 'High Sugar Level';
        } else {
            $alert = '';
        }
    } elseif ($sugar_type === 'random') {
        if ($sugar_level < 110) {
            $alert = 'Low Sugar Level';
        } elseif ($sugar_level > 170) {
            $alert = 'High Sugar Level';
        } else {
            $alert = '';
        }
    }

    // Check if we are updating a sugar level
    if (!empty($_POST['id'])) {
        $sugar_id = $_POST['id'];

        // Update the sugar level in the database
        $updateQuery = "UPDATE sugar_levels SET sugar_level = ?, sugar_type = ?, reading_date = ?, reading_time = ?, alert = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$sugar_level, $sugar_type, $reading_date, $reading_time, $alert, $sugar_id]);

       
        // Set a session variable for the update notification
        $_SESSION['update_notification'] = "Sugar level updated successfully!";
        // Return success message as JSON
        echo json_encode(['status' => 'success', 'message' => 'Sugar level updated successfully!']);
        exit(); // Ensure to exit after sending the response
    } else {
        // If not updating, insert a new sugar level
        // Insert the sugar level into the database
        $insertQuery = "INSERT INTO sugar_levels (user_id, sugar_level, sugar_type, reading_date, reading_time, alert) VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->execute([$user_id, $sugar_level, $sugar_type, $reading_date, $reading_time, $alert]);

        // Set a session variable for the insert notification
        $_SESSION['insert_notification'] = "Sugar Level added successfully!";
        // Return success message as JSON
        echo json_encode(['status' => 'success', 'message' => 'Sugar level added successfully!']);
        exit(); // Ensure to exit after sending the response
    }
}


// Fetch sugar levels for the logged-in user
$sugarLevelsQuery = "SELECT * FROM sugar_levels WHERE user_id = ?";
$stmt = $conn->prepare($sugarLevelsQuery);
$stmt->execute([$user_id]);
$sugarLevels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle sugar level deletion
if (isset($_GET['delete'])) {
    $sugar_id = $_GET['id'];

    // Delete the sugar level from the database
    $deleteQuery = "DELETE FROM sugar_levels WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->execute([$sugar_id]);


    // Return success message as JSON
    echo json_encode(['status' => 'success', 'message' => 'Sugar level deleted successfully!']);
    exit(); // Ensure to exit after sending the response
}

// Render the sugar levels
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sugar Levels</title>
    <link rel="stylesheet" href="css/styles2.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="contain">
        <header class="header">
            <h1>Sugar Levels</h1>
        </header>

        <div class="content">
            <div class="add-button"><span onclick="openForm()">+</span></div>
            <div id="sugarLevelsList">
                <?php foreach ($sugarLevels as $sugar): ?>
                    <div class="tile" id="sugar_<?php echo $sugar['id']; ?>">
                        <span>
                            <div class="sugar-detail">
                                <strong>Sugar Level:</strong> <span><?php echo htmlspecialchars($sugar['sugar_level']); ?></span> mg/dL
                            </div>
                            <div class="sugar-detail">
                                <strong>Type:</strong> <span><?php echo htmlspecialchars($sugar['sugar_type']); ?></span>
                            </div>
                            <div class="sugar-detail">
                                <strong>Date & Time:</strong> <span><?php echo htmlspecialchars($sugar['reading_date'] . ' ' . $sugar['reading_time']); ?></span>
                            </div>
                            <div class="sugar-detail">
                                <strong style="color: red;"><span><?php echo htmlspecialchars($sugar['alert']); ?></span></strong>
                            </div>
                        </span>
                        <div>
                            <button class="edit-button" type="button" onclick="openEditForm(<?php echo htmlspecialchars(json_encode($sugar)); ?>)">Edit</button>
                            <button type="button" onclick="openConfirmationModal(<?php echo htmlspecialchars($sugar['id']); ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="chart-container h">
            <h1>Fasting Sugar Levels Bar Chart</h1>
            <canvas id="fastingChart"></canvas>
            <br>
            <h1>Random Sugar Levels Bar Chart</h1>
            <canvas id="randomChart"></canvas>
        </div>

        <!-- Notification for deletion and updates -->
        <div class="notification" id="notification" style="display:none;"></div>

        <!-- Overlay and Modal for Form -->
        <div class="overlay" id="overlay" style="display:none;"></div>

        <div class="modal" id="modal" style="display:none;">
            <h2 id="formTitle">Add New Sugar Level</h2>
            <form id="sugarForm" method="POST" action="" onsubmit="return false;">

                <input type="hidden" id="sugarId" name="id"> <!-- Hidden input for the sugar ID -->
                <label for="sugarLevel">Sugar Level (mg/dL)</label>
                <input type="number" id="sugarLevel" name="sugarLevel" required>

                <label for="sugarType">Type</label>
                <select id="sugarType" name="sugarType" required>
                    <option value="fasting">Fasting</option>
                    <option value="random">Random</option>
                </select>

                <label for="readingDate">Date</label>
                <input type="date" id="readingDate" name="readingDate" required>

                <label for="readingTime">Time</label>
                <input type="time" id="readingTime" name="readingTime" required>

                <button type="button" onclick="submitForm()">Submit</button>
            </form>
        </div>

        <!-- Confirmation Modal for Deletion -->
        <div class="confirmation-overlay" id="confirmationOverlay" style="display:none;"></div>
        <div class="confirmation-modal" id="confirmationModal" style="display:none;">
            <h2>Are you sure you want to delete this sugar level reading?</h2>
            <div>
                <button id="confirmDelete">Yes, Delete</button>
                <button onclick="closeConfirmationModal()">No, Cancel</button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('sugarForm').addEventListener('keydown', function(event) {
    if (event.key === 'Enter') {
        event.preventDefault(); // Prevent the default form submission
        submitForm(); // Call your AJAX submit function
    }
});

        // Prepare data for charts
        const sugarLevels = <?php echo json_encode($sugarLevels); ?>;
        const fastingData = sugarLevels.filter(sugar => sugar.sugar_type === 'fasting');
        const randomData = sugarLevels.filter(sugar => sugar.sugar_type === 'random');

        const fastingLabels = fastingData.map(sugar => `${sugar.reading_date} ${sugar.reading_time}`);
        const fastingValues = fastingData.map(sugar => sugar.sugar_level);

        const randomLabels = randomData.map(sugar => `${sugar.reading_date} ${sugar.reading_time}`);
        const randomValues = randomData.map(sugar => sugar.sugar_level);

        // Initialize Fasting Chart (Bar Chart)
        const ctxFasting = document.getElementById('fastingChart').getContext('2d');
        new Chart(ctxFasting, {
            type: 'bar',
            data: {
                labels: fastingLabels,
                datasets: [{
                    label: 'Fasting Sugar Levels',
                    data: fastingValues,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Initialize Random Chart (Bar Chart)
        const ctxRandom = document.getElementById('randomChart').getContext('2d');
        new Chart(ctxRandom, {
            type: 'bar',
            data: {
                labels: randomLabels,
                datasets: [{
                    label: 'Random Sugar Levels',
                    data: randomValues,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        function openForm() {
            document.getElementById('formTitle').innerText = 'Add New Sugar Level';
            document.getElementById('sugarForm').reset();
            document.getElementById('modal').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function openEditForm(sugar) {
            document.getElementById('formTitle').innerText = 'Edit Sugar Level';
            document.getElementById('sugarId').value = sugar.id;
            document.getElementById('sugarLevel').value = sugar.sugar_level;
            document.getElementById('sugarType').value = sugar.sugar_type;
            document.getElementById('readingDate').value = sugar.reading_date;
            document.getElementById('readingTime').value = sugar.reading_time;
            document.getElementById('modal').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function closeForm() {
            document.getElementById('modal').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }

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

        function submitForm() {
            const formData = new FormData(document.getElementById('sugarForm'));
            formData.append('ajax', 'true'); 

            fetch('', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification(data.message);
                    location.reload(); // Refresh to show updated list
                } else {
                    showNotification('An error occurred. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.');
            });
        }

        // Function to open confirmation modal for deletion
        function openConfirmationModal(sugarId) {
            document.getElementById('confirmationModal').style.display = 'block';
            document.getElementById('confirmationOverlay').style.display = 'block';

            // Set the onclick function for the confirmation button
            document.getElementById('confirmDelete').onclick = function() {
                fetch('?delete=true&id=' + sugarId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showNotification(data.message);
                        document.getElementById('sugar_' + sugarId).remove(); // Remove from UI
                        closeConfirmationModal();
                    } else {
                        showNotification('Failed to delete sugar level. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to delete sugar level. Please try again.');
                });
            };
        }

        function closeConfirmationModal() {
            document.getElementById('confirmationModal').style.display = 'none';
            document.getElementById('confirmationOverlay').style.display = 'none';
        }
          // Display notifications from session variables on page load
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
