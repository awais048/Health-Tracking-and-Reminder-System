<?php
session_start();
include 'connection.php'; // Include your database connection file

// Function to send JSON responses
function sendResponse($status, $data)
{
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'data' => $data]);
    exit();
}

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    // If it's an AJAX request, send a JSON response
    if (isset($_GET['action'])) {
        sendResponse('error', 'Unauthorized');
    } else {
        header("Location: login.php"); // Redirect to login if not logged in
        exit();
    }
}

// Get the current user's ID based on the email
$email = $_SESSION['email'];
$query = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // If it's an AJAX request, send a JSON response
    if (isset($_GET['action'])) {
        sendResponse('error', 'User not found');
    } else {
        header("Location: login.php"); // Redirect to login if user not found
        exit();
    }
}

$user_id = $user['id'];

// Handle AJAX requests
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'create':
            // Handle appointment creation
            $input = json_decode(file_get_contents('php://input'), true);
            $appointment_title = trim($input['appointmentTitle'] ?? '');
            $appointment_date = trim($input['appointmentDate'] ?? '');
            $appointment_time = trim($input['appointmentTime'] ?? '');
            $doctor_name = trim($input['doctorName'] ?? '');
            $location = trim($input['location'] ?? '');

            // Validate input
            if ($appointment_title && $appointment_date && $appointment_time && $doctor_name && $location) {
                $insertQuery = "INSERT INTO appointments (user_id, appointment_title, appointment_date, appointment_time, doctor_name, location) VALUES (?, ?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->execute([$user_id, $appointment_title, $appointment_date, $appointment_time, $doctor_name, $location]);

                // Fetch the newly created appointment
                $appointment_id = $conn->lastInsertId();
                $selectQuery = "SELECT * FROM appointments WHERE id = ?";
                $selectStmt = $conn->prepare($selectQuery);
                $selectStmt->execute([$appointment_id]);
                $newAppointment = $selectStmt->fetch(PDO::FETCH_ASSOC);

                sendResponse('success', $newAppointment);
            } else {
                sendResponse('error', 'All fields are required.');
            }
            break;

        case 'read':
            // Handle fetching appointments
            $appointmentsQuery = "SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC, appointment_time DESC";
            $stmt = $conn->prepare($appointmentsQuery);
            $stmt->execute([$user_id]);
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            sendResponse('success', $appointments);
            break;

        case 'update':
            // Handle appointment update
            $input = json_decode(file_get_contents('php://input'), true);
            $appointment_id = intval($input['id'] ?? 0);
            $appointment_title = trim($input['appointmentTitle'] ?? '');
            $appointment_date = trim($input['appointmentDate'] ?? '');
            $appointment_time = trim($input['appointmentTime'] ?? '');
            $doctor_name = trim($input['doctorName'] ?? '');
            $location = trim($input['location'] ?? '');

            // Validate input
            if ($appointment_id && $appointment_title && $appointment_date && $appointment_time && $doctor_name && $location) {
                // Verify the appointment belongs to the user
                $verifyQuery = "SELECT id FROM appointments WHERE id = ? AND user_id = ?";
                $verifyStmt = $conn->prepare($verifyQuery);
                $verifyStmt->execute([$appointment_id, $user_id]);
                if ($verifyStmt->rowCount() === 0) {
                    sendResponse('error', 'Forbidden: You cannot edit this appointment.');
                }

                // Get the current date and time and add 3 hours
                $currentDateTime = new DateTime();
                $currentDateTime->add(new DateInterval('PT3H')); // Add 3 hours to current time
                $currentDateTimeFormatted = $currentDateTime->format('Y-m-d H:i:s');

                // Concatenate appointment date and time for comparison
                $appointmentDateTime = $appointment_date . ' ' . $appointment_time;

                // Check if the updated date and time are greater than the current date and time + 3 hours
                if ($appointmentDateTime > $currentDateTimeFormatted) {
                    $is_notified = 0; // Reset the notification status if the new appointment is more than 3 hours away
                } else {
                    $is_notified = 1; // Keep it as notified if the time is less than 3 hours away
                }

                // Update the appointment with the new data and is_notified status
                $updateQuery = "UPDATE appointments SET appointment_title = ?, appointment_date = ?, appointment_time = ?, doctor_name = ?, location = ?, is_notified = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->execute([$appointment_title, $appointment_date, $appointment_time, $doctor_name, $location, $is_notified, $appointment_id]);

                // Fetch the updated appointment
                $selectQuery = "SELECT * FROM appointments WHERE id = ?";
                $selectStmt = $conn->prepare($selectQuery);
                $selectStmt->execute([$appointment_id]);
                $updatedAppointment = $selectStmt->fetch(PDO::FETCH_ASSOC);

                sendResponse('success', $updatedAppointment);
            } else {
                sendResponse('error', 'All fields are required.');
            }
            break;


        case 'delete':
            // Handle appointment deletion
            $input = json_decode(file_get_contents('php://input'), true);
            $appointment_id = intval($input['id'] ?? 0);

            if ($appointment_id) {
                // Verify the appointment belongs to the user
                $verifyQuery = "SELECT id FROM appointments WHERE id = ? AND user_id = ?";
                $verifyStmt = $conn->prepare($verifyQuery);
                $verifyStmt->execute([$appointment_id, $user_id]);
                if ($verifyStmt->rowCount() === 0) {
                    sendResponse('error', 'Forbidden: You cannot delete this appointment.');
                }

                $deleteQuery = "DELETE FROM appointments WHERE id = ?";
                $deleteStmt = $conn->prepare($deleteQuery);
                $deleteStmt->execute([$appointment_id]);

                sendResponse('success', 'Appointment deleted successfully.');
            } else {
                sendResponse('error', 'Invalid appointment ID.');
            }
            break;

        default:
            sendResponse('error', 'Invalid action.');
            break;
    }
}

