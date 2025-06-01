<?php
session_start();
$lang = $_SESSION['lang'] ?? 'vi';

set_time_limit(0);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
use SleekDB\SleekDB;

function safe($str) {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

$titleLabel     = $lang === 'en' ? "📚 Approved Posts" : "📚 Bài viết đã duyệt";
$submitLabel    = $lang === 'en' ? "📤 Submit New Post" : "📤 Gửi bài viết mới";
$translateLabel = $lang === 'en' ? "Translate to Vietnamese" : "Dịch sang Tiếng Anh";
$nextLang       = $lang === 'en' ? 'vi' : 'en';
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <title>Trang chủ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .avatar {
      width: 48px;
      height: 48px;
      object-fit: cover;
      border-radius: 50%;
    }
    .post-card {
      margin-bottom: 20px;
    }
  </style>
</head>
<body class="bg-light">
<div class="container my-4">
  <h2 class="mb-4">
    📄 <?= $titleLabel ?>
  </h2>

  <form method="POST" action="translate_request.php" class="mb-3">
    <button name="lang" value="<?= $nextLang ?>" class="btn btn-outline-primary btn-sm">
      🌐 <?= $translateLabel ?>
    </button>
  </form>

  <?php
  $translatedFile = __DIR__ . "/database/translated_$lang.json";

  if (file_exists($translatedFile)) {
    $translated = json_decode(file_get_contents($translatedFile), true);
    if (!empty($translated)) {
      foreach ($translated as $post) {
        ?>
        <div class="card shadow-sm post-card">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <?php if (!empty($post['author']['avatar'])): ?>
                <img src="database/avatars/<?= safe($post['author']['avatar']) ?>" alt="Avatar" class="avatar me-2">
              <?php endif; ?>
              <strong><?= safe($post['author']['name']) ?></strong>
            </div>
            <h5 class="card-title mb-1"><?= safe($post['title']) ?></h5>
            <p class="card-text"><?= nl2br(safe($post['about'])) ?></p>
            <p class="text-muted small mb-0">🕒 <?= safe($post['created_at']) ?></p>
          </div>
        </div>
        <?php
      }
    } else {
      echo "<p><i>⏳ Đang xử lý bản dịch...</i></p>";
    }
  } else {
    // Nếu chưa có bản dịch, hiển thị gốc
    $store = SleekDB::store('news', __DIR__ . '/database', ['timeout' => false]);
    $posts = $store->where('status', '=', 'approved')->fetch();
    usort($posts, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

    if (empty($posts)) {
      echo "<p class='text-muted'>⚠️ " . ($lang === 'en' ? "No approved posts yet." : "Chưa có bài viết nào được duyệt.") . "</p>";
    } else {
      foreach ($posts as $post) {
        ?>
        <div class="card shadow-sm post-card">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <?php if (!empty($post['author']['avatar'])): ?>
                <img src="database/avatars/<?= safe($post['author']['avatar']) ?>" alt="Avatar" class="avatar me-2">
              <?php endif; ?>
              <strong><?= safe($post['author']['name']) ?></strong>
            </div>
            <h5 class="card-title mb-1"><?= safe($post['title']) ?></h5>
            <p class="card-text"><?= nl2br(safe($post['about'])) ?></p>
            <p class="text-muted small mb-0">🕒 <?= safe($post['created_at']) ?></p>
          </div>
        </div>
        <?php
      }
    }
  }
  ?>

  <p class="mt-4">
    <a href="submit.php" class="btn btn-sm btn-link">✍️ <?= $submitLabel ?></a>
  </p>
</div>
</body>
</html>
