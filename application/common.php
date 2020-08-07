<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
// 
error_reporting(E_ALL & ~E_NOTICE);


function logTxt($param){
    return file_put_contents('log.txt',"\r\n \r\n".date('Y-m-d H:i:s',time())."\r\n ".'提交数据：'.json_encode($param),FILE_APPEND);
}

/**
 * 通用API接口数据输出
 * @param string $msg
 * @param int $status
 * @param array $data
 */
function render_json($data = array(), $msg = '', $status = 0)
{
    header('Content-type: application/json');
    $result = array();
     $data = is_array($data) ? $data : ['info'=>$data];
    if ($data) {
        if (is_assoc($data)) {
            $result = $data;
        } else {
            $result['lists'] = $data;
        }
        $return = array_change_key_case($result,CASE_LOWER);
        array_case($return);
    } else {
        $result = new stdClass();
    }
   
    exit(json_encode(["errorMsg" => $msg,"errorCode" => $status, "data" => $return]));
}


/**
 * 通用化API接口返回json
 * @param int $status 业务状态码
 * @param string $message 传递消息
 * @param array $data 传递数据
 * @param int $httpCode HTTP状态码
 */
function showJson($data=[],$status,$message,$httpCode=200)
{
     $dataJson=[
         'status'=>$status,
         'msg'=>$message,
         'data'=>$data,
     ];
    $return = array_change_key_case($dataJson,CASE_LOWER);
    array_case($return);
     return json($return,$httpCode);
}


/**
 * 判断是否关联数组 true:关联数组
 * @param $data
 * @return bool
 */
function is_assoc($data)
{
    if (is_array($data)) {
        return array_keys($data) !== range(0, count($data) - 1);
    }
    return true;
}

/**
 * 手机号码正则验证
 * @param $mobile
 * @return bool
 */
function check_mobile($mobile)
{
    $pattern = '/^1[3456789]\d{9}$/';
    if (preg_match($pattern, $mobile)) {
        return true;
    }
    return false;
}

/**
 * 邮箱正则验证
 * @param $email
 * @return bool
 */
function check_email($email)
{
    $pattern = '/^[a-zA-Z0-9]+([-_.][a-zA-Z0-9]+)*@([a-zA-Z0-9]+[-.])+([a-z]{2,5})$/';
    if (preg_match($pattern, $email)) {
        return true;
    }
    return false;
}

/**
 * 获取图片绝对路径
 * @param string $path
 * @return bool|string
 */
function get_pic($path = '')
{
    if (strpos($path, "http") === false) {
        $path = 'http://' . $_SERVER['HTTP_HOST'] . $path;
    }
    return $path;
}

/**
 * curl请求
 * @param $url
 * @param $postFields
 * @return mixed
 * @throws Exception
 */
function curl($url, $postFields = array())
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //https请求
    if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == 'https') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    if (is_array($postFields) && count($postFields) > 0) {
        $postBodyString = '';
        $postMultipart = false;
        foreach ($postFields as $k => $v) {
            //判断是不是文件上传
            //文件上传用multipart/form-data，否则用www-form-urlencoded
            if ("@" != substr($v, 0, 1)) {
                $postBodyString .= "$k=" . urlencode($v) . "&";
            } else {
                $postMultipart = true;
            }
        }
        unset($k, $v);
        curl_setopt($ch, CURLOPT_POST, true);
        if ($postMultipart) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
        }
    }
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch), 0);
    } else {
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (200 !== $httpStatusCode) {
            throw new Exception($response, $httpStatusCode);
        }
    }
    curl_close($ch);

    return $response;
}

/**
 * 循环删除目录和文件
 * @param string $dir_name
 * @return bool
 */
function delete_dir_file($dir_name)
{
    $result = false;
    if (is_dir($dir_name)) {
        if ($handle = opendir($dir_name)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($dir_name . DIRECTORY_SEPARATOR . $item)) {
                        delete_dir_file($dir_name . DIRECTORY_SEPARATOR . $item);
                    } else {
                        unlink($dir_name . DIRECTORY_SEPARATOR . $item);
                    }
                }
            }
            closedir($handle);
            if (rmdir($dir_name)) {
                $result = true;
            }
        }
    }

    return $result;
}



/**
 * 数组的所有的键都转换为大写字母或小写字母
 * @DateTime 2020-05-28T08:57:35+0800
 * @param    string
 * @return   [type]
 */
function array_case(&$array, $case=CASE_LOWER)
{   
    //ini_set("memory_limit", "1024M");
    $array = array_change_key_case($array, $case);
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            array_case($array[$key], $case);
        }
    }
}

/**
 * @DateTime 2020-05-22T14:35:23+0800
 * @param    [type]
 * @return   [type]
 */
function escapeJsonString($value) {
    $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
    $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
    $result = str_replace($escapers, $replacements, $value);
    return $result;
}


/**
 * @param string $url post请求地址
 * @param array $params
 * @return mixed
 */
function curl_post($url, array $params = array())
{
    $data_string = json_encode($params);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt(
        $ch, CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/json'
        )
    );
    $data = curl_exec($ch);
    curl_close($ch);
    return ($data);
}


function curl_post_raw($url, $rawData)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
    curl_setopt(
        $ch, CURLOPT_HTTPHEADER,
        array(
            'Content-Type: text'
        )
    );
    $data = curl_exec($ch);
    curl_close($ch);
    return ($data);
}



/**
 * @param string $url get请求地址
 * @param int $httpCode 返回状态码
 * @return mixed
 */
function curl_get($url, &$httpCode = 0)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //不做证书校验,部署在linux环境下请改为true
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $file_contents = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $file_contents;
}


/**
 * @DateTime 2020-05-22T08:40:36+0800
 * @param    [int]
 * @return   [string]
 */
function getRandChar($length)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;

    for ($i = 0;
         $i < $length;
         $i++) {
        $str .= $strPol[rand(0, $max)];
    }

    return $str;
}


/**
 * @DateTime 2020-05-22T08:41:36+0800
 * @param    [array]
 * @param    [array]
 * @return   [array]
 */
function fromArrayToModel($m , $array)
{
    foreach ($array as $key => $value)
    {
        $m[$key] = $value;
    }
    return $m;
}