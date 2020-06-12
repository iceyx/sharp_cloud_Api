<?php
namespace app\api\controller\v1;
use app\api\controller\Api;
use app\api\controller\Send;
use app\api\model\SysSetting as SettingModel;

/**
 *
 * 
 */
class System extends Api
{
	    /**
     * 不需要鉴权方法
     * index、save不需要鉴权
     * ['index','save']
     * 所有方法都不需要鉴权
     * [*]
     */
    protected $noAuth = ['settingInfo'];



	public function settingInfo()
	{
		$info = SettingModel::getSetting();
		return self::returnMsg(200,'success',$info);
	}
}