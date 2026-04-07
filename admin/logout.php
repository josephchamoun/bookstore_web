<?php
session_start();
session_destroy();
header('Location: /bookstore_api/admin/login.php');
exit;