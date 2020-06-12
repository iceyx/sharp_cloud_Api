<?php

namespace app\api\behavior;


class CORS
{	
	/**
	 * @DateTime 2020-05-22T14:49:05+0800
	 * @param    [type]
	 * @return   [type]
	 */
    public function appInit($params)
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: token,Origin, X-Requested-With,X_Requested_With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: POST,GET,PUT');
        if(request()->isOptions()){
            exit();
        }
    }
}