<?php
set_time_limit(0);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
use SleekDB\SleekDB;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function safe($str) {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function triggerReTranslate() {
    try {
        $conn = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $conn->channel();
        $channel->queue_declare('translate_page_queue', false, true, false, false);
        foreach (['vi', 'en'] as $lang) {
            $msg = new AMQPMessage(json_encode(['lang' => $lang]), ['delivery_mode' => 2]);
            $channel->basic_publish($msg, '', 'translate_page_queue');
        }
        $channel->close();
        $conn->close();
    } catch (Exception $e) {
        echo "<p style='color:red;'>\u274c Lỗi gửi dịch lại: " . safe($e->getMessage()) . "</p>";
    }
}

$adminStore = SleekDB::store('news', __DIR__ . '/database', ['timeout' => false]);
$userStore  = SleekDB::store('news', __DIR__ . '/../user/database', ['timeout' => false]);

// Xóa bài user
if (isset($_GET['delete_user'])) {
    $post = $userStore->findOneBy(["custom_id", "=", $_GET['delete_user']]);
    if ($post && !empty($post['author']['avatar'])) {
        $avatarPath = __DIR__ . '/../user/database/avatars/' . $post['author']['avatar'];
        $others = $userStore->findBy(["custom_id", "!=", $_GET['delete_user']]);
        $used = array_filter($others, fn($p) => $p['author']['avatar'] === $post['author']['avatar']);
        if (file_exists($avatarPath) && empty($used)) unlink($avatarPath);
    }
    if ($post) $userStore->deleteById($post['_id']);
    triggerReTranslate();
    header("Location: admin.php"); exit;
}

// Xóa bài admin
if (isset($_GET['delete_admin'])) {
    $post = $adminStore->findOneBy(["custom_id", "=", $_GET['delete_admin']]);
    if ($post && !empty($post['author']['avatar'])) {
        $avatarPath = __DIR__ . '/database/avatars/' . $post['author']['avatar'];
        $others = $adminStore->findBy(["custom_id", "!=", $_GET['delete_admin']]);
        $used = array_filter($others, fn($p) => $p['author']['avatar'] === $post['author']['avatar']);
        if (file_exists($avatarPath) && empty($used)) unlink($avatarPath);
    }
    if ($post) $adminStore->deleteById($post['_id']);
    header("Location: admin.php"); exit;
}

// Duyệt bài
if (isset($_GET['approve'])) {
    $item = $adminStore->findOneBy(["custom_id", "=", $_GET['approve']]);
    if ($item) {
        // Không tạo custom_id mới ở đây, vì đã có sẵn từ submit.php
        $conn = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $conn->channel();
        $channel->queue_declare('approve_queue', false, true, false, false);
        $channel->basic_publish(new AMQPMessage(json_encode($item), ['delivery_mode' => 2]), '', 'approve_queue');
        $channel->close();
        $conn->close();
    }
    header("Location: admin.php"); exit;
}


$editItem = null;
if (isset($_GET['edit_user'])) {
    $editItem = $userStore->findOneBy(["custom_id", "=", $_GET['edit_user']]);
}

// Gửi sửa bài
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['custom_id'] ?? '';
    $payload = [
        'custom_id' => $id,
        'title' => $_POST['title'] ?? '',
        'about' => $_POST['about'] ?? '',
        'author_name' => $_POST['author_name'] ?? '',
        'author_avatar' => $_POST['existing_avatar'] ?? ''
    ];
    $conn = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
    $channel = $conn->channel();
    $channel->queue_declare('edit_queue', false, true, false, false);
    $channel->basic_publish(new AMQPMessage(json_encode($payload), ['delivery_mode' => 2]), '', 'edit_queue');
    $channel->close();
    $conn->close();
    triggerReTranslate();
    header("Location: admin.php"); exit;
}

