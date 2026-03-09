<?php
include 'config.php';

if (!is_logged_in()) {
    header('Location: index.php');
    exit;
}

$humidity_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($humidity_id) {
    if (is_admin()) {
        // Admin can delete any record
        $stmt = $conn->prepare("DELETE FROM humidity WHERE humidity_id=?");
        $stmt->bind_param("i", $humidity_id);
        $stmt->execute();
        $stmt->close();

        // Delete from user_logs
        $stmt = $conn->prepare("DELETE FROM user_logs WHERE humidity_id=?");
        $stmt->bind_param("i", $humidity_id);
        $stmt->execute();
        $stmt->close();

        $message = "Record deleted successfully.";
    } else {
        // Regular user can delete only their own records
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT COUNT(*) FROM user_logs WHERE user_id=? AND humidity_id=?");
        $stmt->bind_param("ii", $user_id, $humidity_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            // Delete from user_logs first
            $stmt = $conn->prepare("DELETE FROM user_logs WHERE humidity_id=? AND user_id=?");
            $stmt->bind_param("ii", $humidity_id, $user_id);
            $stmt->execute();
            $stmt->close();

            // Delete from humidity table
            $stmt = $conn->prepare("DELETE FROM humidity WHERE humidity_id=?");
            $stmt->bind_param("i", $humidity_id);
            $stmt->execute();
            $stmt->close();

            $message = "Record deleted successfully.";
        } else {
            $message = "You do not have permission to delete this record.";
        }
    }
}

header("Location: dashboard.php?msg=" . urlencode($message));
exit;
?>