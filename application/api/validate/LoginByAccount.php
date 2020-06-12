<?php

namespace app\api\validate;


use think\Validate;

/**
 * 帐号密码登录
 * Class LoginByAccount
 * @package app\common\validate
 */
class LoginByAccount extends Validate
{
    protected $rule = [
        'USERNAME' => 'require',
        'PASSWORD' => 'require'
    ];

    protected $message = [
        'USERNAME.require' => '请输入帐号',
        'PASSWORD.require' => '请输入登录密码'
    ];
}