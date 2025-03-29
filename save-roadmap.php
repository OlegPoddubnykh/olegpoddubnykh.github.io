<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Логирование ошибок в файл
function logError($message) {
    $logFile = __DIR__ . '/save-error.log';
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed', 'method' => $_SERVER['REQUEST_METHOD']]);
    logError("Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    exit;
}

// Получаем данные из POST-запроса
$rawInput = file_get_contents('php://input');
if (!$rawInput) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty request body']);
    logError("Empty request body");
    exit;
}

$data = json_decode($rawInput, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data', 'raw' => substr($rawInput, 0, 100)]);
    logError("Invalid JSON data: " . substr($rawInput, 0, 100));
    exit;
}

// Путь к файлу данных
$file = __DIR__ . '/roadmap-data.json';

// Проверяем существование файла
if (!file_exists($file)) {
    // Если файл не существует, создаем его
    if (!touch($file)) {
        http_response_code(500);
        echo json_encode(['error' => 'Cannot create file', 'file' => $file]);
        logError("Cannot create file: $file");
        exit;
    }
    chmod($file, 0644);
}

// Проверяем права доступа к файлу
if (!is_writable($file)) {
    http_response_code(500);
    $filePerms = substr(sprintf('%o', fileperms($file)), -4);
    $fileOwner = posix_getpwuid(fileowner($file))['name'];
    $fileGroup = posix_getgrgid(filegroup($file))['name'];
    
    echo json_encode([
        'error' => 'File is not writable', 
        'file' => $file,
        'perms' => $filePerms,
        'owner' => $fileOwner,
        'group' => $fileGroup,
        'phpuser' => posix_getpwuid(posix_geteuid())['name']
    ]);
    logError("File is not writable: $file (perms: $filePerms, owner: $fileOwner, group: $fileGroup)");
    exit;
}

// Создаем резервную копию перед сохранением
if (file_exists($file) && filesize($file) > 0) {
    $backup = $file . '.bak';
    if (!copy($file, $backup)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create backup', 'file' => $backup]);
        logError("Failed to create backup: $backup");
        exit;
    }
}

// Проверяем корректность входных данных
if (!isset($data['roadmap']) || !is_array($data['roadmap'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data structure, roadmap array is required']);
    logError("Invalid data structure: roadmap array is missing");
    exit;
}

// Сохраняем данные
$encodedData = json_encode($data, JSON_PRETTY_PRINT);
if ($encodedData === false) {
    http_response_code(500);
    echo json_encode(['error' => 'JSON encoding failed', 'json_error' => json_last_error_msg()]);
    logError("JSON encoding failed: " . json_last_error_msg());
    exit;
}

if (file_put_contents($file, $encodedData) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save data to file', 'file' => $file]);
    logError("Failed to save data to file: $file");
    
    // В случае ошибки восстанавливаем из резервной копии
    if (file_exists($backup)) {
        copy($backup, $file);
    }
    exit;
}

// Устанавливаем правильные права доступа
chmod($file, 0644);
echo json_encode(['success' => true, 'file' => $file]);
?> 