<?php
session_start();
session_destroy();

setcookie("remember_me", "", time() - 3600, "/", "", true, true);

header("Location: ../index.php");
exit();

?>