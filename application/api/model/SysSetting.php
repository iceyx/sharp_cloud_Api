<?php
namespace app\api\model;


use app\lib\exception\BaseException;
use think\Model;

class SysSetting extends Model
{
    protected $table = 'sys_app_setting';
    protected $hidden = [''];

    public static function getSetting()
    {
    	$res = self::where('id','>=',1)->find();
		$data['guide_info'] = [
			'start_pic_url' => 'http://47.107.100.27:8080/ytgz/uploadFiles/uploadImgs/'.$res['START_PIC_URL'],
			'login_pic_url' => 'http://47.107.100.27:8080/ytgz/uploadFiles/uploadImgs/'.$res['LOGIN_PIC_URL'],
			'introduce' => escapeJsonString($res['INTRODUCE']),
			'map_extent' => $res['MAP_EXTENT']
		];
		$data['app_info'] = ['version'=> '1.0.1','update_msg'=>''];
		return $data;
    }


    public static function versionDetail()
    {	

    	$data['is_force'] = true;  // 是否强制更新
    	$data['has_update'] = true;  // 是否需要更新  默认true， 手动自行判断
    	$data['is_ignorable'] = false;  // 是否显示 “忽略该版本”
    	$data['version_code'] = '1'; // 新版本号
    	$data['version_name'] = '1.0.0'; // 新版名称
    	$data['update_log'] = '1、优化api接口。2、优化页面，及数据加载速度。4、优化更新界面。';  // 新版更新日志
    	$data['apk_url'] = 'http://www.gxrykj.com.cn:8080/ytgz/uploadFiles/app-ytgz.apk'; // 新版本下载链接
    	$data['apk_size'] = self::remote_filesize('http://www.gxrykj.com.cn:8080/ytgz/uploadFiles/app-ytgz.apk'); // 新版本大小
    	return $data;

    }

    /**
     * 获取远程app大小
     * [remote_filesize description]
     * @DateTime 2020-08-31T09:54:01+0800
     * @param    [type]                   $uri  [description]
     * @param    string                   $user [description]
     * @param    string                   $pw   [description]
     * @return   [type]                         [description]
     */
    public static function remote_filesize($uri,$user='',$pw='')   
	{   
	    // start output buffering    
	    ob_start();   
	    // initialize curl with given uri    
	    $ch = curl_init($uri);   
	    // make sure we get the header    
	    curl_setopt($ch, CURLOPT_HEADER, 1);   
	    // make it a http HEAD request    
	    curl_setopt($ch, CURLOPT_NOBODY, 1);   
	    // if auth is needed, do it here    
	    if (!empty($user) && !empty($pw))   
	    {   
	        $headers = array('Authorization: Basic ' . base64_encode($user.':'.$pw));   
	        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);   
	    }   
	    $okay = curl_exec($ch);   
	    curl_close($ch);   
	    // get the output buffer    
	    $head = ob_get_contents();   
	    // clean the output buffer and return to previous    
	    // buffer settings    
	    ob_end_clean();   
	   
	    //echo '<br>head-->'.$head.'<----end <br>';   
	   
	    // gets you the numeric value from the Content-Length    
	    // field in the http header    
	    $regex = '/Content-Length:\s([0-9].+?)\s/';   
	    $count = preg_match($regex, $head, $matches);   
	   
	    // if there was a Content-Length field, its value    
	    // will now be in $matches[1]    
	    if (isset($matches[1]))   
	    {   
	        $size = $matches[1];   
	    }   
	    else   
	    {   
	        $size = 'unknown';   
	    }   
	    $last=round($size/1024,2);    
	    //return $last.' MB';  
	    return intval($last);
	}

	//转化mb
	public static function getSize($num) {
	        $p = 0;
	        $format = 'bytes';
	        if( $num > 0 && $num < 1024 ) {
	          $p = 0;
	          return number_format($num) . ' ' . $format;
	        }
	        if( $num >= 1024 && $num < pow(1024, 2) ){
	          $p = 1;
	          $format = 'KB';
	       }
	       if ( $num >= pow(1024, 2) && $num < pow(1024, 3) ) {
	         $p = 2;
	         $format = 'MB';
	       }
	       if ( $num >= pow(1024, 3) && $num < pow(1024, 4) ) {
	         $p = 3;
	         $format = 'GB';
	       }
	       if ( $num >= pow(1024, 4) && $num < pow(1024, 5) ) {
	         $p = 3;
	         $format = 'TB';
	       }
	       $num /= pow(1024, $p);
	       return number_format($num, 3) . ' ' . $format;
		}

}