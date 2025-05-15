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
            flex-grow: 3;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow: hidden;
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
        .welcome-message {
            text-align: center;
            margin: 20px 0;
        }
        .user-table, .grades-table, .subjects-table {
            width: 100%;
            margin-top: 20px;
            overflow-x: auto;
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
            cursor: pointer;
        }
        #addUserForm {
            display: none;
            text-align: center;
            margin-top: 20px;
        }
        input[type="text"], input[type="password"], input[type="number"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 80%;
            margin-bottom: 10px;
        }
        .add-user-button {
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .add-user-button:hover {
            background-color: #218838;
        }
        .delete-button {
            padding: 10px 15px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .delete-button:hover {
            background-color: #c82333;
        }
        .action-buttons {
            margin-top: 10px;
        }
        .action-buttons > button {
            margin-right: 10px;
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
            <div class="menu-item" id="manageUsersButton" onclick="showUserTable()">
                <img src="pic/icon_1.png" alt="Управление группами">
                <span class="menu-text">Состав группы</span>
            </div>
            <div class="menu-item" id="manageGradesButton" onclick="showGradesTable()">
                <img src="pic/icon_2.png" alt="Успеваемость">
                <span class="menu-text">Успеваемость по группе</span>
            </div>
            <div class="menu-item" id="manageSubjectsButton" onclick="showSubjectsTable()">
                <img src="pic/icon_5-1.png" alt="Управление учебными предметами">
                <span class="menu-text">Управление учебными предметами</span>
            </div>
        </div>
        
        <div class="content">
            <div class="header">
                <h1>Вы авторизованы как куратор группы <?= $username ?></h1>
                <button class="logout-button" onclick="window.location.href='logout.php'">Выйти</button>
            </div>

            <div class="welcome-message" id="welcomeMessage">
                <h2>Добро пожаловать на панель куратора!</h2>
                <p>Здесь вы можете управлять составом группы и добавлять студентов, управлять оценками и посещаемостью.</p>
            </div>

            <div class="user-table" id="userTable" style="display: none;">
                <h2>Состав группы</h2>
                <table>
                    <thead>
                        <tr>
                            <th onclick="sortTable(0)">Фамилия</th>
                            <th onclick="sortTable(1)">Имя</th>
                            <th onclick="sortTable(2)">Отчество</th>
                            <th onclick="sortTable(3)">Логин</th>
                            <th>Пароль</th>
                            <th onclick="sortTable(4)">Дата регистрации</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php foreach ($users as $user): ?>
                        <tr id="user-<?= htmlspecialchars($user['id']) ?>">
                            <td><?= htmlspecialchars($user['family']) ?></td>
                            <td><?= htmlspecialchars($user['firstname']) ?></td>
                            <td><?= htmlspecialchars($user['lastname']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['password']) ?></td>
                            <td><?= date('Y-m-d', strtotime(htmlspecialchars($user['created_at']))) ?></td>
                            <td><button class="delete-button" onclick="deleteUser(<?= htmlspecialchars($user['id']) ?>)">Удалить</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="grades-table" id="gradesTable" style="display: none;">
                <h2>Успеваемость студентов</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ФИО</th>
                            <th>Сентябрь</th>
                            <th>Октябрь</th>
                            <th>Ноябрь</th>
                            <th>Декабрь</th>
                            <th>1 Семестр</th>
                            <th>Январь</th>
                            <th>Февраль</th>
                            <th>Март</th>
                            <th>Апрель</th>
                            <th>Май</th>
                            <th>Июнь</th>
                            <th>2 Семестр</th>
                        </tr>
                    </thead>
                    <tbody id="gradesTableBody">
                        <!-- Данные будут добавлены через JavaScript -->
                    </tbody>
                </table>
            </div>

            <div class="subjects-table" id="subjectsTable" style="display: none;">
                <h2>Учебные предметы</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Название предмета</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="subjectsTableBody">
                        <!-- Subjects will be loaded here -->
                    </tbody>
                </table>
                <div id="addSubjectForm" style="margin-top: 20px;">
                    <input type="text" id="subjectName" placeholder="Название предмета" required>
                    <button class="add-user-button" id="submitAddSubjectButton">Добавить предмет</button>
                    <p class="message" id="subjectMessage"></p>
                </div>
            </div>

            <div style="margin-top: 20px; display: none;" id="addUserButtonContainer">
                <button class="add-user-button" id="addUserButton" onclick="showAddUserForm()">Добавить пользователя</button>
            </div>

            <div id="addUserForm" style="display: none;">
                <h2>Добавить пользователя</h2>
                <input type="text" id="firstname" placeholder="Имя" required>
                <input type="text" id="lastname" placeholder="Отчество" required>
                <input type="text" id="family" placeholder="Фамилия" required>
                <div class="action-buttons" style="display: flex; justify-content: center; margin-top: 10px;">
                    <button class="add-user-button" id="submitAddUserButton">Добавить</button>
                    <button class="logout-button" onclick="hideAddUserForm()">Вернуться</button>
                </div>
                <p class="message" id="userMessage"></p>
            </div>
        </div>
    </div>

    <script>
        const addUserButton = document.getElementById('submitAddUserButton');
        const userMessage = document.getElementById('userMessage');
        const submitAddSubjectButton = document.getElementById('submitAddSubjectButton');
        const subjectMessage = document.getElementById('subjectMessage');

        function showUserTable() {
            document.getElementById('welcomeMessage').style.display = 'none';
            document.getElementById('userTable').style.display = 'block';
            document.getElementById('gradesTable').style.display = 'none';
            document.getElementById('subjectsTable').style.display = 'none'; // Hide subjects table
            document.getElementById('addUserButtonContainer').style.display = 'block';
            document.getElementById('addUserForm').style.display = 'none';
        }

        function showGradesTable() {
            document.getElementById('welcomeMessage').style.display = 'none';
            document.getElementById('userTable').style.display = 'none';
            document.getElementById('gradesTable').style.display = 'block';
            document.getElementById('subjectsTable').style.display = 'none'; // Hide subjects table
            document.getElementById('addUserButtonContainer').style.display = 'none'; // Скрываем кнопку
            loadGrades();
        }

        function loadGrades() {
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "load_grades.php", true);
            xhr.onload = function() {
                const response = JSON.parse(xhr.responseText);
                const gradesTableBody = document.getElementById('gradesTableBody');
                gradesTableBody.innerHTML = ''; // Clear existing rows

                if (response.students.length === 0) {
                    gradesTableBody.innerHTML = '<tr><td colspan="14">Нет студентов для отображения.</td></tr>';
                    return;
                }

                // Сортировка студентов по алфавиту
                response.students.sort((a, b) => {
                    return `${a.family} ${a.firstname}`.localeCompare(`${b.family} ${b.firstname}`);
                });

                response.students.forEach(student => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${student.family} ${student.firstname} ${student.lastname}</td>
                        ${response.subjects.map(subject => `
                            <td>
                                <input type="number" min="1" max="10" placeholder="Оценка" data-subject-id="${subject.id}">
                            </td>
                        `).join('')}
                    `;
                    gradesTableBody.appendChild(row);
                });
            };
            xhr.send();
        }

        function showSubjectsTable() {
            document.getElementById('welcomeMessage').style.display = 'none';
            document.getElementById('userTable').style.display = 'none';
            document.getElementById('gradesTable').style.display = 'none';
            document.getElementById('subjectsTable').style.display = 'block'; // Показываем таблицу предметов
            loadSubjects(); // Загружаем данные предметов
        }

        function loadSubjects() {
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "load_subjects.php", true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        const subjectsTableBody = document.getElementById('subjectsTableBody');
                        subjectsTableBody.innerHTML = ''; // Очистка существующих строк
                        response.subjects.forEach(subject => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${subject.name}</td>
                                <td><button class="delete-button" onclick="deleteSubject(${subject.id})">Удалить</button></td>
                            `;
                            subjectsTableBody.appendChild(row);
                        });
                    } else {
                        console.error(response.message);
                    }
                } else {
                    console.error('Ошибка загрузки данных: ' + xhr.statusText);
                }
            };
            xhr.send();
        }
        submitAddSubjectButton.onclick = function() {
            const subjectName = document.getElementById('subjectName').value.trim();
            if (!subjectName) {
                subjectMessage.textContent = "Ошибка: Название предмета обязательно.";
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "add_subject.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                const response = JSON.parse(xhr.responseText);
                if (response.status === 'error') {
                    subjectMessage.textContent = response.message;
                } else {
                    subjectMessage.textContent = 'Предмет добавлен!';
                    loadSubjects(); // Перезагружаем предметы
                    document.getElementById('subjectName').value = ''; // Очищаем ввод
                }
            };
            xhr.send(`name=${encodeURIComponent(subjectName)}`);
};

        function deleteSubject(subjectId) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "delete_subject.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                const response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    loadSubjects(); // Reload subjects after deletion
                } else {
                    alert(response.message);
                }
            };
            xhr.send(`id=${subjectId}`);
        }

        function showAddUserForm() {
            document.getElementById('userTable').style.display = 'none';
            document.getElementById('gradesTable').style.display = 'none';
            document.getElementById('addUserForm').style.display = 'block';
            document.getElementById('addUserButtonContainer').style.display = 'none';
        }

        function hideAddUserForm() {
            document.getElementById('userTable').style.display = 'block';
            document.getElementById('addUserForm').style.display = 'none';
            document.getElementById('addUserButtonContainer').style.display = 'block';
        }

        addUserButton.onclick = function() {
            const firstname = document.getElementById('firstname').value.trim();
            const lastname = document.getElementById('lastname').value.trim();
            const family = document.getElementById('family').value.trim();
            const username = `${<?= json_encode($_SESSION['user']); ?>}-uch${Math.floor(Math.random() * 1000)}`;
            const password = Math.random().toString(36).slice(-8);
            const groupId = <?= json_encode($groupId) ?>;

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
                    addUserToTable(response.id, response.username, response.family, response.firstname, response.lastname, password);
                    document.getElementById('firstname').value = '';
                    document.getElementById('lastname').value = '';
                    document.getElementById('family').value = '';
                    sortTable(0);
                }
            };
            xhr.send(`username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&firstname=${encodeURIComponent(firstname)}&lastname=${encodeURIComponent(lastname)}&family=${encodeURIComponent(family)}&groupId=${groupId}`);
        };

        function addUserToTable(id, username, family, firstname, lastname, password) {
            const newRow = document.createElement('tr');
            newRow.id = `user-${id}`;
            newRow.innerHTML = `
                <td>${family}</td>
                <td>${firstname}</td>
                <td>${lastname}</td>
                <td>${username}</td>
                <td>${password}</td>
                <td>${new Date().toLocaleDateString()}</td>
                <td><button class="delete-button" onclick="deleteUser(${id})">Удалить</button></td>
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

        function sortTable(columnIndex) {
            const table = document.querySelector("table");
            const rows = Array.from(table.rows).slice(1);
            const sortedRows = rows.sort((a, b) => {
                const cellA = a.cells[columnIndex].innerText.toLowerCase();
                const cellB = b.cells[columnIndex].innerText.toLowerCase();
                return cellA.localeCompare(cellB);
            });
            sortedRows.forEach(row => table.appendChild(row));
        }

        const toggleButton = document.getElementById('toggleButton');
        const sidebar = document.getElementById('sidebar');

        toggleButton.onclick = function() {
            sidebar.classList.toggle('collapsed');
            const menuTexts = sidebar.querySelectorAll('.menu-text');
            menuTexts.forEach(text => {
                text.style.display = sidebar.classList.contains('collapsed') ? 'none' : 'inline';
            });
            const icons = sidebar.querySelectorAll('.menu-item img');
            icons.forEach(icon => {
                icon.style.transform = sidebar.classList.contains('collapsed') ? 'scale(1.5)' : 'scale(1)';
            });
        };
    </script>
</body>
</html>