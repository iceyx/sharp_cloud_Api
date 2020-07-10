<?php
namespace app\api\model;

use think\Model;
use think\Db;
/**
 * 
 */
class RoomModuleApparatus extends Model
{	
	protected $table = 'tran_room_module_apparatus';

	/**
	 * 获取监测点
	 * [getPoint description]
	 * @DateTime 2020-06-08T18:46:33+0800
	 * @return   [type]                   [description]
	 */
	public static function getPoint()
	{
		return Db::table('tran_room_module_apparatus')
							->alias('trma')
							->leftJoin(['tran_room_module trm'], "trma.ROOM_MODULE_ID = trm.ID")
							->group('trma.ID')
							->count();
	}
	
	/**
	 * 获取公司仪器编号
	 * @DateTime 2020-05-27T10:06:07+0800
	 * @param    string
	 * @return   [type]
	 */
	public static function getApparatusNum($company_id, $type_name = '', $apparatus_type = '', $list = false)
	{
		$where['t.COMPANY_ID'] = ['eq',$company_id];
		$where['s.NAME'] = ['like', $type_name];

		$list = Db::table('tran_room_module')
					->alias('t')
					->leftJoin(['sys_dictionaries s'], 't.PID = s.DICTIONARIES_ID')
					->where($where)
					->find();
		$module_id = $list['ID'];
		$where1['t.ROOM_MODULE_ID'] = ['eq', $module_id];
		switch ($apparatus_type) {
			case '0':
				
				$where['c.ID'] = ['exp', 'IS NOT NULL'];
				$res = RoomModuleApparatus::alias('t')
											->leftJoin('cipd c', 'c.ID = t.APPARATUS_ID')
											->where($where1)
											->field('c.NAME, c.LINK_NUMBER')
											->select()->toArray();
				break;

			case '1':
				$where['e.ID'] = ['exp', 'IS NOT NULL'];
				$res = RoomModuleApparatus::alias('t')
											->leftJoin('electricity_meter e', 'e.ID = t.APPARATUS_ID')
											->where($where1)
											->field('e.NAME, e.LINK_NUMBER')
											->select()->toArray();
				break;
			case '2':
				$where['m.ID'] = ['exp', 'IS NOT NULL'];
				$res = RoomModuleApparatus::alias('t')
											->leftJoin('multimeter m', 'm.ID = t.APPARATUS_ID')
											->where($where1)
											->field('m.NAME, m.LINK_NUMBER')
											->select()->toArray();
				break;
			case '3':
				$where['w.ID'] = ['exp', 'IS NOT NULL'];
				$res = RoomModuleApparatus::alias('t')
											->leftJoin('temperature_controller w', 'w.ID = t.APPARATUS_ID')
											->where($where1)
											->field('w.NAME, w.LINK_NUMBER')
											->select()->toArray();
				break;

			case '4':
				$where['f.ID'] = ['exp', 'IS NOT NULL'];
				$res = RoomModuleApparatus::alias('t')
											->leftJoin('fault_meter f', 'f.ID = t.APPARATUS_ID')
											->where($where1)
											->field('f.NAME, f.LINK_NUMBER')
											->select()->toArray();
				break;
			
			default:
				$res = '';
				break;
		}
		$arr = '';
		foreach ($res as $key => $value) {
			$arr[] = $value['LINK_NUMBER'];
		}
		return $arr;
	}
}