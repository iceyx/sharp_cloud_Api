<?php
namespace app\api\model;

use think\Model;
use think\Db;

/**
 * 多功能表历史数据
 */
class MultimeterDataHistory extends Model
{
	

	/**
	 * 获取平均值,最大值,最小值
	 * @DateTime 2020-05-29T12:00:08+0800
	 * @param    [type]
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getValue($where, $field, $start, $end)
	{
		return MultimeterDataHistory::whereBetweenTime('TIME',$start, $end)->where($where)->field($field)->select();
	}

	/**
	 * 列表
	 * @DateTime 2020-05-29T15:01:16+0800
	 * @return   [type]
	 */
	public static function getList($where, $field, $start, $end)
	{
		return MultimeterDataHistory::whereBetweenTime('TIME',$start, $end)->where($where)->field($field)->order('TIME ASC')->select();
	}
	
}