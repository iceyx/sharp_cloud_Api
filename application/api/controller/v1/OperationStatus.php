<?php
namespace app\api\controller\v1;

use think\Request;
use app\api\controller\Send;
use app\api\controller\Api;
use app\api\common\Page;
use app\api\validate\ValidataCommon;
use think\Db;
use app\api\model\FaultInfo;
use app\api\model\RoomModuleApparatus;
use app\api\model\TranRoomModule;

/**
 * 
 */
class OperationStatus extends Api
{	

	/**
	 * 公司状况运行状况故障详情
	 * @DateTime 2020-05-27T19:05:51+0800
	 * @return   [type]
	 */
	public function detail()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		$transformer = RoomModuleApparatus::getApparatusNum($param['COMPANY_ID'], '变压器', '3');//变压器仪器列表
		$protection = RoomModuleApparatus::getApparatusNum($param['COMPANY_ID'], '高压柜', '0');//高压柜综保列表
		$faultInstrument = RoomModuleApparatus::getApparatusNum($param['COMPANY_ID'], '高压柜', '4');//高压柜故障仪
		$lowInCabinet = RoomModuleApparatus::getApparatusNum($param['COMPANY_ID'], '低压进线柜', '2');//低压进线柜多功能表
		$lowOutCabinet = RoomModuleApparatus::getApparatusNum($param['COMPANY_ID'], '低压出线柜', '2');//低压出线柜多功能表
		$metering  = RoomModuleApparatus::getApparatusNum($param['COMPANY_ID'], '计量柜', '1');//计量柜电度表
		$empty = ['NUM'=> 0,'FAULTID'=> '', 'STATUS' => 0];
		$arr['byq'] = $transformer ? FaultInfo::getNewArr($transformer) : $empty;
		$arr['gyg'] = $protection ? FaultInfo::getNewArr($protection) : $empty;
		$arr['dyj'] = $lowInCabinet ? FaultInfo::getNewArr($lowInCabinet) : $empty;
		
		$arr['dyc'] = $lowOutCabinet ? FaultInfo::getNewArr($lowOutCabinet) : $empty;
		$arr['jlg'] = $metering ?  FaultInfo::getNewArr($metering) : $empty;

		$metering_info = FaultInfo::getNewArr($metering);

		$module_id = TranRoomModule::getModuleId($param['COMPANY_ID'], '计量柜');

		if ($module_id) {
			$month = Db::name('meas_day')
						->field('sum(ACTIVE_ELECTRIC_QUANTITY) as month')
						->where('MODULE_ID','eq', $module_id)
						->whereTime('STATISTICS_TIME', 'm')
						->select();
			$day = Db::name('meas_day')
						->field('sum(ACTIVE_ELECTRIC_QUANTITY) as month')
						->where('MODULE_ID','eq', $module_id)
						->whereTime('STATISTICS_TIME', 'd')
						->select();
			$str = $metering ? implode(',', $metering) : '-1';

			//实时查询电度表功率
			$active_power = Db::name('electricity_meter_data_ct')
								->whereIn('LINK_NUMBER', $str)
								->order('TIME desc')
								->group('LINK_NUMBER')
								->select();
			$active_powers = 0;
			foreach ($active_power as $key => $value) {
				$active_powers+=$value['ACTIVE_POWER'];
			}

			$arr['jl1']['active_power'] = $active_powers ? sprintf("%.2f", $active_powers) : 0.00;
			$arr['jl1']['day_quantity'] = $day[0]['month'] ? sprintf("%.2f", $day[0]['month']) : 0.00;
			$arr['jl1']['month_quantity'] = $month[0]['month'] ? sprintf("%.2f", $month[0]['month']) : 0.00;
		}

		$module_low = TranRoomModule::getModuleId($param['COMPANY_ID'], '低压进线柜');

