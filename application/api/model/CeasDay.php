<?php
namespace app\api\model;

use think\Model;

/**
 * 
 */
class CeasDay extends Model
{
	
	/**
	 * 月度电量
	 * [getMouthElectri description]
	 * @DateTime 2020-06-09T08:37:26+0800
	 * @return   [type]                   [description]
	 */
	public static function getMouthElectri()
	{
		return CeasDay::alias('cd')
				->field('sum(ACTIVE_ELECTRIC_QUANTITY) as mouthelectri')
				->whereTime('STATISTICS_TIME', 'm')
				->find();
	}

	/**
	 * 上个月度 电量
	 * [getMouthElectri description]
	 * @DateTime 2020-06-16T18:00:03+0800
	 * @return   [type]                   [description]
	 */
	public static function getLastMouthElectri()
	{
		return CeasDay::alias('cd')
				->field('sum(ACTIVE_ELECTRIC_QUANTITY) as mouthelectri')
				->whereTime('STATISTICS_TIME', 'last month')
				->find();
	}



	/**
	 * [yearElectri 年度电量]
	 * @DateTime 2020-06-09T08:39:20+0800
	 * @return   [type]                   [description]
	 */
	public static function getYearElectri()
	{
		return CeasDay::alias('cd')
					->field('sum(ACTIVE_ELECTRIC_QUANTITY) as yearElectri')
					->whereTime('STATISTICS_TIME', 'y')
					->find();
	}

	/**
	 *
	 * 去年年度电量
	 * [getYearElectri description]
	 * @DateTime 2020-06-16T18:01:52+0800
	 * @return   [type]                   [description]
	 */
	public static function getLastYearElectri()
	{
		return CeasDay::alias('cd')
					->field('sum(ACTIVE_ELECTRIC_QUANTITY) as yearElectri')
					->whereTime('STATISTICS_TIME', 'last year')
					->find();
	}
}