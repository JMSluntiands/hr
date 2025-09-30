<?php
session_start();
session_unset();
session_destroy();
header("Location: ../index?logged_out=1");
exit();
