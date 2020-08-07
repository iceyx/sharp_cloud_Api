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
		//>limit($LIMIT * ($OFFSET - 1), $LIMIT)
		return Esqr::where($where)->order('START_TIME', 'desc')->select()->toArray();
	}


	/**
	 * 添加
	 * @DateTime 2020-06-01T09:28:29+0800
	 * @param    [type]
	 */
	public static function add($data)
	{
		Esqr::insertGetId($data);
		return true;
	}


	/**
	 * 删除
	 * @DateTime 2020-06-01T09:30:36+0800
	 * @param    [type]
	 * @return   [type]
	 */
	public static function notSoftdelete($where)
	{
		return Esqr::where($where)->delete();
	}

}