<?php
use Phalcon\Mvc\User\Component;

class Templates extends Component
{
	public $basedir = '/www/hx9999.com/inf.hx9999.com';
	public $templatedir = 'block';

    public function getContent($template_id, $data = array(), $ttl = 0)
    {
		$template_id = intval($template_id);
		$template_file = sprintf("%s/template/%s/%s.phtml", $this->basedir, $this->templatedir, $template_id);
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
				return 'Template Record Not Found';
			}
		}
		if(is_file($template_file)){
			$view = new \Phalcon\Mvc\View();
			$view->setViewsDir($this->basedir.'/template');
			$view->setRenderLevel(Phalcon\Mvc\View::LEVEL_LAYOUT);
			if(!empty($data)){
				$view->setVars($data);
			}
			//$view->cache(array('key' => 'my-key', 'lifetime' => 86400));
			$view->start();
			$view->render($this->templatedir, "$template_id");
			$view->finish();
			$content =  $view->getContent();

			return($content);
		}else{
			return 'Template File Not Found';
		}        
    }

    public function destroy($template_id)
    {
        $template_file = sprintf("%s/template/%s/%s.phtml", $this->basedir, $this->templatedir, $template_id);
		unlink($template_file);
    }
	
	public function partial($template_id, $data = array(), $ttl = 0)
    {
		echo $this->getContent($template_id, $data, $ttl);
    }

}
