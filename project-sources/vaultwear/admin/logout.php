<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/bootstrap.php";

unset($_SESSION["admin_id"], $_SESSION["admin_username"]);
session_regenerate_id(true);
set_flash("success", "You have been logged out.");
redirect("admin/login.php");
