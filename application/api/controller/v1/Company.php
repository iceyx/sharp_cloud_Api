<?php
namespace app\api\controller\v1;

use think\Request;
use app\api\controller\Send;
use app\api\controller\Api;
use app\api\common\Page;
use app\api\validate\ValidataCommon;
use think\Db;
use app\api\model\CaotspDay;
use app\api\model\ClaasHour;
use app\api\model\Company as CompanyModel;
use app\api\model\RoomModuleApparatus;
use app\api\model\CeasDay;

/**
 * 公司
 */
class Company extends Api
{	

	/**
	 * 公司数据
	 * [getCompanyData description]
	 * @DateTime 2020-06-08T15:33:50+0800
	 * @return   [type]                   [description]
	 */
	public function getCompanyData()
	{	
		$uid = $this->user_id;

		$companys = CompanyModel::getCompanyByUid($uid);
		//企业数
		$companyCount = count($companys);
		//检测点数
		$ponit = RoomModuleApparatus::getPoint();
		//月度电量
		$mouthElectricity = CeasDay::getMouthElectri();
		//年度电量
		$yearElectricity = CeasDay::getYearElectri();


		//总负荷
		$elecSql = Db::name('electricity_meter')
						->alias('el')
						->leftJoin(['tran_room_module_apparatus trma'],'el.ID = trma.APPARATUS_ID')
						->leftJoin(['tran_room_module trm'], 'trma.ROOM_MODULE_ID = trm.ID')
						->select();
		foreach ($elecSql as $key => $value) {
			$num[$key] = $value['LINK_NUMBER'];
		}

		$numstr = implode(',', $num);
		$totalLoad = db::name('electricity_meter_data_ct')
						->alias('eld')
						->field('CAST(sum(eld.ACTIVE_POWER) AS DECIMAL(10.5)) as tolalLoad')
						->where('eld.LINK_NUMBER','in',$num)
						->find();
		 //容量
		$totalCapacity = Db::name('multimeter')
							->alias('mm')
							->field('sum(mm.TRANSFORMER_CAPACITY) AS totalCapacity')
							->leftJoin(['tran_room_module_apparatus trma'], 'mm.ID = trma.APPARATUS_ID and trma.APPARATUS_TYPE = 2')
							->leftJoin(['tran_room_module trm'], 'trma.ROOM_MODULE_ID = trm.ID')
							->find();
		//最大负荷
		$maxLoad = ClaasHour::getMaxLoad();

		$companyData = [];
		$companyData['companyCount'] = $companyCount ? $companyCount : 0;
		$companyData['pont'] = $ponit ? $ponit : 0;
		$companyData['mouthElectricity'] = $mouthElectricity['mouthelectri'] ? $mouthElectricity['mouthelectri'] : 0.00;
		$companyData['yearElectricity'] = $yearElectricity['yearElectri'] ? $yearElectricity['yearElectri'] : 0.00;
		$companyData['totalLoad'] = $totalLoad['tolalload'] ? $totalLoad['tolalload'] : 0.00;
		$companyData['totalCapacity'] = $totalCapacity['totalcapacity'] ? $totalCapacity['totalcapacity'] : 0;
		$companyData['maxLoad'] = $maxLoad['LARGEST_LOAD'] ? $maxLoad['LARGEST_LOAD'] : 0.0;

		return render_json($companyData);

	}
	/**
	 * 单位用电-电量（图饼）
	 * @DateTime 2020-05-25T09:58:25+0800
	 * @param    string
	 */
	public function CompanyElecPie()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		$where = [];
		if($param['START_TIME']){
		    $where['STATISTICS_TIME'][]=array('egt',$param['START_TIME']);
		}
	    
