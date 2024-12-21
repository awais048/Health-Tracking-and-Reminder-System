<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Destroy the session when the form is submitted
    session_destroy();
    header("Location: login.php"); // Redirect to the login page after logout
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Tracker Navbar</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap"
      rel="stylesheet"
    />
    <style>
        .navbar {
            display: flex;
            justify-content: space-around;
            background-color: darkslategray;
            padding: 10px;
            position: fixed;
            width: 97%;
            border-radius: 50px;
            align-items: center;
            z-index: 1100;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: background-color 0.3s;
            margin: 0 10px;
        }
        .navbar a:hover {
            background-color: darkcyan;
        }
        .active {
            background-color: darkcyan;
        }
        .btn{
            padding: 10px 13px;
            font-weight: bold;
            font-size: 14px;
            border-radius: 50px;
            background-color: red;
            color: white;
            border: none;
            cursor: pointer;
        }

        .btn:hover{
            background-color: brown;
        }

        .menu-icon {
            display: none;
            font-size: 28px;
            cursor: pointer;
            color: white;
            z-index: 1210;
        }

        /* Pop-up notification styles */
        .custom-popup {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: black;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            opacity: 0;
            transition: opacity 0.5s ease, transform 0.5s ease;
            z-index: 1300; /* Set z-index higher for visibility */
        }

        .custom-popup.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .popup-button {
            background-color: darkcyan;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
            margin-left: 10px;
        }

        .popup-button:hover {
            background-color: teal;
        }

        @media (max-width: 900px) {
            .navbar{
                width: 94%;
            }
        }
        @media (max-width: 800px) {
            .navbar{
                width: 93%;
            }
        }
        @media (max-width: 1031px) {
            .navbar{
                width: 95%;
                justify-content: space-between;
            }
            .navbar ul {
                display: none;
                flex-direction: column;
                gap: 30px;
                background-color: #082a48;
                position: absolute;
                top: 0;
                left: 0;
                height: 100vh;
                width: 230px;
                padding: 10px;
                padding-top: 60px;
                z-index: 1100;
                border-top-left-radius: 30px;
            }

            .navbar ul.active {
                display: flex;
            }

            .menu-icon {
                display: block;
                z-index: 1200;
                margin: auto 20px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="menu-icon" onclick="toggleMenu()">&#9776;</div>
    <ul class="nav-list">
        <a href="dashboard.php" class="nav-link">Home</a>
        <a href="medicines.php" class="nav-link">Medicines</a>
        <a href="exercises.php" class="nav-link">Exercises</a>
        <a href="appointments.php" class="nav-link">Appointments</a>
        <a href="sugar_levels.php" class="nav-link">Sugar Levels</a>
        <a href="blood_pressure.php" class="nav-link">Blood Pressure</a>
    </ul>

    <form method="POST"> <!-- Inline form for logout button -->
        <button type="submit" class="nav-link btn">Logout</button>
    </form>
</nav>

<script>
const links = document.querySelectorAll('.nav-link');
links.forEach(link => {
    link.addEventListener('click', function() {
        links.forEach(link => link.classList.remove('active'));
        this.classList.add('active');
    });
});

// Highlight the active link based on the current URL
const currentLocation = window.location.href;
links.forEach(link => {
    if (link.href === currentLocation) {
        link.classList.add('active');
    }
});

let notifications = [];

// Function to fetch notifications
function fetchNotifications() {
    fetch('fetch_notifications.php') // Change the URL to your PHP file for fetching notifications
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                // Show pop-up notifications for new notifications
                showNotificationPopup(data);
                // Add new notifications to the stack
                addToNotificationStack(data);
            }
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

// Function to show notifications popup
function showNotificationPopup(data) {
    data.forEach(notification => {
        createCustomPopup(`${notification.type} Reminder: ${notification.title}`);
    });
}

// Function to create a custom pop-up notification
function createCustomPopup(message) {
    const popup = document.createElement('div');
    popup.classList.add('custom-popup');
    popup.innerHTML = `
        ${message}
        <button class="popup-button" onclick="closePopup(this)">Got it</button>
    `;
    document.body.appendChild(popup);

    setTimeout(() => {
        popup.classList.add('show');
    }, 100);

    
}

// Function to close the popup when "Got it" is clicked
function closePopup(button) {
    const popup = button.parentElement;
    popup.classList.remove('show');
    setTimeout(() => {
        document.body.removeChild(popup);
    }, 300);
}

// Function to add new notifications to the stack
function addToNotificationStack(data) {
    data.forEach(notification => {
        notifications.unshift(notification);  // Add to the beginning of the array for stack behavior
    });
}

// Simulate new notifications being fetched every 5 seconds
setInterval(fetchNotifications, 5000);

// Hamburger menu toggle
function toggleMenu() {
        const navList = document.querySelector('.nav-list');
        navList.classList.toggle('active');
    }

    // Hide the menu when clicking outside
    document.addEventListener('click', function(event) {
        const navList = document.querySelector('.nav-list');
        const menuIcon = document.querySelector('.menu-icon');

        if (!navList.contains(event.target) && !menuIcon.contains(event.target)) {
            navList.classList.remove('active');
        }
    });
</script>

</body>
</html>
