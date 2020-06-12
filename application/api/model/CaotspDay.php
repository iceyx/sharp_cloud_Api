<?php
namespace app\api\model;

use think\Model;

/**
 * 
 */
class CaotspDay extends Model
{
	
	/**
	 *  单位用电-电量（图饼）
	 * @DateTime 2020-05-25T10:03:44+0800
	 * @param    string
	 */
	public static function getCompanyElecPie($where)
	{
		return CaotspDay::field('sum(PEAK_POWER) as PEAK_POWER,sum(VALLEY_POWER) as VALLEY_POWER,sum(FLAT_POWER) as FLAT_POWER,sum(CUSP_POWER) as CUSP_POWER')
						->where($where)
						->select();
	}


	/**
	 * 单位用电-电量（列表）
	 * @DateTime 2020-05-25T10:25:34+0800
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getElecList($where, $limit, $offset)
	{
		return CaotspDay::field("date_format(STATISTICS_TIME,'%Y-%m') as STATISTICS_TIME,sum(PEAK_POWER) as PEAK_POWER,sum(VALLEY_POWER) as VALLEY_POWER,sum(FLAT_POWER) as FLAT_POWER,sum(CUSP_POWER) as CUSP_POWER")
						->where($where)
						->order('STATISTICS_TIME desc')
						->group("date_format(STATISTICS_TIME,'%Y-%m')")
						->limit($limit,$offset)
						->select();

	}


	/**
	 * 单位用电-电量（饼图）
	 * @DateTime 2020-05-25T10:29:26+0800
	 * @param    string
	 */
	public static function getElecPie($where)
	{
		return CaotspDay::field('sum(PEAK_POWER) as PEAK_POWER,sum(VALLEY_POWER) as VALLEY_POWER,sum(FLAT_POWER) as FLAT_POWER,sum(CUSP_POWER) as CUSP_POWER')
						->where($where)
						->select();
	}


	/**
	 * [getElecPie description]
	 * @DateTime 2020-06-02T08:52:17+0800
	 * @param    [type]                   $where [description]
	 * @return   [type]                          [description]
	 */
	public static function getElecPieByTime($where)
	{
		return CaotspDay::field('sum(PEAK_POWER) as PEAK_POWER,sum(VALLEY_POWER) as VALLEY_POWER,sum(FLAT_POWER) as FLAT_POWER,sum(CUSP_POWER) as CUSP_POWER')
						->whereTime('STATISTICS_TIME', 'month')
						->where($where)
						->select();
	}
}