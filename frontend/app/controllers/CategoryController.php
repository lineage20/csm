<?php
class CategoryController extends ControllerBase
{	
	public function indexAction()
	{

	}
	public function htmlAction($template_id, $parent_id){
		$template_id = intval($template_id);
		$parent_id = intval($parent_id);
		
		if(empty($parent_id) || empty($template_id)){
			echo '404';
		}
		
		$this->view->disable();
		
		$template_file = $this->basedir."/template/category/".$template_id.".phtml";
		$categroy_file = $this->basedir."/static/category/html/$template_id/$parent_id.html";
		
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
    			echo 'Template Not Found';
    			return;
    		}
		}
		
		$conditions = 'parent_id = :parent_id: AND visibility = :visibility:';
		
		$parameters = array(
				'parent_id' => $parent_id,
				'visibility' => 'Visible'
		);
		$categorys = Category::find(array(
				$conditions,
    			"bind" => $parameters
		));
		
		if(count($categorys) == 0){
			$this->response->setStatusCode(404, 'Article Category Not Found');
			echo 'Article Category Not Found';
		}else{
			$view = new \Phalcon\Mvc\View();
			$view->setViewsDir($this->basedir.'/template');
			$view->setRenderLevel(Phalcon\Mvc\View::LEVEL_LAYOUT);
			$view->setVar('template_id',$template_id);
			$view->setVar('parent_id',$parent_id);
			$view->setVar('categorys',$categorys);
			$view->start();
			$view->render("category","$template_id");
			$view->finish();
			
			$content =  $view->getContent();
			
			if($content){
				if(!is_dir(dirname($categroy_file))){
					mkdir(dirname($categroy_file), 0755, TRUE);
				}
				file_put_contents($categroy_file, $content);
			}
			print($content);
		}

	}
	public function jsonAction($parent_id){
		$result = array();
		$this->view->disable();
		
		$parent_id = intval($parent_id);
		
		$conditions = 'parent_id = :parent_id: AND visibility = :visibility:';
		
		$parameters = array(
				'parent_id' => $parent_id,
				'visibility' => 'Visible'
		);
		$categorys = Category::find(array(
				$conditions,
				"bind" => $parameters
		));
		
		foreach ($categorys as $category){
			$result[$category->id]=$category->name;
		}
		$response = new Phalcon\Http\Response();
		$response->setHeader('Cache-Control', 'max-age=60');
		$response->setContentType('application/json', 'utf-8');
		$response->setContent(json_encode($result));
		return $response;
	}
	
	public function pageAction(){
		 
	}
	public function purgeAction($template_id, $parent_id = 0){
		
		$template_id = intval($template_id);
		$parent_id = intval($parent_id);
		
		$template_file = $this->basedir."/template/category/".$template_id.".phtml";
		 
		unlink($template_file);
		
		if($parent_id > 0){
			$categroy_path = $this->basedir."/static/category/html/$template_id/$parent_id.html";
		}else{
			$categroy_path = $this->basedir."/static/category/html/$template_id/*";
		}

		array_map('unlink', glob($categroy_path));
	}
}
