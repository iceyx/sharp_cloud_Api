<?php
namespace app\api\controller\v1;

use think\Request;
use app\api\controller\Send;
use app\api\controller\Api;
use app\api\validate\ValidataCommon;
use think\Db;
use app\api\model\FaultInfo;
use app\api\model\RoomModuleApparatus;
use app\api\model\ControlCommand;
use app\api\model\ElectricityMeterDataHistory;
use app\api\model\CipdDataHistory;
use app\api\model\FaultMeterDataHistory;
use app\api\model\TemperatureControllerDataHistory;
use app\api\model\MultimeterDataHistory;
use app\api\model\User;

/**
 * 
 */
class DataQuery extends Api
{
	/**
	 * 数据查询(仪表列表)
	 * @DateTime 2020-05-28T11:58:59+0800
	 * @return   [type]
	 */
	public function getEquitment()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		ValidataCommon::validateCheck(['CATEGORY' => 'require'], $this->request->param('')); //参数验证
		$company_id = $param['COMPANY_ID'];
		$arr = [];
		switch ($param['CATEGORY']) {
			case '计量柜':
				$res = RoomModuleApparatus::getApparatusNum($company_id, '计量柜', '1', true);
				if(count($res) == 0) return self::returnMsg(300, '无数据', null);
				foreach ($res as $key => $value) {
					$where['LINK_NUMBER'] = $value['LINK_NUMBER'];
					$where['IS_READING'] = 0;
					$number = FaultInfo::faultCount($where);
					$res[$key]['STATUS'] = $number;
					}
				
				$arr = $res;
				
				break;
			case '高压柜':
				//高压柜故障仪
				$res = RoomModuleApparatus::getApparatusNum($company_id, '高压柜', '4', true);
				if (count($res) != 0) {
					foreach ($res as $k => $v) {
						$where['LINK_NUMBER'] = $v['LINK_NUMBER'];
						$wheres['LINK_NUMBER'] = $v['LINK_NUMBER'];
						$wheres['IS_READING'] = 0;
						$number = FaultInfo::faultCount($wheres);
						$current_status  = ControlCommand::getStatus($where);
						$res[$k]['STATUS'] = $number;
						$res[$k]['CURRENT_STATUS'] = $current_status;
					}
				}
				
				
				//高压柜综保列表
				$reszb = RoomModuleApparatus::getApparatusNum($company_id, '高压柜', '0', true);
				if (count($res) == 0 && count($reszb) == 0) {
					return self::returnMsg(300, '无数据！', null);
				}

				$where['IS_READING'] = 0;
				foreach ($reszb as $key => $value) {
					$where1['LINK_NUMBER'] = $value['LINK_NUMBER'];
					$wheres1['LINK_NUMBER'] = $value['LINK_NUMBER'];
					$numbers = FaultInfo::faultCount($where);
					$reszb[$key]['STATUS'] = $numbers;
					$current_status1  = ControlCommand::getStatus($where1);
					$reszb[$key]['STATUS'] = $numbers;
					$reszb[$key]['CURRENT_STATUS'] = $current_status1;
				}
				
				$arr = ['gzy' => $res, 'zb'=> $reszb];
				
				break;

			case '变压器':
				$res = RoomModuleApparatus::getApparatusNum($company_id, '变压器', '3', true);
				if(count($res) == 0) return self::returnMsg(300, '无数据', null);
				$where['IS_READING'] = 0;
				foreach ($res as $key => $value) {
					$where['LINK_NUMBER'] = $value['LINK_NUMBER'];
					$number = FaultInfo::faultCount($where);
					$res[$key]['STATUS'] = $number;
				}
				$arr = $res;
				break;

			case '低压进线柜':
				$res = RoomModuleApparatus::getApparatusNum($company_id, '低压进线柜', '2', true);
				if(count($res) == 0) return self::returnMsg(300, '无数据', null);
				$where['IS_READING'] = 0;
				foreach ($res as $key => $value) {
					$where['LINK_NUMBER'] = $value['LINK_NUMBER'];
					$number = FaultInfo::faultCount($where);
					$res[$key]['STATUS'] = $number;
				}
				$arr = $res;
				break;
			case '低压出线柜':
				$res = RoomModuleApparatus::getApparatusNum($company_id, '低压出线柜', '2', true);
				if(count($res) == 0) return self::returnMsg(300, '无数据', null);
				$where['IS_READING'] = 0;
				foreach ($res as $key => $value) {
					$where['LINK_NUMBER'] = $value['LINK_NUMBER'];
					$number = FaultInfo::faultCount($where);
					$res[$key]['STATUS'] = $number;
				}
				$arr = $res;
				break;
			case '其他':
				$res = RoomModuleApparatus::getApparatusNum($company_id, '其他', '2', true);
				if(count($res) == 0) return self::returnMsg(300, '无数据', null);
				$where['IS_READING'] = 0;
				foreach ($res as $key => $value) {
					$where['LINK_NUMBER'] = $value['LINK_NUMBER'];
					$number = FaultInfo::faultCount($where);
					$res[$key]['STATUS'] = $number;
				}
				$arr = $res;
				break;
			
			default:
				$arr = [];
				break;
		}
		return self::returnMsg(200, 'success', $arr);

	}


	/**
	 * 获取仪表数据
	 * @DateTime 2020-05-28T15:58:14+0800
	 * @return   [type]
	 */
	public function getDataList()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['LINK_NUMBER' => 'require'], $this->request->param('')); //参数验证设备编码
		ValidataCommon::validateCheck(['HIS_NOW' => 'require'], $this->request->param('')); //参数验证历史数据还是实时数据
		ValidataCommon::validateCheck(['CATEGORY' => 'require'], $this->request->param('')); //参数验证分类


		//暂时这样处理，以后再优化。1、2的差距只有时间查询条件
		if ($param['HIS_NOW'] == 1) {
			$list = $this->realTimeData($param);
		}
			
		if ($param['HIS_NOW'] == 2) {
			$list = $this->historyData($param);
		}

		$array=[
			'CURRENT_A'=>'A相电流',
			'CURRENT_B'=>'B相电流',
			'CURRENT_C'=>'C相电流',
			'VOLTAGE_A'=>'A相电压',
			'VOLTAGE_B'=>'B相电压',
			'VOLTAGE_C'=>'C相电压',
			'POWER_FACTOR'=>'功率因数',
			'ACTIVE_ELECTRICITY'=>'有功电量',
			'REACTIVE_ELECTRICITY'=>'无功电量',
			'ACTIVE_POWER'=>'有功功率',
			'REACTIVE_POWER'=>'无功功率',
			'FREQUENCY'=>'频率',
			'TEMPERATURE_A'=>'A相温度(℃)',
			'TEMPERATURE_B'=>'B相温度(℃)',
			'TEMPERATURE_C'=>'C相温度(℃)',
			'STARTING_STATUS'=>'风机启动状态',
			'CLOSED_STATUS'=>'继电器闭合状态',
			'HARMONIC_CURRENT_A'=>'三相谐波电流A',
			'HARMONIC_CURRENT_B'=>'三相谐波电流B',
			'HARMONIC_CURRENT_C'=>'三相谐波电流C',
			'HARMONIC_VOLTAGE_A'=>'三相谐波电压A',
			'HARMONIC_VOLTAGE_B'=>'三相谐波电压B',
			'HARMONIC_VOLTAGE_C'=>'三相谐波电压C',
			'DEMAND'=>'需量',
			'BREAK_STATUS'=>'断路器状态',
			'STATUS_A'=>'A相状态',
			'STATUS_B'=>'B相状态',
			'STATUS_C'=>'C相状态'
		];

		foreach ($list as $key => $value) {
			$arr[$key]['time'] = $value['TIME'];
			foreach ($value as $k => $v) {
				if ($k != 'TIME' && $k != 'LINK_NUMBER') {
					if ($k == 'STATUS_A' || $k == 'STATUS_B' || $k == 'STATUS_C') {
						$v = $v ? '异常' : '正常';
					}elseif ($k == 'STARTING_STATUS' || $k == 'CLOSED_STATUS') {
						$v = $v ? '开启' : '关闭';
					}

					if (is_numeric($v)) {
						$arr[$key]['list'][] = ['name' => $array[$k], 'value' => sprintf("%.2f", $v)];
					}else{
						$arr[$key]['list'][] = ['name' => $array[$k], 'value' =>$v];
					}
					

					// if ($param['CATEGORY'] == '计量柜') {
					// 	if ($param['TYPE'] == '有功功率' || $param['TYPE'] == '无功功率') {
					// 		$arr[$key]['list'][] = ['name' => $array[$k], 'value' => sprintf("%.2f", $v)];
					// 	}elseif ($param['TYPE'] == '有功电量' || $param['TYPE'] == '无功电量') {
					// 		$arr[$key]['list'][] = ['name' => $array[$k], 'value' => sprintf("%.2f", $v)];
					// 	}else{
					// 		$arr[$key]['list'][] = ['name' => $array[$k], 'value' => sprintf("%.2f", $v)];
					// 	}
					// }else{
					// 	if (is_numeric($v)) {
					// 		$arr[$key]['list'][] = ['name' => $array[$k], 'value' => sprintf("%.2f", $v)];
					// 	}else{
					// 		$arr[$key]['list'][] = ['name' => $array[$k], 'value' =>$v];
					// 	}
					// }
				}
			}
		}

		return self::returnMsg(200, 'success', $arr);

	}


	/**
	 * 图表接口
	 * @DateTime 2020-05-29T16:58:38+0800
	 * @return   [type]
	 */
	public function figure()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['LINK_NUMBER' => 'require'], $this->request->param('')); //参数验证设备编码
		ValidataCommon::validateCheck(['HIS_NOW' => 'require'], $this->request->param('')); //参数验证历史数据还是实时数据
		ValidataCommon::validateCheck(['CATEGORY' => 'require'], $this->request->param('')); //参数验证分类

		if ($param['HIS_NOW'] == 1) {
			$START_TIME = date("Y-m-d H:i:s",mktime(0,0,0,date("m"),date("d"),date("Y")));
			$END_TIME = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d"),date("Y")));
		}
		if ($param['HIS_NOW'] == 2) {
			//默认开始日期从 去年今天开始
			$START_TIME = date('Y-m-d H:i:s',($param['START_TIME'] ? strtotime($param['START_TIME']) : strtotime('-4 year')));
			//结束时间当前时间 
			$END_TIME = date('Y-m-d H:i:s',($param['END_TIME'] ? strtotime($param['END_TIME'])+86400 : time()));
		}
		
		$where['TIME'] = ['between', $START_TIME, $END_TIME];
		$field = 'TIME, LINK_NUMBER';
		$where['LINK_NUMBER'] = $param['LINK_NUMBER'];
		switch ($param['CATEGORY']) {
			case '计量柜':
				
				ValidataCommon::validateCheck(['TYPE' => 'require'], $this->request->param('')); //参数验证类型
				$xAxisUnit = '时间:时';

				switch ($param['TYPE']) {
					case '三相电流':
						$field = $field.",CURRENT_A, CURRENT_B, CURRENT_C, date_format(TIME,'%H:%i') as TIME1";
						$yAxisUnit = "电流: A";
						$fields = 'max(CURRENT_A) as MAX_CURRENT_A,max(CURRENT_B) as MAX_CURRENT_B,max(CURRENT_C) as MAX_CURRENT_C,min(CURRENT_A) as MIN_CURRENT_A,min(CURRENT_B) as MIN_CURRENT_B,min(CURRENT_C) as MIN_CURRENT_C,avg(CURRENT_A) as AVG_CURRENT_A,avg(CURRENT_B) as AVG_CURRENT_B,avg(CURRENT_C) as AVG_CURRENT_C';
						//最大值查询//最小值查询//平均值查询
						$res = ElectricityMeterDataHistory::getValue($where, $fields);
						$max = is_array($res[0]) ? max($res[0]) : 0;
						$min = is_array($res[0]) ? min($res[0]) : 0;
						$avgres = round(($res[0]['AVG_CURRENT_A'] + $res[0]['AVG_CURRENT_B'] + $res[0]['AVG_CURRENT_C']) / 3, 2);
						$avg['max'] = sprintf("%.2f", $max);
						$avg['min'] = sprintf("%.2f", $min);
						$avg['avg'] = sprintf("%.2f", $avgres);

						$list = ElectricityMeterDataHistory::getList($where, $field);
						$arr[0]['name'] = 'A相电流';
						$arr[1]['name'] = 'B相电流';
						$arr[2]['name'] = 'C相电流';
						$arr[0]['list'] = [];
						$arr[1]['list'] = [];
						$arr[2]['list'] = [];
						$xAxis = [];
						foreach ($list as $key => $value) {
							$arr[0]['list'][] = $val['CURRENT_A'];
							$arr[1]['list'][] = $val['CURRENT_B'];
							$arr[2]['list'][] = $val['CURRENT_C'];
							$xAxis[] = $val['TIME1'];
						}
						break;

					case '三相电压':
						$field = $field.",VOLTAGE_A, VOLTAGE_B, VOLTAGE_C, date_format(TIME,'%H:%i') as TIME1";
						$yAxisUnit = "电压: V";

						$fields = "max(VOLTAGE_A) as MAX_VOLTAGE_A,max(VOLTAGE_B) as MAX_VOLTAGE_B,max(VOLTAGE_C) as MAX_VOLTAGE_C,min(VOLTAGE_A) as MIN_VOLTAGE_A,min(VOLTAGE_B) as MIN_VOLTAGE_B,min(VOLTAGE_C) as MIN_VOLTAGE_C,avg(VOLTAGE_A) as AVG_VOLTAGE_A,avg(VOLTAGE_B) as AVG_VOLTAGE_B,avg(VOLTAGE_C) as VOLTAGE_C";
						//最大值查询//最小值查询//平均值查询
						$res = ElectricityMeterDataHistory::getValue($where, $fields);
						$max = is_array($res[0]) ? max($res[0]) : 0;
						$min = is_array($res[0]) ? min($res[0]) : 0;
						$avgres = round(($res[0]['AVG_VOLTAGE_A'] + $res[0]['AVG_VOLTAGE_B'] + $res[0]['AVG_VOLTAGE_C']) / 3, 2);
						$avg['max'] = sprintf("%.2f", $max);
						$avg['min'] = sprintf("%.2f", $min);
						$avg['avg'] = sprintf("%.2f", $avgres);

						$list = ElectricityMeterDataHistory::getList($where, $field);
						$arr[0]['name'] = 'A相电压';
						$arr[1]['name'] = 'B相电压';
						$arr[2]['name'] = 'C相电压';
						$arr[0]['list'] = [];
						$arr[1]['list'] = [];
						$arr[2]['list'] = [];
						$xAxis = [];
						foreach ($list as $key => $value) {
							$arr[0]['list'][] = $val['VOLTAGE_A'];
							$arr[1]['list'][] = $val['VOLTAGE_B'];
							$arr[2]['list'][] = $val['VOLTAGE_C'];
							$xAxis[] = $val['TIME1'];
						}
						break;

					case '功率因数':
						$field = $field.",POWER_FACTOR,date_format(TIME,'%H:%i') as TIME1";
						$yAxisUnit="因数: ";

						$fields = "max(POWER_FACTOR) as MAX_POWER_FACTOR,min(POWER_FACTOR) as MIN_POWER_FACTOR,avg(POWER_FACTOR) as AVG_POWER_FACTOR";
						//最大值查询//最小值查询//平均值查询
						$res = ElectricityMeterDataHistory::getValue($where, $fields);
						$avgres = $res[0]['AVG_POWER_FACTOR'];
						$avg['max'] = $res[0]['MAX_POWER_FACTOR'];
						$avg['min'] = $res[0]['MIN_POWER_FACTOR'];
						$avg['avg'] = sprintf("%.2f", $avgres);

						$list = ElectricityMeterDataHistory::getList($where, $field);
						$arr[0]['name']="功率因数";
						$arr[0]['list'] = [];
						$xAxis = [];
						foreach ($list as $key => $value) {
							$arr[0]['list'][]=$val['POWER_FACTOR'];
							$xAxis[]=$val['TIME1'];
						}

						break;

					case '有功电量':
						$field = $field.",ACTIVE_ELECTRICITY,date_format(TIME,'%H:%i') as TIME1";
						$yAxisUnit = "有功电量: KWH";
						$fields = "max(ACTIVE_ELECTRICITY) as MAX_ACTIVE_ELECTRICITY,min(ACTIVE_ELECTRICITY) as MIN_ACTIVE_ELECTRICITY,avg(ACTIVE_ELECTRICITY) as AVG_ACTIVE_ELECTRICITY";
						//最大值查询//最小值查询//平均值查询
						$res = ElectricityMeterDataHistory::getValue($where, $fields);
						$avgres = $res[0]['AVG_ACTIVE_ELECTRICITY'];
						$avg['max'] = $res[0]['MAX_ACTIVE_ELECTRICITY'] * 1;
						$avg['min'] = $res[0]['MIN_ACTIVE_ELECTRICITY'] * 1;
						$avg['avg'] = sprintf("%.2f", $avgres * 1);

						$list = ElectricityMeterDataHistory::getList($where, $field);
						$arr[0]['name']="有功电量";
						$arr[0]['list'] = [];
						$xAxis = [];
						foreach ($list as $key => $value) {
							$arr[0]['list'][]=$val['ACTIVE_ELECTRICITY'] * 1;
							$xAxis[]=$val['TIME1'];
						}
						break;

					case '无功电量':
						$field = $field.",REACTIVE_ELECTRICITY,date_format(TIME,'%H:%i') as TIME1";
						$yAxisUnit = "无功电量: KVRA";

						$fields = "max(REACTIVE_ELECTRICITY) as MAX_REACTIVE_ELECTRICITY,min(REACTIVE_ELECTRICITY) as MIN_REACTIVE_ELECTRICITY,avg(REACTIVE_ELECTRICITY) as AVG_REACTIVE_ELECTRICITY";

						//最大值查询//最小值查询//平均值查询
						$res = ElectricityMeterDataHistory::getValue($where, $fields);
						$avgres = $res[0]['AVG_REACTIVE_ELECTRICITY'];
						$avg['max'] = $res[0]['MAX_REACTIVE_ELECTRICITY'] * 1;
						$avg['min'] = $res[0]['MIN_REACTIVE_ELECTRICITY'] * 1;
						$avg['avg'] = sprintf("%.2f", $avgres * 1);

						$list = ElectricityMeterDataHistory::getList($where, $field);
						$arr[0]['name']="无功电量";
						$arr[0]['list'] = [];
						$xAxis = [];
						foreach ($list as $key => $value) {
							$arr[0]['list'][]=$val['REACTIVE_ELECTRICITY'] * 1;
							$xAxis[]=$val['TIME1'];
						}
						break;

					case '有功功率':
						$field = $field.",ACTIVE_POWER,date_format(TIME,'%Y-%m-%d %H:%i:%s') as TIME1";
						$yAxisUnit = "有功功率: KW";
						$fields = "max(ACTIVE_POWER) as MAX_ACTIVE_POWER,min(ACTIVE_POWER) as MIN_ACTIVE_POWER,avg(ACTIVE_POWER) as AVG_ACTIVE_POWER";

						//最大值查询//最小值查询//平均值查询
						$res = ElectricityMeterDataHistory::getValue($where, $fields);
						$avgres = $res[0]['AVG_ACTIVE_POWER'];
						$avg['max'] = $res[0]['MAX_ACTIVE_POWER'] * 1;
						$avg['min'] = $res[0]['MIN_ACTIVE_POWER'] * 1;
						$avg['avg'] = sprintf("%.2f", $avgres * 1);

						$list = ElectricityMeterDataHistory::getList($where, $field);
						$arr[0]['name'] = "有功功率";
						$arr[0]['list'] = [];
						$xAxis = [];
						foreach ($list as $key => $value) {
							$arr[0]['list'][]=$val['ACTIVE_POWER'] * 1;
							$xAxis[]=$val['TIME1'];
						}

						$fieldel = $field.",ACTIVE_POWER";
						$yesterday = ElectricityMeterDataHistory::getYesterday($where, $fieldel);
						$arr[1]['name'] = "昨天";
						$arr[1]['list'] = [];
						foreach ($list as $key => $value) {
							$arr[1]['list'][]=$val['ACTIVE_POWER'] * 1;
							$xAxis[]=$val['TIME1'];
						}
						break;
					case '无功功率':
						$field = $field.",REACTIVE_POWER,date_format(TIME,'%H:%i') as TIME1";
						$yAxisUnit = "无功功率: KRV";
						$fields = "max(REACTIVE_POWER) as MAX_REACTIVE_POWER,min(REACTIVE_POWER) as MIN_REACTIVE_POWER,avg(REACTIVE_POWER) as AVG_REACTIVE_POWER";

						//最大值查询//最小值查询//平均值查询
						$res = ElectricityMeterDataHistory::getValue($where, $fields);
						$avgres = $res[0]['AVG_REACTIVE_POWER'];
						$avg['max'] = $res[0]['MAX_REACTIVE_POWER'] * 1;
						$avg['min'] = $res[0]['MIN_REACTIVE_POWER'] * 1;
						$avg['avg'] = sprintf("%.2f", $avgres * 1);

						$list = ElectricityMeterDataHistory::getList($where, $field);
						$arr[0]['name'] = "无功功率";
						$arr[0]['list'] = [];
						$xAxis = [];
						foreach ($list as $key => $value) {
							$arr[0]['list'][]=$val['REACTIVE_POWER'] * 1;
							$xAxis[]=$val['TIME1'];
						}

						break;

					case '频率':
						$field = $field.",FREQUENCY,date_format(TIME,'%H:%i') as TIME1";
						$yAxisUnit = "频率: HZ";
						$fields = "max(FREQUENCY) as MAX_FREQUENCY,min(FREQUENCY) as MIN_FREQUENCY,avg(FREQUENCY) as AVG_FREQUENCY";
						//最大值查询//最小值查询//平均值查询
						$res = ElectricityMeterDataHistory::getValue($where, $fields);
						$avgres = $res[0]['AVG_FREQUENCY'];
						$avg['max'] = $res[0]['MAX_FREQUENCY'];
						$avg['min'] = $res[0]['MIN_FREQUENCY'];
						$avg['avg'] = sprintf("%.2f", $avgres);

						$list = ElectricityMeterDataHistory::getList($where, $field);
						$arr[0]['name'] = "频率";
						$arr[0]['list'] = [];
						$xAxis = [];
						foreach ($list as $key => $value) {
							$arr[0]['list'][]=$val['FREQUENCY'];
							$xAxis[]=$val['TIME1'];
						}
						break;
					
					default:
						$returnArr = [];
						break;
				}

				$returnArr['data'] = $arr;
				$returnArr['avg'] = $avg;
				$returnArr['yAxisUnit'] = $yAxisUnit;
				$returnArr['xAxisUnit'] = $xAxisUnit;
				$returnArr['xAxis'] = $xAxis;

				break;
			
			case '高压柜':
				ValidataCommon::validateCheck(['EQUITMENT_NAME' => 'require'], $this->request->param('')); //参数验证仪器名称
				if ($param['EQUITMENT_NAME'] == '综保') {
					ValidataCommon::validateCheck(['TYPE' => 'require'], $this->request->param('')); //参数验证类型
					
					$xAxisUnit = '时间: 时';
					switch ($param['TYPE']) {
						case '三相电流':
							$field =$field.",CURRENT_A,CURRENT_B,CURRENT_C,date_format(TIME,'%H:%i') as TIME1";
							$yAxisUnit="电流: A";
							$list = CipdDataHistory::getList($where, $field);
							$arr[0]['name'] = 'A相电流';
							$arr[1]['name'] = 'B相电流';
							$arr[2]['name'] = 'C相电流';
							$arr[0]['list'] = [];
							$arr[1]['list'] = [];
							$arr[2]['list'] = [];
							$xAxis = [];
							foreach ($list as $key => $value) {
								$arr[0]['list'][] = $val['CURRENT_A'];
								$arr[1]['list'][] = $val['CURRENT_B'];
								$arr[2]['list'][] = $val['CURRENT_C'];
								$xAxis[] = $val['TIME1'];
							}
							break;

						case '三相电压':
							$field =$field.",VOLTAGE_A,VOLTAGE_B,VOLTAGE_C,date_format(TIME,'%H:%i') as TIME1";
							$yAxisUnit="电压: V";

							$list = CipdDataHistory::getList($where, $field);
							$arr[0]['name'] = 'A相电压';
							$arr[1]['name'] = 'B相电压';
							$arr[2]['name'] = 'C相电压';
							$arr[0]['list'] = [];
							$arr[1]['list'] = [];
							$arr[2]['list'] = [];
							$xAxis = [];
							foreach ($list as $key => $value) {
								$arr[0]['list'][] = $val['VOLTAGE_A'];
								$arr[1]['list'][] = $val['VOLTAGE_B'];
								$arr[2]['list'][] = $val['VOLTAGE_C'];
								$xAxis[] = $val['TIME1'];
							}
							break;
						
						default:
							$returnArr = [];
							break;
					}

					$returnArr['data'] = $arr;
					$returnArr['yAxisUnit'] = $yAxisUnit;
					$returnArr['xAxisUnit'] = $xAxisUnit;
					$returnArr['xAxis'] = $xAxis;

				}
				
				 //圆柱形
				if ($param['EQUITMENT_NAME'] == '故障仪') {
					$field = $field.",STATUS_A,STATUS_B,STATUS_C,date_format(TIME,'%Y-%m-%d %H:%i:%s') as TIME";
					$list = FaultMeterDataHistory::getList($where, $field);
					$list = array_slice($list, -100);
					return returnMsg(200, 'success', $list);
				}
				break;

			case '变压器':
				// $field = 'TIME,LINK_NUMBER';
				// $where['LINK_NUMBER'] = $param['LINK_NUMBER'];
				$xAxisUnit = '时间: 时';
				$yAxisUnit = '温度:';
				$field = $field.",TEMPERATURE_A,TEMPERATURE_B,TEMPERATURE_C,date_format(TIME,'%Y-%m-%d %H:%i') as TIME1";
				$list = TemperatureControllerDataHistory::getList($where, $field);
				$arr[0]['name'] = 'A项温度';
				$arr[1]['name'] = 'B项温度';
				$arr[2]['name'] = 'C项温度';
				$arr[0]['list'] = [];
				$arr[1]['list'] = [];
				$arr[2]['list'] = [];
				$xAxis = [];
				foreach ($list as $key => $value) {
					$arr[0]['list'][] = $val['TEMPERATURE_A'];
					$arr[1]['list'][] = $val['TEMPERATURE_B'];
					$arr[2]['list'][] = $val['TEMPERATURE_C'];
					$xAxis[] = $val['TIME1'];
				}
				break;

			case '低压进线柜':
				$xAxisUnit='时间: 时';

				switch ($param['TYPE']) {
					case '三相电流':
						$returnArr = $this->lowInAndOutCabinetCurrent($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '三相电压':
						$returnArr = $this->lowInAndOutCabinetVoltage($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '功率因数':
						$returnArr = $this->lowInAndOutCabinetPowerFactor($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '有功电量':
						$returnArr = $this->lowInAndOutCabinetActiveElect($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '无功电量':
						$returnArr = $this->lowInAndOutCabinetReactiveElect($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '有功功率':
						$returnArr = $this->lowInAndOutCabinetActivePower($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '无功功率':
						$returnArr = $this->lowInAndOutCabinetReactivePower($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '频率':
						$returnArr = $this->lowInAndOutCabinetFrequency($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;
					
					case '三相谐波电流':
						$field = $field.",HARMONIC_CURRENT_A,HARMONIC_CURRENT_B,HARMONIC_CURRENT_C,date_format(TIME,'%H:%i') as TIME1";
						$yAxisUnit = "三相谐波电流: A";
						$list = MultimeterDataHistory::getList($where, $field);
						$arr[0]['name'] = '三相谐波电流A';
						$arr[1]['name'] = '三相谐波电流B';
						$arr[2]['name'] = '三相谐波电流C';
						$arr[0]['list'] = [];
						$arr[1]['list'] = [];
						$arr[2]['list'] = [];
						$xAxis = [];
						foreach ($list as $key => $value) {
							$arr[0]['list'][] = $val['HARMONIC_CURRENT_A'];
							$arr[1]['list'][] = $val['HARMONIC_CURRENT_B'];
							$arr[2]['list'][] = $val['HARMONIC_CURRENT_C'];
							$xAxis[] = $val['TIME1'];
						}
						break;

					case '三相谐波电压':
						$field = $field.",HARMONIC_VOLTAGE_B,HARMONIC_VOLTAGE_C,HARMONIC_VOLTAGE_A,date_format(TIME,'%H:%i') as TIME1";
						$yAxisUnit = "三相谐波电压: V";
						$list = MultimeterDataHistory::getList($where, $field);
						$arr[0]['name'] = '三相谐波电压A';
						$arr[1]['name'] = '三相谐波电压B';
						$arr[2]['name'] = '三相谐波电压C';
						$arr[0]['list'] = [];
						$arr[1]['list'] = [];
						$arr[2]['list'] = [];
						$xAxis = [];
						foreach ($list as $key => $value) {
							$arr[0]['list'][] = $val['HARMONIC_VOLTAGE_A'];
							$arr[1]['list'][] = $val['HARMONIC_VOLTAGE_B'];
							$arr[2]['list'][] = $val['HARMONIC_VOLTAGE_C'];
							$xAxis[] = $val['TIME1'];
						}

						break;

					case '需量':
						$field = $field.",DEMAND,date_format(TIME,'%H:%i') as TIME1";
						$yAxisUnit = "需量: ";
						$list = MultimeterDataHistory::getList($where, $field);
						$arr[0]['name'] = '需量';
						$arr[0]['list'] = [];
						$xAxis = [];
						foreach ($list as $key => $value) {
							$arr[0]['list'][] = $val['DEMAND'];
							$xAxis[] = $val['TIME1'];
						}

						break;
					default:
						# code...
						break;
				}

				
				break;

			case '低压出线柜':
				switch ($param['TYPE']) {
					case '三相电流':
						$returnArr = $this->lowInAndOutCabinetCurrent($where);
						return self::returnMsg(200, 'success', $returnArr);
						break;
					case '三相电压':
						$returnArr = $this->lowInAndOutCabinetVoltage($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '功率因数':
						$returnArr = $this->lowInAndOutCabinetPowerFactor($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '有功电量':
						$returnArr = $this->lowInAndOutCabinetActiveElect($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '无功电量':
						$returnArr = $this->lowInAndOutCabinetReactiveElect($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '有功功率':
						$returnArr = $this->lowInAndOutCabinetActivePower($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '无功功率':
						$returnArr = $this->lowInAndOutCabinetReactivePower($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '频率':
						$returnArr = $this->lowInAndOutCabinetFrequency($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;
					
					default:
						# code...
						break;
				}
				break;

			case '其他':
				switch ($param['TYPE']) {
					case '三相电流':
						$returnArr = $this->lowInAndOutCabinetCurrent($where);
						return self::returnMsg(200, 'success', $returnArr);
						break;
					case '三相电压':
						$returnArr = $this->lowInAndOutCabinetVoltage($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '功率因数':
						$returnArr = $this->lowInAndOutCabinetPowerFactor($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '有功电量':
						$returnArr = $this->lowInAndOutCabinetActiveElect($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '无功电量':
						$returnArr = $this->lowInAndOutCabinetReactiveElect($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '有功功率':
						$returnArr = $this->lowInAndOutCabinetActivePower($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '无功功率':
						$returnArr = $this->lowInAndOutCabinetReactivePower($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;

					case '频率':
						$returnArr = $this->lowInAndOutCabinetFrequency($where, $field);
						return self::returnMsg(200, 'success', $returnArr);
						break;
					
					default:
						# code...
						break;
				}
				break;

			default:
				# code...
				break;
		}

		$returnArr['data'] = $arr;
		$returnArr['yAxisUnit'] = $yAxisUnit;
		$returnArr['xAxisUnit'] = $xAxisUnit;
		$returnArr['xAxis'] = $xAxis;
		foreach ($returnArr['data'] as $key => $value) {
			foreach ($value['list'] as $k => $v) {
				$value['list'][$k] = sprintf("%.2f", $v);
			}
			$returnArr['data'][$key]['list'] = array_slice($value['list'], -100);
		}
		$returnArr['xAxis'] = $arr = array_slice($returnArr['xAxis'], -100);

		return self::returnMsg(200, 'success', $returnArr);
	}


	/**
	 * 实时数据查询接口
	 * @DateTime 2020-05-28T16:04:52+0800
	 * @return   [type]
	 */
	public function realTimeData($param)
	{	
		
		$field = 'TIME, LINK_NUMBER';
		$where['LINK_NUMBER'] = ['eq', $param['LINK_NUMBER']];
		$OFFSET = $param['OFFSET'] ? $param['OFFSET'] : 20;
		$LIMIT = $param['LIMIT'] ? $param['LIMIT'] : 0;
		//dump($whereTime);exit;
		switch ($param['CATEGORY']) {
			case '计量柜':
				
				$list = [];
				switch ($param['TYPE']) {
					case '三相电流':
						$field = $field.',CURRENT_A, CURRENT_B, CURRENT_C';
						break;
					case '三相电压':
						$field = $field.',VOLTAGE_A, VOLTAGE_B, VOLTAGE_C';
						break;
					case '功率因数':
						$field = $field.',POWER_FACTOR';
						break;
					case '有功电量':
						$field = $field.',ACTIVE_ELECTRICITY';
						break;
					case '无功电量':
						$field = $field.',REACTIVE_ELECTRICITY';
						break;
					case '有功功率':
						$field = $field.',ACTIVE_POWER';
						break;
					case '无功功率':
						$field = $field.',REACTIVE_POWER';
						break;
					case '频率':
						$field = $field.',FREQUENCY';
						break;
					default:
						$field = $field.', CURRENT_A, CURRENT_B, CURRENT_C, VOLTAGE_A, VOLTAGE_B, VOLTAGE_C, POWER_FACTOR, ACTIVE_ELECTRICITY, REACTIVE_ELECTRICITY, ACTIVE_POWER, REACTIVE_POWER, FREQUENCY';
						break;
				}
				$list = Db::name('electricity_meter_data_history')
							->field($field)
							->whereTime('TIME', 'd')
							->where($where)
							->limit($LIMIT, $OFFSET)
							->order('TIME desc')
							->select();
				break;

			case '高压柜':
				ValidataCommon::validateCheck(['EQUITMENT_NAME' => 'require'], $this->request->param('')); //参数验证仪器名称
				if ($param['EQUITMENT_NAME'] == '综保') {
					//三相电流、三相电压、功率因数、有功电量、无功电量、有功功率、无功功率、频率
					switch ($param['TYPE']) {
						case '三相电流':
							$field = $field.',CURRENT_A, CURRENT_B, CURRENT_C';
							break;
						case '三相电压':
							$field = $field.',VOLTAGE_A, VOLTAGE_B, VOLTAGE_C';
							break;
						default:
							$field = $field.',CURRENT_A, CURRENT_B, CURRENT_C, VOLTAGE_A, VOLTAGE_B, VOLTAGE_C';
							break;
					}

					$list = Db::name('cipd_data_history')
								->field($field)
								->whereTime('TIME', 'd')
								->where($where)
								->limit($LIMIT, $OFFSET)
								->order('TIME DESC')
								->select();
				}

				if ($param['EQUITMENT_NAME'] == '故障仪') {
					$field = $field.',STATUS_A, STATUS_B, STATUS_C';
					$list = Db::name('fault_meter_data_history')
								->field($field)
								->whereTime('TIME', 'd')
								->where($where)
								->limit($LIMIT, $OFFSET)
								->order('TIME DESC')
								->select();
				}

				break;

			case '变压器':
				//$param['TYPE']=='温度';
				$field =$field.',TEMPERATURE_A, TEMPERATURE_B, TEMPERATURE_C, STARTING_STATUS, CLOSED_STATUS';

				$list = Db::name('temperature_controller_data_history')
							->field($where)
							->whereTime('TIME', 'd')
							->where($where)
							->limit($LIMIT, $OFFSET)
							->order('TIME DESC')
							->select();
				break;

			case '低压进线柜':
				switch ($param['TYPE']) {
					case '三相电流':
						$field = $field.',CURRENT_A, CURRENT_B, CURRENT_C';
						break;
					case '三相电压':
						$field = $field.',VOLTAGE_A, VOLTAGE_B, VOLTAGE_C';
						break;
					case '功率因数':
						$field = $field.',POWER_FACTOR';
						break;
					case '有功电量':
						$field = $field.',ACTIVE_ELECTRICITY';
						break;
					case '无功电量':
						$field = $field.',REACTIVE_ELECTRICITY';
						break;
					case '有功功率':
						$field = $field.',ACTIVE_POWER';
						break;
					case '无功功率':
						$field = $field.',REACTIVE_POWER';
						break;
					case '频率':
						$field = $field.',FREQUENCY';
						break;
					case '三相谐波电流':
						$field = $field.',HARMONIC_CURRENT_A, HARMONIC_CURRENT_B, HARMONIC_CURRENT_C';
						break;
					case '三相谐波电压':
						$field = $field.',HARMONIC_VOLTAGE_A, HARMONIC_VOLTAGE_B, HARMONIC_VOLTAGE_C';
						break;
					case '需量':
						$field = $field.',DEMAND';
						break;
					default:
						$field = $field.', CURRENT_A, CURRENT_B, CURRENT_C, VOLTAGE_A, VOLTAGE_B, VOLTAGE_C, POWER_FACTOR, ACTIVE_ELECTRICITY, REACTIVE_ELECTRICITY, ACTIVE_POWER, REACTIVE_POWER, FREQUENCY,HARMONIC_CURRENT_A, HARMONIC_CURRENT_B, HARMONIC_CURRENT_C, HARMONIC_VOLTAGE_A, HARMONIC_VOLTAGE_B, HARMONIC_VOLTAGE_C, DEMAND, BREAK_STATUS';
						break;
				}

				$list = Db::name('multimeter_data_history')
							->field($field)
							->whereTime('TIME', 'd')
							->where($where)
							->limit($LIMIT, $OFFSET)
							->order('TIME DESC')
							->select();
				break;

			case '低压出线柜':
				switch ($param['TYPE']) {
					case '三相电流':
						$field = $field.',CURRENT_A, CURRENT_B, CURRENT_C';
						break;
					case '三相电压':
						$field = $field.',VOLTAGE_A, VOLTAGE_B, VOLTAGE_C';
						break;
					case '功率因数':
						$field = $field.',POWER_FACTOR';
						break;
					case '有功电量':
						$field = $field.',ACTIVE_ELECTRICITY';
						break;
					case '无功电量':
						$field = $field.',REACTIVE_ELECTRICITY';
						break;
					case '有功功率':
						$field = $field.',ACTIVE_POWER';
						break;
					case '无功功率':
						$field = $field.',REACTIVE_POWER';
						break;
					case '频率':
						$field = $field.',FREQUENCY';
						break;
					default:
						$field = $field.', CURRENT_A, CURRENT_B, CURRENT_C, VOLTAGE_A, VOLTAGE_B, VOLTAGE_C, POWER_FACTOR, ACTIVE_ELECTRICITY, REACTIVE_ELECTRICITY, ACTIVE_POWER, REACTIVE_POWER, FREQUENCY, BREAK_STATUS';
						break;
				}
				$list = Db::name('multimeter_data_history')
							->field($field)
							->whereTime('TIME', 'd')
							->where($where)
							->limit($LIMIT, $OFFSET)
							->order('TIME DESC')
							->select();
				break;

			case '其他':
				switch ($param['TYPE']) {
					case '三相电流':
						$field = $field.',CURRENT_A, CURRENT_B, CURRENT_C';
						break;
					case '三相电压':
						$field = $field.',VOLTAGE_A, VOLTAGE_B, VOLTAGE_C';
						break;
					case '功率因数':
						$field = $field.',POWER_FACTOR';
						break;
					case '有功电量':
						$field = $field.',ACTIVE_ELECTRICITY';
						break;
					case '无功电量':
						$field = $field.',REACTIVE_ELECTRICITY';
						break;
					case '有功功率':
						$field = $field.',ACTIVE_POWER';
						break;
					case '无功功率':
						$field = $field.',REACTIVE_POWER';
						break;
					case '频率':
						$field = $field.',FREQUENCY';
						break;
					default:
						$field = $field.', CURRENT_A, CURRENT_B, CURRENT_C, VOLTAGE_A, VOLTAGE_B, VOLTAGE_C, POWER_FACTOR, ACTIVE_ELECTRICITY, REACTIVE_ELECTRICITY, ACTIVE_POWER, REACTIVE_POWER, FREQUENCY, BREAK_STATUS';
						break;
				}

				$list = Db::name('multimeter_data_history')
							->field($field)
							->whereTime('TIME', 'd')
							->where($where)
							->limit($LIMIT, $OFFSET)
							->order('TIME DESC')
							->select();
				break;
			
			default:
				$list = [];
				break;
		}


		return $list;
	}

	/**
	 * 实时数据查询接口
	 * @DateTime 2020-05-28T16:04:52+0800
	 * @return   [type]
	 */
	public function historyData($param)
	{	
		
		//默认开始日期从 去年今天开始
		$START_TIME = date('Y-m-d H:i:s',($param['START_TIME'] ? strtotime($param['START_TIME']) : strtotime('-1 year')));
		//结束时间当前时间 
		$END_TIME = date('Y-m-d H:i:s',($param['END_TIME'] ? strtotime($param['END_TIME'])+86400 : time()));
		//$whereTime = ['TIME', [$START_TIME, $END_TIME]];
		$whereTime['TIME'] = array($START_TIME, $END_TIME);
		$field = 'TIME, LINK_NUMBER';
		$where['LINK_NUMBER'] = ['eq', $param['LINK_NUMBER']];
		$OFFSET = $param['OFFSET'] ? $param['OFFSET'] : 20;
		$LIMIT = $param['LIMIT'] ? $param['LIMIT'] : 0;
		switch ($param['CATEGORY']) {
			case '计量柜':
				
				$list = [];
				switch ($param['TYPE']) {
					case '三相电流':
						$field = $field.',CURRENT_A, CURRENT_B, CURRENT_C';
						break;
					case '三相电压':
						$field = $field.',VOLTAGE_A, VOLTAGE_B, VOLTAGE_C';
						break;
					case '功率因数':
						$field = $field.',POWER_FACTOR';
						break;
					case '有功电量':
						$field = $field.',ACTIVE_ELECTRICITY';
						break;
					case '无功电量':
						$field = $field.',REACTIVE_ELECTRICITY';
						break;
					case '有功功率':
						$field = $field.',ACTIVE_POWER';
						break;
					case '无功功率':
						$field = $field.',REACTIVE_POWER';
						break;
					case '频率':
						$field = $field.',FREQUENCY';
						break;
					default:
						$field = $field.', CURRENT_A, CURRENT_B, CURRENT_C, VOLTAGE_A, VOLTAGE_B, VOLTAGE_C, POWER_FACTOR, ACTIVE_ELECTRICITY, REACTIVE_ELECTRICITY, ACTIVE_POWER, REACTIVE_POWER, FREQUENCY';
						break;
				}
				$list = Db::name('electricity_meter_data_history')
							->field($field)
							->whereTime('TIME', [$START_TIME, $END_TIME])
							->where($where)
							->limit($LIMIT, $OFFSET)
							->order('TIME desc')
							->select();
				break;

			case '高压柜':
				ValidataCommon::validateCheck(['EQUITMENT_NAME' => 'require'], $this->request->param('')); //参数验证仪器名称
				if ($param['EQUITMENT_NAME'] == '综保') {
					//三相电流、三相电压、功率因数、有功电量、无功电量、有功功率、无功功率、频率
					switch ($param['TYPE']) {
						case '三相电流':
							$field = $field.',CURRENT_A, CURRENT_B, CURRENT_C';
							break;
						case '三相电压':
							$field = $field.',VOLTAGE_A, VOLTAGE_B, VOLTAGE_C';
							break;
						default:
							$field = $field.',CURRENT_A, CURRENT_B, CURRENT_C, VOLTAGE_A, VOLTAGE_B, VOLTAGE_C';
							break;
					}

					$list = Db::name('cipd_data_history')
								->field($field)
								->whereTime('TIME', [$START_TIME, $END_TIME])
								->where($where)
								->limit($LIMIT, $OFFSET)
								->order('TIME DESC')
								->select();
				}

				if ($param['EQUITMENT_NAME'] == '故障仪') {
					$field = $field.',STATUS_A, STATUS_B, STATUS_C';
					$list = Db::name('fault_meter_data_history')
								->field($field)
								->whereTime('TIME', 'd')
								->where($where)
								->limit($LIMIT, $OFFSET)
								->order('TIME DESC')
								->select();
				}

				break;

			case '变压器':
				//$param['TYPE']=='温度';
				$field =$field.',TEMPERATURE_A, TEMPERATURE_B, TEMPERATURE_C, STARTING_STATUS, CLOSED_STATUS';

				$list = Db::name('temperature_controller_data_history')
							->field($where)
							->whereTime('TIME', [$START_TIME, $END_TIME])
							->where($where)
							->limit($LIMIT, $OFFSET)
							->order('TIME DESC')
							->select();
				break;

			case '低压进线柜':
				switch ($param['TYPE']) {
					case '三相电流':
						$field = $field.',CURRENT_A, CURRENT_B, CURRENT_C';
						break;
					case '三相电压':
						$field = $field.',VOLTAGE_A, VOLTAGE_B, VOLTAGE_C';
						break;
					case '功率因数':
						$field = $field.',POWER_FACTOR';
						break;
					case '有功电量':
						$field = $field.',ACTIVE_ELECTRICITY';
						break;
					case '无功电量':
						$field = $field.',REACTIVE_ELECTRICITY';
						break;
					case '有功功率':
						$field = $field.',ACTIVE_POWER';
						break;
					case '无功功率':
						$field = $field.',REACTIVE_POWER';
						break;
					case '频率':
						$field = $field.',FREQUENCY';
						break;
					case '三相谐波电流':
						$field = $field.',HARMONIC_CURRENT_A, HARMONIC_CURRENT_B, HARMONIC_CURRENT_C';
						break;
					case '三相谐波电压':
						$field = $field.',HARMONIC_VOLTAGE_A, HARMONIC_VOLTAGE_B, HARMONIC_VOLTAGE_C';
						break;
					case '需量':
						$field = $field.',DEMAND';
						break;
					default:
						$field = $field.', CURRENT_A, CURRENT_B, CURRENT_C, VOLTAGE_A, VOLTAGE_B, VOLTAGE_C, POWER_FACTOR, ACTIVE_ELECTRICITY, REACTIVE_ELECTRICITY, ACTIVE_POWER, REACTIVE_POWER, FREQUENCY,HARMONIC_CURRENT_A, HARMONIC_CURRENT_B, HARMONIC_CURRENT_C, HARMONIC_VOLTAGE_A, HARMONIC_VOLTAGE_B, HARMONIC_VOLTAGE_C, DEMAND, BREAK_STATUS';
						break;
				}

				$list = Db::name('multimeter_data_history')
							->field($field)
							->whereTime('TIME', [$START_TIME, $END_TIME])
							->where($where)
							->limit($LIMIT, $OFFSET)
							->order('TIME DESC')
							->select();
				break;

			case '低压出线柜':
				switch ($param['TYPE']) {
					case '三相电流':
						$field = $field.',CURRENT_A, CURRENT_B, CURRENT_C';
						break;
					case '三相电压':
						$field = $field.',VOLTAGE_A, VOLTAGE_B, VOLTAGE_C';
						break;
					case '功率因数':
						$field = $field.',POWER_FACTOR';
						break;
					case '有功电量':
						$field = $field.',ACTIVE_ELECTRICITY';
						break;
					case '无功电量':
						$field = $field.',REACTIVE_ELECTRICITY';
						break;
					case '有功功率':
						$field = $field.',ACTIVE_POWER';
						break;
					case '无功功率':
						$field = $field.',REACTIVE_POWER';
						break;
					case '频率':
						$field = $field.',FREQUENCY';
						break;
					default:
						$field = $field.', CURRENT_A, CURRENT_B, CURRENT_C, VOLTAGE_A, VOLTAGE_B, VOLTAGE_C, POWER_FACTOR, ACTIVE_ELECTRICITY, REACTIVE_ELECTRICITY, ACTIVE_POWER, REACTIVE_POWER, FREQUENCY, BREAK_STATUS';
						break;
				}
				$list = Db::name('multimeter_data_history')
							->field($field)
							->whereTime('TIME', [$START_TIME, $END_TIME])
							->where($where)
							->limit($LIMIT, $OFFSET)
							->order('TIME DESC')
							->select();
				break;

			case '其他':
				switch ($param['TYPE']) {
					case '三相电流':
						$field = $field.',CURRENT_A, CURRENT_B, CURRENT_C';
						break;
					case '三相电压':
						$field = $field.',VOLTAGE_A, VOLTAGE_B, VOLTAGE_C';
						break;
					case '功率因数':
						$field = $field.',POWER_FACTOR';
						break;
					case '有功电量':
						$field = $field.',ACTIVE_ELECTRICITY';
						break;
					case '无功电量':
						$field = $field.',REACTIVE_ELECTRICITY';
						break;
					case '有功功率':
						$field = $field.',ACTIVE_POWER';
						break;
					case '无功功率':
						$field = $field.',REACTIVE_POWER';
						break;
					case '频率':
						$field = $field.',FREQUENCY';
						break;
					default:
						$field = $field.', CURRENT_A, CURRENT_B, CURRENT_C, VOLTAGE_A, VOLTAGE_B, VOLTAGE_C, POWER_FACTOR, ACTIVE_ELECTRICITY, REACTIVE_ELECTRICITY, ACTIVE_POWER, REACTIVE_POWER, FREQUENCY, BREAK_STATUS';
						break;
				}

				$list = Db::name('multimeter_data_history')
							->field($field)
							->whereTime('TIME', [$START_TIME, $END_TIME])
							->where($where)
							->limit($LIMIT, $OFFSET)
							->order('TIME DESC')
							->select();
				break;
			
			default:
				$list = [];
				break;
		}


		return $list;
	}


	/**
	 * 指令
	 * @DateTime 2020-05-29T17:50:49+0800
	 * @return   [type]
	 */
	public function windosCommand()
	{
		$param = $this->request->param('');
		$where['LINK_NUMBER'] = $param['LINK_NUMBER'];
		$data['USER_ID'] = $param['USERID'];
		$user = User::getUserField($data, 'COMMAND');
		ValidataCommon::validateCheck(['LINK_NUMBER' => 'require'], $this->request->param('')); //参数验证
		if ($user['COMMAND'] == $param['CODE']) {
			$control_command = ControlCommand::getCommand($where);
			if (empty($control_command)) {
				$user_id = $param['USERID'];
				switch ($param['TYPE']) {
					case '高压柜':
						$res = Db::name('cipd')->where($where)->field('NAME')->find();
						break;
					case '低压进线柜':
						$res = Db::name('multimeter')->where($where)->field('NAME')->find();
						break;
					default:
						# code...
						break;
				}
				if (!empty($res)) {
					$data['ID'] = md5($user_id.time());
					$data['COMMAND_NAME'] = '打开' . $res['NAME'];
					$data['EQUIPMENT_NAME'] = $res['NAME'];
					$data['CURRENT_STATUS'] = 1;
					$data['COMMAND_CONTENT'] = 1;
					$data['IP_ADDRESS'] = $_SERVER["REMOTE_ADDR"];
					$data['LINK_NUMBER'] = $param['LINK_NUMBER'];
					$save['CURRENT_STATUS']=1;
					ControlCommand::add($data);
				}
			}else{
				$save['CURRENT_STATUS'] = 0;
				if ($control_command['CURRENT_STATUS']==0) {
					$save['CURRENT_STATUS'] = 1;
					$save['IP_ADDRESS']= $_SERVER["REMOTE_ADDR"];
					$save['COMMAND_CONTENT']=1;
				}else{
					$save['CURRENT_STATUS'] = 0;
					$save['IP_ADDRESS']= $_SERVER["REMOTE_ADDR"];
					$save['COMMAND_CONTENT']=0;
				}

				ControlCommand::update($where, $save);
			}

		return returnMsg(200, '操作成功！', null);
		}

	}


	/**
	 * 低压进出线柜频率
	 * @DateTime 2020-05-29T16:34:29+0800
	 * @param    [type]
	 * @return   [type]
	 */
	private function lowInAndOutCabinetFrequency($where, $field)
	{	
		$xAxisUnit = '时间: 时';
		$field = $field.",FREQUENCY,date_format(TIME,'%H:%i') as TIME1";
		$yAxisUnit = "频率: HZ";
		$list = MultimeterDataHistory::getList($where, $field);
		$arr[0]['name'] = '无功功率';
		$arr[0]['list'] = [];
		$xAxis = [];
		foreach ($list as $key => $value) {
			$arr[0]['list'][] = $val['FREQUENCY'];
			$xAxis[] = $val['TIME1'];
		}
		$returnArr['data'] = $arr;
		$returnArr['yAxisUnit'] = $yAxisUnit;
		$returnArr['xAxisUnit'] = $xAxisUnit;
		$returnArr['xAxis'] = $xAxis;
		return $returnArr;
	}


	/**
	 * 低压进出线柜无功功率
	 * @DateTime 2020-05-29T16:34:29+0800
	 * @param    [type]
	 * @return   [type]
	 */
	private function lowInAndOutCabinetReactivePower($where, $field)
	{	
		$xAxisUnit = '时间: 时';
		$field = $field.",REACTIVE_POWER,date_format(TIME,'%H:%i') as TIME1";
		$yAxisUnit = "无功功率: KRV";
		$list = MultimeterDataHistory::getList($where, $field);
		$arr[0]['name'] = '无功功率';
		$arr[0]['list'] = [];
		$xAxis = [];
		foreach ($list as $key => $value) {
			$arr[0]['list'][] = $val['REACTIVE_POWER'];
			$xAxis[] = $val['TIME1'];
		}
		$returnArr['data'] = $arr;
		$returnArr['yAxisUnit'] = $yAxisUnit;
		$returnArr['xAxisUnit'] = $xAxisUnit;
		$returnArr['xAxis'] = $xAxis;
		return $returnArr;
	}

	/**
	 * 低压进出线柜有功功率
	 * @DateTime 2020-05-29T16:34:29+0800
	 * @param    [type]
	 * @return   [type]
	 */
	private function lowInAndOutCabinetActivePower($where, $field)
	{	
		$xAxisUnit = '时间: 时';
		$field = $field.",ACTIVE_POWER,date_format(TIME,'%H:%i') as TIME1";
		$yAxisUnit = "有功功率: KW";
		$list = MultimeterDataHistory::getList($where, $field);
		$arr[0]['name'] = '有功功率';
		$arr[0]['list'] = [];
		$xAxis = [];
		foreach ($list as $key => $value) {
			$arr[0]['list'][] = $val['ACTIVE_POWER'];
			$xAxis[] = $val['TIME1'];
		}
		$returnArr['data'] = $arr;
		$returnArr['yAxisUnit'] = $yAxisUnit;
		$returnArr['xAxisUnit'] = $xAxisUnit;
		$returnArr['xAxis'] = $xAxis;
		return $returnArr;
	}


	/**
	 * 低压进出线柜无功电量
	 * @DateTime 2020-05-29T16:34:29+0800
	 * @param    [type]
	 * @return   [type]
	 */
	private function lowInAndOutCabinetReactiveElect($where, $field)
	{	
		$xAxisUnit = '时间: 时';
		$field =$field.",REACTIVE_ELECTRICITY,date_format(TIME,'%H:%i') as TIME1";
		$yAxisUnit="无功电量: KVRA";

		$list = MultimeterDataHistory::getList($where, $field);
		$arr[0]['name'] = '无功电量';
		$arr[0]['list'] = [];
		$xAxis = [];
		foreach ($list as $key => $value) {
			$arr[0]['list'][] = $val['REACTIVE_ELECTRICITY'];
			$xAxis[] = $val['TIME1'];
		}
		$returnArr['data'] = $arr;
		$returnArr['yAxisUnit'] = $yAxisUnit;
		$returnArr['xAxisUnit'] = $xAxisUnit;
		$returnArr['xAxis'] = $xAxis;
		return $returnArr;
	}


	/**
	 * 低压进出线柜有功电量
	 * @DateTime 2020-05-29T16:34:29+0800
	 * @param    [type]
	 * @return   [type]
	 */
	private function lowInAndOutCabinetActiveElect($where, $field)
	{	
		$xAxisUnit = '时间: 时';
		$field = $field.",ACTIVE_ELECTRICITY,date_format(TIME,'%H:%i') as TIME1";
		$yAxisUnit = "有功电量: KWH";
		$list = MultimeterDataHistory::getList($where, $field);
		$arr[0]['name'] = '有功电量';
		$arr[0]['list'] = [];
		$xAxis = [];
		foreach ($list as $key => $value) {
			$arr[0]['list'][] = $val['ACTIVE_ELECTRICITY'];
			$xAxis[] = $val['TIME1'];
		}

		$returnArr['data'] = $arr;
		$returnArr['yAxisUnit'] = $yAxisUnit;
		$returnArr['xAxisUnit'] = $xAxisUnit;
		$returnArr['xAxis'] = $xAxis;
		return $returnArr;
	}



	/**
	 * 低压进出线柜功率因数
	 * @DateTime 2020-05-29T16:34:29+0800
	 * @param    [type]
	 * @return   [type]
	 */
	private function lowInAndOutCabinetPowerFactor($where, $field)
	{	
		$xAxisUnit = '时间: 时';
		$field = $field.",POWER_FACTOR,date_format(TIME,'%H:%i') as TIME1";
		$yAxisUnit = "因数: ";

		$list = MultimeterDataHistory::getList($where, $field);
		$arr[0]['name'] = '功率因数';
		$arr[0]['list'] = [];
		$xAxis = [];
		foreach ($list as $key => $value) {
			$arr[0]['list'][] = $val['POWER_FACTOR'];
			$xAxis[] = $val['TIME1'];
		}

		$returnArr['data'] = $arr;
		$returnArr['yAxisUnit'] = $yAxisUnit;
		$returnArr['xAxisUnit'] = $xAxisUnit;
		$returnArr['xAxis'] = $xAxis;
		return $returnArr;
	}


	/**
	 * 低压进出线柜三相电压
	 * @DateTime 2020-05-29T16:34:29+0800
	 * @param    [type]
	 * @return   [type]
	 */
	private function lowInAndOutCabinetVoltage($where, $field)
	{	
		$xAxisUnit = '时间: 时';
		$field = $field.",VOLTAGE_A,VOLTAGE_B,VOLTAGE_C,date_format(TIME,'%H:%i') as TIME1";
		$yAxisUnit = "电压: V";
		$list = MultimeterDataHistory::getList($where, $field);
		$arr[0]['name'] = 'A相电压';
		$arr[1]['name'] = 'B相电压';
		$arr[2]['name'] = 'C相电压';
		$arr[0]['list'] = [];
		$arr[1]['list'] = [];
		$arr[2]['list'] = [];
		$xAxis = [];
		foreach ($list as $key => $value) {
			$arr[0]['list'][] = $val['VOLTAGE_A'];
			$arr[1]['list'][] = $val['VOLTAGE_B'];
			$arr[2]['list'][] = $val['VOLTAGE_C'];
			$xAxis[] = $val['TIME1'];
		}

		$returnArr['data'] = $arr;
		$returnArr['yAxisUnit'] = $yAxisUnit;
		$returnArr['xAxisUnit'] = $xAxisUnit;
		$returnArr['xAxis'] = $xAxis;
		return $returnArr;
	}


	/**
	 * 低压进出线柜三相电流
	 * @DateTime 2020-05-29T16:27:33+0800
	 * @param    string
	 * @return   [type]
	 */
	private function lowInAndOutCabinetCurrent($where, $field)
	{	
		$xAxisUnit = '时间: 时';
		$field = $field.",CURRENT_A,CURRENT_B,CURRENT_C,date_format(TIME,'%H:%i') as TIME1";
		$yAxisUnit = "电流: A";
		$list = MultimeterDataHistory::getList($where, $field);
		$arr[0]['name'] = 'A相电流';
		$arr[1]['name'] = 'B相电流';
		$arr[2]['name'] = 'C相电流';
		$arr[0]['list'] = [];
		$arr[1]['list'] = [];
		$arr[2]['list'] = [];
		$xAxis = [];
		foreach ($list as $key => $value) {
			$arr[0]['list'][] = $val['CURRENT_A'];
			$arr[1]['list'][] = $val['CURRENT_B'];
			$arr[2]['list'][] = $val['CURRENT_C'];
			$xAxis[] = $val['TIME1'];
		}

		$returnArr['data'] = $arr;
		$returnArr['yAxisUnit'] = $yAxisUnit;
		$returnArr['xAxisUnit'] = $xAxisUnit;
		$returnArr['xAxis'] = $xAxis;
		return $returnArr;
	}

}