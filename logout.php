<?php
require_once 'config.php';

// Hancurkan session
session_unset();
session_destroy();

// Redirect ke halaman utama
 $_SESSION['message'] = "Anda telah logout";
 $_SESSION['message_type'] = "info";
redirect('index.php');
?>