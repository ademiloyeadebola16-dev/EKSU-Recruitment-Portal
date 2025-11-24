 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Example</title>
    <style>
        body {
            background-color: #f4f4f4; /* Set the background color */
            font-family: 'Arial', sans-serif; /* Set the font family */
            color: #333; /* Set the text color */
            margin: 20px; /* Add some margin for better visibility */
        }

        h1 {
            color: #007BFF; /* Set a different color for the heading */
        }
    </style>
</head>
<body>

    <?php
    // PHP code starts here

    // Display a greeting message
    echo "<h1>Hello, PHP!</h1>";

    // Get the current date and time
    $currentDate = date("Y-m-d H:i:s");

    // Display the current date and time
    echo "<p>Current Server Date and Time: $currentDate</p>";

    // PHP code ends here
    ?>

</body>
</html>