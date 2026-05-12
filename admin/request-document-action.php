<?php
/**
 * Back-end entry for approve/decline (POST). Same logic as same-page POST on request-document.php.
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';
require_once __DIR__ . '/request-document-handler.inc.php';
