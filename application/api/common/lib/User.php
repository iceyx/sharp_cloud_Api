<?php

namespace app\api\common\lib;
use app\api\common\lib\Encryption;

/**
 * 用户基础类库
 * Class User
 * @package app\common\lib
 */
class User
{   
    public static $accessTokenPrefix = 'accessToken_';
    public static $refreshAccessTokenPrefix = 'refreshAccessToken_';
    public static $expires = 720000;
    public static $refreshExpires = 600*600*24*300;   //刷新token过期时间

    /**
     * 相关token生成
     * @param $userinfo
     * @param string $type
     * @return bool
     */
    public static function encodeToken($userinfo, $type = 'access_token')
    {   
        $ret = false;
        $lastLoginTime = time();
        $lastLoginIp = \request()->ip();
        if ($userinfo && $type) {
            switch ($type) {
                case 'access_token':
                    $ret = Encryption::encode($userinfo['USER_ID'] . ':' . $lastLoginTime . ':' . $lastLoginIp);
                    break;
            }
        }
        return $ret;
    }

    /**
     * token解密
     * @param $token
     * @param string $type
     * @return array
     */
    public static function decodeToken($token, $type = 'access_token')
    {
        $ret = array();
        if ($token && $type) {
            $str = Encryption::decode($token);
            if ($str) {
                switch ($type) {
                    case 'access_token':
                        list($ret['USER_ID'], $ret['LAST_LOGIN'], $ret['IP']) = explode(':', $str);
                        break;
                }
            }
        }

        return $ret;
    }



    /**
     * 存储token
     * @param $accessToken
     * @param $accessTokenInfo
     */
    public static function saveAccessToken($accessToken, $accessTokenInfo)
    {   
        //存储accessToken
        cache(self::$accessTokenPrefix.$accessToken, $accessTokenInfo, self::$expires);
    }

    /**
     * 刷新token存储
     * @param $accessToken
     * @param $accessTokenInfo
     */
    public static function saveRefreshToken($refresh_token,$appid)
    {
        //存储RefreshToken
        cache(self::$refreshAccessTokenPrefix.$appid,$refresh_token,self::$refreshExpires);
    }

    /**
     * 清除accessToken
     * [cleanAccessToken description]
     * @DateTime 2020-06-10T10:59:41+0800
     * @param    [type]                   $accessToken [description]
     * @return   [type]                                [description]
     */
    public static function cleanAccessToken($accessToken)
    {   
        
        cache(self::$accessTokenPrefix.$accessToken,' ');
    }
}