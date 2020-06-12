<?php
namespace app\api\model;

use think\Model;
use think\Db;

/**
 * 多功能表历史数据
 */
class MultimeterDataHistory extends AnotherClass
{
	
	/**
	 * 列表
	 * @DateTime 2020-05-29T15:01:16+0800
	 * @return   [type]
	 */
	public static function getList($where, $field)
	{
		return CipdDataHistory::where($where)->field($field)->order('TIME ASC')->select();
	}
	
}