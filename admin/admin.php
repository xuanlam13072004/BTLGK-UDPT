<?php
session_start();
$lang = $_SESSION['lang'] ?? 'vi';

set_time_limit(0);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
use SleekDB\SleekDB;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Escape HTML special characters
 */
function safe($str) {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Khi bài được sửa hoặc xóa, gửi lại request để làm mới bản dịch
 */
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
        echo "<p style='color:red;'>❌ Lỗi gửi dịch lại: " . safe($e->getMessage()) . "</p>";
    }
}

/**
 * Khởi tạo SleekDB stores:
 * - $adminStore: nơi lưu bài chờ duyệt
 * - $userStore: nơi lưu bài đã duyệt
 */
$adminStore = SleekDB::store('news', __DIR__ . '/database', ['timeout' => false]);
$userStore  = SleekDB::store('news', __DIR__ . '/../user/database', ['timeout' => false]);

/**
 * Danh sách category (giữ keys giống như submit.php)
 */
$categories = [
    'all'             => $lang === 'en' ? 'All Categories'   : 'Tất cả chủ đề',
    'cuoc_song'       => $lang === 'en' ? 'Life'            : 'Cuộc sống',
    'gia_dinh'        => $lang === 'en' ? 'Family'          : 'Gia đình',
    'hoc_tap'         => $lang === 'en' ? 'Study'           : 'Học tập',
    'truyen_cam_hung' => $lang === 'en' ? 'Inspiration'     : 'Truyền cảm hứng'
];

/**
 * Lấy filter từ query string:
 * - q: từ khóa tìm kiếm theo tiêu đề
 * - cat: category lọc (mặc định = 'all')
 */
$keyword = trim($_GET['q'] ?? '');
$selectedCategory = $_GET['cat'] ?? 'all';
if (!array_key_exists($selectedCategory, $categories)) {
    $selectedCategory = 'all';
}

/**
 * Xóa bài đã duyệt đang nằm trong userStore
 * Sử dụng custom_id làm index, sau khi tìm được document,
 * thì xoá bằng _id thực.
 */
if (isset($_GET['delete_user'])) {
    $post = $userStore->findOneBy(["custom_id", "=", $_GET['delete_user']]);
    if ($post && !empty($post['author']['avatar'])) {
        $avatarPath = __DIR__ . '/../user/database/avatars/' . $post['author']['avatar'];
        $others = $userStore->findBy(["custom_id", "!=", $_GET['delete_user']]);
        $used = array_filter($others, fn($p) => $p['author']['avatar'] === $post['author']['avatar']);
        if (file_exists($avatarPath) && empty($used)) {
            unlink($avatarPath);
        }
    }
    if ($post) {
        $userStore->deleteById($post['_id']);
    }
    triggerReTranslate();
    header("Location: admin.php?cat=" . urlencode($selectedCategory) . "&q=" . urlencode($keyword));
    exit;
}

/**
 * Xóa bài chờ duyệt nằm trong adminStore
 */
if (isset($_GET['delete_admin'])) {
    $post = $adminStore->findOneBy(["custom_id", "=", $_GET['delete_admin']]);
    if ($post && !empty($post['author']['avatar'])) {
        $avatarPath = __DIR__ . '/database/avatars/' . $post['author']['avatar'];
        $others = $adminStore->findBy(["custom_id", "!=", $_GET['delete_admin']]);
        $used = array_filter($others, fn($p) => $p['author']['avatar'] === $post['author']['avatar']);
        if (file_exists($avatarPath) && empty($used)) {
            unlink($avatarPath);
        }
    }
    if ($post) {
        $adminStore->deleteById($post['_id']);
    }
    header("Location: admin.php?cat=" . urlencode($selectedCategory) . "&q=" . urlencode($keyword));
    exit;
}

/**
 * Duyệt bài (đẩy vào RabbitMQ queue "approve_queue")
 * Chúng ta gửi toàn bộ document (bao gồm cả custom_id)
 */
if (isset($_GET['approve'])) {
    $item = $adminStore->findOneBy(["custom_id", "=", $_GET['approve']]);
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
    header("Location: admin.php?cat=" . urlencode($selectedCategory) . "&q=" . urlencode($keyword));
    exit;
}

/**
 * Nếu user click “Sửa” trên bài viết đã duyệt, sẽ mở form điền thông tin
 */
$editItem = null;
if (isset($_GET['edit_user'])) {
    $editItem = $userStore->findOneBy(["custom_id", "=", $_GET['edit_user']]);
}

