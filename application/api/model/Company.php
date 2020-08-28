<?php
namespace app\api\model;

use think\Model;
use think\Db;
use app\api\model\User;

/**
 * 
 */
class Company extends Model
{   


	/**
     * @DateTime 2020-05-22T18:28:47+0800
     * @param    [array]
     * @return   [array]
     */
    public static function getCompanyByUid($uid)
    {	
    	$arr = [];
        $user = User::getUserinfo($uid);
        $query = Db::table('sys_user')
                        ->alias('u')
                        ->leftJoin(['tran_user_company uc'], 'u.USER_ID = uc.USER_ID')
                        ->field('u.USER_ID, u.NAME as staff_name, uc.COMPANY_ID')
                        ->where('u.USER_TYPE','eq', '2')
                        ->buildSql();
        $field = 'c.ID, c.NAME, c.LONGITUDE, c.LATITUDE, c.ADDRESS, q.staff_name';
    	if ($user['USER_TYPE'] == 0) {
    		switch ($user['AREA_ID']) {
    			case -1:
                    //超级管理员
                    
                    $company_id = Company::alias('c')
                                            ->leftJoin([$query."q"]," c.ID = q.COMPANY_ID")
                                            ->field($field)
                                            ->where('c.PID', 'neq', '0')
                                            ->select();
    				break;
    			default:
                //区域管理员
    				$area = Db::table('sys_dictionaries')->where('DICTIONARIES_ID', 'eq', 'AREA_ID')->field('PARENT_ID')->find();

    				if ($area['PARENT_ID'] == '1') {
                        //需要查询该区域子级城市
    					$area = Db::table('sys_dictionaries')->where('PARENT_ID', 'eq', $user['AREA_ID'])->select();
    					foreach ($area as $key => $value) {
    						$str.= $value['DICTIONARIES_ID'].',';
    					}
    					$areaid = rtrim($str,',');
    					$company_id = Company::alias('c')
                                            ->leftJoin([$query.'q'], 'c.ID = q.COMPANY_ID')
                                            ->whereIn('AREA_ID',$areaid)->where('PID','neq','0')
                                            ->field($field)
                                            ->select();
    					
    				}else{
                        //查询该区域的公司
    					$areaid = $user['AREA_ID'];
                        $where['c.AREA_ID'] = ['=', $areaid];
                        $where['c.PID'] = ['!=', '0'];
    					$company_id = Company::alias('c')
                                            ->leftJoin([$query.'q'], 'c.ID = q.COMPANY_ID')
                                            ->where($where)
                                            ->field($field)
                                            ->select();
    				}

    				break;
    		}

    	}else{//电工企业用户
    		$company_id = Db::table('tran_user_company')
    					->alias('t')
                        ->leftJoin([$query.'q'], 't.COMPANY_ID = q.COMPANY_ID')
    					->leftJoin('company c','t.COMPANY_ID = c.ID')
    					->where('t.USER_ID','=', $user['USER_ID'])
    					->field($field.',c.COMPANY_TYPE')
                        ->group('q.staff_name')
    					->select();
            // if ($company_id) {
            //     foreach ($company_id as $key => $value) {
            //         if ($value['ID'] != null) {
            //             $company_id[] = $value;
            //         }
            //     }
            // }
    	}
    	return $company_id;
    }

