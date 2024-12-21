<?php
session_start();
// No need to include connection.php here as it will be done in fetch_notifications.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .notification {
            margin: 10px 0; /* Add some space between notifications */
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Notifications</h1>
        <div id="notifications" class="list-group">
            <div class="list-group-item notification">
                Loading notifications...
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        function fetchNotifications() {
            $.ajax({
                url: 'fetch_notifications.php', // URL to fetch notifications
                method: 'GET',
                success: function(data) {
                    // Check if data is not empty
                    if (Array.isArray(data) && data.length > 0) {
                        $('#notifications').empty(); // Clear current notifications

                        // Rebuild the notification list
                        data.forEach(function(notification) {
                            $('#notifications').append(
                                '<div class="list-group-item notification">' +
                                    '<strong>' + notification.type + ':</strong> ' + 
                                    notification.title +
                                '</div>'
                            );
                        });
                    } else {
                        $('#notifications').html(
                            '<div class="list-group-item notification">No notifications at this time.</div>'
                        );
                    }
                },
                error: function() {
                    console.error('Error fetching notifications.');
                }
            });
        }

        // Call fetchNotifications every 30 seconds
        setInterval(fetchNotifications, 10000);
        // Fetch notifications on initial load
        fetchNotifications();
    </script>
</body>
</html>
