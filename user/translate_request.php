<?php
session_start(); // phải là dòng đầu tiên, không có khoảng trắng hay echo trước đó

$lang = $_POST['lang'] ?? 'vi';
$_SESSION['lang'] = $lang;

// Gửi yêu cầu dịch qua RabbitMQ như cũ
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$data = ['lang' => $lang];
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('translate_page_queue', false, true, false, false);

$msg = new AMQPMessage(json_encode($data), ['delivery_mode' => 2]);
$channel->basic_publish($msg, '', 'translate_page_queue');

$channel->close();
$connection->close();

header("Location: home.php");
exit;