        /**
     * @DateTime 2020-05-22T18:28:47+0800
     * @param    [array]
     * @return   [array]
     */
    public static function getCompanyFaultByUid($uid)
    {   
        $arr = [];
        $user = User::getUserinfo($uid);
        $query = Db::table('sys_user')
                        ->alias('u')
                        ->leftJoin(['tran_user_company uc'], 'u.USER_ID = uc.USER_ID')
                        ->field('u.USER_ID, u.NAME as staff_name, uc.COMPANY_ID')
                        ->where('u.USER_TYPE','eq', '2')
                        ->buildSql();
        $field = 'c.ID, c.NAME, c.LONGITUDE, c.LATITUDE, c.ADDRESS, q.staff_name';
        if ($user['USER_TYPE'] == 0) {
            switch ($user['AREA_ID']) {
                case -1:
                    //超级管理员
                    
                    $company_id = Company::alias('c')
                                            ->leftJoin([$query."q"]," c.ID = q.COMPANY_ID")
                                            ->field($field)
                                            ->where('c.PID', 'neq', '0')
                                            ->select()->toArray();
                    break;
                default:
                //区域管理员
                    $area = Db::table('sys_dictionaries')->where('DICTIONARIES_ID', 'eq', 'AREA_ID')->field('PARENT_ID')->find();

                    if ($area['PARENT_ID'] == '1') {
                        //需要查询该区域子级城市
                        $area = Db::table('sys_dictionaries')->where('PARENT_ID', 'eq', $user['AREA_ID'])->select();
                        foreach ($area as $key => $value) {
                            $str.= $value['DICTIONARIES_ID'].',';
                        }
                        $areaid = rtrim($str,',');
                        $company_id = Company::alias('c')
                                            ->leftJoin([$query.'q'], 'c.ID = q.COMPANY_ID')
                                            ->whereIn('AREA_ID',$areaid)->where('PID','neq','0')
                                            ->field($field)
                                            ->select()->toArray();
                        
                    }else{
                        //查询该区域的公司
                        $areaid = $user['AREA_ID'];
                        $where['c.AREA_ID'] = ['=', $areaid];
                        $where['c.PID'] = ['!=', '0'];
                        $company_id = Company::alias('c')
                                            ->leftJoin([$query.'q'], 'c.ID = q.COMPANY_ID')
                                            ->where($where)
                                            ->field($field)
                                            ->select()->toArray();
                    }

                    break;
            }

        }else{//电工企业用户
            $company_id = Db::table('tran_user_company')
                        ->alias('t')
                        ->leftJoin([$query.'q'], 't.COMPANY_ID = q.COMPANY_ID')
                        ->leftJoin('company c','t.COMPANY_ID = c.ID')
                        ->where('t.USER_ID','=', $user['USER_ID'])
                        ->field($field.',c.COMPANY_TYPE')
                        ->group('q.staff_name')
                        ->select()->toArray();
            // if ($company_id) {
            //     foreach ($company_id as $key => $value) {
            //         if ($value['ID'] != null) {
            //             $company_id[] = $value;
            //         }
            //     }
            // }
        }
        return $company_id;
    }


    /**
     * 以用户id、城市名称查找公司列表
     * @DateTime 2020-05-27T16:10:20+0800
     * @param    [type]
     * @return   [type]
     */
    public static function getCompanyByUidCityName($uid, $cityname)
    {   
        $arr = [];
        $user = User::getUserinfo($uid);
        $query = Db::table('sys_user')
                        ->alias('u')
                        ->leftJoin(['tran_user_company uc'], 'u.USER_ID = uc.USER_ID')
                        ->field('u.USER_ID, u.NAME as staff_name, uc.COMPANY_ID')
                        ->where('u.USER_TYPE','eq', '2')
                        ->buildSql();
        $areaList = Company::alias('c')
                            ->leftJoin(['sys_dictionaries d'],'d.DICTIONARIES_ID = c.AREA_ID')
                            ->field('c.area_id')
                            ->where('d.NAME','like', $cityname)
                            ->select();
        $strs = '';
        foreach ($areaList as $key => $value) {
            if ($key == 0) {
                $strs = $value['area_id'];
            }else{
                $strs.=','.$value['area_id'];
            }
        }
        if (empty($strs)) {
            $strs = '-1';
        }
        $field = 'c.ID, c.NAME, c.LONGITUDE, c.LATITUDE, c.ADDRESS, q.staff_name';
        if ($user['USER_TYPE'] == 0) {
            switch ($user['AREA_ID']) {
                case -1:
                    //超级管理员
                    $where['c.PID'] = ['neq', '0'];
                    $company_id = Company::alias('c')
                                            ->leftJoin([$query."q"]," c.ID = q.COMPANY_ID")
                                            ->field($field)
                                            ->whereIn('c.AREA_ID',$strs)
                                            ->where($where)
                                            ->select();
                    break;
                default:
                //区域管理员
                    $area = Db::table('sys_dictionaries')->where('DICTIONARIES_ID', 'eq', 'AREA_ID')->field('PARENT_ID')->find();

                    if ($area['PARENT_ID'] == '1') {
                        //需要查询该区域子级城市
                        $area = Db::table('sys_dictionaries')->where('PARENT_ID', 'eq', $user['AREA_ID'])->select();
                        foreach ($area as $key => $value) {
                            $str.= $value['DICTIONARIES_ID'].',';
                        }
                        $areaid = rtrim($str,',');
                        $company_id = Company::alias('c')
                                            ->leftJoin([$query.'q'], 'c.ID = q.COMPANY_ID')
                                            ->whereIn('AREA_ID',$areaid)->where('PID','neq','0')
                                            ->field($field)
                                            ->select();
                        
                    }else{
                        //查询该区域的公司
                        $areaid = $user['AREA_ID'];
                        foreach ($areaList as $key => $value) {
                            if ($areaid == $value['area_id']) {
                                $str1 = $areaid;
                            }
                        }
                        if (empty($str1)) {
                            $str1 = '-1';
                        }
                        $where['c.AREA_ID'] = ['eq', $str1];
                        $where['c.PID'] = ['neq', '0'];
                        $company_id = Company::alias('c')
                                            ->leftJoin([$query.'q'], 'c.ID = q.COMPANY_ID')
                                            ->where($where)
                                            ->field($field)
                                            ->select();
                    }

                    break;
            }

        }else{//电工企业用户

            $where['t.USER_ID'] = ['eq', $user['USER_ID']];
            $company_id = Db::table('tran_user_company')
                        ->alias('t')
                        ->leftJoin([$query.'q'], 't.COMPANY_ID = q.COMPANY_ID')
                        ->leftJoin('company c','t.COMPANY_ID = c.ID')
                        ->whereIn('c.AREA_ID',$strs)
                        ->where($where)
                        ->field($field.',c.COMPANY_TYPE')
                        ->group('q.staff_name')
                        ->select();
            // if ($company_id) {
            //     foreach ($company_id as $key => $value) {
            //         if ($value['ID'] != null) {
            //             $company_id[] = $value;
            //         }
            //     }
            // }
        }
        return $company_id;
    }



