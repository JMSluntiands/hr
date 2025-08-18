<?php
session_start();

// Destroy lahat ng session data
session_unset();
session_destroy();

// Redirect sa login page
header("Location: ../index");
exit;
