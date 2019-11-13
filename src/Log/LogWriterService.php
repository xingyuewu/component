<?php
/**
 * 自定义日志记录类
 *
 * @author 陈瀚禧
 * @created 2016-06-27
 * @version 1.0
 */
namespace PHPCommon\Log;

use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class LogWriterService
{
    /**
     * 日志句柄数组
     *
     * @var array
     */
    protected $_log_handle;

    /**
     * APP环境获取句柄
     *
     * @var Application
     */
    protected $_app;

    /**
     * 请求句柄
     *
     * @var Request
     */
    protected $_request;

    /**
     * 今天日期
     *
     * @var string
     */
    protected $_date;

    /**
     * 日志等级配置
     *
     * @var array
     */
    protected $_levels = [
        'debug' => MonologLogger::DEBUG,
        'info' => MonologLogger::INFO,
        'notice' => MonologLogger::NOTICE,
        'warning' => MonologLogger::WARNING,
        'error' => MonologLogger::ERROR,
        'critical' => MonologLogger::CRITICAL,
        'alert' => MonologLogger::ALERT,
        'emergency' => MonologLogger::EMERGENCY,
    ];

    /**
     * 构造函数
     *
     * @param  Application $app
     * @param  Request $request
     * @return void
     */
    public function __construct($app, $request)
    {
        $this->_app = $app;
        $this->_request = $request;
        $this->_date = date('Y-m-d');
    }

    /**
     * debug级别日志记录
     *
     * @param  string $path
     * @param  string $message
     * @param  array $context
     * @param  int $merchant_num
     * @return void
     */
    public function debug($path, $message, $merchant_num = 0, $context = [])
    {
        return $this->writeLog($path, __FUNCTION__, $message, $context, $merchant_num);
    }

    /**
     * emergency级别日志记录
     *
     * @param  string $path
     * @param  string $message
     * @param  array $context
     * @param  int $merchant_num
     * @return void
     */
    public function emergency($path, $message, $merchant_num = 0, $context = [])
    {
        return $this->writeLog($path, __FUNCTION__, $message, $context, $merchant_num);
    }

    /**
     * alert级别日志记录
     *
     * @param  string $path
     * @param  string $message
     * @param  array $context
     * @param  int $merchant_num
     * @return void
     */
    public function alert($path, $message, $merchant_num = 0, $context = [])
    {
        return $this->writeLog($path, __FUNCTION__, $message, $context, $merchant_num);
    }

    /**
     * critical级别日志记录
     *
     * @param  string $path
     * @param  string $message
     * @param  array $context
     * @param  int $merchant_num
     * @return void
     */
    public function critical($path, $message, $merchant_num = 0, $context = [])
    {
        return $this->writeLog($path, __FUNCTION__, $message, $context, $merchant_num);
    }


    /**
     * error级别日志记录
     *
     * @param  string $path
     * @param  string $message
     * @param  array $context
     * @param  int $merchant_num
     * @return void
     */
    public function error($path, $message, $merchant_num = 0, $context = [])
    {
        return $this->errors($path, $message, $merchant_num, $context);
    }

    public function errors($path, $message, $merchant_num = 0, $context = [])
    {
        return $this->writeLog($path, 'error', $message, $context, $merchant_num);
    }

    /**
     * warning级别日志记录
     *
     * @param  string $path
     * @param  string $message
     * @param  array $context
     * @param  int $merchant_num
     * @return void
     */
    public function warning($path, $message, $merchant_num = 0, $context = [])
    {
        return $this->writeLog($path, __FUNCTION__, $message, $context, $merchant_num);
    }

    /**
     * notice级别日志记录
     *
     * @param  string $path
     * @param  string $message
     * @param  array $context
     * @param  int $merchant_num
     * @return void
     */
    public function notice($path, $message, $merchant_num = 0, $context = [])
    {
        return $this->writeLog($path, __FUNCTION__, $message, $context, $merchant_num);
    }

    /**
     * info级别日志记录
     *
     * @param  string $path
     * @param  string $message
     * @param  array $context
     * @return void
     */
    public function info($path, $message, $merchant_num = 0, $context = [])
    {
        return $this->writeLog($path, __FUNCTION__, $message, $context, $merchant_num);
    }

    /**
     * 日志埋点
     *
     * @param  string $path
     * @param  string $message
     * @param  array $context
     * @return void
     */
    public function logBuryingPoint($path, $message, $context = [])
    {
        return $this->customWriteLog($path, __FUNCTION__, $message, $context);
    }

    /**
     * 记录日志
     * @date 2019年09月30日 15:24:20
     * @param $path
     * @param $level
     * @param $message
     * @param $context
     * @param $merchant_num
     * @return void
     * @throws \Exception
     */
    protected function writeLog($path, $level, $message, $context, $merchant_num)
    {
        //获取店铺id
        if (empty($path)) {
            $path = $level;
        }
        if (empty($merchant_num)) {
            $merchant = session()->get(config('key.merchant_session_key'));
            if (is_object($merchant)) {
                $merchant_num = $merchant->merchant_num;
            }
        }
        $key = "{$this->_date}/{$merchant_num}/{$path}";
        if (is_array($message)) {
            $message = var_export($message, true);
        }

        if (!isset($this->_log_handle[$key]) || !($this->_log_handle[$key] instanceof MonologLogger)) {
            $path = storage_path() . '/logs/' . $key . '.log';
            $this->_log_handle[$key] = new MonologLogger($this->_app->environment());
            $this->_log_handle[$key]->pushHandler(new StreamHandler($path, $this->parseLevel($level)));
        }

        if (!is_array($context)) {
            $context = [$context];
        }
        $this->_log_handle[$key]->{$level}($message, $context);
    }

    /**
     * @param $path
     * @param $level
     * @param $message
     * @param $context
     * @param $merchant_num
     * @throws \Exception
     * 自定义日志、用于日志埋点
     */
    protected function customWriteLog($path, $level, $message, $context)
    {
        //当前是几点
        $h = date('H');
        $key = FormatLogData::getLogName($path)."/{$this->_date}/{$path}".'_'.$h;
        if (!isset($this->_log_handle[$key]) || !($this->_log_handle[$key] instanceof MonologLogger))
        {
            $path = storage_path() . '/logs/' . $key . '.log';
            $this->_log_handle[$key] = new MonologLogger($this->_app->environment());
            $this->_log_handle[$key]->pushHandler(new StreamHandler($path, MonologLogger::INFO));
        }
        if (!is_array($context)) {
            $context = [$context];
        }
        $this->_log_handle[$key]->info($message, $context);
    }

    /**
     * 获取等级代号
     *
     * @param  string $level
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    protected function parseLevel($level)
    {
        if (isset($this->_levels[$level])) {
            return $this->_levels[$level];
        }

        throw new InvalidArgumentException('Invalid log level.');
    }

}