     /**
     * 以用户id、城市名称查找公司列表
     * @DateTime 2020-05-25T18:41:13+0800
     * @return   [type]
     */
    public static function getCompanyByUidCityName1($uid,$cityname)
    {   
        // $query = Db::table('sys_user')
        //                 ->alias('u')
        //                 ->leftJoin(['tran_user_company uc'], 'u.USER_ID = uc.USER_ID')
        //                 ->field('u.USER_ID, u.NAME as staff_name, uc.COMPANY_ID')
        //                 ->where('u.USER_TYPE','eq', '2')
        //                 ->buildSql();
        // $areaList = Company::leftJoin('',' sys_dictionaries.DICTIONARIES_ID , mst_company.AREA_ID')
        //                     ->field('company.area_id')
        //                     ->where('sys_dictionaries.NAME="'.$cityname.'"')
        //                     ->select();
        // $str = '';
        // dump($areaList);exit;
        foreach ($areaList as $key => $value) {
            if ($key == 0) {
                $str = $value['area_id'];
            }else{
                $str.=',' . $value['area_id'];
            }
        }

        if (empty($str)) {
           $str = "-1";
        }

        $user = User::getUserinfo($uid);


        if ($user['USER_TYPE'] == 0) {
            if ($user['AREA_ID'] == "-1") {//超级管理员
                $where['mst_company.PID'] = ['neq', '0'];
                $where['mst_company.AREA_ID'] = ['in', $str];
                $company_id = Company::join("LEFT JOIN (select mm.USER_ID,mm.NAME as staff_name,bb.COMPANY_ID FROM sys_user mm left join tran_user_company bb on mm.USER_ID =bb.USER_ID where mm.USER_TYPE='2') as user  on mst_company.ID=user.COMPANY_ID")
                                        ->field('mst_company.ID as COMPANY_ID, mst_company.NAME, mst_company.LONGITUDE, mst_company.LATITUDE, mst_company.ADDRESS, user.staff_name')
                                        ->where($where)
                                        ->select();
                if(empty($company_id)) return $company_id;exit;
                foreach ($company_id as $key => $value) {
                    $arr[] = [
                        'ID' => $value['COMPANY_ID'],
                        'NAME' => $value['NAME'],
                        'LONGITUDE' => $value['LONGITUDE'],
                        'LATITUDE' => $value['LATITUDE'],
                        'ADDRESS' => $value['ADDRESS'],
                        'staff_name' => $value['staff_name']
                    ];
                }

            }else{
                $area = Db::table('sys_dictionaries')
                            ->field('PARENT_ID')
                            ->where('DICTIONARIES_ID', '=', $user['AREA_ID'])
                            ->find();
                if(empty($area)) return $area;exit;
                if ($area['PARENT_ID'] == '1') {
                    unset($area);
                    $area = Db::name('sys_dictionaries')->where('PARENT_ID' ,$user['AREA_ID'])->select();
                    foreach ($area as $key => $value) {
                        foreach ($areaList as $k => $val) {
                            if ($value['DICTIONARIES_ID'] == $val['area_id']) {
                                $str.= $val['DICTIONARIES_ID'].',';
                            }
                        }
                    }
                    $areaid = rtrim($str, ',');
                    $company_id = Company::join("LEFT JOIN (select mm.USER_ID, mm.NAME as staff_name, bb.COMPANY_ID FROM sys_user mm left join tran_user_company bb on mm.USER_ID, bb.USER_ID where mm.USER_TYPE = '2') as user on mst_company.ID = user.COMPANY_ID")
                                            ->where([['mst_company.AREA_ID',['in', $areaid],['mst_company.PID',['neq', '0']]]])
                                            ->field('mst_company.ID as COMPANY_ID, mst_company.NAME, mst_company.LONGITUDE, mst_company.LATITUDE, mst_company.ADDRESS, user.staff_name')
                                            ->select();
                    if(empty($company_id)) return $company_id;exit;
                    foreach ($company_id as $key => $value) {
                       $arr[] = [
                            'ID' => $value['COMPANY_ID'],
                            'NAME' => $value['NAME'],
                            'LONGITUDE' => $value['LONGITUDE'],
                            'LATITUDE' => $value['LATITUDE'],
                            'ADDRESS' => $value['ADDRESS'],
                            'staff_name' => $value['staff_name']
                        ];
                    }
                }else{
                    $areaid = $user['AREA_ID'];
                    foreach ($areaList as $key => $value) {
                        if ($areaid == $value['area_id']) {
                            $str = $areaid;
                        }
                    }
                    if (empty($str)) {
                        $str = '-1';
                    }
                    $where['mst_company.AREA_ID'] = $str;
                    $where['mst_company.PID'] = ['neq', '0'];
                    $company_id = Company::join("LEFT JOIN (select mm.USER_ID, mm.NAME as staff_name, bb.COMPANY_ID FROM sys_user mm left join tran_user_company bb on mm.USER_ID =  bb.USER_ID where mm.USER_TYPE, '2') as user on mst_company.ID, user.COMPANY_ID")
                                        ->field('mst_company.ID, mst_company.NAME, mst_company.LONGITUDE, mst_company.LATITUDE, mst_company.ADDRESS, user.staff_name')
                                        ->where($where)
                                        ->select();
                    foreach ($company_id as $key => $value) {
                        $arr[] = [
                            'ID' => $value['COMPANY_ID'],
                            'NAME' => $value['NAME'],
                            'LONGITUDE' => $value['LONGITUDE'],
                            'LATITUDE' => $value['LATITUDE'],
                            'ADDRESS' => $value['ADDRESS'],
                            'staff_name' => $value['staff_name']
                        ];
                    }
                }
            }
        }else{//电工企业用户

            $where['mst_company.AREA_ID'] = $str;
            $where['mst_company.PID'] = ['neq', '0'];
            $company_id = Company::join("LEFT JOIN mst_company ON tran_user_company.COMPANY_ID = mst_company.ID")
                                ->join("LEFT JOIN (select mm.USER_ID,mm.NAME as staff_name FROM sys_user mm where mm.USER_TYPE='2') as user on user.USER_ID=tran_user_company.USER_ID")
                                ->field('mst_company.ID as COMPANY_ID, mst_company.COMPANY_TYPE,mst_company.PID,mst_company.NAME,mst_company.LONGITUDE,mst_company.LATITUDE,mst_company.ADDRESS,user.staff_name')
                                ->where($where)
                                ->select();

            if(empty($company_id)) return $company_id;exit;
            foreach ($company_id as $key => $value) {
                if ($value['COMPANY_TYPE'] != 1) {
                    $arr[] = [
                        'ID' => $value['COMPANY_ID'],
                        'NAME' => $value['NAME'],
                        'LONGITUDE' => $value['LONGITUDE'],
                        'LATITUDE' => $value['LATITUDE'],
                        'ADDRESS' => $value['ADDRESS'],
                        'staff_name' => $value['staff_name']
                    ];
                }
            }
        }

        return $arr;

    }
}