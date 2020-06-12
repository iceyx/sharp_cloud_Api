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
		ValidataCommon::validateCheck(['USERID' => 'require'], $this->request->param('')); //参数验证
		if (isset($param['CITYNAME']) && $param['CITYNAME']) {
			$company_list = Company::getCompanyByUidCityName($param['USERID'],$param['CITYNAME']);
		}else{
			$company_list = Company::getCompanyByUid($param['USERID']);
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

		
		return self::returnMsg(200, 'success', $company_list);

	}
}