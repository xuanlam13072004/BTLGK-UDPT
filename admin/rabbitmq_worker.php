<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use SleekDB\SleekDB;

// Store vào database riêng của admin
$store = SleekDB::store('news', __DIR__ . '/database');

// ✅ Sửa: Lưu avatar vào thư mục admin/database/avatars
$avatarDir = __DIR__ . '/database/avatars';
if (!is_dir($avatarDir)) {
    mkdir($avatarDir, 0755, true);
}

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('posts_queue', false, true, false, false);

echo "🟢 Worker đang chờ bài viết từ RabbitMQ...\n";

$callback = function ($msg) use ($store, $avatarDir) {
    $data = json_decode($msg->body, true);

    if (isset($data['title'], $data['about'], $data['author'])) {
        // ✅ Nếu có ảnh base64 thì lưu vào admin/database/avatars/
        if (!empty($data['author']['avatar']) && !empty($data['author']['avatar_base64'])) {
            $filePath = $avatarDir . '/' . $data['author']['avatar'];
            $imageData = base64_decode($data['author']['avatar_base64']);
            if ($imageData !== false) {
                file_put_contents($filePath, $imageData);
            }
        }

        // ❌ Không lưu base64 vào SleekDB
        unset($data['author']['avatar_base64']);

        $store->insert($data);
        echo "✅ Đã lưu bài viết: {$data['title']}\n";
    } else {
        echo "⚠️ Dữ liệu không hợp lệ. Bỏ qua.\n";
    }
};

$channel->basic_consume('posts_queue', '', false, true, false, false, $callback);

while ($channel->is_open()) {
    try {
        $channel->wait(null, false, 5);
    } catch (Exception $e) {
        echo "❌ Lỗi khi chờ dữ liệu: " . $e->getMessage() . "\n";
    }
}
