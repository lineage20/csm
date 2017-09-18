<?php

class IndexController extends ControllerBase
{

    public function indexAction()
    {
	$user = User::find();
        echo json_encode($this->objToArray->ohYeah($user));
        exit();	
    }
    

}

