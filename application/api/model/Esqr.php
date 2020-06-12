<?php
namespace app\api\model;

use think\Model;

/**
 * 节能分析查询记录表
 */
class Esqr extends Model
{

	/**
	 * 获取负荷分析列表
	 * @DateTime 2020-05-29T18:21:59+0800
	 * @param    [type]
	 * @param    [type]
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getList($where, $LIMIT, $OFFSET)
	{
		return Esqr::where($where)->limit($LIMIT, $OFFSET)->select();
	}


	/**
	 * 添加
	 * @DateTime 2020-06-01T09:28:29+0800
	 * @param    [type]
	 */
	public static function add($data)
	{
		return Esqr::insertGetId($data);
	}


	/**
	 * 删除
	 * @DateTime 2020-06-01T09:30:36+0800
	 * @param    [type]
	 * @return   [type]
	 */
	public static function delete($where)
	{
		return Esqr::where($where)->delete();
	}

}