<?php
session_start();
$lang = $_SESSION['lang'] ?? 'vi';

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function safe($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// 🎯 Từ ngữ theo ngôn ngữ
$labels = [
    'vi' => [
        'pageTitle' => '📤 Gửi bài viết mới',
        'title' => 'Tiêu đề:',
        'about' => 'Mô tả:',
        'author' => 'Tên tác giả:',
        'avatar' => 'Ảnh đại diện:',
        'submit' => 'Gửi bài',
        'back' => '⬅ Về trang chủ',
        'success' => '✅ Bài viết đã gửi thành công, chờ duyệt!',
        'err_required' => [
            'title' => 'Tiêu đề không được để trống.',
            'about' => 'Mô tả không được để trống.',
            'author' => 'Tên tác giả không được để trống.',
        ],
        'err_upload' => 'Lỗi khi upload file ảnh avatar!',
        'err_type' => 'Chỉ upload file ảnh JPG, PNG, GIF!',
    ],
    'en' => [
        'pageTitle' => '📤 Submit New Post',
        'title' => 'Title:',
        'about' => 'Description:',
        'author' => 'Author Name:',
        'avatar' => 'Avatar:',
        'submit' => 'Submit',
        'back' => '⬅ Back to homepage',
        'success' => '✅ Post submitted successfully. Waiting for approval!',
        'err_required' => [
            'title' => 'Title cannot be empty.',
            'about' => 'Description cannot be empty.',
            'author' => 'Author name cannot be empty.',
        ],
        'err_upload' => 'Error uploading avatar!',
        'err_type' => 'Only JPG, PNG, GIF images allowed!',
    ]
];
$t = $labels[$lang];

// Xử lý khi gửi bài
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $about = trim($_POST['about'] ?? '');
    $author_name = trim($_POST['author_name'] ?? '');

    $errors = [];
    if ($title === '') $errors[] = $t['err_required']['title'];
    if ($about === '') $errors[] = $t['err_required']['about'];
    if ($author_name === '') $errors[] = $t['err_required']['author'];

    $author_avatar = null;

    if (isset($_FILES['author_avatar_file']) && $_FILES['author_avatar_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['author_avatar_file'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($file['type'], $allowedTypes)) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $newFileName = uniqid('avatar_') . '.' . $ext;

            $uploadPath = __DIR__ . '/../admin/database/avatars/' . $newFileName;
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $author_avatar = $newFileName;
            } else {
                $errors[] = $t['err_upload'];
            }
        } else {
            $errors[] = $t['err_type'];
        }
    }

    if (empty($errors)) {
// Thêm vào trong if (empty($errors)) { ... }
        $data = [
            'custom_id' => 'post_' . bin2hex(random_bytes(8)), // ✅ ID riêng
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

            echo "<p style='color:green;'>{$t['success']}</p>";
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ RabbitMQ Error: " . safe($e->getMessage()) . "</p>";
        }
    } else {
        foreach ($errors as $err) {
            echo "<p style='color:red;'>" . safe($err) . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <title><?= $t['pageTitle'] ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- ✅ Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-5" style="max-width: 600px;">

  <div class="card shadow-sm">
    <div class="card-body">
      <h3 class="card-title mb-4">📝 <?= $t['pageTitle'] ?></h3>

      <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label"><?= $t['title'] ?></label>
          <input type="text" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label"><?= $t['about'] ?></label>
          <textarea name="about" class="form-control" rows="5" required></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label"><?= $t['author'] ?></label>
          <input type="text" name="author_name" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label"><?= $t['avatar'] ?></label>
          <input type="file" name="author_avatar_file" class="form-control" accept="image/*">
        </div>

        <button type="submit" class="btn btn-primary">📤 <?= $t['submit'] ?></button>
      </form>

      <p class="mt-3">
        <a href="home.php" class="btn btn-link">← <?= $t['back'] ?></a>
      </p>
    </div>
  </div>

</div>
</body>
</html>
