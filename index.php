<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
require_once 'functions.php';

// Обработка действий
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_client':
            if (!empty($_POST['client_name'])) {
                $client_name = trim($_POST['client_name']);
                $pass_option = isset($_POST['use_password']) ? '2' : '1';
                add_client($client_name, $pass_option);
                header('Location: index.php?success=Пользователь ' . $client_name . ' добавлен');
                exit;
            }
            break;
        case 'revoke_client':
            if (!empty($_POST['client_id'])) {
                revoke_client($_POST['client_id']);
                header('Location: index.php?success=Пользователь удален');
                exit;
            }
            break;
    }
}

// Получить список клиентов
$clients = get_clients();

// Получить статус активных подключений
$connections = get_active_connections();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenVPN Управление</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>OpenVPN Панель управления</h1>
        
        <div class="navigation">
            <a href="stats.php" class="button">Детальная статистика</a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Активные подключения</h2>
            <table>
                <thead>
                    <tr>
                        <th>Пользователь</th>
                        <th>IP-адрес</th>
                        <th>Подключен с</th>
                        <th>Время подключения</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($connections)): ?>
                        <tr>
                            <td colspan="4" class="center">Нет активных подключений</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($connections as $conn): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($conn['common_name']); ?></td>
                                <td><?php echo htmlspecialchars($conn['real_address']); ?></td>
                                <td><?php echo htmlspecialchars($conn['virtual_address']); ?></td>
                                <td><?php echo htmlspecialchars($conn['connected_since']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>Управление пользователями</h2>
            
            <h3>Добавить нового пользователя</h3>
            <form method="post" action="">
                <input type="hidden" name="action" value="add_client">
                <div class="form-group">
                    <label for="client_name">Имя пользователя:</label>
                    <input type="text" id="client_name" name="client_name" required pattern="[a-zA-Z0-9_-]+" title="Только буквы, цифры, подчеркивания и дефисы">
                </div>
                <div class="form-group">
                    <label for="use_password">
                        <input type="checkbox" id="use_password" name="use_password" value="1">
                        Защитить конфигурацию паролем
                    </label>
                </div>
                <div class="form-group">
                    <button type="submit">Добавить</button>
                </div>
            </form>
            
            <h3>Существующие пользователи</h3>
            <table>
                <thead>
                    <tr>
                        <th>Имя</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="3" class="center">Нет пользователей</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clients as $id => $client): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['name']); ?></td>
                                <td><?php echo htmlspecialchars($client['created']); ?></td>
                                <td>
                                    <a href="download.php?client=<?php echo htmlspecialchars($client['name']); ?>" class="button small">Скачать .ovpn</a>
                                    <form method="post" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="revoke_client">
                                        <input type="hidden" name="client_id" value="<?php echo $id; ?>">
                                        <button type="submit" class="button small danger" onclick="return confirm('Вы действительно хотите удалить этого пользователя?')">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 