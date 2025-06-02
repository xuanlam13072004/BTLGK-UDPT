<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use SleekDB\SleekDB;

// 1. Khởi tạo SleekDB store cho User DB (nơi chứa các bài “đã duyệt”)
$userStore = SleekDB::store('news', __DIR__ . '/../user/database', ['timeout' => false]);

// 2. Kết nối RabbitMQ, subscribe vào queue "edit_queue"
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('edit_queue', false, true, false, false);

echo "🟢 Edit Worker đang chạy...\n";

$callback = function ($msg) use ($userStore) {
    $data = json_decode($msg->body, true);
    $customId = isset($data['custom_id']) ? (string)$data['custom_id'] : '';

    if (empty($customId)) {
        echo "⚠️ Thiếu custom_id trong dữ liệu!\n";
        return;
    }

    // 3. Tìm đúng document hiện có dựa vào custom_id
    $items = $userStore->findBy(["custom_id", "=", $customId]);
    if (empty($items)) {
        echo "❌ Không tìm thấy bài viết với custom_id: $customId\n";
        return;
    }
    // Lấy bản ghi đầu tiên (duy nhất)
    $item = $items[0];

    // 4. Chỉnh sửa trực tiếp trên $item (object hiện tại):
    //    - Không xóa _id, chỉ cập nhật các trường khác.
    $item['title']    = isset($data['title'])       ? trim($data['title'])       : (isset($item['title']) ? $item['title'] : '');
    $item['about']    = isset($data['about'])       ? trim($data['about'])       : (isset($item['about']) ? $item['about'] : '');
    $item['category'] = isset($data['category'])    ? $data['category']         : (isset($item['category']) ? $item['category'] : '');
    $item['author'] = [
        'name'   => isset($data['author_name'])  ? trim($data['author_name'])  : (isset($item['author']['name']) ? $item['author']['name'] : ''),
        'avatar' => isset($data['author_avatar'])? $data['author_avatar']      : (isset($item['author']['avatar']) ? $item['author']['avatar'] : '')
    ];
    // Cập nhật lại ngày giờ sửa
    $item['created_at'] = date('Y-m-d H:i:s');
    // Trạng thái vẫn để "approved" (hoặc giữ nguyên)
    $item['status'] = $item['status'] ?? 'approved';

    // 5. Gọi update() – truyền nguyên $item (có chứa _id) để SleekDB tự động ghi đè document đó
    try {
        $userStore->update($item);
        echo "✅ Đã cập nhật bài viết với custom_id: $customId ( _id nội bộ: {$item['_id']} )\n";
    } catch (Exception $e) {
        echo "❌ Lỗi khi gọi update(): " . $e->getMessage() . "\n";
    }
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('edit_queue', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
