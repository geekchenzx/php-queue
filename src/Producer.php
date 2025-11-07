<?php

namespace PhpQueue;

/**
 * 生产者类
 * 提供消息投递功能，支持延迟消息
 */
class Producer
{
    /** @var Queue 核心队列实例 */
    protected $queue;
    protected $maxRetries = 3;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    /**
     * 投递消息
     * @param string $queueName 队列名
     * @param array $data 消息数据
     * @param int $delay 延迟时间（秒），0 表示立即投递
     * @return bool
     */
    public function push(string $queueName, array $data, int $delay = 0): bool
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);


        if ($delay > 0) {
            // 延迟队列 key
            $delayedQueue = "delayed:$queueName";
            $score = time() + $delay;
            return $this->queue->zadd($delayedQueue, $score, $payload);
        }

        return $this->queue->rpush($queueName, $payload);
    }
}
