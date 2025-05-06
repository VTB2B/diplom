<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            height: 100vh; /* Высота на весь экран */
            overflow: hidden; /* Убираем прокрутку */
        }
        .main {
            display: flex;
            flex: 1; /* Занимает доступное пространство */
            overflow: hidden; /* Убираем горизонтальную прокрутку */
        }
        .sidebar {
            width: 200px; /* Исходная ширина панели меню */
            background-color: #007bff; /* Синий цвет для панели меню */
            color: white;
            padding: 15px; /* Отступы */
            height: 100%; /* Высота панели меню на весь экран */
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column; /* Вертикальная компоновка */
            transition: width 0.3s; /* Плавный переход для изменения ширины */
            position: relative; /* Позволяет размещать кнопку внутри панели */
        }
        .sidebar.collapsed {
            width: 100px; /* Новая ширина панели меню */
        }
        .toggle-button {
            background: none;
            border: none;
            cursor: pointer;
            position: center; /* Абсолютное позиционирование */
            top: 15px; /* Отступ сверху */
            right: 15px; /* Отступ справа */
        }
        .sidebar h2 {
            text-align: center;
            font-size: 1.3rem; /* Размер шрифта */
            margin-bottom: 15px; /* Отступ снизу */
            transition: opacity 0.3s; /* Плавный переход для исчезновения текста */
        }
        .menu-item {
            margin: 10px 0; /* Отступы */
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            cursor: pointer;
            transition: opacity 0.3s; /* Плавный переход для изменения прозрачности */
            display: flex; /* Используем flex для выравнивания иконки и текста */
            align-items: center;
        }
        .menu-item img {
            width: 30px; /* Ширина иконок */
            height: 30px; /* Высота иконок */
            margin-right: 10px; /* Отступ между иконкой и текстом */
        }
        .menu-text {
            transition: opacity 0.3s; /* Плавный переход для текста */
        }
        .sidebar.collapsed .menu-text {
            opacity: 0; /* Скрываем текст при сворачивании */
        }
        .content {
            flex-grow: 1;
            padding: 15px; /* Отступы */
            display: flex;
            flex-direction: column;
            justify-content: flex-start; /* Начало по вертикали */
            overflow-y: auto; /* Вертикальная прокрутка для контента */
            overflow-x: hidden; /* Убираем горизонтальную прокрутку */
        }
        .header {
            width: 100%;
            display: flex;
            justify-content: space-between; /* Выравнивание по краям */
            align-items: center;
            background-color: #ffffff;
            padding: 10px 15px; /* Отступы */
            border-bottom: 1px solid #e3e6f0;
        }
        .header h1 {
            margin: 0;
            font-size: 1.2rem;
            color: #333;
            text-align: center; /* Центрируем заголовок */
            flex-grow: 1; /* Позволяем заголовку занимать доступное пространство */
        }
        .logout-button {
            padding: 8px 12px; /* Отступы */
            background-color: #dc3545; /* Красный цвет для кнопки выхода */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s; /* Анимация при наведении */
            margin-right: 15px; /* Сдвигаем кнопку чуть левее */
        }
        .logout-button:hover {
            background-color: #c82333; /* Темно-красный цвет при наведении */
            transform: scale(1.05); /* Увеличение при наведении */
        }
        .welcome-message {
            border: 2px solid #007bff; /* Синяя рамка */
            border-radius: 10px;
            padding: 20px; /* Отступы */
            text-align: center;
            margin: 20px auto; /* Центрируем по горизонтали */
            background-color: #f0f8ff; /* Светлый фон для сообщения */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Тень */
            width: 90%; /* Ширина сообщения */
            max-width: 600px; /* Максимальная ширина для больших экранов */
            opacity: 0; /* Начальная непрозрачность */
            transform: translateY(20px); /* Начальная позиция */
            transition: opacity 1s, transform 1s; /* Плавный переход для появления */
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%; /* Ширина панели меню для мобильных */
                height: auto; /* Высота для мобильных */
                padding: 10px; /* Уменьшенные отступы */
            }
            .header {
                flex-direction: column; /* Вертикальное выравнивание для мобильных */
                align-items: flex-start; /* Выравнивание элементов по левому краю */
            }
            .header h1 {
                font-size: 1.2rem; /* Размер заголовка для мобильных */
                margin-bottom: 10px; /* Отступ снизу для заголовка */
            }
            .logout-button {
                width: 100%; /* Ширина кнопки для мобильных */
                margin-top: auto; /* Сдвигаем кнопку вниз */
            }
            .welcome-message {
                display: none; /* Скрываем сообщение на мобильных устройствах */
            }
        }
    </style>
</head>
<body>
    <div class="main" id="mainContent">
        <div class="sidebar" id="sidebar">
            <button class="toggle-button" id="toggleButton">
                <img src="pic/menu.png" alt="Toggle Menu" style="width: 20px; height: 20px;">
            </button>
            <h2 id="sidebarTitle">Панель меню</h2>
            <div class="menu-item">
                <img src="pic/icon1.png" alt="Управление группами">
                <span class="menu-text">Управление группами</span>
            </div>
            <div class="menu-item">
                <img src="pic/icon1.png" alt="Успеваемость">
                <span class="menu-text">Успеваемость по группе</span>
            </div>
            <div class="menu-item">
                <img src="pic/icon1.png" alt="Посещаемость">
                <span class="menu-text">Посещаемость по группе</span>
            </div>
            <div class="menu-item">
                <img src="pic/icon1.png" alt="Сводка">
                <span class="menu-text">Сводка</span>
            </div>
        </div>
        
        <div class="content">
            <div class="header">
                <h1>Вы авторизованы как администратор</h1>
                <button class="logout-button" onclick="window.location.href='logout.php'">Выйти</button>
            </div>
            
            <div class="welcome-message" id="welcomeMessage">
                <h2>Добро пожаловать на панель администратора!</h2>
                <p>Здесь вы можете управлять группами, просматривать успеваемость и посещаемость.</p>
            </div>
        </div>
    </div>

    <script>
        const toggleButton = document.getElementById('toggleButton');
        const sidebar = document.getElementById('sidebar');
        const sidebarTitle = document.getElementById('sidebarTitle');

        let menuCollapsed = false;

        toggleButton.onclick = function() {
            menuCollapsed = !menuCollapsed;
            sidebar.classList.toggle('collapsed', menuCollapsed);

            const menuTexts = sidebar.querySelectorAll('.menu-text');
            menuTexts.forEach(text => {
                text.style.opacity = menuCollapsed ? '0' : '1'; // Плавное исчезновение текста
            });
        };

        // Функция для плавного появления приветственного сообщения
        window.onload = function() {
            welcomeMessage.style.opacity = 1; // Устанавливаем непрозрачность в 1
            welcomeMessage.style.transform = 'translateY(0)'; // Возвращаем в исходное положение
        };
    </script>
</body>
</html>