$pendingPosts = $adminStore->fetch();
$approvedPosts = $userStore->findBy(["status", "=", "approved"]);
usort($pendingPosts, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
usort($approvedPosts, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Admin - Quản lý bài viết</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- ✅ Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    .avatar {
      width: 40px;
      height: 40px;
      object-fit: cover;
      border-radius: 50%;
      margin-right: 8px;
    }
  </style>
</head>
<body class="bg-light">

<div class="container my-5">

  <h2 class="mb-3">📥 Bài viết chờ duyệt</h2>

  <?php if (empty($pendingPosts)): ?>
    <p class="text-muted">⚠️ Không có bài viết nào đang chờ.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-warning">
          <tr>
            <th>Tiêu đề</th>
            <th>Tác giả</th>
            <th>Thời gian</th>
            <th>Hành động</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendingPosts as $item): ?>
            <tr>
              <td><?= safe($item['title']) ?></td>
              <td>
                <?php if (!empty($item['author']['avatar'])): ?>
                  <img class="avatar" src="/database/avatars/<?= safe($item['author']['avatar']) ?>" alt="">
                <?php endif; ?>
                <?= safe($item['author']['name']) ?>
              </td>
              <td><?= safe($item['created_at']) ?></td>
              <td>
                <a href="?approve=<?= $item['custom_id'] ?>" class="btn btn-success btn-sm">✔️ Duyệt</a>
                <a href="?delete_admin=<?= $item['custom_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Xoá bài này?')">🗑️ Xoá</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <h2 class="mt-5 mb-3">✅ Bài viết đã duyệt</h2>

  <?php if (empty($approvedPosts)): ?>
    <p class="text-muted">⚠️ Chưa có bài viết nào được duyệt.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-success">
          <tr>
            <th>Tiêu đề</th>
            <th>Tác giả</th>
            <th>Thời gian</th>
            <th>Hành động</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($approvedPosts as $item): ?>
            <tr>
              <td><?= safe($item['title']) ?></td>
              <td>
                <?php
                $avatarPath = realpath(__DIR__ . '/../user/database/avatars/' . ($item['author']['avatar'] ?? ''));
                $avatarData = '';
                if ($avatarPath && file_exists($avatarPath)) {
                    $mime = mime_content_type($avatarPath);
                    $base64 = base64_encode(file_get_contents($avatarPath));
                    $avatarData = "data:$mime;base64,$base64";
                }
                ?>
                <?php if ($avatarData): ?>
                  <img class="avatar" src="<?= $avatarData ?>" alt="">
                <?php endif; ?>
                <?= safe($item['author']['name']) ?>
              </td>
              <td><?= safe($item['created_at']) ?></td>
              <td>
                <a href="?edit_user=<?= $item['custom_id'] ?>" class="btn btn-warning btn-sm">✏️ Sửa</a>
                <a href="?delete_user=<?= $item['custom_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Xoá bài viết đã duyệt này?')">🗑️ Xoá</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php if ($editItem): ?>
    <div class="card mt-5 shadow-sm">
      <div class="card-body">
        <h3 class="card-title">✏️ Sửa bài viết đã duyệt</h3>
        <form method="POST">
          <input type="hidden" name="custom_id" value="<?= safe($editItem['custom_id']) ?>">

          <input type="hidden" name="existing_avatar" value="<?= safe($editItem['author']['avatar'] ?? '') ?>">

          <div class="mb-3">
            <label class="form-label">Tiêu đề:</label>
            <input type="text" name="title" class="form-control" value="<?= safe($editItem['title']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Mô tả:</label>
            <textarea name="about" rows="4" class="form-control" required><?= safe($editItem['about']) ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Tên tác giả:</label>
            <input type="text" name="author_name" class="form-control" value="<?= safe($editItem['author']['name']) ?>" required>
          </div>

          <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
          <a href="admin.php" class="btn btn-link">Huỷ</a>
        </form>
      </div>
    </div>
  <?php endif; ?>

</div>

</body>
</html>

