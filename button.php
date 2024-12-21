<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Connection</title>
    <style>

        .button-container {
            text-align: center;
        }

        #connectButton {
            padding: 15px 30px;
            font-size: 18px;
            color: white;
            background-color: darkblue;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        #connectButton.connecting,
        #connectButton.disconnecting {
            background-color: #FFC107;
            cursor: not-allowed;
        }

        #connectButton.connected {
            background-color: #f44336;
        }

        #status {
            margin-top: 15px;
            font-size: 16px;
        }
    </style>
</head>
<body>

<div class="button-container">
    <button id="connectButton">
        Connect to Device
    </button>
    <div id="status">
        Status: Disconnected
    </div>
</div>

<script>
    const button = document.getElementById('connectButton');
    const statusDiv = document.getElementById('status');

    // Load the connection state from localStorage
    let deviceConnected = localStorage.getItem('device_connected') === 'true';

    // Initialize button and status text based on the connection state
    if (deviceConnected) {
        button.classList.add('connected');
        button.textContent = 'Disconnect from Device';
        statusDiv.textContent = 'Status: Connected';
    } else {
        button.textContent = 'Connect to Device';
        statusDiv.textContent = 'Status: Disconnected';
    }

    button.addEventListener('click', function () {
        let action = button.classList.contains('connected') ? 'disconnect' : 'connect';
        let originalText = button.textContent;

        // Update button text to show the connecting/disconnecting state
        button.textContent = action === 'connect' ? 'Connecting...' : 'Disconnecting...';
        button.classList.add(action === 'connect' ? 'connecting' : 'disconnecting');

        // Simulate a server response using a timeout (replace with actual AJAX call if needed)
        setTimeout(function() {
            // Update localStorage and button state based on the action
            if (action === 'connect') {
                deviceConnected = true;
                localStorage.setItem('device_connected', 'true');
                button.textContent = 'Disconnect from Device';
                button.classList.remove('connecting');
                button.classList.add('connected');
                statusDiv.textContent = 'Status: Connected';
            } else {
                deviceConnected = false;
                localStorage.setItem('device_connected', 'false');
                button.textContent = 'Connect to Device';
                button.classList.remove('disconnecting');
                button.classList.remove('connected');
                statusDiv.textContent = 'Status: Disconnected';
            }
        }, 1000); // Simulate 1-second delay
    });
</script>

</body>
</html>
