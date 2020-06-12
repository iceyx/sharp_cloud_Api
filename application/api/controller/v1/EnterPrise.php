<?php
namespace app\api\controller\v1;

use think\Controller;
use think\Request;
use app\api\controller\Send;
use app\api\controller\Api;
use app\api\common\Page;
use app\api\validate\ValidataCommon;
use app\api\model\Company;
use app\api\model\Notice as NoticeModel;
use app\api\model\ClaasHour as ClaasHourModel;
use think\Db;
/**
 * 
 */
class EnterPrise extends Api
{
	
	/**
	 * 企业用电-电量（饼图）
	 * @DateTime 2020-05-22T18:49:00+0800
	 * @param    Request
	 * @return   [type]
	 */
	public function electriCityPie(Request $request)
	{
		$param = $this->request->param('');
		$company_relation = Company::getCompanyByUid($param['USER_ID']);

		//return returnMsg(200,'success',company_relation);
		$str = implode(',', $company_relation);

		$where['COMPANY_ID'] = ['in', $str];
		if ($param['START_TIME']) {
			$where['STATISTICS_TIME'][] = ['egt', $param['START_TIME']];
		}
		
		if ($param['END_TIME']) {
			$where['STATISTICS_TIME'][] = ['lt', $param['END_TIME']];
		}

		$pie = Db::name('caotsp_day')
			->field('sum(PEAK_POWER) as PEAK_POWER, sum(VALLEY_POWER) as VALLEY_POWER, sum(FLAT_POWER) as FLAT_POWER, sum(CUSP_POWER) as CUSP_POWER')
			->where($where)
			->select();
		return $pie ? self::returnMsg(200,'success',$pie):$pie;
	}

	/**
	 * //企业用电-电量（列表,饼图）
	 * @DateTime 2020-05-23T10:28:18+0800
	 * @return   [type]
	 */
	public function electriCityList()
	{
		$param = $this->request->param('');
		$company_relation = Company::getCompanyByUid($param['USER_ID']);
		if (!$company_relation) return self::returnMsg(300,'用户未关联有企业！',$company_relation);
		foreach ($company_relation as $k => $v) {
			$company_id[$k] = $v['ID'];
		}
		$company_ids = isset($company_id) ? implode(',', $company_id) : $company_id;
		$where['COMPANY_ID'] = ['in', $company_ids];
		
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
		$list = Db::name('caotsp_day')
			->field("date_format(STATISTICS_TIME, '%Y-%m') as STATISTICS_TIME, sum(PEAK_POWER) as PEAK_POWER, sum(VALLEY_POWER) as VALLEY_POWER, sum(FLAT_POWER) as FLAT_POWER, sum(CUSP_POWER) as CUSP_POWER")
			->where($where)
			->order('STATISTICS_TIME desc')
			->group("date_format(STATISTICS_TIME, '%Y-%m')")
			->select();
		$pie = Db::name('caotsp_day')
			->field('sum(PEAK_POWER) as PEAK_POWER, sum(VALLEY_POWER) as VALLEY_POWER, sum(FLAT_POWER) as FLAT_POWER, sum(CUSP_POWER) as CUSP_POWER')
			->where($where)
			->select();

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
	 * 企业用电-负荷（列表）
	 * @DateTime 2020-05-23T11:26:02+0800
	 * @return   [type]
	 */
	public function loadList()
	{
		$param = $this->request->param('');
		$company_relation = Company::getCompanyByUid($this->user_id);
		if (!$company_relation) return self::returnMsg(300,'用户未关联有企业！',$company_relation);
		foreach ($company_relation as $k => $v) {
			$company_id[$k] = $v['ID'];
		}
		$company_ids = isset($company_id) ? implode(',', $company_id) : $company_id;
		$where['COMPANY_ID'] = ['in', $company_ids];
		$where1['COMPANY_ID'] = ['in', $company_ids];
		if ($param['LOAD_TIME']) {
			$START_TIME=date('Y-m-d H:i:s',(strtotime($param['LOAD_TIME'])));
		    $END_TIME=date('Y-m-d H:i:s',(strtotime($param['LOAD_TIME'])+86400));
		    $START_TIME1=date('Y-m-d H:i:s',(strtotime($param['LOAD_TIME'])-86400));
		    $END_TIME1=date('Y-m-d H:i:s',(strtotime($param['LOAD_TIME'])));
		    $where['LOAD_TIME'][]=array('egt',$START_TIME);
			$where['LOAD_TIME'][]=array('lt',$END_TIME);
			$where1['LOAD_TIME'][]=array('egt',$START_TIME1);
			$where1['LOAD_TIME'][]=array('lt',$END_TIME1);
		  
		}else{
			$START_TIME = date("Y-m-d H:i:s",mktime(0,0,0,date("m"),date("d"),date("Y")));
           	$END_TIME = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d"),date("Y")));
		    $START_TIME1=date('Y-m-d H:i:s',mktime(0,0,0,date('m'),date('d')-1,date('Y')));
		    $END_TIME1=date('Y-m-d H:i:s',mktime(0,0,0,date('m'),date('d'),date('Y'))-1);
		  
		   
		}
		$now_list = ClaasHourModel::getNow($where);
		
		$past_list = ClaasHourModel::getYesterDay($where1);

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

		foreach ($now_list as $uniqid => $row) {
			foreach ($row as $key => $value) {
				$arrSort[$key][$uniqid] = $value;
			}
		}
		if (count($now_list) == 0) {
			$arrSort['LARGEST_LOAD'] = 0;
			$arrSort['PEAK_VALLEY_DIFFERENCE'] = 0;
			$arrSort['PEAK_VALLEY_RATE'] = 0;
			$arrSort['AVERAGE_LOAD'] = 0;
			$arrSort['LOAD_RATE'] = 0;
			$arrSort['LARGEST_LOAD'] = 0;
			$arrSort['LARGEST_LOAD_TIME'] = 0;
			$arrSort['MINIMUM_LOAD'] = 0;
			$arrSort['MINIMUM_LOAD_TIME'] = 0;
		}else{
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
	 * @DateTime 2020-05-23T09:44:03+0800
	 * @return   [type]
	 */
	public function noticeNew()
	{	
		$where = [];
		$res = NoticeModel::getNotice($where);
		return self::returnMsg(200,'success',$res);
	}
}