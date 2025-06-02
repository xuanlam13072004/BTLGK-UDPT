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

// Danh sách categories (giữ nguyên key giống submit.php)
$categories = [
    'all'            => $lang === 'en' ? 'All Categories'   : 'Tất cả chủ đề',
    'cuoc_song'      => $lang === 'en' ? 'Life'            : 'Cuộc sống',
    'gia_dinh'       => $lang === 'en' ? 'Family'          : 'Gia đình',
    'hoc_tap'        => $lang === 'en' ? 'Study'           : 'Học tập',
    'truyen_cam_hung'=> $lang === 'en' ? 'Inspiration'     : 'Truyền cảm hứng'
];

// Lấy filter từ GET: cat = chủ đề, q = chuỗi tìm tiêu đề
$selectedCategory = $_GET['cat'] ?? 'all';
$keyword          = trim($_GET['q'] ?? '');

if (!array_key_exists($selectedCategory, $categories)) {
    $selectedCategory = 'all';
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <title>Blog Timeline</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    body {
      background-color: #f9fafb;
      font-family: 'Segoe UI', sans-serif;
      padding-top: 72px;
    }
    .navbar {
      background-color: #ffffff;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .navbar-brand {
      color: #2563eb !important;
      font-weight: bold;
      font-size: 1.4rem;
    }
    .category-filter {
      text-align: center;
      margin: 20px 0 10px 0;
    }
    .category-filter .btn {
      margin: 4px;
      border-radius: 20px;
    }
    .search-box {
      max-width: 500px;
      margin: 10px auto 20px auto;
      display: flex;
      align-items: center;
      padding: 8px 12px;
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .search-box input {
      border: none;
      outline: none;
      width: 100%;
      padding: 6px;
      font-size: 1rem;
    }
    .post-item {
      background-color: #fff;
      border-radius: 14px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.06);
      padding: 20px;
      margin-bottom: 24px;
    }
    .post-item img.cover {
      max-width: 100%;
      height: auto;
      border-radius: 12px;
      margin-bottom: 10px;
    }
    .post-title {
      font-size: 1.2rem;
      font-weight: bold;
      color: #2563eb;
      margin-bottom: 8px;
    }
    .author-avatar {
      width: 42px;
      height: 42px;
      object-fit: cover;
      border-radius: 50%;
      margin-right: 8px;
    }
    .post-meta {
      font-size: 0.9rem;
      color: #6b7280;
    }
    .footer {
      background: #1e293b;
      color: #e5e7eb;
      padding: 20px 0;
      text-align: center;
      margin-top: 60px;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar fixed-top navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="#"><i class="fa-solid fa-seedling"></i> Blog Timeline</a>
    <div class="d-flex align-items-center ms-auto">
      <form method="POST" action="translate_request.php" class="d-inline-block mb-0 me-3">
        <button name="lang" value="<?= $nextLang ?>" class="btn btn-link nav-link d-inline-block p-0" style="color:#2563eb;">
          <i class="fa-solid fa-globe"></i> <?= strtoupper($nextLang) ?>
        </button>
      </form>
      <a class="btn btn-sm btn-outline-primary" href="submit.php"><i class="fa-solid fa-pen-to-square"></i> <?= $submitLabel ?></a>
    </div>
  </div>
</nav>

<!-- Filter + Search -->
<div class="container">
  <div class="category-filter">
    <?php foreach ($categories as $key => $label): ?>
      <a href="?cat=<?= urlencode($key) ?>" class="btn <?= $key === $selectedCategory ? 'btn-primary' : 'btn-outline-primary' ?>">
        <?= safe($label) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <form method="GET" class="search-box">
    <input type="hidden" name="cat" value="<?= htmlspecialchars($selectedCategory) ?>">
    <i class="fa fa-search text-muted me-2"></i>
    <input type="text" name="q" placeholder="<?= $lang==='en'?'Search by title...':'Tìm theo tiêu đề...' ?>" value="<?= htmlspecialchars($keyword) ?>">
  </form>
</div>

<!-- Posts -->
<div class="container">
  <?php
    // Logic lấy bài viết
    $translatedFile = __DIR__ . "/database/translated_$lang.json";
    $postsToShow = [];

    function getCatLabel($cat, $lang) {
      switch ($cat) {
        case 'cuoc_song': return $lang==='en'?'Life':'Cuộc sống';
        case 'gia_dinh': return $lang==='en'?'Family':'Gia đình';
        case 'hoc_tap': return $lang==='en'?'Study':'Học tập';
        case 'truyen_cam_hung': return $lang==='en'?'Inspiration':'Truyền cảm hứng';
        default: return '';
      }
    }

    function postMatchesFilter2($post, $selCat, $kw) {
      if ($selCat !== 'all' && (!isset($post['category']) || $post['category'] !== $selCat)) return false;
      if ($kw !== '' && mb_stripos($post['title'], $kw) === false) return false;
      return true;
    }

    if (file_exists($translatedFile)) {
      $translated = json_decode(file_get_contents($translatedFile), true);
      if (!empty($translated)) {
        foreach ($translated as $post) {
          if (postMatchesFilter2($post, $selectedCategory, $keyword)) {
            $postsToShow[] = $post;
          }
        }
      }
    } else {
      $store = SleekDB::store('news', __DIR__ . '/database', ['timeout' => false]);
      $posts = $selectedCategory === 'all'
        ? $store->where('status', '=', 'approved')->fetch()
        : $store->where('status', '=', 'approved')->where('category', '=', $selectedCategory)->fetch();
      usort($posts, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
      if ($keyword !== '') $posts = array_filter($posts, fn($p) => mb_stripos($p['title'], $keyword) !== false);
      foreach ($posts as $post) $postsToShow[] = $post;
    }
  ?>

  <?php if (empty($postsToShow)): ?>
    <div class="text-center text-muted my-5">
      🥺 <?= $lang==='en' ? 'No posts found.' : 'Không tìm thấy bài viết nào.' ?>
    </div>
  <?php else: ?>
    <?php foreach ($postsToShow as $post): ?>
      <div class="post-item">
        <?php if (!empty($post['author']['avatar'])): ?>
          <div class="d-flex align-items-center mb-2">
            <img src="database/avatars/<?= safe($post['author']['avatar']) ?>" alt="avatar" class="author-avatar">
            <strong><?= safe($post['author']['name']) ?></strong>
          </div>
        <?php else: ?>
          <p><strong><?= safe($post['author']['name']) ?></strong></p>
        <?php endif; ?>

        <?php if (!empty($post['cover'])): ?>
          <img src="database/covers/<?= safe($post['cover']) ?>" class="cover" alt="cover">
        <?php endif; ?>

        <div class="post-title"><?= safe($post['title']) ?></div>
        <div><?= nl2br(safe($post['about'])) ?></div>
        <div class="post-meta mt-2">
          <?= safe($post['created_at']) ?> — <em><?= getCatLabel($post['category'] ?? '', $lang) ?></em>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Footer -->
<footer class="footer">
  <div class="container">
    <small>&copy; 2025 Blog Timeline | <?= $lang==='en'?'All rights reserved':'Mọi quyền được bảo lưu' ?></small>
  </div>
</footer>

</body>
</html>
