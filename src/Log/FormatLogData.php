<?php
/**
 * Created by PhpStorm.
 * User: 齐正宁
 * Date: 2019/11/11
 * Time: 15:58
 */

namespace PHPCommon\Log;

class FormatLogData
{

    //定义日志标签
    const LOG_REQUEST_IN = '_request_in';
    const LOG_REQUEST_OUT = '_request_out';
    const LOG_MYSQL_EXCEPTION = '_mysql_exception';
    const LOG_REDIS_EXCEPTION = '_redis_exception';
    const LOG_HTTP_REQUEST = '_http_request';
    const LOG_HTTP_SUCCESS = '_http_success';
    const LOG_HTTP_FAILURE = '_http_failure';
    const LOG_UNDEFINED = '_undefined';

    //定义调用方
    const CALLER_ENBRANDS = 'enbrands';
    const CALLER_ENBRANDS_CLIENT = 'enbrands_client';

    //定义日志文件名称(兼容日志埋点和双11标准日志格式)
    const LOG_POINT             = 'log_burying_point';
    const LOG_NORMAL_FORMAT     = 'normal_format_log';

    /**
     * 日志名称
     * @return string
     */
    public static function getLogName($name = null)
    {
        if ($name) {
            return $name;
        }
        return self::LOG_NORMAL_FORMAT;
    }

    /**
     * @return string
     * 获取 trace_id  header 头里面不支持下划线，全部转成小写（nginx）
     */
    public static function getTraceId(){
        $header = \Request::header();
        $param = \Request::all();
        $trace_id = '';
        if(isset($param['traceId']) && $param['traceId']){
            $trace_id = $param['traceId'];
        }elseif(isset($param['traceid']) && $param['traceid']){
            $trace_id = $param['traceid'];
        }elseif(isset($header['traceId']) && $header['traceId']){
            $trace_id = $header[$traceId][0];
        }elseif(isset($header['traceid']) && $header['traceid']){
            $trace_id = $header['traceid'][0];
        }elseif(isset($GLOBALS['trace_id']) && $GLOBALS['trace_id']){
            $trace_id = $GLOBALS['trace_id'];
        }else{
            //之前的traceid生成方法 javacurl方法里面
            $trace_id = substr(md5(uniqid()),8,16);
            //input 和 header 头里面都没有trace_id，重新生成放到global里面
            $GLOBALS['trace_id'] = $trace_id;
        }
        return $trace_id;
    }

