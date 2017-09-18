<?php

class DetailController extends ControllerBase
{
	
    public function indexAction()
    {
	
    }
    
    public function htmlAction($template_id, $category_id, $article_id){
    
    	
    	$template_id = intval($template_id);
    	$category_id = intval($category_id);
    	$article_id = intval($article_id);
    	 
    	if(empty($category_id) || empty($template_id)){
    		$this->response->setStatusCode(404, 'Not Found');
    	}
    	    	 
    	$this->view->disable();
    	 
    	$template_file = $this->basedir."/template/detail/".$template_id.".phtml";
    	$article_file = $this->basedir."/static/detail/html/$template_id/$category_id/$article_id.html";
    	 
    	if(!is_file($template_file) || 1==1){
    		 
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
    			echo 'Template Not Found';
    			return;
    		}
    	}    	
    	
    	$conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND id = :article_id: AND visibility = :visibility:";
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'division_category_id' => $category_id,
    			'article_id' => $article_id,
    			'visibility' => 'Visible'
    	);

		if($article_id == 0){
			$article = Article::findFirst(array(
				"(category_id = :category_id: OR division_category_id = :division_category_id:) AND visibility = 'Visible'",
				"bind" => array(
					'category_id' => $category_id,
					'division_category_id' => $category_id,
				),
				"order" => "id DESC",
				"limit" => 1
			));
		}else{		
			$article = Article::findFirst(array(
				$conditions,
				"bind" => $parameters
			));
		}
		
		if($article){

	    	$view = new \Phalcon\Mvc\View();
	    	$view->setViewsDir($this->basedir.'/template');
	    	$view->setRenderLevel(Phalcon\Mvc\View::LEVEL_LAYOUT);
	    	$view->setVar('article',$article);
	    	$view->setVar('article_id',$article_id);
	    	$view->setVar('category_id',$category_id);
	    	$view->setVar('template_id',$template_id);
	    	$view->start();
	    	$view->render("detail","$template_id");
	    	$view->finish();
	    	 
	    	$content =  $view->getContent();
	    	 
	    	if(!is_dir(dirname($article_file))){
	    		mkdir(dirname($article_file), 0755, TRUE);
	    	}
			
			if($article_id != 0){
				file_put_contents($article_file, $content);
			}
	    	
	    	$this->response->setHeader('Cache-Control', 'max-age=60');
	    	print($content); 
		}else{
			$this->response->setStatusCode(404, 'Article Not Found');
			echo 'Article Not Found';
		} 	
    	
    }
    public function jsonAction($category_id, $article_id){

    	$this->view->disable();
    
    	$conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND id = :article_id: AND visibility = :visibility:";
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'division_category_id' => $category_id,
    			'article_id' => $article_id,
    			'visibility' => 'Visible'
    	);
    	$article = Article::findFirst(array(
    			$conditions,
    			"bind" => $parameters
    	));

    	$response = new Phalcon\Http\Response();
    	$response->setHeader('Cache-Control', 'max-age=60');
    	$response->setContentType('application/json', 'utf-8');
    	$response->setContent(json_encode($article));
    	return $response;
    }    
    
    public function pageAction(){
    	 
    }
    public function purgeAction($template_id, $category_id, $article_id){
    	$template_id = intval($template_id);
    	$category_id = intval($category_id);
    	$article_id  = intval($article_id);
    	 
    	$template_file = $this->basedir."/template/detail/".$template_id.".phtml";
    	 
    	unlink($template_file);
    	 
    	if($article_id > 0){
    		$article_path = $this->basedir."/static/detail/html/$template_id/$category_id/$article_id.html";
    	}else{
    		$article_path = $this->basedir."/static/detail/html/$template_id/$category_id/*.html";
    	}
    	 
    	array_map('unlink', glob($article_path));    	
    }
}

