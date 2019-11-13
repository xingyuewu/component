<?php

namespace PHPCommon\Utils;

class HttpClient
{
    //预设local访问值,priority为1,优先访问127.0.0.1
    const REMOTE_PRIORITY = 0;
    const LOCAL_PRIORITY = 1;

    //预定义变量
    private $_response = array();
    private $_version = '';
    private $_priority = 0;
    private $_localUrl = '';
    private $_remoteUrl = '';
    private $_action = '';
    private $_retry = 1;
    private $_post = 1;
    private $_timeoutSec = 0;
    private $_timeoutMsec = 200;
    private $_localTimeoutMsec = 200;
    private $_connectTimeoutSec = 0;
    private $_connectTimeoutMsec = 100;
    private $_localConnectTimeoutMsec = 100;
    private $_headers = array();
    private $_options = array();

    private static $_instance = null;

    /**
     * 创建HttpClient实例
     * @param array $iniConfigData
     * @return object HttpClient
     */
    public static function getInstance($iniConfigData)
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self($iniConfigData);
        }
        return self::$_instance;
    }

    public function __construct($iniConfigData)
    {
        if (isset($iniConfigData['version'])) {
            $this->_version = $iniConfigData['version'];
        }
        if (isset($iniConfigData['priority'])) {
            $this->_priority = intval($iniConfigData['priority']);
        }
        if (isset($iniConfigData['post'])) {
            $this->_post = intval($iniConfigData['post']);
        }
        if (isset($iniConfigData['action'])) {
            $this->_action = strval($iniConfigData['action']);
        }
        if (isset($iniConfigData['localBaseUrl'])) {
            $this->_localUrl = rtrim($iniConfigData['localBaseUrl'], '/') . '/' . $this->_action;
        }
        if (isset($iniConfigData['remoteBaseUrl'])) {
            $this->_remoteUrl = rtrim($iniConfigData['remoteBaseUrl'], '/') . '/' . $this->_action;
        }
        if (isset($iniConfigData['timeoutSec']) && isset($iniConfigData['connectTimeoutSec'])) {
            $this->_timeoutSec = intval($iniConfigData['timeoutSec']);
            $this->_connectTimeoutSec = intval($iniConfigData['connectTimeoutSec']);
        }
        if (isset($iniConfigData['timeoutMsec']) && isset($iniConfigData['connectTimeoutMsec'])) {
            $this->_timeoutMsec = intval($iniConfigData['timeoutMsec']);
            $this->_connectTimeoutMsec = intval($iniConfigData['connectTimeoutMsec']);
        }
        if (isset($iniConfigData['retry'])) {
            $this->_retry = intval($iniConfigData['retry']);
        }
        if (isset($iniConfigData['headers']) && is_array($iniConfigData['headers'])) {
            $this->_headers = $iniConfigData['headers'];
        } else {
            $this->_headers = array();
        }
    }

    /**
     * 传入必要参数,发起request
     * @param array $aInPut
     * @return array
     */
    public function send($aInPut)
    {
        return $this->sendRequest($aInPut, $this->_retry);
    }

    /**
     * 发送request参数,获取response结果
     * @param array $aInPut
     * @param int $iRetry
     * @return array
     */
    public function sendRequest($aInPut, $iRetry = 1)
    {
        if ($this->_priority == self::LOCAL_PRIORITY && $iRetry == $this->_retry) {
            $sHttpUrl = $this->_localUrl;
            $this->_options = array(
                CURLOPT_POST => $this->_post,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_NOSIGNAL => 1,
                CURLOPT_TIMEOUT_MS => $this->_localTimeoutMsec,
                CURLOPT_CONNECTTIMEOUT_MS => $this->_localConnectTimeoutMsec,
            );
        } elseif ($this->_timeoutSec > 0 && $this->_connectTimeoutSec > 0) {
            $sHttpUrl = $this->_remoteUrl;
            $this->_options = array(
                CURLOPT_POST => $this->_post,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_TIMEOUT => $this->_timeoutSec,
                CURLOPT_CONNECTTIMEOUT => $this->_connectTimeoutSec,
            );
        } else {
            $sHttpUrl = $this->_remoteUrl;
            $this->_options = array(
                CURLOPT_POST => $this->_post,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_NOSIGNAL => 1,
                CURLOPT_TIMEOUT_MS => $this->_timeoutMsec,
                CURLOPT_CONNECTTIMEOUT_MS => $this->_connectTimeoutMsec,
            );
        }
        if ($this->_version !== '') {
            $aInPut['version'] = $this->_version;
        }
        $jsonHearFlag = false;
        if (!empty($this->_headers)) {
            foreach ($this->_headers as $sHeaderVal) {
                if (strtolower($sHeaderVal) == 'content-type:application/json') {
                    $jsonHearFlag = true;
                    continue;
                }
                $this->_options[CURLOPT_HTTPHEADER][] = $sHeaderVal;
            }
        }
        $aPublic = array(
            'ip' => "127.0.0.1",
            'timestamp' => time(),
            'sessionKey' => md5("127.0.0.1" . time() . "jifen")
        );
        $aInPut = array_merge($aInPut, $aPublic);
        //echo json_encode($aInPut);
        if ($jsonHearFlag) {
            $sRequestParams = json_encode($aInPut);
        } else {
            $sRequestParams = http_build_query($aInPut);
        }

        if ($this->_options[CURLOPT_POST]) {
            $this->_options[CURLOPT_POSTFIELDS] = $sRequestParams;
        } else {
            $sHttpUrl = $sHttpUrl . '?' . $sRequestParams;
        }

        $sTraceId = isset($_SERVER['JIFENN_TRACE_RID']) ? strval($_SERVER['HTTP_DIDI_HEADER_RID']) : '';
        if (!empty($sTraceId)) {
            $this->_options[CURLOPT_HTTPHEADER][] = 'jifenn-header-rid: ' . $sTraceId;
        }
        //add span id
        if (function_exists("gen_span_id")) {
            $sSpanId = gen_span_id();
        } else {
            $sSpanId = isset($_global_span_id) ? strval($_global_span_id) : '';
        }
        if (!empty($sSpanId)) {
            $this->_options[CURLOPT_HTTPHEADER][] = 'jifenn-header-spanid: ' . $sSpanId;
        }

        //add hint code & hint content
        $hintCode = isset($_SERVER['HTTP_JIFENN_HEADER_HINT_CODE']) ? strval($_SERVER['HTTP_JIFENN_HEADER_HINT_CODE']) : '';
        if (!empty($hintCode)) {
            $this->_options[CURLOPT_HTTPHEADER][] = 'jifenn-header-hint-code: ' . $hintCode;
        }
        $hintContent = isset($_SERVER['HTTP_jifenn_HEADER_HINT_CONTENT']) ? strval($_SERVER['HTTP_JIFENN_HEADER_HINT_CONTENT']) : '';
        if (!empty($hintContent)) {
            $this->_options[CURLOPT_HTTPHEADER][] = 'jifenn-header-hint-content: ' . $hintContent;
        }

        if (function_exists('trackGetContext')) {
            $sTrackContext = trackGetContext();
            if (!empty($trackContext)) {
                $this->_options[CURLOPT_HTTPHEADER][] = 'jifenn-enable-track-log: ' . $sTrackContext;
            }
        }

        $hCurlInstance = curl_init($sHttpUrl);
        foreach ($this->_options as $sKey => $iValue) {
            curl_setopt($hCurlInstance, $sKey, $iValue);
        }
        //exec请求,获取respose结果
        $sResult = curl_exec($hCurlInstance);
        $iErrno = curl_errno($hCurlInstance);
        $aErrmsg = curl_error($hCurlInstance);
        $aResult = self::jsonToArray($sResult);
        $this->_response = array(
            'errno' => $iErrno,
            'errmsg' => $aErrmsg,
            'result' => $aResult,
        );
        $iHttpCode = curl_getinfo($hCurlInstance, CURLINFO_HTTP_CODE);
        if ($iHttpCode != 200 || empty($aResult)) {
            $iRetry = $iRetry - 1;
            if (function_exists('com_log_warning')) {
                com_log_warning('_com_http_failure', $iErrno, $aErrmsg, array("cspanid" => $sSpanId, "message" => "request fail", "url" => $sHttpUrl, "args" => json_encode($aInPut), "retry" => $iRetry));
            }
            if ($iRetry >= 0) {
                $this->sendRequest($aInPut, $iRetry);
            }
        } else {
            if (function_exists('com_log_notice')) {
                $info = curl_getinfo($hCurlInstance);
                com_log_notice('_com_http_success', array("cspanid" => $sSpanId, "url" => $sHttpUrl,
                    "args" => json_encode($aInPut), "response" => $sResult,
                    "errno" => $iErrno, "errmsg" => $aErrmsg, 'proc_time' => $info['total_time']));
            }
        }
        return $this->_response;
    }

    /**
     * json转换为数组:传入数据为空,非字符串类型,及不能转换数组,返回原传入数据
     * @params string $sJsonData
     * return array
     */
    public static function jsonToArray($sJsonData)
    {
        if (empty($sJsonData) || !is_string($sJsonData)) {
            return $sJsonData;
        }
        $aResult = json_decode($sJsonData, true);
        if (empty($aResult)) {
            return $sJsonData;
        }
        return $aResult;
    }
}
