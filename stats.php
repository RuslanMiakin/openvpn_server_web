<?php
session_start();
require_once 'functions.php';

// Проверка авторизации
if (!isset($_SESSION['auth'])) {
    header('Location: index.php');
    exit;
}

// Получение данных о нагрузке на сервер
function get_server_load() {
    $load = sys_getloadavg();
    return [
        '1m' => $load[0],
        '5m' => $load[1],
        '15m' => $load[2]
    ];
}

// Получение статуса OpenVPN сервера
function get_openvpn_status() {
    $output = [];
    $return_var = 0;
    exec('systemctl status openvpn@server', $output, $return_var);
    
    return [
        'active' => ($return_var === 0),
        'status' => implode("\n", $output)
    ];
}

// Получение статистики использования трафика
function get_traffic_stats() {
    $connections = get_active_connections();
    $total_received = 0;
    $total_sent = 0;
    
    foreach ($connections as $conn) {
        $total_received += isset($conn['bytes_received']) ? intval($conn['bytes_received']) : 0;
        $total_sent += isset($conn['bytes_sent']) ? intval($conn['bytes_sent']) : 0;
    }
    
    return [
        'received' => format_bytes($total_received),
        'sent' => format_bytes($total_sent),
        'total' => format_bytes($total_received + $total_sent)
    ];
}

// Форматирование байтов в человекочитаемый формат
function format_bytes($bytes, $precision = 2) {
    $units = ['Б', 'КБ', 'МБ', 'ГБ', 'ТБ'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Получение данных
$server_load = get_server_load();
$openvpn_status = get_openvpn_status();
$traffic_stats = get_traffic_stats();
$connections = get_active_connections();
$active_users_count = count($connections);
$all_users = get_clients();
$all_users_count = count($all_users);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenVPN - Статистика</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>OpenVPN Статистика</h1>
        
        <div class="navigation">
            <a href="index.php">← Вернуться на главную</a>
        </div>
        
        <div class="section">
            <h2>Общая информация</h2>
            
            <div class="stats-grid">
                <div class="stats-item">
                    <h3>Статус сервера</h3>
                    <p class="stats-value <?php echo $openvpn_status['active'] ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo $openvpn_status['active'] ? 'Активен' : 'Неактивен'; ?>
                    </p>
                </div>
                
                <div class="stats-item">
                    <h3>Пользователи</h3>
                    <p class="stats-value"><?php echo $active_users_count; ?> / <?php echo $all_users_count; ?></p>
                    <p class="stats-subtitle">активно / всего</p>
                </div>
                
                <div class="stats-item">
                    <h3>Нагрузка на сервер</h3>
                    <p class="stats-value"><?php echo $server_load['1m']; ?>, <?php echo $server_load['5m']; ?>, <?php echo $server_load['15m']; ?></p>
                    <p class="stats-subtitle">за 1, 5 и 15 минут</p>
                </div>
                
                <div class="stats-item">
                    <h3>Всего трафика</h3>
                    <p class="stats-value"><?php echo $traffic_stats['total']; ?></p>
                    <p class="stats-subtitle">
                        Получено: <?php echo $traffic_stats['received']; ?><br>
                        Отправлено: <?php echo $traffic_stats['sent']; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>Активные подключения</h2>
            
            <?php if (empty($connections)): ?>
                <p>Нет активных подключений</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Пользователь</th>
                            <th>IP-адрес</th>
                            <th>VPN IP</th>
                            <th>Получено</th>
                            <th>Отправлено</th>
                            <th>Подключен с</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($connections as $conn): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($conn['common_name']); ?></td>
                                <td><?php echo htmlspecialchars($conn['real_address']); ?></td>
                                <td><?php echo htmlspecialchars($conn['virtual_address']); ?></td>
                                <td><?php echo isset($conn['bytes_received']) ? format_bytes(intval($conn['bytes_received'])) : '-'; ?></td>
                                <td><?php echo isset($conn['bytes_sent']) ? format_bytes(intval($conn['bytes_sent'])) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($conn['connected_since']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 