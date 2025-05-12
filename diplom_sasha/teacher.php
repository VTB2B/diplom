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

    // Получение информации о текущем пользователе
    $stmt = $pdo->prepare("SELECT role, group_id FROM users WHERE username = :username");
    $stmt->execute([':username' => $_SESSION['user']]);
    $user = $stmt->fetch();

    if (!$user || $user['role'] !== 'teacher') {
        header('Location: login.php');
        exit();
    }

    $groupId = $user['group_id'];

    // Получение пользователей с тем же group_id, исключая авторизованного
    $stmt = $pdo->prepare("SELECT id, username, password, firstname, lastname, family, created_at FROM users WHERE group_id = :groupId AND username != :currentUser ORDER BY family ASC");
    $stmt->execute([':groupId' => $groupId, ':currentUser' => $_SESSION['user']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $username = htmlspecialchars($_SESSION['user']);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель куратора</title>
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
        .button {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            width: 150px;
            margin: 0 5px; /* Отступы между кнопками */
        }
        .delete-button {
            background-color: #dc3545; /* Красный */
        }
        .add-user-button {
            background-color: #28a745; /* Зеленый */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            padding: 10px 15px;
            transition: background-color 0.3s;
        }
        .add-user-button:hover {
            background-color: #218838; /* Темнее при наведении */
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
        }
        .content {
            flex-grow: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
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
        .logout-button {
            background-color: #dc3545; /* Красный */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            padding: 10px 15px;
            transition: background-color 0.3s;
        }
        .logout-button:hover {
            background-color: #c82333; /* Темнее при наведении */
        }
        .user-table {
            width: 100%;
            margin-top: 20px;
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
        #addUserForm {
            display: none;
            text-align: center;
            margin-top: 20px; /* Отступ сверху */
        }
        input[type="text"], input[type="password"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 80%;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="main">
        <div class="sidebar">
            <h2>Панель меню</h2>
            <div class="menu-item" id="manageUsersButton">Состав группы</div>
        </div>
        
        <div class="content">
            <div class="header">
                <h1>Вы авторизованы как куратор группы <?= $username ?></h1>
                <button class="logout-button" onclick="window.location.href='logout.php'">Выйти</button>
            </div>
            
            <div class="user-table" id="userTable">
                <h2>Состав группы</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Имя пользователя</th>
                            <th>Фамилия</th>
                            <th>Имя</th>
                            <th>Отчество</th>
                            <th>Дата регистрации</th>
                            <th>Пароль</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php foreach ($users as $user): ?>
                        <tr id="user-<?= htmlspecialchars($user['id']) ?>">
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['family']) ?></td>
                            <td><?= htmlspecialchars($user['firstname']) ?></td>
                            <td><?= htmlspecialchars($user['lastname']) ?></td>
                            <td><?= htmlspecialchars($user['created_at']) ?></td>
                            <td><?= htmlspecialchars($user['password']) ?></td> <!-- Hide password for security -->
                            <td><button class="button delete-button" onclick="deleteUser(<?= htmlspecialchars($user['id']) ?>)">Удалить</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 20px;">
                <button class="add-user-button" onclick="showAddUserForm()">Добавить пользователя</button>
            </div>

            <div id="addUserForm">
                <h2>Добавить пользователя</h2>
                <input type="text" id="firstname" placeholder="Имя" required>
                <input type="text" id="lastname" placeholder="Отчество" required>
                <input type="text" id="family" placeholder="Фамилия" required>
                <div style="display: flex; justify-content: center; margin-top: 10px;">
                    <button class="button add-user-button" id="submitAddUserButton">Добавить</button>
                    <button class="button logout-button" onclick="hideAddUserForm()">Вернуться</button>
                </div>
                <p class="message" id="userMessage"></p>
            </div>
        </div>
    </div>

    <script>
        const addUserButton = document.getElementById('submitAddUserButton');
        const userMessage = document.getElementById('userMessage');

        function showAddUserForm() {
            document.getElementById('userTable').style.display = 'none';
            document.getElementById('addUserForm').style.display = 'block';
        }

        function hideAddUserForm() {
            document.getElementById('userTable').style.display = 'block';
            document.getElementById('addUserForm').style.display = 'none';
        }

        addUserButton.onclick = function() {
            const firstname = document.getElementById('firstname').value.trim();
            const lastname = document.getElementById('lastname').value.trim();
            const family = document.getElementById('family').value.trim();
            const username = `${<?= json_encode($_SESSION['user']); ?>}-uch${Math.floor(Math.random() * 1000)}`; // Generate username
            const password = Math.random().toString(36).slice(-8); // Generate random password
            const groupId = <?= json_encode($groupId) ?>; // Get group ID

            if (!firstname || !lastname || !family) {
                userMessage.textContent = "Ошибка: Все поля обязательны для заполнения.";
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "add_student.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                const response = JSON.parse(xhr.responseText);
                if (response.status === 'error') {
                    userMessage.textContent = response.message;
                } else {
                    userMessage.textContent = 'Пользователь добавлен! Пароль: ' + password;
                    addUserToTable(response.id, username, family, firstname, lastname, password); // Передать пароль
                    document.getElementById('firstname').value = '';
                    document.getElementById('lastname').value = '';
                    document.getElementById('family').value = '';
                }
            };
            xhr.send(`username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&firstname=${encodeURIComponent(firstname)}&lastname=${encodeURIComponent(lastname)}&family=${encodeURIComponent(family)}&groupId=${groupId}`);
        };

        function addUserToTable(id, username, family, firstname, lastname, password) {
            const newRow = document.createElement('tr');
            newRow.id = `user-${id}`;
            newRow.innerHTML = `
                <td>${username}</td>
                <td>${family}</td>
                <td>${firstname}</td>
                <td>${lastname}</td>
                <td>${new Date().toLocaleDateString()}</td>
                <td>${password}</td> <!-- Передать пароль правильно -->
                <td><button class="button delete-button" onclick="deleteUser(${id})">Удалить</button></td>
            `;
            document.getElementById('userTableBody').appendChild(newRow);
        }

        function deleteUser(userId) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "delete_student.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                const response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    document.getElementById(`user-${userId}`).remove();
                } else {
                    alert(response.message);
                }
            };
            xhr.send(`id=${userId}`);
        }
    </script>
</body>
</html>