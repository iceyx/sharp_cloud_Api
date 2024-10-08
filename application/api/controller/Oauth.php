<?php
namespace app\api\controller;

use app\api\controller\Send;
use think\Exception;
use think\facade\Request;
use think\facade\Cache;

/**
 * API鉴权验证
 */
class Oauth
{
    use Send;
    
    /**
     * accessToken存储前缀
     *
     * @var string
     */
    public static $accessTokenPrefix = 'accessToken_';

    /**
     * 过期时间秒数
     *
     * @var int
     */
    public static $expires = 7200;

    /**
     * 认证授权 通过用户信息和路由
     * @param Request $request
     * @return \Exception|UnauthorizedException|mixed|Exception
     * @throws UnauthorizedException
     */
    final function authenticate()
    {      
        return self::certification(self::getClient());
    }

    /**
     * 获取用户信息
     * @param Request $request
     * @return $this
     * @throws UnauthorizedException
     */
    public static function getClient()
    {   
        //获取头部信息
        try {
            $authorization = Request::header('Authentication');   //获取请求中的authentication字段，值形式为USERID asdsajh..这种形式

            $authorization = explode(" ", $authorization);        //explode分割，获取后面一窜base64加密数据

            $authorizationInfo  = explode(":", base64_decode($authorization[1]));  //对base_64解密，获取到用:拼接的自字符串，然后分割，可获取appid、accesstoken、uid这三个参数
            $clientInfo['user_id'] = $authorizationInfo[0];
            $clientInfo['token'] = $authorizationInfo[1];
            return $clientInfo;
        } catch (Exception $e) {
            return self::returnMsg(401,'Invalid authorization credentials',Request::header(''));
        }
    }

    /**
     * 获取用户信息后 验证权限
     * @return mixed
     */
    public static function certification($data = []){
        $getCacheAccessToken = Cache::get(self::$accessTokenPrefix . $data['token']);  //获取缓存token
        if(!$getCacheAccessToken){
            return self::returnMsg(200,'未登录，请先登录!',"fail");
        }
        return $data;
    }

    /**
     * 检测当前控制器和方法是否匹配传递的数组
     *
     * @param array $arr 需要验证权限的数组
     * @return boolean
     */
    public static function match($arr = [])
    {
        $request = Request::instance();
        $arr = is_array($arr) ? $arr : explode(',', $arr);
        if (!$arr)
        {
            return false;
        }
        $arr = array_map('strtolower', $arr);
        // 是否存在
        if (in_array(strtolower($request->action()), $arr) || in_array('*', $arr))
        {
            return true;
        }

        // 没找到匹配
        return false;
    }

    /**
     * 生成签名
     * _字符开头的变量不参与签名
     */
    public static function makeSign ($data = [],$app_secret = '')
    {   
        unset($data['version']);
        unset($data['sign']);
        return self::_getOrderMd5($data,$app_secret);
    }

    /**
     * 计算ORDER的MD5签名
     */
    private static function _getOrderMd5($params = [] , $app_secret = '') {
        ksort($params);
        $params['key'] = $app_secret;
        return strtolower(md5(urldecode(http_build_query($params))));
    }

}