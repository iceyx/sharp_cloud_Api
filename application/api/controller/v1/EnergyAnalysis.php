<?php
namespace app\api\controller\v1;

use think\Request;
use app\api\controller\Send;
use app\api\controller\Api;
use app\api\validate\ValidataCommon;
use app\api\model\Esqr;
use app\api\model\ClaasHour;
use app\api\model\CaotspDay;
use think\Db;

/**
 * 节能分析
 */
class EnergyAnalysis extends Api
{
	/**
	 * 负荷分析列表
	 * @DateTime 2020-05-29T18:16:43+0800
	 */
	public function LoadList()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		$USERID = $param['USERID'];
		$OFFSET = $param['OFFSET'] ? $param['OFFSET'] : 20;
		$LIMIT = $param['LIMIT'] ? $param['LIMIT'] : 0;
		$where['COMPANY_ID'] = $param['COMPANY_ID'];
		$where['USER_ID'] = $USERID;
		$list = Esqr::getList($where, $LIMIT, $OFFSET);
		foreach ($list as $key => $value) {
			if ($value['AVERAGE_LOAD_PRE5'] >= $value['AVERAGE_LOAD']) {
				 //减少
				$list[$key]['type'] = 1;
			}else{
				//增加
				$list[$key]['type'] = 2;
			}
		}

		return count($list) ? self::returnMsg(200, 'success', $list) : self::returnMsg(300, '无数据!', null);
	}


	/**
	 * 添加分析
	 * @DateTime 2020-05-29T18:32:17+0800
	 */
	public function addList()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		$USERID = $param['USERID'];
		$starttime=DATE('Y-m-d',strtotime($param['START_TIME']));
		$endtime=DATE('Y-m-d',strtotime($param['END_TIME']));
		if ($starttime == $endtime) {
			return self::returnMsg(300, '请选择同一天时间段！');
		}



		$where['LOAD_TIME'] = ['between', [$param['START_TIME'] , $param['END_TIME']]];



		$where['COMPANY_ID'] = $param['COMPANY_ID'];
		$field = 'avg(AVERAGE_LOAD) as AVERAGE_LOAD';
		$list = ClaasHour::getTimeData($where, $field);// 选择时间段的平均负荷
		if (count($list) == 0) {
			return self::returnMsg(300, '无数据',null);
		}

		$weekData = ClaasHour::getWeek($field);

		$avg1 = sprintf('%.1f', $list['AVERAGE_LOAD']);
		$avg2 = sprintf('%.1f', $weekData['AVERAGE_LOAD_PRE5']);//获取本周数据

		$reduce_load = $avg2 - $avg1;

		$data['REDUCE_LOAD'] = ads('$reduce_load');
		$data['ID'] = md5($USERID.time());
		$data['COMPANY_ID'] = $param['COMPANY_ID'];
		$data['AVERAGE_LOAD'] = $avg1;
		$data['AVERAGE_LOAD_PRE5'] = $avg2;
		$data['START_TIME'] = $param['START_TIME'];
		$data['END_TIME'] = $param['END_TIME'];
		$data['USER_ID'] = $USERID;

		$res = Esqr::add($data);

		return $res ? self::returnMsg(200, '添加成功！',$res) : self::returnMsg(300, '添加失败', null);
	}



	/**
	 * 删除
	 * @DateTime 2020-06-01T09:25:02+0800
	 * @return   [type]
	 */
	public function delList()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['ID' => 'require'], $this->request->param('')); //参数验证
		$where['ID'] = $param['ID'];
		$res = Esqr::delete($where);
		return $res ? self::returnMsg(200, '删除成功',$res) : self::returnMsg(300, '删除失失败', null);
	}

	/**
	 * 
	 * @DateTime 2020-06-01T09:54:55+0800
	 * @return   [type]
	 */
	public function fpg()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		$where['COMPANY_ID'] = $param['COMPANY_ID'];
		$elect_pie = CaotspDay::getElecPieByTime($where);
		
		if (count($elect_pie)) {
			$spike = ($elect_pie[0]['PEAK_POWER'] + $elect_pie[0]['CUSP_POWER']) * 0.36;//计算尖峰电量
			$arr['data'] = $elect_pie;
			$money = sprintf('%.2f', $spike);
			$str = "温馨提示：目前尖峰电量占比比较高，建议降低在尖峰时段负荷。大概节约{$money}电费";
			$arr['proposal'] = urlencode($str);
			return self::returnMsg(200, 'success', $arr);
		}

		return self::returnMsg(300, '无数据！', null);

	}


	/**
	 *  //报装分析
	 * @DateTime 2020-06-02T09:02:38+0800
	 * @param    string                   $value [description]
	 */
	public function demandAnalysis()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		$OFFSET = $param['OFFSET'] ? $param['OFFSET'] : 20;
		$LIMIT = $param['LIMIT'] ? $param['LIMIT'] : 0;
		// $start_time = date('Y-m-01 00:00:00',strtotime('-1 month'));
		//$end_time = date("Y-m-d 23:59:59", strtotime(-date('d').'day'));
		$reporting = Db::name('multimeter')
						->alias('m')
					   ->leftJoin('rfa_month r','m.LINK_NUMBER = r.LINK_NUMBER')
					   ->whereTime('STATISTICS_TIME', 'last month')
					   ->where('r.COMPANY_ID', 'eq', $param['COMPANY_ID'])
					   ->field('r.*, date_format(r.STATISTICS_TIME,"%Y-%m-%d") as STATISTICS_TIME, m.NAME')
					   ->order('r.STATISTICS_TIME DESC')
					   ->limit($LIMIT, $OFFSET)
					   ->select();
		//echo Db::name('multimeter')->getlastsql();exit;
		if (count($reporting)) {
			foreach ($reporting as $key => $value) {
				if ($value['VOUCH_DEMAND'] > $value['CAPACITY']) {
					$reporting[$key]['INFO'] = '建议：更换为容量报装方式可节省'.$value['PRICE_SPREAD'];
				}else{
					$reporting[$key]['INFO'] = '建议：更换为需量报装方式可节省'.$value['PRICE_SPREAD'];
				}
			}


			return self::returnMsg(200, 'success', $reporting);
		}

		return self::returnMsg(300, '无数据！',null);


	}


}
