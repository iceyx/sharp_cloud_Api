<?php

namespace app\api\controller\v1;

use think\Controller;
use think\Request;
use app\api\controller\Send;
use app\api\controller\Api;
use app\api\common\Page;
use app\api\model\User as UserModel;
use app\api\model\Company;
use app\api\validate\ValidataCommon;
use think\Session;
use app\api\validate\LoginByAccount;

class User extends Api
{   
    /**
     * 不需要鉴权方法
     * index、save不需要鉴权
     * ['index','save']
     * 所有方法都不需要鉴权
     * [*]
     */
    protected $noAuth = ['login'];
    
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {   


       //通用参数验证
        ValidataCommon::validateCheck(['lng' => 'require', 'lat' => 'require'], $this->request->param('')); //参数验证
        //通用分页
        list($page, $size) = Page::getPage($this->request->param(''));
        dump($this->uid);
    }


    /**
     * 帐号密码登录
     */
    public function login()
    {
        $data = $this->request->param('');
        $validate = new LoginByAccount;
        $result = $validate->check($data);
        if ($result !== true) {
            render_json($validate->getError(), 0);
        }
        //密码校验
        $user = UserModel::loginByAccount($data);
        if ($user) {
            $companys = Company::getCompanyByUid($user['USER_ID']);
            $company_type = count($companys);
            $company_type>1?$arr['company_type']=1:$arr['company_type']=0;
            $company_type<1?$user['company_type']=0:$user['company_type']=1;
            Session('USERINFO',$user);
            render_json(array(
                'TOKEN' => $user['access_token'],
                'USER_ID' => $user['USER_ID'],
                'COMPANY_TYPE' => $arr['company_type'],
                'USERNAME' => $user['USERNAME'],
                'NAME' => $user['NAME'],
            ));
        }
        render_json('','用户名或密码错误', 301);
    }


    /**
     * 此方法不用
     * @DateTime 2020-05-22T08:35:36+0800
     * @param    Request
     * @return   [type]
     */
    public function login111(Request $request)
    {   
        ValidataCommon::validateCheck(['USERNAME' => 'require', 'PASSWORD' => 'require'], $this->request->param('')); //参数验证
        $user = model('User')->where('USERNAME', $data['USERNAME'])->find();
        if (!$user) {
            return self::returnMsg(500,'error','用户名不存在');
        }

        $pwd = UserModel::getpass($this->request->param('USERNAME'),$this->request->param('PASSWORD'));
        if ($user['PASSWORD'] != $pwd) {
            return self::returnMsg(500,'error','用户名或密码不正确');
        }
        $companys = Company::getCompanyByUid($user['USER_ID']);

        $company_type = count($companys);

        $company_type>1?$arr['company_type']=1:$arr['company_type']=0;
        $company_type<1?$user['company_type']=0:$user['company_type']=1;
        Session('USERINFO',$user);
        $arr['user_id'] = $user['USER_ID'];
        return self::returnMsg(200,'登录成功!',$arr);
    }


    /**
     * 退出登录
     */
    public function logout()
    {
        $userObj = UserModel::getUserinfo($this->user_id);
        if ($userObj && $userObj->logout($this->token)) {
            render_json('','用户已注销', 0);
        }
        render_json('','未知错误', 0);
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo()
    {
        $userinfo = UserModel::get($this->user_id);
        $userinfo = $userinfo->toArray();
        $info = array();
        //头像
        $avatar = $userinfo['avatar'];
        if ($avatar) {
            $info['avatar'] = get_pic($avatar);
        } else {
            $info['avatar'] = 'http://' . $_SERVER['HTTP_HOST'] . '/static/images/head_img.png';
        }
        $info['mobile'] = $userinfo['mobile'] ?: '';
        $info['email'] = $userinfo['email'] ?: '';
        $info['username'] = $userinfo['username'] ?: '';

        render_json('获取成功', 1, $info);
    }

    /**
     * 更新用户信息
     */
    public function updateInfo()
    {
        
        $data = input('post.');
        if ($data['username']) {
            $isExist = model('User')->where([['username', '=', $data['username']], ['uid', '<>', $uid]])->count();
            if ($isExist) {
                render_json('用户名已存在', 0);
            }
        }
        //获取用户信息
        $userObj = UserModel::get($uid);
        if (!$userObj) {
            render_json('账号存在异常', 0);
        }
        //上传头像
        if (request()->file('avatar')) {
            $data['avatar'] = Upload::uploadByFile('avatar', 'avatar');
        }
        //修改操作
        $data['uid'] = $uid;
        try {
            $userObj->updateUserInfo($data);
        } catch (\Exception $e) {
            render_json('保存失败', 0);
        }
        render_json('保存成功', 1);
    }

  
}
