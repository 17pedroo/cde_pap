<?php
require __DIR__ . "/includes/config.php";

$role = $_SESSION["role"] ?? "";

if ($role === "student") {
	header("Location: dashboard.php");
} elseif ($role === "guardian") {
	header("Location: guardian_dashboard.php");
} elseif ($role === "staff" || $role === "admin") {
	header("Location: admin_dashboard.php");
} else {
	header("Location: login.php");
}
exit;
?>