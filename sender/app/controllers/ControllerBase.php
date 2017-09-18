<?php

class ControllerBase extends \Phalcon\Mvc\Controller
{
    public $Division_id;
    public $url;
    public $basedir = '/www/hx9999.com/inf.hx9999.com';
    public function initialize(){
        if (!$this->session->has("auth")) {
            return $this->response->redirect('/login/index');
        }else{
            $name = $this->session->get("auth");
            $id = $name['id'];
            $this->Division_id = $id;
        }
    }
    
}