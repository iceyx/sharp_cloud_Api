<?php
namespace app\api\model;

use think\Model;
use app\api\model\RoomModuleApparatus;
use think\Db;
/**
 * 
 */
class FaultInfo extends Model
{	

	/**
	 * 故障详情
	 * @DateTime 2020-05-27T17:10:43+0800
	 * @param    [type]
	 * @param    string
	 * @return   [type]
	 */
	public static function getDetail($id, $company_id = '')
	{	
		$info = FaultInfo::alias('f')
							->leftJoin(['sys_user u'], 'f.ELECTRICIAN_ID = u.USER_ID')
							->field('f.ID, f.OCCURRENCE_TIME, f.IP_ADDRESS, f.EQUIPMENT_NAME, f.STATUS, f.MONITORING_INFO, u.NAME')
							->where('f.ID',$id)
							->find();
		$up['IS_READING'] = 1;
		if ($company_id) {
			$company = Db::name('company')
						->where('ID', $company_id)
						->find();
			$info['COMPANY_NAME'] = $company['NAME'];
		}
		FaultInfo::where('ID', $id)->update($up);
		return $info;
	}
	
	/**
	 * @DateTime 2020-05-27T16:42:08+0800
	 * @param    [type]
	 * @param    boolean
	 * @return   [type]
	 */
	public static function getFaultByCompanyId($Company_id,$one = false)
	{
		
		$str = self::getfaultNum($Company_id);
		//$where['LINK_NUMBER'] = $str;
		//$where['IS_READING'] = '0';
		if ($one) {
			return self::getFaultOne($str);
		}else{
			$res = FaultInfo::alias('f')
						->leftJoin(['sys_user u'], 'f.ELECTRICIAN_ID = u.USER_ID')
						->whereIn('LINK_NUMBER',$str)
						->where('IS_READING','0')
						->field('u.NAME, f.*')
						->order('f.OCCURRENCE_TIME desc')
						->select();
			return count($res);
		}
		

	}

	/**
	 * 组装新数组
	 * @DateTime 2020-05-27T17:18:57+0800
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getNewArr($linkNumber)
	{	

		$str = $linkNumber ? implode(',', $linkNumber) : '-1';
		$faultlist = '';
		$faultlist = FaultInfo::whereIn('LINK_NUMBER', $str)
							->where('IS_READING', 0)
							->select();
		$status = 0;
		$num = 0;
		$faultid = 0;
		if (count($faultlist) != 0) {
			$status = 1;
			foreach ($faultlist as $key => $value) {
				if ($value['STATUS'] == 2) {
					$status = 2;
				}
				$num = $num + 1;
			}
			$faultid = $faultlist[0]['ID'];
		}else{
			$num = 0;
		}

		$arr['NUM'] = $num;
		$arr['FAULTID'] = $faultid;
		$arr['STATUS'] = $status;
		return $arr;

	}
	

	/**
	 * 
	 * @DateTime 2020-05-28T10:46:14+0800
	 * @param    string
	 */
	public static function getfaultNum($Company_id)
	{
		$transformer = RoomModuleApparatus::getApparatusNum($Company_id, '变压器', '3');//变压器仪器列表
		$protection = RoomModuleApparatus::getApparatusNum($Company_id, '高压柜', '0');//高压柜综保列表
		$faultInstrument = RoomModuleApparatus::getApparatusNum($Company_id, '高压柜', '4');//高压柜故障仪
		$lowInCabinet = RoomModuleApparatus::getApparatusNum($Company_id, '低压进线柜', '2');//低压进线柜多功能表
		$lowOutCabinet = RoomModuleApparatus::getApparatusNum($Company_id, '低压出线柜', '2');//低压出线柜多功能表
		$metering  = RoomModuleApparatus::getApparatusNum($Company_id, '计量柜', '1');//计量柜电度表
		$others = RoomModuleApparatus::getApparatusNum($Company_id, '其他', '2');//计量柜电度表

		//查询变压器故障列表
		$arr = [];
		if(!empty($transformer)) $arr = array_merge($arr, $transformer);
		if(!empty($protection)) $arr = array_merge($arr, $protection);
		if(!empty($faultInstrument)) $arr = array_merge($arr, $faultInstrument);
		if(!empty($lowInCabinet)) $arr = array_merge($arr, $lowInCabinet);
		if(!empty($lowOutCabinet)) $arr = array_merge($arr, $lowOutCabinet);
		if(!empty($metering)) $arr = array_merge($arr, $metering);
		if(!empty($others)) $arr = array_merge($arr, $others);
		$str = $arr ? implode(',', $arr) : '-1';
		return $str;
	}


	/**
	 * 故障列表
	 * @DateTime 2020-05-27T10:38:01+0800
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getFaultOne($where)
	{	
		return FaultInfo::alias('f')
						->leftJoin(['sys_user u'], 'f.ELECTRICIAN_ID = u.USER_ID')
						->whereIn('LINK_NUMBER',$where)
						->where('IS_READING','0')
						->field('u.NAME, f.*')
						->order('f.OCCURRENCE_TIME desc')
						->find();
	}



	/**
	 * @DateTime 2020-05-28T11:54:02+0800
	 * @return   [type]
	 */
	public static function faultCount($where)
	{
		return FaultInfo::where($where)->count();
	}
}