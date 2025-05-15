<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$dbFilePath = __DIR__ . '/users.db';

try {
    $pdo = new PDO("sqlite:$dbFilePath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Проверка роли пользователя
    $stmt = $pdo->prepare("SELECT role FROM users WHERE username = :username");
    $stmt->execute([':username' => $_SESSION['user']]);
    $user = $stmt->fetch();

    if (!$user || $user['role'] !== 'admin') {
        header('Location: login.php');
        exit();
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}

$stmt = $pdo->prepare("SELECT id, username, password, created_at FROM users WHERE role = 'teacher'");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            display: flex;
            height: 100vh;
            background-color: #f4f7fa;
        }
        .main {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        .error-message {
            color: red; /* Красный цвет для сообщений об ошибках */
        }
        .sidebar {
            width: 250px;
            background-color: #4a74e4;
            color: white;
            padding: 20px;
            height: 100%;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            transition: width 0.3s;
        }
        .collapsed {
            width: 80px;
        }
        .toggle-button {
            background: none;
            border: none;
            cursor: pointer;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .sidebar h2 {
            text-align: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        .menu-item {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
        }
        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .menu-item img {
            width: 24px;
            height: 24px;
            margin-right: 10px;
            transition: transform 0.3s;
        }
        .content {
            flex-grow: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
            align-items: center;
        }
        .header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 15px;
            border-bottom: 1px solid #e3e6f0;
        }
        .header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #333;
        }
        .logout-button {
            padding: 10px 15px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .logout-button:hover {
            background-color: #c82333;
        }
        .welcome-message, .user-table {
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 20px auto;
            background-color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 700px;
            display: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #4a74e4;
            color: white;
        }
        .back-button, .add-user-button, .delete-button, .change-username-button, .change-password-button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        .back-button:hover,
        .add-user-button:hover,
        .change-username-button:hover,
        .change-password-button:hover {
            background-color: #0056b3;
        }
        .delete-button {
            background-color: #dc3545; /* Красная кнопка удаления */
        }
        .delete-button:hover {
            background-color: #c82333; /* Более темный красный при наведении */
        }
        .message {
            color: green;
            margin-top: 10px;
        }
        #addUserForm {
            display: none;
            text-align: center;
        }
        input[type="text"], input[type="password"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 80%;
            margin-bottom: 10px;
        }
        .action-buttons {
            display: flex;
            justify-content: space-around; /* Распределение кнопок */
            margin-top: 10px;
        }
        .action-buttons button {
            margin: 0 5px; /* Отступы между кнопками */
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="main" id="mainContent">
        <div class="sidebar" id="sidebar">
            <h2 id="sidebarTitle">Панель меню</h2>
            <button class="toggle-button" id="toggleButton">
                <img src="pic/menu.png" alt="Toggle Menu" style="width: 20px; height: 20px;">
            </button>
            <div class="menu-item" id="manageUsersButton">
                <img src="pic/icon_1.png" alt="Управление группами">
                <span class="menu-text">Управление группами</span>
            </div>
            <div class="menu-item">
                <img src="pic/icon_2.png" alt="Успеваемость">
                <span class="menu-text">Успеваемость по группе</span>
            </div>
            <div class="menu-item">
                <img src="pic/icon_3.png" alt="Посещаемость">
                <span class="menu-text">Посещаемость по группе</span>
            </div>
            <div class="menu-item">
                <img src="pic/icon_4.png" alt="Сводка">
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

            <div class="user-table" id="userTable">
                <h2>Учетные записи кураторов групп</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Имя пользователя</th>
                            <th>Пароль</th>
                            <th>Дата регистрации</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php foreach ($users as $user): ?>
                        <tr id="user-<?= htmlspecialchars($user['id']) ?>">
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['password']) ?></td>
                            <td><?= htmlspecialchars($user['created_at']) ?></td>
                            <td class="action-buttons">
                                <button class="change-username-button" onclick="showChangeUsernameForm(<?= $user['id'] ?>)">Сменить логин</button>
                                <button class="change-password-button" onclick="showChangePasswordForm(<?= $user['id'] ?>)">Сменить пароль</button>
                                <button class="delete-button" onclick="deleteUser(<?= $user['id'] ?>)">Удалить</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button class="add-user-button" onclick="showAddUserForm()">Добавить пользователя</button>
            </div>

            <div id="addUserForm" style="display: none; text-align: center;">
                <h2>Добавить пользователя</h2>
                <form id="userForm">
                    <input type="text" id="username" placeholder="Имя пользователя" required>
                    <button type="button" id="addUserButton">Добавить</button>
                </form>
                <p class="message" id="userMessage"></p>
                <button class="back-button" onclick="showUserTable()">Вернуться к таблице пользователей</button>
            </div>

            <div id="changeUsernameForm" style="display: none; text-align: center;">
                <h2>Сменить логин</h2>
                <input type="hidden" id="changeUsernameUserId">
                <input type="text" id="newUsername" placeholder="Новый логин" required>
                <button type="button" id="updateUsernameButton">Сменить логин</button>
                <p class="message" id="usernameMessage"></p>
                <button class="back-button" onclick="hideChangeUsernameForm()">Вернуться</button>
            </div>

            <div id="changePasswordForm" style="display: none; text-align: center;">
                <h2>Сменить пароль</h2>
                <input type="hidden" id="changePasswordUserId">
                <input type="password" id="newPassword" placeholder="Новый пароль" required>
                <button type="button" id="updatePasswordButton">Сменить пароль</button>
                <p class="message" id="passwordMessage"></p>
                <button class="back-button" onclick="hideChangePasswordForm()">Вернуться</button>
            </div>
        </div>
    </div>

    <script>
        const toggleButton = document.getElementById('toggleButton');
        const sidebar = document.getElementById('sidebar');
        const welcomeMessage = document.getElementById('welcomeMessage');
        const userTable = document.getElementById('userTable');
        const manageUsersButton = document.getElementById('manageUsersButton');
        const addUserForm = document.getElementById('addUserForm');
        const userMessage = document.getElementById('userMessage');
        const addUserButton = document.getElementById('addUserButton');
        const usernameInput = document.getElementById('username');
        const userTableBody = document.getElementById('userTableBody');

        let menuCollapsed = false;

        toggleButton.onclick = function() {
            menuCollapsed = !menuCollapsed;
            sidebar.classList.toggle('collapsed', menuCollapsed);
            const menuTexts = sidebar.querySelectorAll('.menu-text');
            menuTexts.forEach(text => {
                text.style.opacity = menuCollapsed ? '0' : '1';
            });
            const icons = sidebar.querySelectorAll('.menu-item img');
            icons.forEach(icon => {
                icon.style.transform = menuCollapsed ? 'scale(1.5)' : 'scale(1)';
            });
        };

        manageUsersButton.onclick = function() {
            welcomeMessage.style.display = 'none';
            userTable.style.display = 'block';
            addUserForm.style.display = 'none'; // Скрываем форму добавления пользователя
        };

        function showAddUserForm() {
            userTable.style.display = 'none';
            addUserForm.style.display = 'block';
        }

        function showUserTable() {
            addUserForm.style.display = 'none';
            userTable.style.display = 'block';
        }

        window.onload = function() {
            welcomeMessage.style.display = 'block';
            userTable.style.display = 'none';
            addUserForm.style.display = 'none';
        };

        // Добавление пользователя через AJAX
        addUserButton.onclick = function() {
            const username = usernameInput.value.trim();
            if (username === '') {
                userMessage.textContent = "Ошибка: Имя пользователя не может быть пустым.";
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "add_user.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                const response = JSON.parse(xhr.responseText);
                if (response.status === 'error') {
                    userMessage.textContent = response.message;
                    userMessage.classList.add('error-message'); // Красный текст для ошибки
                } else {
                    userMessage.textContent = 'Пользователь добавлен! Пароль: ' + response.password;
                    userMessage.classList.remove('error-message'); // Удаляем класс ошибки
                    addUserToTable(response.id, username, response.password, response.created_at); // Добавление пользователя в таблицу
                    usernameInput.value = ''; // Очистите поле ввода
                }
            };
            xhr.send("username=" + encodeURIComponent(username));
        };

        function addUserToTable(id, username, password, createdAt) {
            const newRow = document.createElement('tr');
            newRow.id = 'user-' + id;
            newRow.innerHTML = `
                <td>${username}</td>
                <td>${password}</td>
                <td>${createdAt}</td>
                <td class="action-buttons">
                    <button class="change-username-button" onclick="showChangeUsernameForm(${id})">Сменить логин</button>
                    <button class="change-password-button" onclick="showChangePasswordForm(${id})">Сменить пароль</button>
                    <button class="delete-button" onclick="deleteUser(${id})">Удалить</button>
                </td>
            `;
            userTableBody.appendChild(newRow);
        }

        function deleteUser(userId) {
            if (confirm('Вы действительно хотите удалить пользователя?')) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "delete_user.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function() {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        document.getElementById('user-' + userId).remove(); // Удалить пользователя из таблицы
                        alert('Пользователь с ID ' + userId + ' удален.');
                    } else {
                        alert('Ошибка: ' + response.message);
                    }
                };
                xhr.send("deleteUser=true&userId=" + encodeURIComponent(userId));
            }
        }

        function showChangeUsernameForm(userId) {
            document.getElementById('changeUsernameUserId').value = userId;
            document.getElementById('changeUsernameForm').style.display = 'block';
        }

        function hideChangeUsernameForm() {
            document.getElementById('changeUsernameForm').style.display = 'none';
        }

        function showChangePasswordForm(userId) {
            document.getElementById('changePasswordUserId').value = userId;
            document.getElementById('changePasswordForm').style.display = 'block';
        }

        function hideChangePasswordForm() {
            document.getElementById('changePasswordForm').style.display = 'none';
        }

        document.getElementById('updateUsernameButton').onclick = function() {
            const userId = document.getElementById('changeUsernameUserId').value;
            const newUsername = document.getElementById('newUsername').value;

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "change_username.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                const response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    document.getElementById('usernameMessage').textContent = 'Логин изменен успешно!';
                    document.querySelector(`#user-${userId} td:nth-child(1)`).textContent = newUsername;
                    hideChangeUsernameForm();
                } else {
                    document.getElementById('usernameMessage').textContent = response.message;
                }
            };
            xhr.send("userId=" + encodeURIComponent(userId) + "&newUsername=" + encodeURIComponent(newUsername));
        };

        document.getElementById('updatePasswordButton').onclick = function() {
            const userId = document.getElementById('changePasswordUserId').value;
            const newPassword = document.getElementById('newPassword').value;

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "change_password.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                const response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    document.getElementById('passwordMessage').textContent = 'Пароль изменен успешно!';
                    document.querySelector(`#user-${userId} td:nth-child(2)`).textContent = newPassword;
                    hideChangePasswordForm();
                } else {
                    document.getElementById('passwordMessage').textContent = response.message;
                }
            };
            xhr.send("userId=" + encodeURIComponent(userId) + "&newPassword=" + encodeURIComponent(newPassword));
        };
    </script>
</body>
</html>