		if ($module_low) {
			$month = Db::name('meas_day')
						->field('sum(ACTIVE_ELECTRIC_QUANTITY) as month')
						->where('MODULE_ID','eq', $module_low)
						->whereTime('STATISTICS_TIME', 'm')
						->select();
			$day = Db::name('meas_day')
						->field('sum(ACTIVE_ELECTRIC_QUANTITY) as month')
						->where('MODULE_ID','eq', $module_low)
						->whereTime('STATISTICS_TIME', 'd')
						->select();
			$str = $lowInCabinet ? implode(',', $lowInCabinet) : '-1';

			//实时查询电度表功率
			$active_power1 = Db::name('electricity_meter_data_ct')
								->whereIn('LINK_NUMBER', $str)
								->order('TIME desc')
								->group('LINK_NUMBER')
								->select();
			$active_powers1 = 0;
			foreach ($active_power1 as $key => $value) {
				$active_powers1+=$value['ACTIVE_POWER'];
			}

			$arr['jl2']['active_power'] = $active_powers1 ? sprintf("%.2f", $active_powers1) : 0.00;
			$arr['jl2']['day_quantity'] = $day[0]['month'] ? sprintf("%.2f", $day[0]['month']) : 0.00;
			$arr['jl2']['month_quantity'] = $month[0]['month'] ? sprintf("%.2f", $month[0]['month']) : 0.00;
		}

