<?php
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

require_once __DIR__ . '/vendor/autoload.php';

function sendToQueue(array $data) {
    try {
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        // ⚠️ Đảm bảo queue đồng bộ về cấu hình (durable = true)
        $channel->queue_declare('posts_queue', false, true, false, false);

        $msg = new AMQPMessage(json_encode($data), ['delivery_mode' => 2]); // 2 = persistent
        $channel->basic_publish($msg, '', 'posts_queue');

        $channel->close();
        $connection->close();
    } catch (Exception $e) {
        throw new Exception("Không thể gửi vào RabbitMQ: " . $e->getMessage());
    }
}
