<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use SleekDB\SleekDB;

$userStore = SleekDB::store('news', __DIR__ . '/../user/database', ['timeout' => false]);

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('edit_queue', false, true, false, false);

echo "🟢 Edit Worker đang chạy...\n";

$callback = function ($msg) use ($userStore) {
    $data = json_decode($msg->body, true);
    $customId = (string)($data['custom_id'] ?? '');

    if (!$customId) {
        echo "⚠️ Thiếu custom_id trong dữ liệu!\n";
        return;
    }

    $items = $userStore->findBy(["custom_id", "=", $customId]);
    if (empty($items)) {
        echo "❌ Không tìm thấy bài viết với custom_id: $customId\n";
        return;
    }

    $originalItem = $items[0];
    $id = $originalItem['_id'];     // Lưu _id
    unset($originalItem['_id']);   // Xoá _id trước khi cập nhật

    // Cập nhật nội dung
    $originalItem['title'] = $data['title'] ?? $originalItem['title'];
    $originalItem['about'] = $data['about'] ?? $originalItem['about'];
    $originalItem['author']['name'] = $data['author_name'] ?? $originalItem['author']['name'];
    $originalItem['author']['avatar'] = $data['author_avatar'] ?? $originalItem['author']['avatar'];
    $originalItem['created_at'] = date('Y-m-d H:i:s');

    // Cập nhật bản ghi bằng update()
    $updated = $userStore->update($originalItem, ["_id", "=", $id]);
    if ($updated) {
        echo "✅ Đã cập nhật bài viết với custom_id: $customId\n";
    } else {
        echo "❌ Lỗi khi cập nhật bài viết với custom_id: $customId\n";
    }
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('edit_queue', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
