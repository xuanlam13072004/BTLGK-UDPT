<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function safe($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// Xử lý khi gửi bài
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $about = trim($_POST['about'] ?? '');
    $author_name = trim($_POST['author_name'] ?? '');

    $errors = [];
    if ($title === '') $errors[] = "Tiêu đề không được để trống.";
    if ($about === '') $errors[] = "Mô tả không được để trống.";
    if ($author_name === '') $errors[] = "Tên tác giả không được để trống.";

    $author_avatar = null;

    if (isset($_FILES['author_avatar_file']) && $_FILES['author_avatar_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['author_avatar_file'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($file['type'], $allowedTypes)) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $newFileName = uniqid('avatar_') . '.' . $ext;

            // ✅ Lưu ảnh vào thư mục admin/database/avatars/
            $uploadPath = __DIR__ . '/../admin/database/avatars/' . $newFileName;
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $author_avatar = $newFileName;
            } else {
                $errors[] = "Lỗi khi upload file ảnh avatar!";
            }
        } else {
            $errors[] = "Chỉ upload file ảnh JPG, PNG, GIF!";
        }
    }

    if (empty($errors)) {
        $data = [
            'title' => $title,
            'about' => $about,
            'author' => [
                'name' => $author_name,
                'avatar' => $author_avatar
            ],
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'pending'
        ];

        try {
            $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
            $channel = $connection->channel();
            $channel->queue_declare('posts_queue', false, true, false, false);

            $msg = new AMQPMessage(json_encode($data), ['delivery_mode' => 2]);
            $channel->basic_publish($msg, '', 'posts_queue');

            $channel->close();
            $connection->close();

            echo "<p style='color:green;'>✅ Bài viết đã gửi thành công, chờ duyệt!</p>";
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Lỗi gửi RabbitMQ: " . safe($e->getMessage()) . "</p>";
        }
    } else {
        foreach ($errors as $err) {
            echo "<p style='color:red;'>" . safe($err) . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Gửi bài viết</title>
  <style>
    body { font-family: Arial; max-width: 600px; margin: 20px auto; }
    input[type="text"], textarea { width: 100%; padding: 6px; margin-bottom: 10px; }
    input[type="file"] { margin-bottom: 10px; }
    button { padding: 8px 15px; }
  </style>
</head>
<body>

<h2>📤 Gửi bài viết mới</h2>

<form method="POST" enctype="multipart/form-data">
  <label>Tiêu đề:</label>
  <input type="text" name="title" required>

  <label>Mô tả:</label>
  <textarea name="about" rows="5" required></textarea>

  <label>Tên tác giả:</label>
  <input type="text" name="author_name" required>

  <label>Ảnh đại diện:</label>
  <input type="file" name="author_avatar_file" accept="image/*">

  <button type="submit">Gửi bài</button>
</form>

<p><a href="home.php">⬅ Về trang chủ</a></p>

</body>
</html>
