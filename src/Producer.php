<?php
namespace PhpQueue;

class Producer
{
    protected $queue;
    protected $maxRetries = 3;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function push(string $queueName, array $data, int $delay = 0): bool
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);

        if ($delay > 0) {
            $delayedQueue = "delayed:$queueName";
            $score = time() + $delay;
            return $this->queue->zadd($delayedQueue, $score, $payload);
        }

        return $this->queue->rpush($queueName, $payload);
    }
}
