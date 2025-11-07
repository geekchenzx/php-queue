<?php

namespace PhpQueue;

/**
 * 核心队列类
 * 支持 Predis 和 PhpRedis 客户端
 * 提供基础 Redis 操作接口：lpush、rpop、zadd、zrangebyscore、zrem 等
 */

use Predis\Client as PredisClient;

class Queue
{
    /** @var Redis|PredisClient Redis 客户端实例 */

    protected $redis;

    /**
     * 构造方法，初始化 Redis 连接
     * @param array $config ['host'=>string,'port'=>int,'password'=>string]
     */
    public function __construct()
    {
        $config =  Config::get("redis");

        $this->redis = $this->connect($config);
    }

    /**
     * 连接 Redis
     * @param array $config
     * @return Redis|PredisClient
     */
    protected function connect(array $config)
    {
        // 优先使用 PhpRedis 扩展
        if (extension_loaded('redis')) {
            $r = new \Redis();
            $r->connect($config['host'] ?? '127.0.0.1', $config['port'] ?? 6379);
            if (isset($config['password'])) $r->auth($config['password']);
            return $r;
        }
        // Predis 客户端
        return new PredisClient([
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? 6379,
            'password' => $config['password'] ?? null
        ]);
    }

    /**
     * 向队列右侧添加消息
     * @param string $queue 队列名
     * @param string $payload 消息内容（JSON 字符串）
     * @return bool
     */
    public function rpush(string $queue, string $payload): bool
    {
        if ($this->redis instanceof \Redis) return $this->redis->rpush($queue, $payload) > 0;
        return $this->redis->rpush($queue, $payload) > 0;
    }
    /**
     * 从队列左侧弹出消息
     * @param string $queue 队列名
     * @return string|null
     */
    public function lpop(string $queue)
    {
        if ($this->redis instanceof \Redis) return $this->redis->lPop($queue);
        return $this->redis->lpop($queue);
    }

    /**
     * 添加有序集合（延迟队列）
     * @param string $key Redis 键名
     * @param int $score 分值（时间戳）
     * @param string $payload 消息内容（JSON）
     * @return bool
     */
    public function zadd(string $key, int $score, string $payload): bool
    {
        if ($this->redis instanceof \Redis) return $this->redis->zAdd($key, $score, $payload) !== false;
        return $this->redis->zadd($key, [$payload => $score]);
    }

    /**
     * 获取有序集合指定分值区间的元素
     * @param string $key
     * @param int $min
     * @param int $max
     * @return array
     */
    public function zrangebyscore(string $key, int $min, int $max)
    {
        if ($this->redis instanceof \Redis) return $this->redis->zRangeByScore($key, $min, $max);
        return $this->redis->zrangebyscore($key, $min, $max);
    }

    /**
     * 从有序集合删除指定元素
     * @param string $key
     * @param string $member
     * @return int
     */
    public function zrem(string $key, string $member)
    {
        if ($this->redis instanceof \Redis) return $this->redis->zRem($key, $member);
        return $this->redis->zrem($key, $member);
    }

    /**
     * 自增键值
     * @param string $key
     * @return int
     */
    public function incr(string $key)
    {
        if ($this->redis instanceof \Redis) return $this->redis->incr($key);
        return $this->redis->incr($key);
    }
    /**
     * 设置 key 过期时间
     * @param string $key
     * @param int $seconds
     */
    public function expire(string $key, int $seconds)
    {
        if ($this->redis instanceof \Redis) $this->redis->expire($key, $seconds);
        else $this->redis->expire($key, $seconds);
    }

    /**
     * 删除 key
     * @param string $key
     */
    public function del(string $key)
    {
        if ($this->redis instanceof \Redis) $this->redis->del($key);
        else $this->redis->del([$key]);
    }
}
