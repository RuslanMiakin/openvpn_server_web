<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['auth'])) {
    header('Location: index.php');
    exit;
}

// Проверка параметра client
if (!isset($_GET['client']) || empty($_GET['client'])) {
    header('Location: index.php?error=Не указан пользователь');
    exit;
}

$client = trim($_GET['client']);

// Проверка имени клиента на допустимые символы
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $client)) {
    header('Location: index.php?error=Недопустимое имя пользователя');
    exit;
}

// Поиск .ovpn файла
$home_dirs = ['/root', '/home'];
$ovpn_file = null;

foreach ($home_dirs as $dir) {
    $path = "{$dir}/{$client}.ovpn";
    if (file_exists($path)) {
        $ovpn_file = $path;
        break;
    }
}

// Если файл не найден в корневой директории, ищем в директориях пользователей
if ($ovpn_file === null) {
    $user_dirs = glob('/home/*', GLOB_ONLYDIR);
    foreach ($user_dirs as $dir) {
        $path = "{$dir}/{$client}.ovpn";
        if (file_exists($path)) {
            $ovpn_file = $path;
            break;
        }
    }
}

// Если файл так и не найден
if ($ovpn_file === null) {
    header('Location: index.php?error=Файл конфигурации не найден');
    exit;
}

// Отправка файла пользователю
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $client . '.ovpn"');
header('Content-Length: ' . filesize($ovpn_file));
readfile($ovpn_file);
exit; 