    /**
     * @param array
     * _request_in 请求进入系统的第一条日志, 核心字段是url(接口的URL),request(参数)
     *_request_out 请求返回的响应日志，为该请求在当前系统的最后一条日志，核心字段是url(接口的URL),response(返回的响应体),elapsed(耗时毫秒),code(接口响应码)
     *_mysql_exception 数据库访问异常日志
     *_redis_exception 缓存访问异常日志
     *_http_request rpc访问的请求日志，核心字段是caller(调用方),callee(被调用方),uri(接口的URL),request(请求参数),retry(重试次数)
     *_http_success rpc访问失败日志，核心字段是caller(调用方),callee(被调用方),uri(接口的URL),response(返回的响应体),elapsed(耗时毫秒),code(接口响应码)
     *_http_failure rpc访问失败日志，核心字段是caller(调用方),callee(被调用方),uri(接口的URL),response(返回的响应体),elapsed(耗时毫秒),code(接口响应码)
     *_undefined 普通日志
     *
     * _request_in格式
     * [xxx.yxxx.getID abc.java]_request_in||trace_id=1231aseasd||host=localhost||client_ip=127.0.0.1||span_id=
     * ||cspand_id=||hintContent={"press_test":1}||url=api/xxx||request={"a":"xyq"}||uid=xxx||merchant_num=10000001||phone=
     * 123||_msg=这是一个开始
     *
     * _request_out格式
     * [xxx.yxxx.getID abc.java]_request_out||trace_id=1231aseasd||host=localhost||client_ip=127.0.0.1||span_id=
     * ||cspand_id=||hintContent={"press_test":1}||url=api/xxx||request={"a":"xyq"}||uid=xxx||merchant_num=10000001||phone=
     * 123||_msg=这是一个开始||response=||elapsed=||code=
     *
     * @return string 文件名.类名.方法名.行号
     */
    public static function getlogNormalFormat($log_param)
    {
        //服务器环境参数
        $server = \Request::server();

        //客户端ip
        $client_ip = array_get($log_param, 'ip');
        if(!$client_ip){
            $client_ip = array_get($server,'REMOTE_ADDR');
        }

        //trace_id
        $trace_id = array_get($log_param, 'traceId');
        if(!$trace_id){
            $trace_id = self::getTraceId();
        }

        //域名
        $host = array_get($log_param, 'domain');
        if (!$host) {
            $host = array_get($server, 'HTTP_HOST');
        }

        //span_id
        $span_id = array_get($log_param, 'spanId');

        //cspand_id
        $cspand_id = array_get($log_param, 'cspandId');

        //hintContent（格式为数组）
        $hintContent = array_get($log_param, 'hintContent', []);
        if (!empty($hintContent)) {
            $hintContent = json_encode($hintContent);
        } else {
            $hintContent = null;
        }

        //提示文字
        $msg = array_get($log_param, 'msg','这家伙很懒，什么都没有留下！');

        //uid
        $uid = array_get($log_param, 'uid');

        //文件名
        $file_name = array_get($log_param, 'fileName');

        //类名
        $class_name = array_get($log_param, 'className');

        //方法名
        $function_name = array_get($log_param, 'functionName');

        //行号
        $number = array_get($log_param, 'number');

        //商家编号
        $merchant_num = array_get($log_param, 'merchantNum');

        //手机号
        $phone = array_get($log_param, 'mobile');

        //url参数已传入的为准
        $url = array_get($log_param, 'uri');
        if (!$url) {
            $url = \Request::path();
        }
        //调用java或外部接口的response(格式为json) 结果
        $response = array_get($log_param, 'response');

        //接口耗时
        $elapsed = array_get($log_param, 'elapsed', 0);

        //接口返回的状态吗
        $code = array_get($log_param, 'code', 0);

        //请求参数 --已传进来的参数为准（格式为数组）
        $request = array_get($log_param, 'request', []);
        if (empty($request)) {
            $request = \Request::all();
        }
        if (!empty($request)) {
            $request = json_encode($request);
        } else {
            $request = null;
        }

        //调用方和被调用方  调用方-默认enbrands_client(会员端)  被调用方callee（调用接口的域名）
        $caller = array_get($log_param, 'caller', self::CALLER_ENBRANDS_CLIENT);
        $callee = array_get($log_param, 'callee');

        //err_no
        $err_no = array_get($log_param, 'errNo', 0);

        //日志标签
        $log_tag = array_get($log_param, 'logTag', self::LOG_REQUEST_IN);

        //日志
        switch ($log_tag) {
            case self::LOG_REQUEST_IN:
                $return = '[' . $file_name . '.' . $class_name . '.' . $function_name . '.' . $number . ']' . self::LOG_REQUEST_IN . '||trace_id=' . $trace_id . '||host=' . $host . '||client_ip=' . $client_ip .
                    '||span_id=' . $span_id . '||cspand_id=' . $cspand_id . '||hintContent=' . $hintContent . '||url=' . $url . '||request=' . $request . '||uid=' . $uid . '||merchant_num=' . $merchant_num . '||phone=' .
                    $phone . '||msg=' . $msg;
                break;
            case self::LOG_REQUEST_OUT:
                $return = '[' . $file_name . '.' . $class_name . '.' . $function_name . '.' . $number . ']' . self::LOG_REQUEST_OUT . '||trace_id=' . $trace_id . '||host=' . $host . '||client_ip=' . $client_ip .
                    '||span_id=' . $span_id . '||cspand_id=' . $cspand_id . '||hintContent=' . $hintContent . '||url=' . $url . '||request=' . $request . '||uid=' . $uid . '||merchant_num=' . $merchant_num . '||phone=' .
                    $phone . '||response=' . $response . '||elapsed=' . $elapsed . '||code=' . $code . '||msg=' . $msg;
                break;
            case self::LOG_HTTP_SUCCESS:
                $return = '[' . $file_name . '.' . $class_name . '.' . $function_name . '.' . $number . ']' . self::LOG_HTTP_SUCCESS . '||trace_id=' . $trace_id . '||host=' . $host . '||client_ip=' . $client_ip .
                    '||span_id=' . $span_id . '||cspand_id=' . $cspand_id . '||caller=' . $caller . '||callee=' . $callee . '||hintContent=' . $hintContent . '||url=' . $url . '||request=' . $request . '||uid=' . $uid . '||merchant_num=' . $merchant_num . '||phone=' .
                    $phone . '||response=' . $response . '||elapsed=' . $elapsed . '||err_no=' . $err_no . '||code=' . $code . '||msg=' . $msg;
                break;
            case self::LOG_HTTP_FAILURE:
                $return = '[' . $file_name . '.' . $class_name . '.' . $function_name . '.' . $number . ']' . self::LOG_HTTP_FAILURE . '||trace_id=' . $trace_id . '||host=' . $host . '||client_ip=' . $client_ip .
                    '||span_id=' . $span_id . '||cspand_id=' . $cspand_id . '||caller=' . $caller . '||callee=' . $callee . '||hintContent=' . $hintContent . '||url=' . $url . '||request=' . $request . '||uid=' . $uid . '||merchant_num=' . $merchant_num . '||phone=' .
                    $phone . '||response=' . $response . '||elapsed=' . $elapsed . '||err_no=' . $err_no . '||code=' . $code . '||msg=' . $msg;
                break;
            default:
                $return = '[' . $file_name . '.' . $class_name . '.' . $function_name . '.' . $number . ']' . self::LOG_REQUEST_IN . '||trace_id=' . $trace_id . '||host=' . $host . '||client_ip=' . $client_ip .
                    '||span_id=' . $span_id . '||cspand_id=' . $cspand_id . '||hintContent=' . $hintContent . '||url=' . $url . '||request=' . $request . '||uid=' . $uid . '||merchant_num=' . $merchant_num . '||phone=' .
                    $phone . '||msg=' . $msg;
        }
        return $return;

    }

}