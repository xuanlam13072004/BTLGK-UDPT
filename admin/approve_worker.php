<?php
set_time_limit(0);
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use SleekDB\SleekDB;

// Kết nối DB
$adminStore = SleekDB::store('news', __DIR__ . '/database', ['timeout' => false]);
$userStore  = SleekDB::store('news', __DIR__ . '/../user/database', ['timeout' => false]);

// Tạo thư mục avatars bên user nếu chưa có
$userAvatarDir = __DIR__ . '/../user/database/avatars';
if (!is_dir($userAvatarDir)) mkdir($userAvatarDir, 0755, true);

// RabbitMQ
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

    // Lấy _id từ bài gốc để xóa sau
    $adminId = $item['_id'];

    // Giữ nguyên custom_id nếu đã có, nếu không thì tạo mới
    if (!isset($item['custom_id']) || !$item['custom_id']) {
        $item['custom_id'] = uniqid('post_');
    }

    // Chuyển avatar nếu cần
    $avatar = $item['author']['avatar'] ?? '';
    $adminAvatarPath = __DIR__ . '/database/avatars/' . $avatar;
    $userAvatarPath  = $userAvatarDir . '/' . $avatar;
    if ($avatar && file_exists($adminAvatarPath)) {
        copy($adminAvatarPath, $userAvatarPath);
    }

    // Cập nhật trạng thái, xoá _id trước khi insert
    $item['status'] = 'approved';
    unset($item['_id']);

    // Chèn sang user DB
    $userStore->insert($item);

    // Xoá bài khỏi admin DB
    $adminStore->deleteById($adminId);

    // Xoá avatar nếu không còn dùng
    if ($avatar) {
        $stillUsed = $adminStore->findBy(["author.avatar", "=", $avatar]);
        if (empty($stillUsed) && file_exists($adminAvatarPath)) {
            unlink($adminAvatarPath);
            echo "🗑️ Đã xoá avatar không còn dùng: $avatar\n";
        }
    }

    echo "✅ Đã duyệt bài: " . ($item['title'] ?? '(không có tiêu đề)') . "\n";
};

$channel->basic_consume('approve_queue', '', false, true, false, false, $callback);

while ($channel->is_open()) {
    try {
        $channel->wait();
    } catch (Exception $e) {
        echo "❌ Lỗi: " . $e->getMessage() . "\n";
    }
}
