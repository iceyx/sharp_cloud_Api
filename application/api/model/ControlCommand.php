<?php
namespace app\api\model;

use think\Model;


/**
 * 控制指令
 */
class ControlCommand extends Model
{
	
	/**
	 * 获取状态
	 * @DateTime 2020-05-28T14:48:52+0800
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getStatus($where)
	{
		return ControlCommand::where($where)->field('CURRENT_STATUS')->find();
	}

	/**
	 * 获取信息
	 * @DateTime 2020-05-29T17:30:29+0800
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getCommand($where)
	{
		return ControlCommand::where($where)->find();
	}

	/**
	 * 添加
	 * @DateTime 2020-05-29T17:37:18+0800
	 * @param    string
	 */
	public static function add($data)
	{
		return ControlCommand::insert($data);
	}

	/**
	 * 修改
	 * @DateTime 2020-05-29T17:30:57+0800
	 * @param    string
	 */
	public static function update($where, $data)
	{
		return ControlCommand::where($where)->update($data);
	}
}