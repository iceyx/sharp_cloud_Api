<?php
namespace app\api\model;

use think\Model;
use think\Db;

/**
 * 故障仪历史数据表
 */
class FaultMeterDataHistory extends Model
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