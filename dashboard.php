<?php
session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Tracker Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<?php include 'navbar.php' ?>
    <div class="contain">
        <br>
        <br>
        <br>
    </div>

    <header class="header">
        <h1>Health Tracker</h1>
    </header>
    
<div class="b">
    <?php include 'button.php'?>
</div>
    <div class="container">

        <div id="particles-js"></div>


        <div class="dashboard">
            <div class="tile" id="medicinesTile" onclick="window.location.href='medicines.php'">
                <i class="fas fa-pills icon"></i>
                <h2>Medicines</h2>
            </div>
            <div class="tile" id="exercisesTile" onclick="window.location.href='exercises.php'">
                <i class="fas fa-dumbbell icon"></i>
                <h2>Exercises</h2>
            </div>
            <div class="tile" id="appointmentsTile" onclick="window.location.href='appointments.php'">
                <i class="fas fa-calendar-check icon"></i>
                <h2>Appointments</h2>
            </div>
            <div class="tile" id="sugarTile" onclick="window.location.href='sugar_levels.php'">
                <i class="fas fa-tint icon"></i>
                <h2>Sugar Levels</h2>
            </div>
            <div class="tile" id="bpTile" onclick="window.location.href='blood_pressure.php'">
                <i class="fas fa-heartbeat icon"></i>
                <h2>Blood Pressure</h2>
            </div>
        </div>
    </div>

  
    <script src="https://cdnjs.cloudflare.com/ajax/libs/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS.load('particles-js', 'particles.json', function() {
        console.log('callback - particles.js config loaded');
    });
    // Prevent auto resubmission on page refresh
    if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        
</script>

</body>
</html>
