<?php
namespace PhpQueue;

use Predis\Client as PredisClient;

class Queue
{
    protected $redis;

    public function __construct(array $config = [])
    {
        $this->redis = $this->connect($config);
    }

    protected function connect(array $config)
    {
        if (extension_loaded('redis')) {
            $r = new \Redis();
            $r->connect($config['host'] ?? '127.0.0.1', $config['port'] ?? 6379);
            if (isset($config['password'])) $r->auth($config['password']);
            return $r;
        }
        return new PredisClient([
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? 6379,
            'password' => $config['password'] ?? null
        ]);
    }

    public function rpush(string $queue, string $payload): bool
    {
        if ($this->redis instanceof \Redis) return $this->redis->rpush($queue, $payload) > 0;
        return $this->redis->rpush($queue, $payload) > 0;
    }

    public function lpop(string $queue)
    {
        if ($this->redis instanceof \Redis) return $this->redis->lPop($queue);
        return $this->redis->lpop($queue);
    }

    public function zadd(string $key, int $score, string $payload): bool
    {
        if ($this->redis instanceof \Redis) return $this->redis->zAdd($key, $score, $payload) !== false;
        return $this->redis->zadd($key, [$payload => $score]);
    }

    public function zrangebyscore(string $key, int $min, int $max)
    {
        if ($this->redis instanceof \Redis) return $this->redis->zRangeByScore($key, $min, $max);
        return $this->redis->zrangebyscore($key, $min, $max);
    }

    public function zrem(string $key, string $member)
    {
        if ($this->redis instanceof \Redis) return $this->redis->zRem($key, $member);
        return $this->redis->zrem($key, $member);
    }

    public function incr(string $key)
    {
        if ($this->redis instanceof \Redis) return $this->redis->incr($key);
        return $this->redis->incr($key);
    }

    public function expire(string $key, int $seconds)
    {
        if ($this->redis instanceof \Redis) $this->redis->expire($key, $seconds);
        else $this->redis->expire($key, $seconds);
    }

    public function del(string $key)
    {
        if ($this->redis instanceof \Redis) $this->redis->del($key);
        else $this->redis->del([$key]);
    }
}