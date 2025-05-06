<?php
session_start();
session_destroy(); // Уничтожаем сессию
header('Location: login.php'); // Перенаправляем на страницу логина
exit();
?>