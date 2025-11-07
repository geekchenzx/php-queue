<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpQueue\Config;
use PhpQueue\Queue;
use PhpQueue\Producer;
use PhpQueue\Consumer;

Config::init(__DIR__ . '/config.php'); // 初始化配置
// 初始化核心队列
$queueCore = new Queue();

echo "=== PhpQueue 测试 ===\n";

// 生产者测试
$producer = new Producer($queueCore);
$producer->push('email', ['type'=>'email','to'=>'user@test.com','content'=>'你好']);
$producer->push('sms', ['type'=>'sms','phone'=>'13800138000','msg'=>'验证码1234'], 10);

echo "消息已投递到队列\n";

// 消费者测试
$consumer = new Consumer($queueCore);

// 注意：这里会进入无限循环，实际测试可用 Ctrl+C 停止
$consumer->consume('email', function($msg){
    echo "[".date('H:i:s')."] 消费消息: " . json_encode($msg, JSON_UNESCAPED_UNICODE) . "\n";
});

