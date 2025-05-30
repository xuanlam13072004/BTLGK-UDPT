<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
use SleekDB\SleekDB;

function safe($str) {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

// ✅ Đọc bài viết từ database của user
$store = SleekDB::store('news', __DIR__ . '/database');
$posts = $store->where('status', '=', 'approved')->fetch();
usort($posts, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Trang chủ</title>
  <style>
    body { font-family: Arial; margin: 20px auto; max-width: 800px; }
    .post { border: 1px solid #ccc; margin-bottom: 15px; padding: 10px; border-radius: 6px; background: #f9f9f9; }
    .avatar { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; vertical-align: middle; }
    .header { display: flex; align-items: center; gap: 10px; }
    .title { font-weight: bold; font-size: 18px; margin-top: 10px; }
    .about { margin-top: 5px; }
    .date { font-size: 12px; color: gray; }
  </style>
</head>
<body>

<h2>📚 Bài viết đã duyệt</h2>

<?php if (empty($posts)): ?>
  <p>⚠️ Chưa có bài viết nào được duyệt.</p>
<?php else: ?>
  <?php foreach ($posts as $post): ?>
    <div class="post">
      <div class="header">
        <?php if (!empty($post['author']['avatar'])): ?>
          <img class="avatar" src="database/avatars/<?= safe($post['author']['avatar']) ?>" alt="Avatar">
        <?php endif; ?>
        <strong><?= safe($post['author']['name']) ?></strong>
      </div>
      <div class="title"><?= safe($post['title']) ?></div>
      <div class="about"><?= nl2br(safe($post['about'])) ?></div>
      <div class="date">🕒 <?= safe($post['created_at']) ?></div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<p><a href="submit.php">📤 Gửi bài viết mới</a></p>

</body>
</html>
