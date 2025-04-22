<?php
session_start();

// Clear all session data
$_SESSION = [];
session_unset();
session_destroy();
exit;