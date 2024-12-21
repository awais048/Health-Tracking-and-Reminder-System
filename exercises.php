

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

// Handle exercise submission or update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $exercise_name = $_POST['exerciseName'];
    $duration = $_POST['duration'];
    $exercise_date = $_POST['exerciseDate'];
    $exercise_time = $_POST['exerciseTime'];
    
    // Check if we are updating an exercise
    if (!empty($_POST['id'])) {
        $exercise_id = $_POST['id'];
    
        // Combine exercise_date and exercise_time into a DateTime object
        $updated_datetime = DateTime::createFromFormat('Y-m-d H:i', $exercise_date . ' ' . $exercise_time);
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
        $updateQuery = "UPDATE exercises SET exercise_name = ?, duration = ?, exercise_date = ?, exercise_time = ?, is_notified = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$exercise_name, $duration, $exercise_date, $exercise_time, $is_notified, $exercise_id]);
    
        // Set a session variable for the update notification
        $_SESSION['update_notification'] = "Exercise updated successfully!";
        
        // Return success message
        echo json_encode(['status' => 'success', 'message' => 'Exercise updated successfully!']);
    }
     else {
        // Insert a new exercise
        $insertQuery = "INSERT INTO exercises (user_id, exercise_name, duration, exercise_date, exercise_time) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->execute([$user_id, $exercise_name, $duration, $exercise_date, $exercise_time]);

        // Set a session variable for the insert notification
        $_SESSION['insert_notification'] = "Exercise added successfully!";
        echo json_encode(['status' => 'success', 'message' => 'Exercise added successfully!']);
    }
    exit(); // Ensure to exit after sending the response
}

// Fetch exercises for the logged-in user
$exercisesQuery = "SELECT * FROM exercises WHERE user_id = ?";
$stmt = $conn->prepare($exercisesQuery);
$stmt->execute([$user_id]);
$exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle exercise deletion
if (isset($_GET['delete'])) {
    $exercise_id = $_GET['id'];

    // Delete the exercise from the database
    $deleteQuery = "DELETE FROM exercises WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->execute([$exercise_id]);

    echo json_encode(['status' => 'success', 'message' => 'Exercise deleted successfully!']);
    exit(); // Ensure to exit after sending the response
}

// Render the exercises page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercises</title>
    <link rel="stylesheet" href="css/styles2.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include 'navbar.php' ?>
    <div class="contain">
        <header class="header">
            <h1>Exercises</h1>
        </header>

        <div class="content">
            <div class="add-button"><span onclick="openForm()">+</span></div>
            <div id="exercisesList">
                <?php foreach ($exercises as $exercise): ?>
                    <div class="tile" id="exercise_<?php echo $exercise['id']; ?>">
                        <span>
                            <strong>Exercise Name: </strong><?php echo htmlspecialchars($exercise['exercise_name']); ?> <br>
                            <strong>Duration: </strong><?php echo htmlspecialchars($exercise['duration']); ?> <br>
                            <strong>Date: </strong><?php echo htmlspecialchars($exercise['exercise_date']); ?> <br>
                            <strong>Time: </strong><?php echo htmlspecialchars($exercise['exercise_time']); ?>
                        </span>
                        <div>
                            <button class="edit-button" type="button" onclick="openEditForm(<?php echo htmlspecialchars(json_encode($exercise)); ?>)">Edit</button>
                            <button type="button" onclick="openConfirmationModal(<?php echo htmlspecialchars($exercise['id']); ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Notification for deletion and updates -->
        <div class="notification" id="notification" style="display:none;"></div>

        <!-- Overlay and Modal for Form -->
        <div class="overlay" id="overlay" style="display:none;"></div>

        <div class="modal" id="modal" style="display:none;">
            <h2 id="formTitle">Add New Exercise</h2> <br>
            <form id="exerciseForm" method="POST" action="">
                <input type="hidden" id="exerciseId" name="id"> <!-- Hidden input for the exercise ID -->
                <label for="exerciseName">Exercise Name</label>
                <input type="text" id="exerciseName" name="exerciseName" required>

                <label for="duration">Duration (minutes)</label>
                <input type="number" id="duration" name="duration" required>

                <label for="exerciseDate">Date</label>
                <input type="date" id="exerciseDate" name="exerciseDate" required>

                <label for="exerciseTime">Time</label>
                <input type="time" id="exerciseTime" name="exerciseTime" required>

                <button type="button" onclick="submitForm()">Submit</button>
            </form>
        </div>

        <!-- Confirmation Modal for Deletion -->
        <div class="confirmation-overlay" id="confirmationOverlay" style="display:none;"></div>
        <div class="confirmation-modal" id="confirmationModal" style="display:none;">
            <h2>Are you sure you want to delete this exercise?</h2>
            <div>
                <button id="confirmDelete">Yes, Delete</button>
                <button onclick="closeConfirmationModal()">No, Cancel</button>
            </div>
        </div>

        <!-- Bar Chart for Exercises -->
        <div class="chart-container h">
        <h1>Exercise Bar Chart</h1>
            <canvas id="exerciseChart"></canvas>
        </div>
    </div>

    <script>
        // Initialize and render the exercise bar chart
        const exerciseNames = <?php echo json_encode(array_column($exercises, 'exercise_name')); ?>;
        const exerciseDurations = <?php echo json_encode(array_column($exercises, 'duration')); ?>;

        const ctx = document.getElementById('exerciseChart').getContext('2d');
        const exerciseChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: exerciseNames,
                datasets: [{
                    label: 'Duration (minutes)',
                    data: exerciseDurations,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
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
                            text: 'Duration (minutes)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Exercises'
                        }
                    }
                },
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                    },
                    title: {
                        display: true,
                    }
                }
            }
        });

        function openForm() {
            document.getElementById('formTitle').innerText = 'Add New Exercise';
            document.getElementById('exerciseForm').reset();
            document.getElementById('modal').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function openEditForm(exercise) {
            document.getElementById('formTitle').innerText = 'Edit Exercise';
            document.getElementById('exerciseId').value = exercise.id;
            document.getElementById('exerciseName').value = exercise.exercise_name;
            document.getElementById('duration').value = exercise.duration;
            document.getElementById('exerciseDate').value = exercise.exercise_date;
            document.getElementById('exerciseTime').value = exercise.exercise_time;
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
                }, 500);
            }, 3000);
        }

        // Function to submit form via AJAX
        function submitForm() {
            const formData = new FormData(document.getElementById('exerciseForm'));
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
        function openConfirmationModal(exerciseId) {
            document.getElementById('confirmationModal').style.display = 'block';
            document.getElementById('confirmationOverlay').style.display = 'block';

            // Set the onclick function for the confirmation button
            document.getElementById('confirmDelete').onclick = function() {
                fetch('?delete=true&id=' + exerciseId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showNotification(data.message);
                        document.getElementById('exercise_' + exerciseId).remove(); 
                        closeConfirmationModal(); 
                    } else {
                        showNotification('An error occurred. Please try again.'); 
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred. Please try again.'); 
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
        // Close confirmation modal when clicking on the overlay
        document.getElementById('confirmationOverlay').addEventListener('click', closeConfirmationModal);

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
