<?php
require 'config.php';

if (!is_logged_in()) {
    header('Location: index.php');
    exit;
}

if (is_admin()) {
    header('Location: admin_dashboard.php');
} else {
    header('Location: user_dashboard.php');
}
exit;