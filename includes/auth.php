<?php
function require_login() {
  if (empty($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
  }
}

function require_staff() {
  require_login();
  if (($_SESSION["role"] ?? "") !== "staff" && ($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    die("Acesso negado.");
  }
}
