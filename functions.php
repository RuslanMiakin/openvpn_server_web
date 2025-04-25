<?php
/**
 * Функции для работы с OpenVPN
 */

/**
 * Добавить нового клиента
 * 
 * @param string $client_name Имя клиента
 * @param string $pass_option Опция пароля (1 - без пароля, 2 - с паролем)
 * @return bool Успешно ли добавлен клиент
 */
function add_client($client_name, $pass_option = '1') {
    // Проверка имени клиента на допустимые символы
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $client_name)) {
        return false;
    }
    
    // Путь к скрипту OpenVPN
    $script_path = '/path/to/openvpn-install.sh'; // Измените на фактический путь
    
    // Формирование команды
    $command = "sudo MENU_OPTION=1 CLIENT=\"{$client_name}\" PASS={$pass_option} {$script_path}";
    
    // Выполнение команды
    exec($command, $output, $return_var);
    
    return $return_var === 0;
}

/**
 * Отозвать (удалить) клиента
 * 
 * @param int $client_index Индекс клиента в списке
 * @return bool Успешно ли удален клиент
 */
function revoke_client($client_index) {
    // Индекс должен быть числом
    if (!is_numeric($client_index)) {
        return false;
    }
    
    // Путь к скрипту OpenVPN
    $script_path = '/path/to/openvpn-install.sh'; // Измените на фактический путь
    
    // Формирование команды для удаления клиента
    // Note: для автоматического выбора клиента нужно создать временный скрипт
    $temp_script = tempnam(sys_get_temp_dir(), 'ovpn_');
    file_put_contents($temp_script, "#!/bin/bash\necho {$client_index}\n");
    chmod($temp_script, 0755);
    
    $command = "sudo MENU_OPTION=2 {$script_path} < {$temp_script}";
    
    // Выполнение команды
    exec($command, $output, $return_var);
    
    // Удаление временного файла
    unlink($temp_script);
    
    return $return_var === 0;
}

/**
 * Получить список клиентов
 * 
 * @return array Список клиентов с их данными
 */
function get_clients() {
    $clients = [];
    
    // Путь к директории с сертификатами
    $pki_index = '/etc/openvpn/easy-rsa/pki/index.txt';
    
    // Если файл не существует, вернуть пустой массив
    if (!file_exists($pki_index)) {
        return $clients;
    }
    
    // Чтение файла index.txt, содержащего информацию о сертификатах
    $index_content = file_get_contents($pki_index);
    $lines = explode("\n", $index_content);
    
    $client_id = 1;
    foreach ($lines as $line) {
        // Пропустить пустые строки и отозванные сертификаты
        if (empty($line) || strpos($line, 'V') !== 0) {
            continue;
        }
        
        // Парсинг строки
        $parts = explode("\t", $line);
        if (count($parts) < 5) {
            continue;
        }
        
        $date = strtotime(trim($parts[1]));
        $cn_part = explode('=', $parts[4]);
        $client_name = end($cn_part);
        
        // Добавление клиента в список
        $clients[$client_id] = [
            'name' => $client_name,
            'created' => date('Y-m-d H:i:s', $date)
        ];
        
        $client_id++;
    }
    
    return $clients;
}

/**
 * Получить список активных подключений
 * 
 * @return array Список активных подключений
 */
function get_active_connections() {
    $connections = [];
    
    // Путь к файлу статуса OpenVPN
    $status_log = '/var/log/openvpn/status.log';
    
    // Если файл не существует, вернуть пустой массив
    if (!file_exists($status_log)) {
        return $connections;
    }
    
    // Чтение файла status.log
    $status_content = file_get_contents($status_log);
    $lines = explode("\n", $status_content);
    
    $client_section = false;
    $route_section = false;
    $clients = [];
    $routes = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Определение секций
        if ($line === 'ROUTING TABLE') {
            $client_section = false;
            $route_section = true;
            continue;
        } elseif ($line === 'CLIENT LIST') {
            $client_section = true;
            $route_section = false;
            continue;
        } elseif ($line === 'GLOBAL STATS') {
            $client_section = false;
            $route_section = false;
            continue;
        }
        
        // Пропуск заголовков и пустых строк
        if (empty($line) || strpos($line, 'Common Name') === 0 || strpos($line, 'Virtual Address') === 0) {
            continue;
        }
        
        // Парсинг информации о клиентах
        if ($client_section) {
            $parts = preg_split('/,/', $line);
            if (count($parts) < 4) {
                continue;
            }
            
            $common_name = trim($parts[0]);
            if ($common_name === 'UNDEF') {
                continue;
            }
            
            $real_address = explode(':', trim($parts[1]))[0];
            $bytes_received = trim($parts[2]);
            $bytes_sent = trim($parts[3]);
            $connected_since = trim($parts[4]);
            
            $clients[$common_name] = [
                'common_name' => $common_name,
                'real_address' => $real_address,
                'bytes_received' => $bytes_received,
                'bytes_sent' => $bytes_sent,
                'connected_since' => $connected_since,
                'virtual_address' => ''
            ];
        }
        
        // Парсинг маршрутов для определения виртуальных адресов
        if ($route_section) {
            $parts = preg_split('/,/', $line);
            if (count($parts) < 3) {
                continue;
            }
            
            $virtual_address = trim($parts[0]);
            $common_name = trim($parts[1]);
            $real_address = explode(':', trim($parts[2]))[0];
            
            if (isset($clients[$common_name])) {
                $clients[$common_name]['virtual_address'] = $virtual_address;
            }
        }
    }
    
    return array_values($clients);
} 