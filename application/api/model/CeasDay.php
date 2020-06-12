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
}