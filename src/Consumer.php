<?php

namespace PhpQueue;

use PhpQueue\Config;

/**
 * 消费者
 */
class Consumer
{
    /** @var Queue 核心队列实例 */
    protected $queue;
    protected $maxRetries = 3;
    /** @var Config 配置文件 */
    protected $config;

    public function __construct(Queue $queue)
    {
        $this->config = Config::get("consumer");

        $this->queue = $queue;
    }

    /**
     * 消费队列
     * @param string $queueName 队列名
     * @param callable $handler 消息处理回调
     * @param int $interval 轮询间隔（秒）
     */
    public function consume(string $queueName, callable $handler, int $interval = 1)
    {
        while (true) {
            $this->moveDueTasks($queueName);
            $msg = $this->queue->lpop($queueName);
            if (!$msg) {
                // 队列和延迟队列都没有消息，休眠
                sleep($this->config['interval']);
                continue;
            }
            $payload = json_decode($msg, true);
            $retryKey = "retry:$queueName:" . md5($msg);
            try {
                $handler($payload);
            } catch (\Throwable $e) {
                echo "error: {$e->getMessage()}\n";
                $retries = $this->queue->incr($retryKey);
                $this->queue->expire($retryKey, 3600);

                if ($retries > $this->config['max_retries']) {
                    $this->queue->rpush("dlq:$queueName", $msg);
                    $this->queue->del($retryKey);
                } else {
                    $this->pushDelayRetry($queueName, $payload, 5);
                }
            }
        }
    }
    /**
     * 将延迟队列中到期消息移动到主队列
     * 延迟时间由生产者投递时动态设置
     * @param string $queueName
     */
    protected function moveDueTasks(string $queueName)
    {
        $delayedQueue = "delayed:$queueName";
        $now = time();
        $items = $this->queue->zrangebyscore($delayedQueue, 0, $now);
        foreach ($items as $msg) {
            $this->queue->rpush($queueName, $msg);
            $this->queue->zrem($delayedQueue, $msg);
        }
    }

    protected function pushDelayRetry(string $queueName, array $data, int $delay = 0)
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);
        $delayedQueue = "delayed:$queueName";
        $delay = $delay ?? $this->config['retry_delay'];

        $score = time() + $delay -= $delay;
        $this->queue->zadd($delayedQueue, $score, $payload);
    }
}
