<?php

class ImageController extends ControllerBase
{

    public function indexAction()
    {
	
    }
    public function get($action, $folder, $filename){
		
		$connection = new MongoClient( "mongodb://neo:chen@192.168.6.1/test" );
		$db = $connection->selectDB('test');

		$grid = $db->getGridFS($folder);

		$image = $grid->findOne($filename);
		
		if ($image) {
			
			$image_file = sprintf("%s/image/%s/%s/%s", $this->basedir,$action, $folder, $filename);
			$content = $image->getBytes();
			//echo $image_file;
			if(!is_dir(dirname($image_file))){
    			mkdir(dirname($image_file), 0755, TRUE);
    		}
			file_put_contents($image_file , $content);

	    	$this->response->setHeader('Cache-Control', 'max-age=60');
			$this->response->setHeader('Content-type', mime_content_type($image_file));
			$this->response->setContent($content);
			echo $content;
			
		}else{
			$this->response->setStatusCode(404, 'Image Not Found');
			$this->response->setContent('Image Not Found');
		}
		return($this->response);
	}
    public function rawAction($folder, $filename, $ver = 0){
    
    	//$filename = intval($filename);
    	$ver = intval($ver);
		$this->view->disable();
		$this->get('raw', $folder, $filename);
    }
    public function thumbnailAction($folder, $filename, $ver = 0){
    	$this->rawAction($folder, $filename, $ver);
    }
}

