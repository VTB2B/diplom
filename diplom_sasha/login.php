<?php
session_start();
$db = new PDO('sqlite:users.db');

$error = ""; // Переменная для хранения сообщения об ошибке

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // Проверка логина и пароля
        $stmt = $db->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && $user['password'] === $password) {
            $_SESSION['user'] = $user['username'];
            header('Location: dashboard.php');
            exit();
        } else {
            $error = "Неверный логин или пароль";
        }
    } catch (PDOException $e) {
        $error = "Ошибка базы данных: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
            background-image: url('pic/fon5.jpg'); /* Укажите путь к вашему изображению фона */
            background-size: cover; /* Заполнить весь экран */
            background-position: center; /* Центрировать изображение */
            background-repeat: no-repeat; /* Не повторять изображение */
        }
        body {
            font-family: Arial, sans-serif;
            background-color: rgba(244, 244, 244, 0.8); /* Полупрозрачный фон для контента */
        }
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background-color: #ffffff;
            border-bottom: 1px solid #e3e6f0;
            flex-wrap: wrap; /* Позволяет элементам переноситься на новую строку */
        }
        .header-title {
            flex: 1;
            text-align: center;
            font-size: 1.5rem; /* Увеличен размер шрифта */
            font-weight: bold; /* Жирный шрифт */
            color: #333;
            padding: 0 10px; /* Отступы по бокам */
            line-height: 1.4; /* Высота строки для лучшего восприятия */
        }
        .header img {
            width: 50px; /* Размер изображений */
            height: auto;
        }
        .main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0; /* Отступы для изображения и формы */
        }
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 90%; /* Ширина формы адаптирована для мобильных устройств */
            max-width: 400px; /* Максимальная ширина */
        }
        h2 {
            text-align: center;
            font-size: 1.5rem; /* Увеличен размер заголовка */
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: rgb(53, 12, 238);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: rgb(53, 12, 238);
        }
        .error {
            color: red;
            text-align: center;
        }
        .footer {
            padding: 1rem;
            background-color: #f8f9fc;
            border-top: 1px solid #e3e6f0;
            text-align: center;
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: auto; /* Позволяет футеру оставаться внизу */
        }
        @media (max-width: 768px) {
            .header-title {
                font-size: 1.2rem; /* Уменьшен размер заголовка для мобильных */
            }
            .header img {
                width: 40px; /* Уменьшен размер изображений для мобильных */
            }
            .form {
                width: 90%; /* Ширина формы для мобильных устройств */
            }
        }
    </style>
</head>
<body>
    <header>
        <img src="pic/belkoop.png" alt="Левое изображение"> 
        <div class="header-title">Система учета успеваемости учащихся</div>
        <img src="pic/bteu.png" alt="Правое изображение"> 
    </header>
    
    <div class="main">
        <div class="login-container">
            <div class="form">
                <h2>Авторизация</h2>
                <form method="POST" action="login.php">
                    <input type="text" name="username" placeholder="Логин" required>
                    <input type="password" name="password" placeholder="Пароль" required>
                    <button type="submit">Войти</button>
                </form>
                <?php if ($error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <span>Copyright &copy; <?php echo date("Y") ?> | Минский филиал УО "Белорусский торгово-экономический университет потребительской кооперации"</span>
    </footer>
</body>
</html>