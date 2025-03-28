<?php
require_once 'auth.php';

if (!isAuthenticated()) {
    header('Location: login.php');
    exit();
}

$jsonFile = 'roadmap-data.json';
$data = json_decode(file_get_contents($jsonFile), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $index = $_POST['index'];
                $data['roadmap'][$index] = [
                    'id' => $_POST['id'],
                    'okr' => $_POST['okr'],
                    'kr' => $_POST['kr'],
                    'name' => $_POST['name'],
                    'start' => $_POST['start'],
                    'end' => $_POST['end'],
                    'owner' => $_POST['owner'],
                    'metrics' => $_POST['metrics'],
                    'status' => $_POST['status']
                ];
                break;
            case 'add':
                $data['roadmap'][] = [
                    'id' => count($data['roadmap']) + 1,
                    'okr' => $_POST['okr'],
                    'kr' => $_POST['kr'],
                    'name' => $_POST['name'],
                    'start' => $_POST['start'],
                    'end' => $_POST['end'],
                    'owner' => $_POST['owner'],
                    'metrics' => $_POST['metrics'],
                    'status' => $_POST['status']
                ];
                break;
            case 'delete':
                $index = $_POST['index'];
                array_splice($data['roadmap'], $index, 1);
                break;
        }
        file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
        header('Location: admin.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление Roadmap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Управление Roadmap</h1>
            <div>
                <a href="roadmap2.html" class="btn btn-secondary me-2">Просмотр Roadmap</a>
                <a href="?logout=1" class="btn btn-danger">Выйти</a>
            </div>
        </div>
        
        <div id="roadmapTable"></div>
    </div>

    <script>
        const data = <?php echo json_encode($data['roadmap']); ?>;
        
        const container = document.getElementById('roadmapTable');
        const hot = new Handsontable(container, {
            data: data,
            rowHeaders: true,
            colHeaders: ['ID', 'OKR', 'KR', 'Название', 'Начало', 'Конец', 'Исполнитель', 'Метрики', 'Статус'],
            columns: [
                { data: 'id', readOnly: true },
                { data: 'okr' },
                { data: 'kr' },
                { data: 'name' },
                { data: 'start', type: 'date' },
                { data: 'end', type: 'date' },
                { data: 'owner' },
                { data: 'metrics' },
                { 
                    data: 'status',
                    type: 'dropdown',
                    source: ['not-started', 'in-progress', 'completed']
                }
            ],
            licenseKey: 'non-commercial-and-evaluation',
            afterChange: function(changes) {
                if (changes) {
                    const row = changes[0][0];
                    const data = this.getSourceDataAtRow(row);
                    
                    fetch('admin.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            'action': 'update',
                            'index': row,
                            'id': data.id,
                            'okr': data.okr,
                            'kr': data.kr,
                            'name': data.name,
                            'start': data.start,
                            'end': data.end,
                            'owner': data.owner,
                            'metrics': data.metrics,
                            'status': data.status
                        })
                    });
                }
            }
        });

        // Add new row button
        const addButton = document.createElement('button');
        addButton.className = 'btn btn-primary mt-3';
        addButton.textContent = 'Добавить новую строку';
        addButton.onclick = function() {
            hot.alter('insert_row');
        };
        container.parentNode.insertBefore(addButton, container.nextSibling);
    </script>
</body>
</html> 