/**
 * Xử lý POST khi user lưu chỉnh sửa (sửa bài đã duyệt)
 * Chúng ta chỉ gửi payload (không tự update trực tiếp), để edit_worker.php xử lý
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customId     = $_POST['custom_id']     ?? '';
    $newTitle     = trim($_POST['title']     ?? '');
    $newAbout     = trim($_POST['about']     ?? '');
    $newCategory  = trim($_POST['category']  ?? '');
    $newAuthor    = trim($_POST['author_name'] ?? '');
    $newAvatar    = $_POST['existing_avatar'] ?? ''; // nếu người dùng không đổi avatar

    if ($customId && $newTitle && $newAbout && $newCategory && $newAuthor) {
        $payload = [
            'custom_id'    => $customId,
            'title'        => $newTitle,
            'about'        => $newAbout,
            'category'     => $newCategory,
            'author_name'  => $newAuthor,
            'author_avatar'=> $newAvatar,
        ];
        try {
            $conn = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
            $channel = $conn->channel();
            $channel->queue_declare('edit_queue', false, true, false, false);
            $msg = new AMQPMessage(json_encode($payload), ['delivery_mode' => 2]);
            $channel->basic_publish($msg, '', 'edit_queue');
            $channel->close();
            $conn->close();
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Lỗi gửi vào edit_queue: " . safe($e->getMessage()) . "</p>";
        }
        triggerReTranslate();
    }
    header("Location: admin.php?cat=" . urlencode($selectedCategory) . "&q=" . urlencode($keyword));
    exit;
}


/**
 * PHẦN HIỂN THỊ (HTML)
 *  - Chúng ta sẽ hiển thị:
 *    + Một form tìm kiếm + chọn category chung
 *    + Bảng “Bài viết chờ duyệt”
 *    + Bảng “Bài viết đã duyệt”
 *    + Nếu $editItem != null => hiển thị form chỉnh sửa
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Admin - Quản lý bài viết</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
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

  <!-- Form chung: chọn category + tìm kiếm theo tiêu đề -->
  <form method="GET" class="row g-2 mb-3">
    <div class="col-auto">
      <select name="cat" class="form-select form-select-sm">
        <?php foreach ($categories as $key => $label): ?>
          <option value="<?= $key ?>" <?= $key === $selectedCategory ? 'selected' : '' ?>>
            <?= safe($label) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <input
        type="text"
        name="q"
        class="form-control form-control-sm"
        style="width: 250px;"
        placeholder="<?= $lang === 'en' ? 'Search by title...' : 'Tìm theo tiêu đề...' ?>"
        value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
      />
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-sm btn-primary">
        <?= $lang === 'en' ? 'Search' : 'Tìm' ?>
      </button>
    </div>
  </form>

  <!-- Tiêu đề -->
  <h2 class="mb-3">📥 Bài viết chờ duyệt</h2>

  <?php
  /**
   * Lấy danh sách “pending” rồi lọc theo category + title
   */
  $allPending = $adminStore->fetch();
  $pendingPosts = [];

  foreach ($allPending as $item) {
    // Lọc theo category (nếu không phải 'all')
    if ($selectedCategory !== 'all' && ($item['category'] ?? '') !== $selectedCategory) {
      continue;
    }
    // Lọc theo từ khóa (tiêu đề)
    if ($keyword !== '' && mb_stripos($item['title'], $keyword) === false) {
      continue;
    }
    $pendingPosts[] = $item;
  }
  usort($pendingPosts, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
  ?>

  <?php if (empty($pendingPosts)): ?>
    <p class="text-muted">⚠️ <?= $lang === 'en' ? 'No pending posts.' : 'Không có bài viết nào đang chờ.' ?></p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-warning">
          <tr>
            <th>Tiêu đề</th>
            <th>Chủ đề</th>
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
                <?php
                  $lab = '';
                  switch ($item['category'] ?? '') {
                    case 'cuoc_song':       $lab = 'Cuộc sống';       break;
                    case 'gia_dinh':        $lab = 'Gia đình';        break;
                    case 'hoc_tap':         $lab = 'Học tập';         break;
                    case 'truyen_cam_hung': $lab = 'Truyền cảm hứng'; break;
                    default:                $lab = '';
                  }
                  echo safe($lab);
                ?>
              </td>
              <td>
                <?php if (!empty($item['author']['avatar'])): ?>
                  <img class="avatar" src="/database/avatars/<?= safe($item['author']['avatar']) ?>" alt="">
                <?php endif; ?>
                <?= safe($item['author']['name']) ?>
              </td>
              <td><?= safe($item['created_at']) ?></td>
              <td>
                <a href="?approve=<?= urlencode($item['custom_id']) ?>&cat=<?= urlencode($selectedCategory) ?>&q=<?= urlencode($keyword) ?>" class="btn btn-success btn-sm">✔️ Duyệt</a>
                <a href="?delete_admin=<?= urlencode($item['custom_id']) ?>&cat=<?= urlencode($selectedCategory) ?>&q=<?= urlencode($keyword) ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('<?= $lang === 'en' ? 'Delete this post?' : 'Xóa bài này?' ?>')">🗑️ Xóa</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>


  <h2 class="mt-5 mb-3">✅ Bài viết đã duyệt</h2>

  <?php
  /**
   * Lấy danh sách “approved” rồi lọc tương tự
   */
  $allApproved = $userStore->where('status', '=', 'approved')->fetch();
  $approvedPosts = [];

  foreach ($allApproved as $item) {
    // Lọc category
    if ($selectedCategory !== 'all' && ($item['category'] ?? '') !== $selectedCategory) {
      continue;
    }
    // Lọc theo từ khóa trên tiêu đề
    if ($keyword !== '' && mb_stripos($item['title'], $keyword) === false) {
      continue;
    }
    $approvedPosts[] = $item;
  }
  usort($approvedPosts, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
  ?>

  <?php if (empty($approvedPosts)): ?>
    <p class="text-muted">⚠️ <?= $lang === 'en' ? 'No approved posts.' : 'Chưa có bài viết nào được duyệt.' ?></p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-success">
          <tr>
            <th>Tiêu đề</th>
            <th>Chủ đề</th>
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
                  $lab = '';
                  switch ($item['category'] ?? '') {
                    case 'cuoc_song':       $lab = 'Cuộc sống';       break;
                    case 'gia_dinh':        $lab = 'Gia đình';        break;
                    case 'hoc_tap':         $lab = 'Học tập';         break;
                    case 'truyen_cam_hung': $lab = 'Truyền cảm hứng'; break;
                    default:                $lab = '';
                  }
                  echo safe($lab);
                ?>
              </td>
              <td>
                <?php
                  // Hiển thị avatar đã base64 để tránh gọi trực tiếp file
                  $avatarPath = realpath(__DIR__ . '/../user/database/avatars/' . ($item['author']['avatar'] ?? ''));
                  if ($avatarPath && file_exists($avatarPath)) {
                    $mime = mime_content_type($avatarPath);
                    $base64 = base64_encode(file_get_contents($avatarPath));
                    echo "<img src=\"data:$mime;base64,$base64\" class=\"avatar\" style=\"width:40px; height:40px; object-fit:cover; border-radius:50%; margin-right:8px;\">";
                  }
                ?>
                <?= safe($item['author']['name']) ?>
              </td>
              <td><?= safe($item['created_at']) ?></td>
              <td>
                <a href="?edit_user=<?= urlencode($item['custom_id']) ?>&cat=<?= urlencode($selectedCategory) ?>&q=<?= urlencode($keyword) ?>" class="btn btn-warning btn-sm">✏️ Sửa</a>
                <a href="?delete_user=<?= urlencode($item['custom_id']) ?>&cat=<?= urlencode($selectedCategory) ?>&q=<?= urlencode($keyword) ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('<?= $lang === 'en' ? 'Delete this post?' : 'Xóa bài viết đã duyệt này?' ?>')">🗑️ Xóa</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>


  <?php if ($editItem): ?>
    <!-- Form chỉnh sửa bài đã duyệt -->
    <div class="card mt-5 shadow-sm">
      <div class="card-body">
        <h3 class="card-title">✏️ <?= $lang === 'en' ? 'Edit Approved Post' : 'Sửa bài viết đã duyệt' ?></h3>
        <form method="POST" class="mt-3">
          <input type="hidden" name="custom_id" value="<?= safe($editItem['custom_id']) ?>">
          <input type="hidden" name="existing_avatar" value="<?= safe($editItem['author']['avatar'] ?? '') ?>">

          <div class="mb-3">
            <label class="form-label"><?= $lang === 'en' ? 'Title:' : 'Tiêu đề:' ?></label>
            <input
              type="text"
              name="title"
              class="form-control"
              value="<?= safe($editItem['title']) ?>"
              required
            >
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $lang === 'en' ? 'Description:' : 'Mô tả:' ?></label>
            <textarea name="about" rows="4" class="form-control" required><?= safe($editItem['about']) ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $lang === 'en' ? 'Category:' : 'Chủ đề:' ?></label>
            <select name="category" class="form-select" required>
              <?php foreach ($categories as $key => $label):
                if ($key === 'all') continue;
              ?>
                <option value="<?= $key ?>" <?= ($editItem['category'] ?? '') === $key ? 'selected' : '' ?>>
                  <?= safe($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label"><?= $lang === 'en' ? 'Author Name:' : 'Tên tác giả:' ?></label>
            <input
              type="text"
              name="author_name"
              class="form-control"
              value="<?= safe($editItem['author']['name']) ?>"
              required
            >
          </div>

          <button type="submit" class="btn btn-primary">💾 <?= $lang === 'en' ? 'Save Changes' : 'Lưu thay đổi' ?></button>
          <a href="admin.php?cat=<?= urlencode($selectedCategory) ?>&q=<?= urlencode($keyword) ?>" class="btn btn-link"><?= $lang === 'en' ? 'Cancel' : 'Huỷ' ?></a>
        </form>
      </div>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
