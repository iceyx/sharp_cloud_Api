<?php
namespace app\api\model;

use think\Model;
use think\Db;

/**
 * 温控仪历史数据
 */
class TemperatureControllerDataHistory extends Model
{
	
	/**
	 * 列表
	 * @DateTime 2020-05-29T15:01:16+0800
	 * @return   [type]
	 */
	public static function getList($where, $field, $start, $end)
	{
		return TemperatureControllerDataHistory::whereBetweenTime('TIME', $start, $end)->where($where)->field($field)->order('TIME ASC')->select();
	}
}