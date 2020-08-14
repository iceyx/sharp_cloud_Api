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
		$USERID = $this->user_id;
		$page = $param['PAGE'] ? $param['PAGE'] : 1;
		$LIMIT = $param['LIMIT'] ? $param['LIMIT'] : 0;
		$where['COMPANY_ID'] = $param['COMPANY_ID'];
		$where['USER_ID'] = $USERID;
		$list = Esqr::getList($where, $LIMIT, $page);
		foreach ($list as $key => $value) {
			if ($value['AVERAGE_LOAD_PRE5'] >= $value['AVERAGE_LOAD']) {
				 //减少
				$list[$key]['type'] = 1;
			}else{
				//增加
				$list[$key]['type'] = 2;
			}
		}

		return render_json($list);
	}


	/**
	 * 添加分析
	 * @DateTime 2020-05-29T18:32:17+0800
	 */
	public function addList()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		$USERID = $this->user_id;
		$starttime=DATE('Y-m-d',strtotime($param['START_TIME']));
		$endtime=DATE('Y-m-d',strtotime($param['END_TIME']));
		if ($starttime != $endtime) {
			return render_json('', '请选择同一天时间段！',201);
		}


		$whereTime =[$param['START_TIME'] , $param['END_TIME']];


		$where['COMPANY_ID'] = $param['COMPANY_ID'];
		$field = 'avg(AVERAGE_LOAD) as AVERAGE_LOAD';
		$list = ClaasHour::getTimeData($where,$whereTime, $field);// 选择时间段的平均负荷
		if (!$list['AVERAGE_LOAD']) {
			return render_json('', '该时间段无数据~', 201);
		}
		$fday = date('y-m-d', strtotime('-5 days', strtotime($starttime)));
		$weekwehreT = [$starttime, $endtime];
		$weekwehre = [$fday, $starttime];
		$weekData = ClaasHour::getWeek($weekwehre,$weekwehreT, $field);
		$avg1 = sprintf('%.1f', $list['AVERAGE_LOAD']);
		$avg2 = sprintf('%.1f', $weekData['AVERAGE_LOAD_PRE5']);//获取本周数据
		$reduce_load = $avg2 - $avg1;

		//$data['REDUCE_LOAD'] = ads($reduce_load);
		$data['REDUCE_LOAD'] = $reduce_load;
		$data['ID'] = md5($USERID.time());
		$data['COMPANY_ID'] = $param['COMPANY_ID'];
		$data['AVERAGE_LOAD'] = $avg1;
		$data['AVERAGE_LOAD_PRE5'] = $avg2;
		$data['START_TIME'] = $param['START_TIME'];
		$data['END_TIME'] = $param['END_TIME'];
		$data['USER_ID'] = $USERID;
		$res = Esqr::add($data);
		return render_json($res);
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
		$res = Esqr::notSoftdelete($where);
		return render_json($res);
	}

	/**
	 * 平谷峰分析
	 * @DateTime 2020-06-01T09:54:55+0800
	 * @return   [type]
	 */
	public function fpg()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		$where['COMPANY_ID'] = $param['COMPANY_ID'];
		$elect_pie = CaotspDay::getElecPieByTime($where);
		$allPower = $elect_pie['PEAK_POWER'] + $elect_pie['VALLEY_POWER'] + $elect_pie['FLAT_POWER'] +$elect_pie['CUSP_POWER'];
		$pie['PEAK_POWER'] = $elect_pie['PEAK_POWER']  ? floatval(number_format($elect_pie['PEAK_POWER'] / $allPower *100, '2')) : 0.00;
		$pie['VALLEY_POWER'] = $elect_pie['VALLEY_POWER'] ? floatval(number_format($elect_pie['VALLEY_POWER'] / $allPower *100,'2')): 0.00;
		$pie['FLAT_POWER'] = $elect_pie['FLAT_POWER'] ? floatval(number_format($elect_pie['FLAT_POWER'] / $allPower *100, '2')) : 0.00;
		$pie['CUSP_POWER'] = $elect_pie['CUSP_POWER'] ? floatval(number_format($elect_pie['CUSP_POWER'] / $allPower *100, '2')) : 0.00;


		$peak['PEAK_POWERS'] = $elect_pie['PEAK_POWER']  ? $elect_pie['PEAK_POWER'] : 0.00;
		$peak['VALLEY_POWERS'] = $elect_pie['VALLEY_POWER'] ? $elect_pie['VALLEY_POWER'] : 0.00;
		$peak['FLAT_POWERS'] = $elect_pie['FLAT_POWER'] ? $elect_pie['FLAT_POWER'] : 0.00;
		$peak['CUSP_POWERS'] = $elect_pie['CUSP_POWER'] ? $elect_pie['CUSP_POWER'] : 0.00;

		$max = array_search(max($peak), $peak);
		$arr = '';
		if ($elect_pie) {
			$spike = ($pie['PEAK_POWER'] + $pie['CUSP_POWER']) * 0.36;//计算尖峰电量
			$arr['peak'] = $peak;
			$arr['pie'] = $pie;
			$money = sprintf('%.2f', $spike);
			$str = "温馨提示：目前尖峰电量占比比较高，建议降低在尖峰时段负荷。大概节约{$money}电费";
			$arr['proposal'] = urlencode($str);
		}
		return render_json($arr);

	}


	/**
	 *  /需量分析
	 * @DateTime 2020-06-02T09:02:38+0800
	 * @param    string                   $value [description]
	 */
	public function demandAnalysis()
	{
		$param = $this->request->param('');
		ValidataCommon::validateCheck(['COMPANY_ID' => 'require'], $this->request->param('')); //参数验证
		$LIMIT = $param['LIMIT'] ? $param['LIMIT'] : 20;
		$page = $param['PAGE'] ? $param['PAGE'] : 1;
		// $start_time = date('Y-m-01 00:00:00',strtotime('-1 month'));
		//$end_time = date("Y-m-d 23:59:59", strtotime(-date('d').'day'));
		$reporting = Db::name('multimeter')
						->alias('m')
					   ->leftJoin('rfa_month r','m.LINK_NUMBER = r.LINK_NUMBER')
					   ->whereTime('STATISTICS_TIME', 'last month')
					   ->where('r.COMPANY_ID', 'eq', $param['COMPANY_ID'])
					   ->field('r.*, date_format(r.STATISTICS_TIME,"%Y-%m-%d") as STATISTICS_TIME, m.NAME')
					   ->order('r.STATISTICS_TIME DESC')
					   ->limit($LIMIT * ($page - 1), $LIMIT)
					   ->select();
		if ($reporting) {
			foreach ($reporting as $key => $value) {
				if ($value['VOUCH_DEMAND'] > $value['CAPACITY']) {
					$reporting[$key]['INFO'] = '建议：更换为容量报装方式可节省'.$value['PRICE_SPREAD'];
				}else{
					$reporting[$key]['INFO'] = '建议：更换为需量报装方式可节省'.$value['PRICE_SPREAD'];
				}
			}
		}
		return render_json($reporting);

	}


}