		return self::returnMsg(200, 'success', $arr);

	}
	
	/**
	 * 运行状况-故障详情
	 * @DateTime 2020-05-27T17:10:31+0800
	 * @return   [type]
	 */
	public function faultDetail()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['ID' => 'require'], $this->request->param('')); //参数验证
		$res = FaultInfo::getDetail($param['ID'], $param['COMPANY_ID']);
		return self::returnMsg(200, 'success', $res);
	}


	/**
	 * 异常统计
	 * @DateTime 2020-05-28T09:00:40+0800
	 * @return   [type]
	 */
	public function exceptionStatistic()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		$arr[0]=array('COMPANY_ID'=>$param['COMPANY_ID'],"TYPE"=>"0","STATUS"=>0,"NAME"=>"线损");
		$arr[1]=array('COMPANY_ID'=>$param['COMPANY_ID'],"TYPE"=>"1","STATUS"=>0,"NAME"=>"功率因数");
		$arr[2]=array('COMPANY_ID'=>$param['COMPANY_ID'],"TYPE"=>"2","STATUS"=>0,"NAME"=>"电流超限");
		$arr[3]=array('COMPANY_ID'=>$param['COMPANY_ID'],"TYPE"=>"3","STATUS"=>0,"NAME"=>"变压器超温");
		$arr[4]=array('COMPANY_ID'=>$param['COMPANY_ID'],"TYPE"=>"4","STATUS"=>0,"NAME"=>"三相不平衡");
		$arr[5]=array('COMPANY_ID'=>$param['COMPANY_ID'],"TYPE"=>"5","STATUS"=>0,"NAME"=>"变压器负载不匹配");
		$arr[6]=array('COMPANY_ID'=>$param['COMPANY_ID'],"TYPE"=>"6","STATUS"=>0,"NAME"=>"窃电");
		$arr[7]=array('COMPANY_ID'=>$param['COMPANY_ID'],"TYPE"=>"7","STATUS"=>0,"NAME"=>"谐波");
		$arr[8]=array('COMPANY_ID'=>$param['COMPANY_ID'],"TYPE"=>"8","STATUS"=>0,"NAME"=>"电压超限");
		$arr[9]=array('COMPANY_ID'=>$param['COMPANY_ID'],"TYPE"=>"9","STATUS"=>0,"NAME"=>"环境温湿度");
		$typearr = Db::name('exception')
						->field('TYPE, count(*) as STATUS')
						->where('COMPANY_ID', 'eq', $param['COMPANY_ID'])
						->whereTime('time','m')
						->group('TYPE')
						->select();
		foreach ($typearr as $t_key => $t_val) {
			foreach ($arr as $key => $value) {
				if ($typearr[$t_key]['TYPE'] == $arr[$key]['TYPE']) {
					$arr[$key]['STATUS'] = '1';
					break;
				}
			}
		}

		$metering  = RoomModuleApparatus::getApparatusNum($param['COMPANY_ID'], '计量柜', '1');//计量柜电度表

		$month = Db::name('ceas_day')
						->field('sum(ACTIVE_ELECTRIC_QUANTITY) as month')
						->where('COMPANY_ID','eq', $param['COMPANY_ID'])
						->whereTime('STATISTICS_TIME', 'm')
						->select();
			$day = Db::name('ceas_day')
						->field('sum(ACTIVE_ELECTRIC_QUANTITY) as month')
						->where('COMPANY_ID','eq', $param['COMPANY_ID'])
						->whereTime('STATISTICS_TIME', 'd')
						->select();
			$str = $metering ? implode(',', $metering) : '-1';

			//实时查询电度表功率
			$active_power = Db::name('electricity_meter_data_ct')
								->field('ACTIVE_POWER,LINK_NUMBER')
								->whereIn('LINK_NUMBER', $str)
								->order('TIME desc')
								->group('LINK_NUMBER')
								->select();
			$active_powers = 0;
			foreach ($active_power as $key => $value) {
				$active_powers+=$value['ACTIVE_POWER'];
			}
			$res['type'] = $arr;
			$res['data']['active_power'] = $active_powers ? sprintf("%.2f", $active_powers) : 0.00;
			$res['data']['day_quantity'] = $day[0]['month'] ? sprintf("%.2f", $day[0]['month']) : 0.00;
			$res['data']['month_quantity'] = $month[0]['month'] ? sprintf("%.2f", $month[0]['month']) : 0.00;
			array_case($res);
			return self::returnMsg(200, 'success', $res);
	}


	/**
	 * 异常详情
	 * @DateTime 2020-05-28T09:43:18+0800
	 * @return   [type]
	 */
	public function exceptionDetail()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		$type = $param['TYPE'] ? $param['TYPE'] : 0;

		$where['TYPE'] = $type;
		//默认开始日期从 去年今天开始
		$START_TIME = date('Y-m-d H:i:s',($param['START_TIME'] ? strtotime($param['START_TIME']) : strtotime('-1 year')));
		//结束时间当前时间 
		$END_TIME = date('Y-m-d H:i:s',($param['END_TIME'] ? strtotime($param['END_TIME'])+86400 : time()));
		$OFFSET = $param['OFFSET'] ? $param['OFFSET'] : 20;
		$LIMIT = $param['LIMIT'] ? $param['LIMIT'] : 0;
		$where['STATUS'] = 1;
		$res = Db::name('exception')
					->field('NAME, STATUS, CAUSE, TIME, ID, TYPE, COMPANY_ID')
					->where($where)
					->whereTime('TIME', [$START_TIME, $END_TIME])
					->order('time desc')
					->limit($LIMIT, $OFFSET)
					->select();
		return $res ? self::returnMsg(200, 'success', $res) :self::returnMsg(300, '无数据！', null);
	}


	/**
	 * 故障信息
	 * @DateTime 2020-05-28T11:01:48+0800
	 * @param    string
	 * @return   [type]
	 */
	public function faultList()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		$str = FaultInfo::getfaultNum($param['COMPANY_ID']);

		//默认开始日期从 去年今天开始
		$START_TIME = date('Y-m-d H:i:s',($param['START_TIME'] ? strtotime($param['START_TIME']) : strtotime('-1 year')));
		//结束时间当前时间 
		$END_TIME = date('Y-m-d H:i:s',($param['END_TIME'] ? strtotime($param['END_TIME'])+86400 : time()));
		$where = [];
		$whereIn = [];
		$type = $param['OCCURRENCE_TYPE'] ? intval($param['OCCURRENCE_TYPE']) : '1,2,3,4';
		$OFFSET = $param['OFFSET'] ? $param['OFFSET'] : 20;
		$LIMIT = $param['LIMIT'] ? $param['LIMIT'] : 0;

		$company = Db::name('company')
						->where('ID', 'eq', $param['COMPANY_ID'])
						->field('NAME')
						->find();
		$faultlist = Db::name('fault_info')
						->alias('f')
						->leftJoin(['sys_user u'], 'f.ELECTRICIAN_ID = u.USER_ID')
						->where($where)
						->whereIn('LINK_NUMBER', $str)
						->whereIn('OCCURRENCE_TYPE', $type)
						->whereTime('OCCURRENCE_TIME', [$START_TIME, $END_TIME])
						->order('f.OCCURRENCE_TIME desc')
						->limit($LIMIT, $OFFSET)
						->select();
		foreach ($faultlist as $key => $value) {
			$faultlist[$key]['COMPANY_NAME'] = $company['NAME'];
		}

		return $faultlist ? self::returnMsg(200, 'success', $faultlist) :self::returnMsg(300, '无数据！', null);

	}



}