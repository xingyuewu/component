<?php

namespace PHPCommon\Utils;

class Config
{
    private static $_cache = array();//存放读取配置

    private static $_config_paths = array();//配置的访问路径

    private static $_has_init = false;//是否已经初始化过

    private static function _init()
    {
        if (self::$_has_init) {
            return null;
        }

        if (defined('CONFIG_PATH')) {
            self::$_config_paths[] = CONFIG_PATH;
        }

        /*
        if (defined('APPPATH')) {
            self::$_config_paths[] = APPPATH . '/config';
        }
        */
        self::$_has_init = true;
    }

    public static function getHttpSDKConf($sModuleName, $sMethod)
    {
        $arrConf = self::get('rpc');
        if ($arrConf == false || !isset($arrConf['service_list']) || !isset($arrConf['service_list'][$sModuleName])) {
            return array();
        }

        $aSerivceConf = $arrConf['service_list'][$sModuleName];
        $aRet = array(
            'remote_host' => $aSerivceConf['remote_host'],
            'local_host' => isset($aSerivceConf['local_host']) ? $aSerivceConf['local_host'] : '',
            'timeout' => $aSerivceConf['timeout'],
            'connect_timeout' => $aSerivceConf['connect_timeout'],
            'retry' => $aSerivceConf['retry'],
            'prefix' => $aSerivceConf['prefix'],
        );
        if ($sMethod != '' && isset($aSerivceConf['method_list'][$sMethod])) {
            $aRet = array_merge($aRet, $aSerivceConf['method_list'][$sMethod]);
        }
        return $aRet;
    }

    public static function getSDKConf($sModuleName)
    {
        $aConfig = self::get('rpc');
        if ($aConfig == false || !isset($aConfig['service_list']) || !isset($aConfig['service_list'][$sModuleName])) {
            return array();
        }

        $aSerivceConfig = $aConfig['service_list'][$sModuleName];
        return $aSerivceConfig;
    }

    /** 获取配置
     *
     * @param string $file 配置的文件名称
     * @return mixed 如果配置存在,直接返回配置数组,不存在返回false
     */
    public static function get($file)
    {
        //缓存里已经有了，就不要再从配置文件加载了
        if (isset(self::$_cache[$file])) {
            return self::$_cache[$file];
        }

        self::_init();
        //$file = ($file == '') ? 'config' : str_replace('.php', '', $file);
        $file = str_replace('.php', '', $file);
        if (isset(self::$_cache[$file])) {
            return self::$_cache[$file];
        }

        if (defined('ENVIRONMENT')) {
            $check_locations = array(ENVIRONMENT . '/' . $file, $file);
        } else {
            $check_locations = array($file);
        }
        foreach (self::$_config_paths as $path) {
            foreach ($check_locations as $location) {
                if (file_exists($path . '/' . $location . '.php')) {
                    $file_path = $path . '/' . $location . '.php';
                    break(2);
                }
            }
        }
        if (!isset($file_path)) {
            return false;
        }

        $config = include($file_path);
        if (! is_array($config)) {
            return false;
        }

        self::$_cache[$file] = $config;
        return $config;
    }

    /** 获取某个文件的配置项
     *
     * @param string $item 配置项名称
     * @param string $file 文件名
     * @return mixed 返回配置数组,如果不存在,返回false
     */
    public static function item($item, $file)
    {
        $arrConfig = self::get($file);
        if (isset($arrConfig[$item])) {
            return $arrConfig[$item];
        }

        return null;
    }
}

