<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // change to your MySQL username
define('DB_PASS', '');           // change to your MySQL password
define('DB_NAME', 'succulent_monitoring');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("<div style='font-family:sans-serif;padding:30px;background:#FFEBEE;color:#B71C1C;border-radius:10px;margin:20px'>
            <strong>Database Connection Failed:</strong> " . $conn->connect_error . "
            <br><br>Please check your <code>config.php</code> credentials.
        </div>");
    }
    $conn->set_charset("utf8");
    return $conn;
}
?>
