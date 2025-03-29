<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Получаем данные из POST-запроса
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// Путь к файлу данных
$file = __DIR__ . '/roadmap-data.json';

// Проверяем права доступа к файлу
if (!is_writable($file) && !is_writable(dirname($file))) {
    http_response_code(500);
    echo json_encode(['error' => 'File is not writable']);
    exit;
}

// Создаем резервную копию перед сохранением
if (file_exists($file)) {
    $backup = $file . '.bak';
    if (!copy($file, $backup)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create backup']);
        exit;
    }
}

// Сохраняем данные
if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT))) {
    // Устанавливаем правильные права доступа
    chmod($file, 0644);
    echo json_encode(['success' => true]);
} else {
    // В случае ошибки восстанавливаем из резервной копии
    if (file_exists($backup)) {
        copy($backup, $file);
    }
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save data']);
}
?> 