// If not an AJAX request, proceed to render the HTML page

// Fetch appointments for the logged-in user (for initial page load)
$appointmentsQuery = "SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC, appointment_time DESC";
$stmt = $conn->prepare($appointmentsQuery);
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Appointments</title>
    <link rel="stylesheet" href="css/styles2.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

    <?php include 'navbar.php' ?>

    <div class="contain">
        <header class="header">
            <h1>Appointments</h1>
        </header>

        <div class="content">
            <div class="add-button"><span onclick="openForm()">+</span></div>
            <div id="appointmentsList">
                <?php foreach ($appointments as $appointment): ?>
                    <div class="tile" data-id="<?php echo htmlspecialchars($appointment['id']); ?>">
                        <span>
                            <strong><?php echo htmlspecialchars($appointment['appointment_title']); ?></strong> <br>
                            <?php echo htmlspecialchars($appointment['appointment_date'] . ' ' . $appointment['appointment_time']); ?>
                            <br>
                            Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?> <br>
                            Location: <?php echo htmlspecialchars($appointment['location']); ?>
                        </span>
                        <div>
                            <button class="edit-button" type="button"
                                onclick="openEditForm(<?php echo htmlspecialchars(json_encode($appointment)); ?>)">Edit</button>
                            <button type="button"
                                onclick="openConfirmationModal(<?php echo htmlspecialchars($appointment['id']); ?>)">Delete</button>
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
            <h2 id="formTitle">Add New Appointment</h2>
            <form id="appointmentForm">
                <input type="hidden" id="appointmentId" name="id"> <!-- Hidden input for the appointment ID -->
                <label for="appointmentTitle">Appointment Title</label>
                <input type="text" id="appointmentTitle" name="appointmentTitle" required>

                <label for="appointmentDate">Date</label>
                <input type="date" id="appointmentDate" name="appointmentDate" required>

                <label for="appointmentTime">Time</label>
                <input type="time" id="appointmentTime" name="appointmentTime" required>

                <label for="doctorName">Doctor Name</label>
                <input type="text" id="doctorName" name="doctorName" required>

                <label for="location">Location</label>
                <input type="text" id="location" name="location" required>

                <button type="submit">Submit</button>
                <!-- <button type="button" onclick="closeForm()">Cancel</button> -->
            </form>
        </div>

        <!-- Confirmation Modal for Deletion -->
        <div class="confirmation-overlay" id="confirmationOverlay" style="display:none;"></div>
        <div class="confirmation-modal" id="confirmationModal" style="display:none;">
            <h2>Are you sure you want to delete this appointment?</h2>
            <div>
                <button id="confirmDelete">Yes, Delete</button>
                <button onclick="closeConfirmationModal()">No, Cancel</button>
            </div>
        </div>
        <div class="h">
            <h1>Appointments Bar Chart</h1>
            <canvas id="appointmentsChart" width="400" height="200"></canvas>
        </div>

    </div>



    <script>
        // Function to open the Add/Edit form
        function openForm(appointment = null) {
            const modal = document.getElementById('modal');
            const overlay = document.getElementById('overlay');
            const formTitle = document.getElementById('formTitle');
            const appointmentForm = document.getElementById('appointmentForm');

            if (appointment) {
                formTitle.innerText = 'Edit Appointment';
                document.getElementById('appointmentId').value = appointment.id;
                document.getElementById('appointmentTitle').value = appointment.appointment_title;
                document.getElementById('appointmentDate').value = appointment.appointment_date;
                document.getElementById('appointmentTime').value = appointment.appointment_time;
                document.getElementById('doctorName').value = appointment.doctor_name;
                document.getElementById('location').value = appointment.location;
            } else {
                formTitle.innerText = 'Add New Appointment';
                appointmentForm.reset();
                document.getElementById('appointmentId').value = '';
            }

            modal.style.display = 'block';
            overlay.style.display = 'block';
        }

        // Function to close the Add/Edit form
        function closeForm() {
            const modal = document.getElementById('modal');
            const overlay = document.getElementById('overlay');
            modal.style.display = 'none';
            overlay.style.display = 'none';
        }

        // Function to show notifications
        function showNotification(message, isError = false) {
            const notification = document.getElementById('notification');
            notification.innerText = message;
            notification.className = 'notification';
            if (isError) {
                notification.classList.add('error');
            }
            notification.style.opacity = 1;
            notification.style.display = 'block';

            setTimeout(() => {
                notification.style.opacity = 0;
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 500);
            }, 3000);
        }

        // Function to fetch and display appointments
        async function loadAppointments() {
            try {
                const response = await fetch('appointments.php?action=read');
                const result = await response.json();

                if (result.status === 'success') {
                    const appointmentsList = document.getElementById('appointmentsList');
                    appointmentsList.innerHTML = '';

                    if (result.data.length === 0) {
                        appointmentsList.innerHTML = '<p>No appointments found.</p>';
                        return;
                    }

                    result.data.forEach(appointment => {
                        const tile = document.createElement('div');
                        tile.className = 'tile';
                        tile.setAttribute('data-id', appointment.id);

                        const span = document.createElement('span');
                        span.innerHTML = `
    <div class="appointment-detail">
        <strong>Title:</strong> <span>${escapeHTML(appointment.appointment_title)}</span>
    </div>
    <div class="appointment-detail">
        <strong>Date & Time:</strong> <span>${escapeHTML(appointment.appointment_date)} ${escapeHTML(appointment.appointment_time)}</span>
    </div>
    <div class="appointment-detail">
        <strong>Doctor:</strong> <span>${escapeHTML(appointment.doctor_name)}</span>
    </div>
    <div class="appointment-detail">
        <strong>Location:</strong> <span>${escapeHTML(appointment.location)}</span>
    </div>
`;
                        tile.appendChild(span);


                        const div = document.createElement('div');

                        const editButton = document.createElement('button');
                        editButton.className = 'edit-button';
                        editButton.type = 'button';
                        editButton.innerText = 'Edit';
                        editButton.onclick = () => openForm(appointment);
                        div.appendChild(editButton);

                        const deleteButton = document.createElement('button');
                        deleteButton.type = 'button';
                        deleteButton.innerText = 'Delete';
                        deleteButton.onclick = () => openConfirmationModal(appointment.id);
                        div.appendChild(deleteButton);

                        tile.appendChild(div);
                        appointmentsList.appendChild(tile);
                    });
                    // Call the function to create the bar chart
                    createBarChart(result.data); // Call the chart function with the appointment data
                } else {
                    showNotification(result.data, true);
                }
            } catch (error) {
                showNotification('Failed to load appointments.', true);
                console.error(error);
            }
        }

        // Function to escape HTML to prevent XSS
        function escapeHTML(str) {
            if (!str) return '';
            return str.replace(/[&<>'"]/g, tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag]));
        }

        // Handle form submission for Add/Edit
        document.getElementById('appointmentForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const appointmentId = document.getElementById('appointmentId').value;
            const appointmentTitle = document.getElementById('appointmentTitle').value.trim();
            const appointmentDate = document.getElementById('appointmentDate').value;
            const appointmentTime = document.getElementById('appointmentTime').value;
            const doctorName = document.getElementById('doctorName').value.trim();
            const location = document.getElementById('location').value.trim();

            if (!appointmentTitle || !appointmentDate || !appointmentTime || !doctorName || !location) {
                showNotification('All fields are required.', true);
                return;
            }

            const payload = {
                appointmentTitle,
                appointmentDate,
                appointmentTime,
                doctorName,
                location
            };

            let url = 'appointments.php?action=create';
            let method = 'POST';

            if (appointmentId) {
                payload.id = appointmentId;
                url = 'appointments.php?action=update';
                method = 'PUT';
            }

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.status === 'success') {
                    closeForm();
                    showNotification(appointmentId ? 'Appointment updated successfully!' : 'Appointment created successfully!');
                    loadAppointments();
                } else {
                    showNotification(result.data, true);
                }
            } catch (error) {
                showNotification('An error occurred.', true);
                console.error(error);
            }
        });

        // Confirmation Modal for Deletion
        function openConfirmationModal(appointmentId) {
            const confirmationModal = document.getElementById('confirmationModal');
            const overlay = document.getElementById('overlay');

            confirmationModal.style.display = 'block';
            overlay.style.display = 'block';

            document.getElementById('confirmDelete').onclick = () => confirmDelete(appointmentId);
        }

        function closeConfirmationModal() {
            const confirmationModal = document.getElementById('confirmationModal');
            const overlay = document.getElementById('overlay');

            confirmationModal.style.display = 'none';
            overlay.style.display = 'none';
        }

        // Function to confirm deletion
        async function confirmDelete(appointmentId) {
            try {
                const response = await fetch('appointments.php?action=delete', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: appointmentId })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    closeConfirmationModal();
                    showNotification(result.data);
                    loadAppointments();
                } else {
                    showNotification(result.data, true);
                }
            } catch (error) {
                showNotification('Failed to delete appointment.', true);
                console.error(error);
            }
        }

        // Close modals when clicking on the overlay
        document.getElementById('overlay').addEventListener('click', function () {
            closeForm();
            closeConfirmationModal();
        });

        // Initial load of appointments
        document.addEventListener('DOMContentLoaded', loadAppointments);

        // Function to create a detailed bar chart
        let chartInstance; // Variable to hold the chart instance

        // Function to create a detailed bar chart
        function createBarChart(appointments) {
            const ctx = document.getElementById('appointmentsChart').getContext('2d');

            // Destroy the existing chart instance if it exists
            if (chartInstance) {
                chartInstance.destroy();
            }

            // Create labels and data for the chart
            const labels = appointments.map(appointment => appointment.appointment_title);
            const data = appointments.map(() => 1); // Each bar represents one appointment

            // Create a new chart instance
            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Appointments',
                        data: data,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)', // Light color for bars
                        borderColor: 'rgba(75, 192, 192, 1)', // Darker color for borders
                        borderWidth: 1,
                        hoverBackgroundColor: 'rgba(75, 192, 192, 0.5)', // Darker color on hover
                        hoverBorderColor: 'rgba(75, 192, 192, 1)', // Dark border on hover
                        barPercentage: 0.5 // Adjusts the width of the bars
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                display: false // Hides the y-axis ticks
                            },
                            title: {
                                display: true,
                                text: 'Appointments',
                                font: {
                                    size: 16
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Appointment Title',
                                font: {
                                    size: 16
                                }
                            },
                            grid: {
                                display: false // Hides the x-axis grid lines
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                title: function (tooltipItems) {
                                    const index = tooltipItems[0].dataIndex;
                                    return `${appointments[index].appointment_date} - Dr. ${appointments[index].doctor_name}`;
                                },
                                label: function (tooltipItem) {
                                    const index = tooltipItem.dataIndex;
                                    return `Title: ${appointments[index].appointment_title}`;
                                },
                                afterLabel: function (tooltipItem) {
                                    const index = tooltipItem.dataIndex;
                                    return `Location: ${appointments[index].location}`;
                                }
                            }
                        },
                        legend: {
                            display: false // Hides the legend since it's not needed in this case
                        }
                    }
                }
            });
        }



    </script>

    <style>
        .h {
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