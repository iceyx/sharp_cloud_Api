<?php
namespace app\api\controller\v1;

use think\Request;
use app\api\controller\Send;
use app\api\controller\Api;
use app\api\common\Page;
use app\api\validate\ValidataCommon;
use think\Db;
use app\api\model\Company;
use app\api\model\FaultInfo;
use think\facade\Cache;

/**
 * 运行状况
 */
class Operation extends Api
{
	

	/**
	 * 运行状况-公司列表(未加经纬度查询)
	 * @DateTime 2020-05-25T18:30:49+0800
	 * @return   [type]
	 */
	public function companyList()
	{
		$param = $this->request->param('');
		$uid = $this->user_id;

		// $list = Db::query("SELECT
		// 					 c.*, (
		// 					  SELECT
		// 					   count(0)
		// 					  FROM
		// 					   mst_fault_info
		// 					  WHERE
		// 					   LINK_NUMBER IN (
		// 					    SELECT
		// 					     mm.LINK_NUMBER
		// 					    FROM
		// 					     tran_room_module_apparatus trma
		// 					    JOIN tran_room_module trm ON trma.ROOM_MODULE_ID = trm.ID
		// 					    JOIN mst_multimeter mm ON mm.ID = trma.APPARATUS_ID
		// 					    WHERE
		// 					     trm.COMPANY_ID = c.id
		// 					    UNION ALL
		// 					     SELECT
		// 					      mem.LINK_NUMBER
		// 					     FROM
		// 					      tran_room_module_apparatus trma
		// 					     JOIN tran_room_module trm ON trma.ROOM_MODULE_ID = trm.ID
		// 					     JOIN mst_electricity_meter mem ON mem.ID = trma.APPARATUS_ID
		// 					     WHERE
		// 					      trm.COMPANY_ID = c.id
		// 					     UNION ALL
		// 					      SELECT
		// 					       mfm.LINK_NUMBER
		// 					      FROM
		// 					       tran_room_module_apparatus trma
		// 					      JOIN tran_room_module trm ON trma.ROOM_MODULE_ID = trm.ID
		// 					      JOIN mst_fault_meter mfm ON mfm.ID = trma.APPARATUS_ID
		// 					      WHERE
		// 					       trm.COMPANY_ID = c.id
		// 					      UNION ALL
		// 					       SELECT
		// 					        mtc.LINK_NUMBER
		// 					       FROM
		// 					        tran_room_module_apparatus trma
		// 					       JOIN tran_room_module trm ON trma.ROOM_MODULE_ID = trm.ID
		// 					       JOIN mst_temperature_controller mtc ON mtc.ID = trma.APPARATUS_ID
		// 					       WHERE
		// 					        trm.COMPANY_ID = c.id
		// 					       UNION ALL
		// 					        SELECT
		// 					         mc.LINK_NUMBER
		// 					        FROM
		// 					         tran_room_module_apparatus trma
		// 					        JOIN tran_room_module trm ON trma.ROOM_MODULE_ID = trm.ID
		// 					        JOIN mst_cipd mc ON mc.ID = trma.APPARATUS_ID
		// 					        WHERE
		// 					         trm.COMPANY_ID = c.id
		// 					   )
		// 					  AND IS_READING = 0
		// 					 ) AS num
		// 					FROM
		// 					 MST_COMPANY c
		// 					LEFT JOIN SYS_DICTIONARIES d ON c.AREA_ID = d.DICTIONARIES_ID
		// 					LEFT JOIN SYS_DICTIONARIES dp ON d.PARENT_ID = dp.DICTIONARIES_ID
		// 					WHERE
		// 					 1 = 1
		// 					AND PID != '0'");

		// return render_json($list);exit;
		//Cache::rm('company_list');
		//上线不使用缓存
		// if (Cache::get('company_list')) {
		// 	return render_json(unserialize(Cache::get('company_list')));
		// }
		if (isset($param['CITYNAME']) && $param['CITYNAME']) {
			$company_list = Company::getCompanyByUidCityName($uid,$param['CITYNAME']);
		}else{
			$company_list = Company::getCompanyByUid($uid)->toArray();
		}
		if ($param['NAME']) {
			foreach ($company_list as $key => $value) {
				if ($param['NAME']==$value['NAME']) {
					$company_list[$key]['NUM'] = FaultInfo::getFaultByCompanyId($value['ID']);
				}else{
					unset($company_list[$key]);
				}
			}
		}else{
			foreach ($company_list as $key => $value) {
				$company_list[$key]['NUM'] = FaultInfo::getFaultByCompanyId($value['ID']);
			}
		}
		foreach ($company_list as $key => $value) {
			if ($company_list[$key]['NUM'] >= 1) {
				$fault = FaultInfo::getFaultByCompanyId($value['ID'],true);
				$company_list[$key]['fault_id'] = $fault['ID'];
				$company_list[$key]['equipment_name'] = $fault['EQUIPMENT_NAME'];
				$company_list[$key]['status'] = $fault['STATUS'];
				$company_list[$key]['ip_address'] = $fault['IP_ADDRESS'];
				$company_list[$key]['monitoring_info'] = $fault['MONITORING_INFO'];
				$company_list[$key]['electrician_id'] = $fault['ELECTRICIAN_ID'];
				$company_list[$key]['is_reading'] = $fault['IS_READING'];
				$company_list[$key]['occurrence_time'] = $fault['OCCURRENCE_TIME'];
				$company_list[$key]['link_number'] = $fault['LINK_NUMBER'];
				if (!$value['ID']) {
					unset($company_list[$key]);
				}
				//$company_list = array_values($company_list);
			}
		}

		//Cache::set('company_list',serialize($company_list),86000);
		
		return render_json($company_list);

	}
}