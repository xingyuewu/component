<?php

namespace PHPCommon\SDK\Member;

use PHPCommon\Utils\HttpClient;
use PHPCommon\Utils\Config;

class Client
{
    const MODULE_NAME = "member-service";
    private static $SDK_VERSION = '1.0.0';
    private $_isLocalCall = 0;

    function __construct()
    {

    }

    private function _buildHttpConf($sAction)
    {
        $aSDkCommonConfig = Config::getSDKConf(self::MODULE_NAME);
        $urlPrefix = '';
        if (isset($aSDkCommonConfig['prefix'])) {
            $urlPrefix = $aSDkCommonConfig['prefix'];
        }
        $sRemoteBaseUrl = 'http://' . $aSDkCommonConfig['host'] . ':' . $aSDkCommonConfig['port'] . $urlPrefix;
        $iniConfig = array(
            'post' => 1,
            'priority' => $this->_isLocalCall,
            'remoteBaseUrl' => $sRemoteBaseUrl,
            'action' => $sAction,
            'retry' => 1,
            'timeoutMsec' => $aSDkCommonConfig['timeout'],
            'connectTimeoutMsec' => $aSDkCommonConfig['connect_timeout'],
            'headers' => array('Content-Type:application/json'),
        );
        return $iniConfig;
    }

    public function call($sAction, $aParams)
    {
        $aResult = array();
        if (empty($aParams)) {
            return $aResult;
        }
        $aInput = $aParams;
        $iniConfig = $this->_buildHttpConf($sAction);
        return (new HttpClient($iniConfig))->send($aInput);
    }
}
