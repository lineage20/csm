<?php

class PushController extends \Phalcon\Mvc\Controller
{
    const TOKEN = "Zly9hkqQd3OX76Qfo2oR2heED27O9Nmh";

    public function indexAction()
    {

    }
    public function apnsAction(){
        $this->view->disable();
        $devicetoken = $this->request->getPost('devicetoken');
        $loginname = $this->request->getPost('loginname');
        $unique = $this->request->getPost('unique');
        $rm_key = $this->request->getPost('key');
        $key = md5($devicetoken.$loginname.$unique.self::TOKEN);
        $status = false;
	$err = "";
        if($rm_key == $key){
            $apple = new Apple();
            $apple->devicetoken = $devicetoken;
            $apple->loginname = $loginname;
            $apple->unique = $unique;
            $apple->ctime = date("Y-m-d H:i:s");
	try{
            $status = $apple->save();
	}
	catch (MongoDuplicateKeyException $e){
		$err="duplicate key error index: devicetoken";
	}
        }else{
		$err = "Invalid key";
	}
        echo json_encode(array("status"=> $status, "err"=>$err));

    }


}

