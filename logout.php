<?php
// logout.php

require_once 'config.php';

// Destroy all session data
session_unset();
session_destroy();

// Redirect to homepage
redirect('index.php');
?>