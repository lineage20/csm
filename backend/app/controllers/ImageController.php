<?php

class ImageController extends ControllerBase
{

    public function indexAction()
    {
	
    }
    public function get($action, $folder, $filename){
    	$attribute = array(
    		'basedir'=>$this->basedir,
    		'response'=>$this->response,
    	);
    	return $this->mongodb->view($action, $folder, $filename , $attribute);
	}
    public function rawAction($folder, $filename, $ver = 0){
    	$ver = intval($ver);
    	
		$this->view->disable();
		$this->get('raw', $folder, $filename);
    }
	public function thumbnailAction($folder, $filename, $ver = 0){
    	$ver = intval($ver);
		$this->view->disable();
		$this->get('thumbnail', $folder, $filename);
    }
}

