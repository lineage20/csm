<?php

class PageController extends ControllerBase
{
	
    public function indexAction()
    {
	
    }
    
    public function htmlAction($dir_name, $template_id, $ver = 0){
    
    	$template_id = intval($template_id);
    	$ver = intval($ver);
    	
		$this->view->disable();
		
    	if(empty($template_id) || empty($dir_name)){
    		$this->response->setStatusCode(404, 'Not Found');
			$this->response->setContent('Template ID Not Found');
    		return($this->response);
    	}

    	$template_file = sprintf("%s/template/page/%s/%s.phtml", $this->basedir, $dir_name, $template_id);
    	$page_file = sprintf("%s/static/page/html/%s/%s.html", $this->basedir, $dir_name, $template_id);
		
    	if(!is_file($template_file)){
    		 
    		$template = Template::findFirst(array(
    				"id = :template_id: AND status = :status:",
    				"bind" => array(
    						'template_id' => $template_id,
    						'status' => 'Enabled'
    				)
    		));
    		 
    		if($template){
    			if(!is_dir(dirname($template_file))){
    				mkdir(dirname($template_file), 0755, TRUE);
    			}
    			file_put_contents($template_file , $template->content);
    		}else{
    			$this->response->setStatusCode(404, 'Template Not Found');
				$this->response->setContent('Template Not Found');
    			return($this->response);
    		}
    	}
		
		if(is_file($template_file)){
			/*
			if(is_file($page_file)){
				
			}else{
				
			}
			*/
	    	$view = new \Phalcon\Mvc\View();
	    	$view->setViewsDir($this->basedir.'/template/page');
	    	$view->setRenderLevel(Phalcon\Mvc\View::LEVEL_LAYOUT);
	    	//$view->setVars($data);
			//$view->cache(array('key' => 'my-key', 'lifetime' => 86400));
			$view->sss = "aaa";
	    	$view->start();
	    	$view->render($dir_name, "$template_id");
	    	$view->finish();

			$content =  $view->getContent();

	    	if(!is_dir(dirname($page_file))){
	    		mkdir(dirname($page_file), 0755, TRUE);
	    	}
			
			if($content){
				file_put_contents($page_file, $content);
			}			
			
			$expireDate = new DateTime();
			$expireDate->modify('+1 minutes');
			$this->response->setExpires($expireDate);
	    	$this->response->setHeader('Cache-Control', 'max-age=60');
			$this->response->setHeader('ETag', $eTag = crc32($content));
			$this->response->setContent($content);

			return($this->response);
		}else{
			$this->response->setStatusCode(404, 'Article Not Found');
			$this->response->setContent('Article Not Found');
			return($this->response);
		} 	
    	
    }
}