		if($param['END_TIME']){
			$where['STATISTICS_TIME'][]=array('elt',$param['END_TIME']);   
		}
		$pie = CaotspDay::getCompanyElecPie($where);
		return self::returnMsg(200,'success',$pie);
	}


	/**
	 * 单位用电-电量（列表）(饼图)
	 * @DateTime 2020-05-25T10:20:09+0800
	 * @param    string
	 */
	public function CompanyElecList()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		$where = [];
		if ($param['START_TIME']) {
			$start_time = date('Y-m-1 00:00:00', strtotime($param['START_TIME']));
			$where['START_TIME'][] = ['egt', $start_time];
		}
		if ($param['END_TIME']) {
			$mdays = date('t', strtotime($param['END_TIME']));
			$end_time = date('Y-m-'.$mdays.' 23:59:59',  strtotime($param['END_TIME']));
			$where['END_TIME'][] = ['elt', $end_time];
		}
		$OFFSET = $param['OFFSET'] ? $param['OFFSET'] : 20;
		$LIMIT = $param['LIMIT'] ? $param['LIMIT'] : 0;

		$list = CaotspDay::getElecList($where,$LIMIT,$OFFSET);

		$pie = CaotspDay::getElecPie($where);
		if ($list) {
			foreach ($list as $key => $value) {
				$sum = $value['PEAK_POWER'] + $value['VALLEY_POWER'] + $value['FLAT_POWER'] + $value['CUSP_POWER'];
				$list[$key]['TOAL_POWER'] = $sum;
			}

			$arr['electricity_list'] = $list;
			$arr['electricity_pie'] = $pie[0];
			return self::returnMsg('200','success',$arr);
		}

		return self::returnMsg('200','无数据');

	}


	/**
	 * 单位用电-负荷（列表）(饼图)
	 * @DateTime 2020-05-25T10:38:53+0800
	 * @param    string
	 */
	public function CompanyLoadList()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		if ($param['LOAD_TIME']) {
			$START_TIME=date('Y-m-d H:i:s',(strtotime($param['LOAD_TIME'])));//选择开始时间
			$END_TIME=date('Y-m-d H:i:s',(strtotime($param['LOAD_TIME'])+86400));//开始时间加上一天时间
			$now_list = ClaasHour::getCompanyNow($START_TIME,$END_TIME);
		    $START_TIME1=date('Y-m-d H:i:s',(strtotime($param['LOAD_TIME'])-86400));//开始时间为选择时间减上一天时间
		    $END_TIME1=date('Y-m-d H:i:s',(strtotime($param['LOAD_TIME'])));//结束时间为当前选择时间
		    $past_list = ClaasHour::getCompanyYesterDay($START_TIME1, $END_TIME1);

		}else{
			$now_list = ClaasHour::getCompanyNowDay();
			$past_list = ClaasHour::getCompanyYesterDayNow();
		   
		}

		$arr=array('00:00','01:00','02:00','03:00','04:00','05:00','06:00','07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00','22:00','23:59');

		$yAxis='';
		if ($now_list) {
			foreach ($arr as $a => $b) {
				foreach ($now_list as $key => $value) {
					if ($value['TIME1'] == $b) {
						$yAxis[$b] = sprintf("%.2f", $value['AVERAGE_LOAD']);
						break;
					}else{
						$yAxis[$b] = '';
					}
				}
			}
		}
		$yAxis_past = '';
		if ($past_list) {
			foreach ($arr as $a => $b) {
				foreach ($past_list as $key => $value) {
					if ($value['TIME1'] == $b) {
						$yAxis_past[$b] = sprintf("%.2f", $value['AVERAGE_LOAD']);
						break;
					}else{
						$yAxis_past[$b] = '';
					}
				}
			}
		}

		$data[0]['name'] = '前天';
		$data[1]['name'] = '前一天';
		$array = array('0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0');
		$data[0]['values'] = $yAxis ? array_values($yAxis) : $array;
		$data[1]['values'] = $yAxis_past ? array_values($yAxis_past) : $array;

		$num = count($now_list);
		$arrSort = [];
		if ($num == 0) {
			$newarr['LARGEST_LOAD'] = '0.00';
			$newarr['PEAK_VALLEY_DIFFERENCE'] = '0.00';
			$newarr['PEAK_VALLEY_RATE'] = '0.00';
			$newarr['AVERAGE_LOAD'] = '0.00';
			$newarr['LOAD_RATE'] = '0.00';
			$newarr['LARGEST_LOAD'] = '0.00';
			$newarr['LARGEST_LOAD_TIME'] = '';
			$newarr['MINIMUM_LOAD'] = '0.00';
			$newarr['MINIMUM_LOAD_TIME'] = '';
		}else{
			foreach ($now_list as $uniqid => $row) {
			foreach ($row as $key => $value) {
					$arrSort[$key][$uniqid] = $value;
				}
			}
			$maxkey=array_search(max($arrSort['LARGEST_LOAD']),$arrSort['LARGEST_LOAD']);
			$minkey=array_search(min($arrSort['MINIMUM_LOAD']),$arrSort['MINIMUM_LOAD']);
			$newarr['PEAK_VALLEY_DIFFERENCE']=sprintf("%.2f",array_sum($arrSort['PEAK_VALLEY_DIFFERENCE'])/$num);
			$newarr['PEAK_VALLEY_RATE']=sprintf("%.2f",array_sum($arrSort['PEAK_VALLEY_RATE'])*100/$num);
			$newarr['AVERAGE_LOAD']=sprintf("%.2f",array_sum($arrSort['AVERAGE_LOAD'])/$num);
			$newarr['LOAD_RATE']=sprintf("%.2f",array_sum($arrSort['LOAD_RATE'])*100/$num);
			$newarr['LARGEST_LOAD']=sprintf("%.2f",max($arrSort['LARGEST_LOAD']))  ;

			$newarr['LARGEST_LOAD_TIME']=$arrSort['LARGEST_LOAD_TIME'][$maxkey];
			$newarr['MINIMUM_LOAD']=sprintf("%.2f",min($arrSort['MINIMUM_LOAD']));
			$newarr['MINIMUM_LOAD_TIME']=$arrSort['MINIMUM_LOAD_TIME'][$minkey];
			$newarr=array_change_key_case($newarr,CASE_LOWER);
			array_case($newarr);
		}

		$newarr['yAxisUnit']='KWH';
		$newarr['xAxisUnit']='时';
		$newarr['data']=$data;
		$newarr['xAxis']=$arr;
		$newarr['item'] =implode(',',$data[0]['values']);
		$newarr['item1'] =implode(',',$data[1]['values']);
		$newarr['item2'] =implode(',',$arr);
		return self::returnMsg(200,'success',$newarr);

	}


	/**
	 * 公司列表
	 * @DateTime 2020-05-25T15:34:01+0800
	 * @return   [type]
	 */
	public function companyList()
	{	
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['USERID' => 'require'], $this->request->param('')); //参数验证
		return self::returnMsg(200, 'success', CompanyModel::getCompanyByUid($param['USERID']));
	}


	/**
	 * 报装分析(未用)
	 * @DateTime 2020-05-25T16:18:00+0800
	 * @return   [type]
	 */
	public function reportingAnalysis()
	{	
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['LINK_NUMBER' => 'require'], $this->request->param('')); //参数验证
		
		$where['LINK_NUMBER'] = ['eq',$param['LINK_NUMBER']];
		$timestamp = strtotime($param['START_TIME']);
	    $timestamp1 = strtotime($param['END_TIME']);
	    $start_time = date( 'Y-m-1 00:00:00', $timestamp );
	   
        $mdays = date( 't', $timestamp1);
        $end_time = date( 'Y-m-' . $mdays . ' 23:59:59', $timestamp1 );

	    $OFFSET = $param['OFFSET'] ? $param['OFFSET'] : 20;
		$LIMIT = $param['LIMIT'] ? $param['LIMIT'] : 0;

		$reportData = Db::name('rfa_month')
						->field("*,date_format(STATISTICS_TIME,'%Y-%m') as STATISTICS_TIME")
						->where($where)
						->whereBetweenTime('STATISTICS_TIME',$start_time, $end_time)
						->order('STATISTICS_TIME desc')
						->limit($LIMIT, $OFFSET)
						->select();
		return self::returnMsg(200, 'success', $reportData);

	}


	/**
	 * @DateTime 2020-05-25T16:57:38+0800
	 * @return   [type]
	 */
	public static function getyq()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证

		$where['tran_room_module.COMPANY_ID'] = ['eq', $param['COMPANY_ID']];
		$where['sys_dictionaries.BIANMA'] = ['eq', '4'];
		$list = Db::table('tran_room_module')
					->join('LEFT JOIN sys_dictionaries ON tran_room_module.PID, sys_dictionaries.DICTIONARIES_ID')
					->where($where)
					->find();
		$where1['tran_room_module_apparatus.ROOM_MODULE_ID'] = $list['ID'];
		$where1['mst_multimeter.ID'] = ['exp','IS NOT NULL'];
		$dgnb_list = Db::table('tran_room_module_apparatus')
						->join('LEFT JOIN mst_multimeter ON mst_multimeter.ID, tran_room_module_apparatus.APPARATUS_ID')
						->where($where1)
						->field('mst_multimeter.NAME,mst_multimeter.LINK_NUMBER')
						->select();

		return returnMsg(200, 'success', $dgnb_list);
	}


	/**
	 * @DateTime 2020-05-25T18:08:21+0800
	 * @param    string
	 */
	public function Voice($value='')
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['STRING' => 'require'], $this->request->param('')); //参数验证
		$mystr = $param['STRING'];
		$findme = '打开';
		$pos = strpos($mystr, $findme);
		if ($pos === false) return self::returnMsg(300,'没有找到该设备！',$pos);

		$string  = str_replace(["打开", ",", "."], "", $mystr);
		$company_list =  CompanyModel::getCompanyByUid($param['USER_ID']);
		$company_name = '';
		$item = '';
		$item = '';
		foreach ($company_list as $key => $value) {
			$pos = strpos($string, $value['NAME']);
			$company_name = $pos ? $value['NAME'] : '';
		}

		$string = str_replace($company_name, "", $string);
		$pos = strpos($string, '高压柜');
		$item = $pos ? '高压柜' : '';

		$pos = strpos($string, '低压进线柜');
		$item = $pos ? '低压进线柜' : '';

		$string = str_replace($item, "", $string);

		$where['NAME'] = $item ? $string : $string;
		$data['TYPE'] = $item;

		$a = Db::name('cipd')->where($where)->find();
		$b = Db::name('fault_meter')->where($where)->find();
		$c = Db::name('multimeter')->where($where)->find();
		switch ($item) {
			case '高压柜':
				if (!$a && !$b) return self::returnMsg(300,'没有找到该设备2！','');
				$data['LINK_NUMBER'] = $a ? $a['LINK_NUMBER'] : $b['LINK_NUMBER'];
				$data['STRING'] = '您确定要打开,' . $company_name . ',' . $item . ',' . $item1 . '吗?';
				break;

			case '低压进线柜':
				if (!$c ) return self::returnMsg(300,'没有找到该设备2！','');
				$data['LINK_NUMBER'] = $c ? $c['LINK_NUMBER'] : '';
				$data['STRING'] = '您确定要打开,' . $company_name . ',' . $item . ',' . $item1 . '吗?';
				break;
			
			default:
				if (!$a && !$b && !$c) return self::returnMsg(300,'没有找到该设备3！','');
				$data['LINK_NUMBER'] = $a ? $a['LINK_NUMBER'] : '';
				$data['LINK_NUMBER'] = $b ? $b['LINK_NUMBER'] : '';
				$data['LINK_NUMBER'] = $c ? $c['LINK_NUMBER'] : '';
				$data['TYPE'] = $c ? '低压进线柜' : '高压柜';
				$data['STRING']='你确定要打开,'.$string.'吗?';
				break;
		}

		return returnMsg(200, 'success', $data);

	}


}