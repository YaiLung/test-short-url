<?php
// Функция для генерации уникального идентификатора
function generateShortCode($length = 5) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

// Путь к файлу данных
$dataFile = 'urls.json';

// Обработка AJAX-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents($dataFile), true) ?? [];
    
    if (isset($_POST['original_url'])) {
        $originalUrl = $_POST['original_url'];
        
        // Проверка, есть ли URL уже в базе
        foreach ($data as $shortCode => $info) {
            if ($info['url'] === $originalUrl) {
                echo json_encode(['short_url' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/' . $shortCode]);
                exit;
            }
        }

        // Генерация нового кода и сохранение
        do {
            $shortCode = generateShortCode();
        } while (isset($data[$shortCode]));

        $data[$shortCode] = ['url' => $originalUrl, 'clicks' => 0];
        file_put_contents($dataFile, json_encode($data));
        echo json_encode(['short_url' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/' . $shortCode]);
        exit;
    }
}

// Перенаправление по короткому URL
if (isset($_SERVER['PATH_INFO'])) {
    $shortCode = ltrim($_SERVER['PATH_INFO'], '/');
    $data = json_decode(file_get_contents($dataFile), true) ?? [];

    if (isset($data[$shortCode])) {
        $data[$shortCode]['clicks']++;
        file_put_contents($dataFile, json_encode($data));
        header('Location: ' . $data[$shortCode]['url']);
        exit;
    } else {
        http_response_code(404);
        echo "Короткая ссылка не найдена.";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сократитель ссылок</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .container {
            margin-top: 50px;
        }
        .short-url {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center">Сократитель ссылок</h1>
    <form id="url-form">
        <div class="mb-3">
            <label for="original-url" class="form-label">Введите оригинальный URL:</label>
            <input type="url" class="form-control" id="original-url" placeholder="http://example.com" required>
        </div>
        <button type="submit" class="btn btn-primary">Сократить</button>
    </form>
    <div class="short-url">
        <p id="short-url-display" class="text-success"></p>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#url-form').submit(function(event) {
            event.preventDefault();
            const originalUrl = $('#original-url').val();
            $.post('', { original_url: originalUrl }, function(response) {
                const data = JSON.parse(response);
                if (data.short_url) {
                    $('#short-url-display').html(`Короткий URL: <a href="${data.short_url}" target="_blank">${data.short_url}</a>`);
                } else {
                    $('#short-url-display').text('Ошибка при создании короткой ссылки.');
                }
            });
        });
    });
</script>
</body>
</html>
