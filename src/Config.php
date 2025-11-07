<?php
namespace PhpQueue;

class Config
{
    /** @var array 配置数组 */
    protected static $config = [
        'redis'    => [
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'password' => null,
            'database' => 0,
        ],
        'consumer' => [
            'interval'     => 1,
            'max_retries'  => 3,
            'retry_delay'  => 5,
        ],
        'producer' => [
            'default_delay' => 0,
        ],
    ];

    /**
     * 初始化配置（读取配置文件 + 动态覆盖）
     * @param string|null $file 配置文件路径
     * @param array $override 动态覆盖配置
     */
    public static function init(string $file = null, array $override = [])
    {
        $fileConfig = [];
        if ($file && file_exists($file)) {
            $fileConfig = include $file;
        }

        // 合并三层配置：默认 < 文件 < 动态覆盖
        self::$config = array_merge(self::$config, $fileConfig, $override);
    }

    /**
     * 获取配置
     * @param string|null $key 支持一级键名，如 'redis', 'consumer'
     * @param mixed $default 默认值
     */
    public static function get(string $key = null, $default = null)
    {
        if ($key === null) return self::$config;
        return self::$config[$key] ?? $default;
    }
}