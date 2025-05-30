<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
use SleekDB\SleekDB;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function safe($str) {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

$adminStore = SleekDB::store('news', __DIR__ . '/database');
$userStore  = SleekDB::store('news', __DIR__ . '/../user/database');

// ❌ Xoá bài ở user DB
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $post = $userStore->findById((int)$_GET['delete_user']);
    if ($post && !empty($post['author']['avatar'])) {
        $avatarFile = $post['author']['avatar'];
        $avatarPath = __DIR__ . '/../user/database/avatars/' . $avatarFile;

        $others = $userStore->findBy(["_id", "!=", $post['_id']]);
        $usedElsewhere = array_filter($others, fn($p) => $p['author']['avatar'] === $avatarFile);

        if (file_exists($avatarPath) && empty($usedElsewhere)) {
            unlink($avatarPath);
        }
    }
    $userStore->deleteById((int)$_GET['delete_user']);
    header("Location: admin.php");
    exit;
}

// ❌ Xoá bài ở admin DB
if (isset($_GET['delete_admin']) && is_numeric($_GET['delete_admin'])) {
    $post = $adminStore->findById((int)$_GET['delete_admin']);
    if ($post && !empty($post['author']['avatar'])) {
        $avatarFile = $post['author']['avatar'];
        $avatarPath = __DIR__ . '/database/avatars/' . $avatarFile;

        $others = $adminStore->findBy(["_id", "!=", $post['_id']]);
        $usedElsewhere = array_filter($others, fn($p) => $p['author']['avatar'] === $avatarFile);

        if (file_exists($avatarPath) && empty($usedElsewhere)) {
            unlink($avatarPath);
        }
    }
    $adminStore->deleteById((int)$_GET['delete_admin']);
    header("Location: admin.php");
    exit;
}

// ✅ Gửi bài được duyệt vào hàng đợi approve_queue
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $item = $adminStore->findById((int)$_GET['approve']);
    if ($item) {
        try {
            $conn = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
            $channel = $conn->channel();
            $channel->queue_declare('approve_queue', false, true, false, false);

            $msg = new AMQPMessage(json_encode($item), ['delivery_mode' => 2]);
            $channel->basic_publish($msg, '', 'approve_queue');

            $channel->close();
            $conn->close();
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Lỗi gửi vào approve_queue: " . safe($e->getMessage()) . "</p>";
        }
    }
    header("Location: admin.php");
    exit;
}

// ✏️ Sửa bài đã duyệt (user DB)
$editItem = null;
if (isset($_GET['edit_user']) && is_numeric($_GET['edit_user'])) {
    $editItem = $userStore->findById((int)$_GET['edit_user']);
}

// ✅ Cập nhật bài viết đã duyệt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $about = trim($_POST['about'] ?? '');
    $author_name = trim($_POST['author_name'] ?? '');
    $author_avatar = $_POST['existing_avatar'] ?? null;

    if ($id && $title && $about && $author_name) {
        $item = $userStore->findById((int)$id);
        if ($item) {
            $item['title'] = $title;
            $item['about'] = $about;
            $item['author']['name'] = $author_name;
            $item['author']['avatar'] = $author_avatar;
            $userStore->update($item);
        }
    }
    header("Location: admin.php");
    exit;
}

// 📋 Danh sách bài viết
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
  <style>
    body { font-family: Arial; margin: 20px auto; max-width: 1000px; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background: #f0f0f0; }
    .pending { background: #fff3cd; }
    .approved { background: #d4edda; }
    .avatar { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; }
    form { margin-top: 30px; }
    input[type="text"], textarea { width: 100%; padding: 6px; margin-bottom: 10px; }
  </style>
</head>
<body>

<h2>📥 Bài viết chờ duyệt</h2>
<?php if (empty($pendingPosts)): ?>
  <p>⚠️ Không có bài viết nào đang chờ.</p>
<?php else: ?>
<table>
  <thead>
    <tr>
      <th>Tiêu đề</th>
      <th>Tác giả</th>
      <th>Thời gian</th>
      <th>Hành động</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($pendingPosts as $item): ?>
      <tr class="pending">
        <td><?= safe($item['title']) ?></td>
        <td>
          <?php if (!empty($item['author']['avatar'])): ?>
            <img class="avatar" src="/database/avatars/<?= safe($item['author']['avatar']) ?>" alt="">
          <?php endif; ?>
          <?= safe($item['author']['name']) ?>
        </td>
        <td><?= safe($item['created_at']) ?></td>
        <td>
          <a href="?approve=<?= $item['_id'] ?>">✅ Duyệt</a> |
          <a href="?delete_admin=<?= $item['_id'] ?>" onclick="return confirm('Xoá bài này?')">🗑️ Xoá</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<h2>✅ Bài viết đã duyệt</h2>
<?php if (empty($approvedPosts)): ?>
  <p>⚠️ Chưa có bài viết nào được duyệt.</p>
<?php else: ?>
<table>
  <thead>
    <tr>
      <th>Tiêu đề</th>
      <th>Tác giả</th>
      <th>Thời gian</th>
      <th>Hành động</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($approvedPosts as $item): ?>
      <tr class="approved">
        <td><?= safe($item['title']) ?></td>
        <td>
          <?php if (!empty($item['author']['avatar'])): ?>
            <img class="avatar" src="/../user/database/avatars/<?= safe($item['author']['avatar']) ?>" alt="">
          <?php endif; ?>
          <?= safe($item['author']['name']) ?>
        </td>
        <td><?= safe($item['created_at']) ?></td>
        <td>
          <a href="?edit_user=<?= $item['_id'] ?>">✏️ Sửa</a> |
          <a href="?delete_user=<?= $item['_id'] ?>" onclick="return confirm('Xoá bài viết đã duyệt này?')">🗑️ Xoá</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php if ($editItem): ?>
  <h3>✏️ Sửa bài viết đã duyệt</h3>
  <form method="POST">
    <input type="hidden" name="id" value="<?= safe($editItem['_id']) ?>">
    <input type="hidden" name="existing_avatar" value="<?= safe($editItem['author']['avatar'] ?? '') ?>">

    <label>Tiêu đề:</label>
    <input type="text" name="title" value="<?= safe($editItem['title']) ?>" required>

    <label>Mô tả:</label>
    <textarea name="about" rows="4" required><?= safe($editItem['about']) ?></textarea>

    <label>Tên tác giả:</label>
    <input type="text" name="author_name" value="<?= safe($editItem['author']['name']) ?>" required>

    <button type="submit">Lưu thay đổi</button>
    <a href="admin.php">Huỷ</a>
  </form>
<?php endif; ?>

</body>
</html>
