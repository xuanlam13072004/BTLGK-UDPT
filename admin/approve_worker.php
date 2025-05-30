<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use SleekDB\SleekDB;

// Kết nối DB
$adminStore = SleekDB::store('news', __DIR__ . '/database');
$userStore  = SleekDB::store('news', __DIR__ . '/../user/database');

// Tạo thư mục avatars bên user nếu chưa có
$userAvatarDir = __DIR__ . '/../user/database/avatars';
if (!is_dir($userAvatarDir)) mkdir($userAvatarDir, 0755, true);

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('approve_queue', false, true, false, false);

echo "🟢 Approve Worker đang chạy...\n";

$callback = function ($msg) use ($adminStore, $userStore, $userAvatarDir) {
    $item = json_decode($msg->body, true);
    if (!$item || !isset($item['_id'])) {
        echo "⚠️ Dữ liệu không hợp lệ!\n";
        return;
    }

    $id = $item['_id'];
    $avatarName = $item['author']['avatar'] ?? '';
    $adminAvatarPath = __DIR__ . '/database/avatars/' . $avatarName;
    $userAvatarPath  = $userAvatarDir . '/' . $avatarName;

    // Chuyển ảnh nếu có
    if ($avatarName && file_exists($adminAvatarPath)) {
        copy($adminAvatarPath, $userAvatarPath);
    }

    // Cập nhật trạng thái và bỏ _id trước khi insert
    $item['status'] = 'approved';
    unset($item['_id']);
    $userStore->insert($item);

    // Xóa bài gốc ở admin
    $adminStore->deleteById($id);

    // Kiểm tra xem ảnh có còn được dùng trong admin DB không
    if ($avatarName) {
        $others = $adminStore->findBy(["author.avatar", "=", $avatarName]);
        if (empty($others) && file_exists($adminAvatarPath)) {
            unlink($adminAvatarPath);
            echo "🗑️ Đã xoá avatar không còn dùng: $avatarName\n";
        }
    }

    echo "✅ Đã duyệt bài: " . ($item['title'] ?? '(không có tiêu đề)') . "\n";
};

$channel->basic_consume('approve_queue', '', false, true, false, false, $callback);

while ($channel->is_open()) {
    try {
        $channel->wait(null, false, 5);
    } catch (Exception $e) {
        echo "❌ Lỗi: " . $e->getMessage() . "\n";
    }
}
