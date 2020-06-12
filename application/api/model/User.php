<?php
namespace app\api\model;

use app\api\controller\Send;
use think\Model;
use think\Db;
use think\model\concern\SoftDelete;
use app\api\common\lib\User as UserLib;
use app\api\common\lib\Encryption;

/**
 *
 * 
 */
class User extends Model
{
	protected $table = 'sys_user';
    protected $pk = 'USER_ID';
    /**
     * 请求时间差
     */
    public static $expires = 720000;
    public static $refreshExpires = 600*600*24*300;   //刷新token过期时间


    /**
     * 帐号登录
     * @param $data
     * @return bool
     */
    public static function loginByAccount($data)
    {   
        $user = User::where('USERNAME',$data['USERNAME'])->find();
       // $data['PASSWORD'] = Encryption::decode($data['PASSWORD']);
        $pwd = self::getpass($data['USERNAME'],$data['PASSWORD']);
        if ($user['PASSWORD'] == $pwd) {
            $accessToken = UserLib::encodeToken($user);
            $user->LAST_LOGIN = date('Y-m-d H:i:s', time());
            $user->IP = request()->ip();
            if ($user->save()) {
                $accessTokenInfo = [
                    'token'  => $accessToken,//访问令牌
                    'expires_time'  => time() + self::$expires,      //过期时间时间戳
                    'refresh_token' => $refresh_token,//刷新的token
                    'refresh_expires_time'  => time() + self::$refreshExpires,      //过期时间时间戳
                    'userInfo'        => $user,//用户信息
                ];
                UserLib::saveAccessToken($accessToken, $accessTokenInfo);  //保存本次token
                return ['USER_ID' => $user['USER_ID'], 'access_token' => $accessToken, 'USERNAME'=>$user['USERNAME'], 'NAME'=>$user['NAME']];
            }
        }
        return false;
    }

    /**
     * 用户快速登录
     * @return bool
     */
    public function loginFast()
    {
        $accessToken = UserLib::encodeToken($this->toArray());
        $this->access_token = $accessToken;
        $this->LAST_LOGIN = time();
        $this->IP = request()->ip();

        if ($this->save()) {
            return $accessToken;
        } else {
            return false;
        }
    }



    /**
     * 手机短信验证
     * @param $data
     * @return bool|int|string
     */
    public function smsRegister($data)
    {
        $data['sec_code'] = $this->setSalt();
        $data['password'] = $this->encryPassword(md5($data['password']), $data['sec_code']);
        $data['reg_time'] = time();
        $data['reg_ip'] = request()->ip();

        Db::startTrans();
        try {
            $user = User::getByMobile($data['mobile']);
            if ($user) {
                $uid = $user['uid'];
            } else {
                model('User')->allowField(true)->save($data);
                $uid = model('User')->getLastInsID();
            }

            Db::commit();
            return $uid;
        } catch (\Exception $e) {
            Db::rollback();
        }
        return false;
    }


     /**
     * 修改密码
     * @param $password
     * @return bool
     */
    public function changePassword($password)
    {
        $newPassword = $this->encryPassword(md5($password), $this->sec_code);
        if ($this->password == $newPassword) {
            render_json('不能与原密码相同', 0);
        } else {
            $this->password = $newPassword;
            return $this->save();
        }
    }


    /**
     * 密码验证
     * @param $password
     * @param $sec_code
     * @return bool
     */
    public static function verifyPassword($password)
    {   
        $user = User::where('USERNAME',$data['USERNAME'])->find();
        if ($user['PASSWORD'] == self::encryPassword($password)) {
            return true;
        }
        return false;
    }

    /**
     * 退出登录
     * @return bool
     */
    public static function logout($token)
    {
        UserLib::cleanAccessToken($token);
        return true;
    }

    /**
     * 更新用户个人信息
     * @param $data
     * @return mixed
     */
    public static function updateUserInfo($data)
    {
        if (!is_array($data)) {
            render_json('传递参数不合法', 0);
        }
        $this->allowField(true)->save($data);

        return $this->uid;
    }


    /**
     * @DateTime 2020-05-22T15:10:57+0800
     * @param    string
     * @return   [array]
     */
	public static function getByUserName($username='')
	{
		$user = User::where('username', '=', $username)->find();
		return $user;
	}

    /**
     * 获取用户信息
     * @DateTime 2020-05-29T17:11:08+0800
     * @param    [type]
     * @return   [type]
     */
    public static function getUserinfo($uid)
    {
        return User::where('USER_ID','=', $uid)->find();
    }

    /**
     * 获取某个字段
     * @DateTime 2020-05-29T17:13:36+0800
     * @param    [type]
     * @param    [type]
     * @return   [type]
     */
    public static function getUserField($where, $field)
    {
        return User::where($where)->field($field)->find();
    }

	/**
	 * @DateTime 2020-05-22T15:47:16+0800
	 * @param    string
	 * @return   [array]
	 */
	public static function getpass($username,$password)
	{	
		$p = file_get_contents("http://47.107.100.27:8080/ytgz/appuser/getPassword?USERNAME={$username}&PASSWORD={$password}");
		$res = json_decode($p);
		return $res->result;
	}


    /**
     * 生成用户唯一登录安全码
     * @param int $length
     * @return string
     */
    public static function setSalt($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $key;
    }

    /**
     * 生成登录密码
     * @param $password 密码
     * @param $secCode 安全码
     * @return string
     */
    public static function encryPassword($password, $secCode)
    {
        $password = preg_match('/^\w{32}$/', $password) ? $password : md5(stripslashes($password));

        return md5($password . md5($secCode));
    }
}