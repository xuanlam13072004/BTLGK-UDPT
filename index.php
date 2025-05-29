<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';
use SleekDB\SleekDB;

function safe($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

$newsStore = SleekDB::store('news', __DIR__ . '/database');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
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
            $uploadPath = __DIR__ . "/avatars/" . $newFileName;
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $author_avatar = $newFileName;
            } else {
                $errors[] = "Lỗi khi upload file ảnh avatar!";
            }
        } else {
            $errors[] = "Chỉ upload file ảnh JPG, PNG, GIF!";
        }
    }

    if (!empty($errors)) {
        foreach ($errors as $err) {
            echo "<p style='color:red;'>" . safe($err) . "</p>";
        }
    } else {
        if ($id) {
            $oldItem = $newsStore->findById((int)$id);
            if ($oldItem) {
                if (!$author_avatar && !empty($oldItem['author']['avatar'])) {
                    $author_avatar = $oldItem['author']['avatar'];
                }
                $oldItem['title'] = $title;
                $oldItem['about'] = $about;
                $oldItem['author'] = [
                    'name' => $author_name,
                    'avatar' => $author_avatar
                ];
                $newsStore->update($oldItem);
            }
        } else {
            $newsStore->insert([
                'title' => $title,
                'about' => $about,
                'author' => [
                    'name' => $author_name,
                    'avatar' => $author_avatar
                ],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        header("Location: index.php");
        exit;
    }
}

if (isset($_GET['delete'])) {
    $newsStore->deleteById((int)$_GET['delete']);
    header("Location: index.php");
    exit;
}

$editItem = null;
if (isset($_GET['edit'])) {
    $editItem = $newsStore->findById((int)$_GET['edit']);
}

$keyword = trim($_GET['keyword'] ?? '');
$filterFn = $keyword !== '' ? function($news) use ($keyword) {
    return stripos($news['title'], $keyword) !== false || stripos($news['about'], $keyword) !== false;
} : null;

$perPage = 5;
$page = max(1, (int) ($_GET['page'] ?? 1));
$allNews = $filterFn ? array_filter($newsStore->fetch(), $filterFn) : $newsStore->fetch();
$total = count($allNews);
$totalPages = ceil($total / $perPage);
$allNews = array_slice($allNews, ($page - 1) * $perPage, $perPage);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Demo SleekDB News</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .news-item { border: 1px solid #ccc; padding: 15px; margin-bottom: 15px; position: relative; }
        .news-title { font-weight: bold; font-size: 1.2em; }
        .author-info { margin-top: 10px; font-size: 0.9em; color: #555; display: flex; align-items: center; }
        .author-info img { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; object-fit: cover; }
        .created-at { color: #999; font-size: 0.8em; margin-top: 5px; }
        .actions { position: absolute; top: 10px; right: 10px; }
        .actions a { margin-left: 10px; text-decoration: none; font-weight: bold; }
        .actions a.delete { color: red; }
        form { margin-bottom: 30px; }
        label { display: block; margin: 8px 0 4px; }
        input[type="text"], textarea { width: 100%; padding: 6px; box-sizing: border-box; }
        input[type="file"] { margin-top: 5px; }
        button { padding: 8px 15px; cursor: pointer; }
        .pagination { margin-top: 20px; }
        .pagination a { margin: 0 5px; text-decoration: none; }
        .pagination strong { margin: 0 5px; }
        .search-box { margin-bottom: 20px; }
    </style>
</head>
<body>

<h1>Quản lý bài viết SleekDB</h1>

<form method="GET" class="search-box">
    <input type="text" name="keyword" placeholder="Tìm kiếm theo tiêu đề hoặc mô tả" value="<?= safe($keyword) ?>" />
    <button type="submit">Tìm kiếm</button>
</form>

<form method="POST" action="index.php" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= safe($editItem['_id'] ?? '') ?>" />

    <label>Tiêu đề:</label>
    <input type="text" name="title" required value="<?= safe($editItem['title'] ?? '') ?>" />

    <label>Mô tả:</label>
    <textarea name="about" rows="4" required><?= safe($editItem['about'] ?? '') ?></textarea>

    <label>Tên tác giả:</label>
    <input type="text" name="author_name" required value="<?= safe($editItem['author']['name'] ?? '') ?>" />

    <label>Ảnh đại diện tác giả (jpg, png, gif):</label>
    <input type="file" name="author_avatar_file" accept="image/*" />
    <?php if (!empty($editItem['author']['avatar'])): ?>
        <p>Ảnh hiện tại:</p>
        <img src="avatars/<?= safe($editItem['author']['avatar']) ?>" alt="Avatar" style="width:50px; border-radius:50%;" />
    <?php endif; ?>

    <button type="submit"><?= $editItem ? 'Cập nhật' : 'Thêm mới' ?></button>
    <?php if ($editItem): ?>
        <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>">Hủy chỉnh sửa</a>
    <?php endif; ?>
</form>

<?php if (empty($allNews)): ?>
    <p>Không có bài viết nào.</p>
<?php else: ?>
    <?php foreach ($allNews as $news): 
        if (!isset($news['_id'])) continue;
        $newsId = $news['_id'];
    ?>
        <div class="news-item">
            <div class="news-title"><?= safe($news['title']) ?></div>
            <div><?= nl2br(safe($news['about'])) ?></div>
            
            <div class="author-info">
                <?php if (!empty($news['author']['avatar'])): ?>
                    <img src="avatars/<?= safe($news['author']['avatar']) ?>" alt="Avatar" />
                <?php endif; ?>
                <div><?= safe($news['author']['name']) ?></div>
            </div>
            <div class="created-at">Đăng lúc: <?= safe($news['created_at'] ?? '') ?></div>
            
            <div class="actions">
                <a href="?edit=<?= safe($newsId) ?>">Chỉnh sửa</a>
                <a class="delete" href="?delete=<?= safe($newsId) ?>" onclick="return confirm('Bạn có chắc muốn xóa bài viết này?');">Xóa</a>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword) ?>">Trang trước</a>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <?php if ($p == $page): ?>
                <strong><?= $p ?></strong>
            <?php else: ?>
                <a href="?page=<?= $p ?>&keyword=<?= urlencode($keyword) ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword) ?>">Trang sau</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

</body>
</html>
