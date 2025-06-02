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

    // Lấy _id gốc để xóa sau khi insert
    $adminId = $item['_id'];

    // Giữ nguyên custom_id nếu có, nếu không có thì tự tạo
    if (!isset($item['custom_id']) || !$item['custom_id']) {
        $item['custom_id'] = uniqid('post_');
    }

    // Chuyển avatar (nếu có) từ admin sang user
    $avatar = $item['author']['avatar'] ?? '';
    $adminAvatarPath = __DIR__ . '/database/avatars/' . $avatar;
    $userAvatarPath  = $userAvatarDir . '/' . $avatar;
    if ($avatar && file_exists($adminAvatarPath)) {
        copy($adminAvatarPath, $userAvatarPath);
    }

    // Đảm bảo trường 'category' vẫn tồn tại. 
    // Nếu admin DB chưa có, bạn có thể gán mặc định hoặc bỏ qua.
    $category = $item['category'] ?? '';

    // Cập nhật trạng thái và xóa _id để insert vào user DB
    $item['status'] = 'approved';
    unset($item['_id']);

    // Chèn sang user DB (SleekDB tự động sinh _id mới)
    $userStore->insert([
        'custom_id'  => $item['custom_id'],
        'title'      => $item['title'],
        'about'      => $item['about'],
        'category'   => $category,
        'author'     => $item['author'],
        'created_at' => $item['created_at'],
        'status'     => $item['status']
    ]);

    // Xoá bài khỏi admin DB
    $adminStore->deleteById($adminId);

    // Xoá avatar khỏi admin nếu không còn dùng
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
