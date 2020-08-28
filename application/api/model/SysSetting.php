<?php
namespace app\api\model;


use app\lib\exception\BaseException;
use think\Model;

class SysSetting extends Model
{
    protected $table = 'sys_app_setting';
    protected $hidden = [''];

    public static function getSetting()
    {
    	$res = self::where('id','>=',1)->find();
		$data['guide_info'] = [
			'start_pic_url' => 'http://47.107.100.27:8080/ytgz/uploadFiles/uploadImgs/'.$res['START_PIC_URL'],
			'login_pic_url' => 'http://47.107.100.27:8080/ytgz/uploadFiles/uploadImgs/'.$res['LOGIN_PIC_URL'],
			'introduce' => escapeJsonString($res['INTRODUCE']),
			'map_extent' => $res['MAP_EXTENT']
		];
		$data['app_info'] = ['version'=> '1.0.1','update_msg'=>''];
		return $data;
    }
}