<?php
namespace app\api\model;

use think\Model;
use think\Db;

/**
 * 电度表
 */
class ElectricityMeterDataHistory extends Model
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
		return ElectricityMeterDataHistory::whereBetweenTime('TIME',$start, $end)->where($where)->field($field)->select();
	}

	/**
	 * 获取自定分类列表
	 * @DateTime 2020-05-29T11:59:46+0800
	 * @param    [type]
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getList($where, $field, $start, $end)
	{
		return ElectricityMeterDataHistory::whereBetweenTime('TIME',$start, $end)->where($where)->field($field)->order('TIME ASC')->select();
	}

	/**
	 * 获取昨天的数据
	 * @DateTime 2020-05-29T11:59:29+0800
	 * @param    string
	 * @return   [type]
	 */
	public static function getYesterday($where, $field)
	{
		return ElectricityMeterDataHistory::whereTime('TIME','yesterday')->field($field)->order('TIME ASC')->select();
	}
}