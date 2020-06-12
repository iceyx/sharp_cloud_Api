<?php
namespace app\api\model;

use think\Model;

/**
 * 
 */
class Notice extends Model
{
	protected $hidden = [''];

	public static function getNotice($where)
	{
		return self::where($where)->order('RELEASE_TIME DESC')->field('ID, TITLE')->limit(1)->find();
	}
	
}