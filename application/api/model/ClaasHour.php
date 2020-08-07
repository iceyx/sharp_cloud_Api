<?php
namespace app\api\model;

use think\Model;

/**
 * 
 */
class ClaasHour extends Model
{	
	public static function getMaxLoad()
	{
		return  ClaasHour::name('claas_hour')
						->alias('mch')
						->field(' LARGEST_LOAD, LOAD_TIME, COMPANY_ID')
						->where('1','1')
						->order('LOAD_TIME DESC')
						->find();
	}
	
	/**
	 * 今天
	 * @DateTime 2020-05-23T11:21:52+0800
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getNow($where)
	{	

		return ClaasHour::where($where)
					->field("sum(LARGEST_LOAD) as LARGEST_LOAD, sum(MINIMUM_LOAD) as MINIMUM_LOAD, avg(PEAK_VALLEY_DIFFERENCE) as PEAK_VALLEY_DIFFERENCE,avg(PEAK_VALLEY_RATE) as PEAK_VALLEY_RATE,avg(AVERAGE_LOAD) as AVERAGE_LOAD,avg(LOAD_RATE) as LOAD_RATE,MINIMUM_LOAD_TIME,LARGEST_LOAD_TIME,date_format(LOAD_TIME,'%H:%i') as TIME1")
					->group('LOAD_TIME')
					->select();//当天 
	}

	/**
	 * 昨天
	 * @DateTime 2020-05-23T11:21:36+0800
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getYesterDay($where)
	{
		return  ClaasHour::where($where)
					->field("avg(AVERAGE_LOAD) as AVERAGE_LOAD,date_format(LOAD_TIME,'%H:%i') as TIME1")
					->group('LOAD_TIME')
					->select();//昨天
	}


	/**
	 * 单位用电-负荷（列表）今天
	 * @DateTime 2020-05-25T10:50:23+0800
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getCompanyNow($START_TIME, $END_TIME)
	{
		return ClaasHour::whereTime('LOAD_TIME',[$START_TIME, $END_TIME])
						->field("*,date_format(LOAD_TIME,'%H:%i') as TIME1,date_format(LARGEST_LOAD_TIME,'%Y-%m-%d %H:%i') as LARGEST_LOAD_TIME,date_format(MINIMUM_LOAD_TIME,'%Y-%m-%d %H:%i') as MINIMUM_LOAD_TIME")
						->order('LOAD_TIME desc')
						->select();
	}


	/**
	 * 单位用电-负荷（列表）昨天
	 * @DateTime 2020-05-25T10:53:12+0800
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getCompanyYesterDay($START_TIME, $END_TIME)
	{
		return ClaasHour::whereTime('LOAD_TIME',[$START_TIME, $END_TIME])
						->field("AVERAGE_LOAD,date_format(LOAD_TIME,'%H:%i') as TIME1")
						->select();
	}


	/**
	 * 默认时间是今天
	 * @DateTime 2020-05-25T12:03:05+0800
	 * @return   [type]
	 */
	public static function getCompanyNowDay()
	{
		return ClaasHour::whereTime('LOAD_TIME','d')
						->field("*,date_format(LOAD_TIME,'%H:%i') as TIME1,date_format(LARGEST_LOAD_TIME,'%Y-%m-%d %H:%i') as LARGEST_LOAD_TIME,date_format(MINIMUM_LOAD_TIME,'%Y-%m-%d %H:%i') as MINIMUM_LOAD_TIME")
						->order('LOAD_TIME desc')
						->select();
	}


	/**
	 * 默认时间是昨天
	 * @DateTime 2020-05-25T12:03:26+0800
	 * @return   [type]
	 */
	public static function getCompanyYesterDayNow()
	{
		return ClaasHour::whereTime('LOAD_TIME','yesterday')
						->field("AVERAGE_LOAD,date_format(LOAD_TIME,'%H:%i') as TIME1")
						->select();
	}

	/**
	 * 选择时间段的平均负荷
	 * @DateTime 2020-05-29T18:30:19+0800
	 * @param    [type]
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getTimeData($where,$whereTime, $field)
	{	
		//dump($whereTime);exit;
		return ClaasHour::where($where)->whereTime('LOAD_TIME',$whereTime)->field($field)->find()->toArray();
	}


	/**
	 * 获取本周数据
	 * @DateTime 2020-06-01T09:12:01+0800
	 * @param    [type]
	 * @return   [type]
	 */
	public static function getWeek($where,$whereT,$field)
	{
		return ClaasHour::whereTime('LOAD_TIME',$where)->whereTime('LOAD_TIME', $whereT)->select();
	}


}