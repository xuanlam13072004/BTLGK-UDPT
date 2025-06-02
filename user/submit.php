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
        'category' => 'Chủ đề:',
        'submit' => 'Gửi bài',
        'back' => '⬅ Về trang chủ',
        'success' => '✅ Bài viết đã gửi thành công, chờ duyệt!',
        'err_required' => [
            'title' => 'Tiêu đề không được để trống.',
            'about' => 'Mô tả không được để trống.',
            'author' => 'Tên tác giả không được để trống.',
            'category' => 'Bạn chưa chọn chủ đề.'
        ],
        'err_upload' => 'Lỗi khi upload file ảnh avatar!',
        'err_type' => 'Chỉ upload file ảnh JPG, PNG, GIF!'
    ],
    'en' => [
        'pageTitle' => '📤 Submit New Post',
        'title' => 'Title:',
        'about' => 'Description:',
        'author' => 'Author Name:',
        'avatar' => 'Avatar:',
        'category' => 'Category:',
        'submit' => 'Submit',
        'back' => '⬅ Back to homepage',
        'success' => '✅ Post submitted successfully. Waiting for approval!',
        'err_required' => [
            'title' => 'Title cannot be empty.',
            'about' => 'Description cannot be empty.',
            'author' => 'Author name cannot be empty.',
            'category' => 'Please choose a category.'
        ],
        'err_upload' => 'Error uploading avatar!',
        'err_type' => 'Only JPG, PNG, GIF images allowed!'
    ]
];
$t = $labels[$lang];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $about = trim($_POST['about'] ?? '');
    $author_name = trim($_POST['author_name'] ?? '');
    $category = trim($_POST['category'] ?? '');

    $errors = [];
    if ($title === '') $errors[] = $t['err_required']['title'];
    if ($about === '') $errors[] = $t['err_required']['about'];
    if ($author_name === '') $errors[] = $t['err_required']['author'];
    if ($category === '') $errors[] = $t['err_required']['category'];

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
        $data = [
            'custom_id' => 'post_' . bin2hex(random_bytes(8)),
            'title' => $title,
            'about' => $about,
            'category' => $category,
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    body {
      background-color: #f8fafc;
      font-family: 'Segoe UI', sans-serif;
    }
    .submit-header {
      text-align: center;
      padding: 40px 10px 10px;
    }
    .submit-header h2 {
      font-weight: 700;
      color: #2563eb;
    }
    .submit-header p {
      color: #475569;
      font-size: 1.1rem;
    }
    .form-section, .preview-section {
      background: #fff;
      border-radius: 16px;
      padding: 30px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }
    .form-label i {
      color: #2563eb;
      margin-right: 6px;
    }
    .preview-card {
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      padding: 20px;
      background: #f9fafb;
    }
    .preview-card h5 {
      color: #1e293b;
      font-weight: 600;
    }
    .preview-avatar {
      width: 48px;
      height: 48px;
      object-fit: cover;
      border-radius: 50%;
      margin-right: 10px;
    }
    .avatar-preview-img {
      display: none;
      margin-top: 10px;
      max-height: 100px;
      border-radius: 8px;
    }
    @media (max-width: 768px) {
      .form-preview-wrapper {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>

<div class="container">
  <div class="submit-header">
    <h2>📝 <?= $t['pageTitle'] ?></h2>
    <p><?= $lang === 'en' ? 'Share your voice with the world!' : 'Chia sẻ câu chuyện của bạn với mọi người!' ?></p>
  </div>

  <div class="row form-preview-wrapper d-flex gap-4 justify-content-center">
    <div class="col-lg-6 form-section">
      <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label"><i class="fa-solid fa-heading"></i> <?= $t['title'] ?></label>
          <input type="text" name="title" class="form-control" required oninput="updatePreview()" id="inputTitle">
        </div>

        <div class="mb-3">
          <label class="form-label"><i class="fa-solid fa-align-left"></i> <?= $t['about'] ?></label>
          <textarea name="about" class="form-control" rows="5" required oninput="updatePreview()" id="inputAbout"></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label"><i class="fa-solid fa-user"></i> <?= $t['author'] ?></label>
          <input type="text" name="author_name" class="form-control" required oninput="updatePreview()" id="inputAuthor">
        </div>

        <div class="mb-3">
          <label class="form-label"><i class="fa-solid fa-tags"></i> <?= $t['category'] ?></label>
          <select name="category" class="form-select" required onchange="updatePreview()" id="inputCategory">
            <option value="">-- Chọn chủ đề --</option>
            <option value="cuoc_song">Cuộc sống</option>
            <option value="gia_dinh">Gia đình</option>
            <option value="hoc_tap">Học tập</option>
            <option value="truyen_cam_hung">Truyền cảm hứng</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label"><i class="fa-solid fa-image"></i> <?= $t['avatar'] ?></label>
          <input type="file" name="author_avatar_file" class="form-control" accept="image/*" onchange="previewAvatar(this)">
          <img id="avatarPreviewImg" class="avatar-preview-img" alt="Avatar Preview">
        </div>

        <button type="submit" class="btn btn-primary w-100">📤 <?= $t['submit'] ?></button>
        <div class="text-center mt-3">
          <a href="home.php" class="btn btn-link">← <?= $t['back'] ?></a>
        </div>
      </form>
    </div>

    <div class="col-lg-5 preview-section">
      <h5 class="mb-3"><i class="fa-solid fa-eye"></i> Xem trước</h5>
      <div class="preview-card">
        <div class="d-flex align-items-center mb-2">
          <img id="previewAvatar" class="preview-avatar" src="" style="display:none">
          <strong id="previewAuthor">Tên tác giả</strong>
        </div>
        <h5 id="previewTitle">Tiêu đề bài viết</h5>
        <div class="text-muted" style="font-size: 0.9rem;">
          <em id="previewCategory">Chủ đề</em>
        </div>
        <p class="mt-2" id="previewAbout">Nội dung mô tả sẽ hiển thị tại đây.</p>
      </div>
    </div>
  </div>
</div>

<script>
  function updatePreview() {
    document.getElementById('previewTitle').innerText = document.getElementById('inputTitle').value || 'Tiêu đề bài viết';
    document.getElementById('previewAuthor').innerText = document.getElementById('inputAuthor').value || 'Tên tác giả';
    document.getElementById('previewAbout').innerText = document.getElementById('inputAbout').value || 'Nội dung mô tả sẽ hiển thị tại đây.';

    const cat = document.getElementById('inputCategory').value;
    let catLabel = '';
    switch (cat) {
      case 'cuoc_song': catLabel = 'Cuộc sống'; break;
      case 'gia_dinh': catLabel = 'Gia đình'; break;
      case 'hoc_tap': catLabel = 'Học tập'; break;
      case 'truyen_cam_hung': catLabel = 'Truyền cảm hứng'; break;
    }
    document.getElementById('previewCategory').innerText = catLabel;
  }

  function previewAvatar(input) {
    const img = document.getElementById('avatarPreviewImg');
    const preview = document.getElementById('previewAvatar');
    if (input.files && input.files[0]) {
      const url = URL.createObjectURL(input.files[0]);
      img.src = url;
      img.style.display = 'block';
      preview.src = url;
      preview.style.display = 'block';
    }
  }
</script>

</body>
</html>
