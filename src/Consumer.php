<?php
namespace PhpQueue;

class Consumer
{
    protected $queue;
    protected $maxRetries = 3;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function consume(string $queueName, callable $handler, int $interval = 1)
    {
        while (true) {
            $this->moveDueTasks($queueName);

            $msg = $this->queue->lpop($queueName);
            if (!$msg) { sleep($interval); continue; }

            $payload = json_decode($msg, true);
            $retryKey = "retry:$queueName:" . md5($msg);

            try {
                $handler($payload);
            } catch (\Throwable $e) {
                $retries = $this->queue->incr($retryKey);
                $this->queue->expire($retryKey, 3600);

                if ($retries > $this->maxRetries) {
                    $this->queue->rpush("dlq:$queueName", $msg);
                    $this->queue->del($retryKey);
                } else {
                    $this->pushDelayRetry($queueName, $payload, 5);
                }
            }
        }
    }

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

    protected function pushDelayRetry(string $queueName, array $data, int $delay)
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);
        $delayedQueue = "delayed:$queueName";
        $score = time() + $delay;
        $this->queue->zadd($delayedQueue, $score, $payload);
    }
}
