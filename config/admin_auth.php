<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: /bookstore_api/admin/login.php');
    exit;
}