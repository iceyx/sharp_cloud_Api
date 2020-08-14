<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

//一般路由规则，访问的url为：v1/address/1,对应的文件为Address类下的read方法

Route::resource(':version/user','api/:version.user');       //资源路由，详情查看tp手册资源路由一章
Route::post(':version/token/refresh','api/:version.token/refresh');

//业务路由
//
//用户登录
Route::post(':version/user/login','api/:version.user/login');

//退出登录
Route::get(':version/user/logout','api/:version.user/logout');

//用户信息
Route::get(':version/user/getUserInfo','api/:version.user/getUserInfo');

//app配置信息
Route::post(':version/System/settingInfo','api/:version.System/settingInfo');

//企业用电-电量（饼图）
Route::post(':version/EnterPrise/electriCityPie','api/:version.EnterPrise/electriCityPie');

//通知
Route::post(':version/EnterPrise/noticeNew','api/:version.EnterPrise/noticeNew');

//企业用电-负荷（列表）
Route::post(':version/EnterPrise/loadList','api/:version.EnterPrise/loadList');

//企业用电-电量（列表）（饼图）
Route::post(':version/EnterPrise/electriCityList','api/:version.EnterPrise/electriCityList');

//单位用电-电量（饼图）
Route::post(':version/Company/CompanyElecPie','api/:version.Company/CompanyElecPie');

//单位用电-电量（图饼）
Route::post(':version/Company/CompanyElecList','api/:version.Company/CompanyElecList');

//单位用电-负荷（列表）(饼图)
Route::post(':version/Company/CompanyLoadList','api/:version.Company/CompanyLoadList');

//公司（列表）
Route::get(':version/Company/companyList','api/:version.Company/companyList');

//概况
Route::get(':version/Company/getCompanyData','api/:version.Company/getCompanyData');

//运行状况-公司列表(未加经纬度查询)
Route::post(':version/Operation/companyList','api/:version.Operation/companyList');

//运行状况-故障详情
Route::post(':version/OperationStatus/faultDetail','api/:version.OperationStatus/faultDetail');

//故障详情
Route::post(':version/OperationStatus/detail','api/:version.OperationStatus/detail');

//异常统计
Route::post(':version/OperationStatus/exceptionStatistic','api/:version.OperationStatus/exceptionStatistic');

//异常详情
Route::post(':version/OperationStatus/exceptionDetail','api/:version.OperationStatus/exceptionDetail');

//故障信息
Route::post(':version/OperationStatus/faultList','api/:version.OperationStatus/faultList');

//数据查询(仪表列表)
Route::post(':version/DataQuery/getEquitment','api/:version.DataQuery/getEquitment');

//数据查询接口(实时，历史)
Route::post(':version/DataQuery/getDataList','api/:version.DataQuery/getDataList');

//修改指令
Route::post(':version/DataQuery/windosCommand','api/:version.DataQuery/windosCommand');

//图像接口
Route::post(':version/DataQuery/figure','api/:version.DataQuery/figure');

//负荷分析列表
Route::post(':version/EnergyAnalysis/LoadList','api/:version.EnergyAnalysis/LoadList');

//添加负荷分析
Route::post(':version/EnergyAnalysis/addList','api/:version.EnergyAnalysis/addList');

//添加负荷分析
Route::post(':version/EnergyAnalysis/delList','api/:version.EnergyAnalysis/delList');

//平谷峰分析
Route::post(':version/EnergyAnalysis/fpg','api/:version.EnergyAnalysis/fpg');

//需量分析
Route::post(':version/EnergyAnalysis/demandAnalysis','api/:version.EnergyAnalysis/demandAnalysis');


