<?php
namespace app\api\model;

use think\Model;
use think\Db;

/**
 * 
 */
class TranRoomModule extends Model
{
	protected $table = 'tran_room_module';
	/**
	 * 获取组件id
	 * @DateTime 2020-05-27T17:42:32+0800
	 * @param    [type]
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getModuleId($company_id, $type_name)
	{
		$where['t.COMPANY_ID'] = $company_id;
		$where['d.NAME'] = $type_name;
		$list = TranRoomModule::alias('t')
								->leftJoin(['sys_dictionaries d'], 't.PID = d.DICTIONARIES_ID')
								->field('t.ID')
								->where($where)
								->find();
		return $list['ID'];						
	}
}