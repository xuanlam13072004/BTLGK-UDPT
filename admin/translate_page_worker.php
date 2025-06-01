<?php
set_time_limit(0);
require_once __DIR__ . '/../vendor/autoload.php';

use Google\Cloud\Translate\V2\TranslateClient;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use SleekDB\SleekDB;

$userStore = SleekDB::store('news', __DIR__ . '/../user/database', ['timeout' => false]);

$translate = new TranslateClient([
  'key' => 'AIzaSyC0noH5QzXy-Xsoojpeoe1H-jWW-vyeOUo' // ← Thay bằng key thật của bạn
]);

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('translate_page_queue', false, true, false, false);

echo "🟢 Translate Page Worker đang chạy...\n";

$callback = function ($msg) use ($userStore, $translate) {
    $data = json_decode($msg->body, true);
    $lang = $data['lang'] ?? 'en';

    echo "📦 Đang dịch ngôn ngữ: $lang\n";

    $translatedFile = __DIR__ . '/../user/database/translated_' . $lang . '.json';
    $posts = $userStore->where('status', '=', 'approved')->fetch();
    $translated = [];

    foreach ($posts as $post) {
        try {
            if ($lang === 'vi') {
                $translated[] = [
                    'title'      => $post['title'],
                    'about'      => $post['about'],
                    'author'     => $post['author'],
                    'created_at' => $post['created_at']
                ];
            } else {
                $translated[] = [
                    'title'      => $translate->translate($post['title'], ['target' => $lang])['text'],
                    'about'      => $translate->translate($post['about'], ['target' => $lang])['text'],
                    'author'     => $post['author'],
                    'created_at' => $post['created_at']
                ];
            }
        } catch (Exception $e) {
            echo "❌ Lỗi khi dịch bài: " . $e->getMessage() . "\n";
        }
    }

    file_put_contents($translatedFile, json_encode($translated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "✅ Đã lưu $translatedFile với đầy đủ trường.\n";
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('translate_page_queue', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
