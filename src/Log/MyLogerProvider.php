<?php
namespace PHPCommon\Log;

use Illuminate\Support\ServiceProvider;
use PHPCommon\Log\LogWriterService as LogWriter;
use Illuminate\Http\Request;
class MyLogerProvider extends ServiceProvider
{
    /**
     * 服务提供者加是否延迟加载.
     *
     * @var bool
     */
    //protected $defer = true;

    /**
     * 日志记录需要request句柄.
     *
     * @var bool
     */
    protected $_request;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Request $request)
    {
        $this->_request = $request;
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('MyLog', function ($app) {
            return new LogWriter($app, $this->_request);
        });
    }

    /**
     * 加载时提供的服务类.
     *
     * @return array
     */
    public function providers()
    {
        //return ['App\Services\LogWriter'];
        return ['PHPCommon\Log\LogWriter'];
    }
}
