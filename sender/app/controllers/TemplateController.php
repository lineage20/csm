<?php 
class TemplateController extends ControllerBase
{
	public function initialize(){
		parent::initialize();
		$this->division_id = $this->Division_id;
		$this->view->division_id = $this->division_id;
		$this->view->frontend_baseUrl = $this->url;
		$this->view->type_name = array(
				'SMS'=>'SMS',
				'Email'=>'Email',
		);
	}
	public function indexAction()
	{
		$search_key = 'template_list_search';
		$this->session->remove($search_key);
		$this->listAction(1,25);
		$this->view->partial('template/list');
	}
	
	public function listAction($page = 1 , $pageSize = 25){
		$search_key = 'template_list_search';
		
		if($this->request->isPost()){
			$params = $this->request->getPost();
			$this->session->set($search_key, $params);
		}
		$where = array();
		
		if($this->session->has($search_key)){
			$where = $this->session->get($search_key);
			$this->view->where  = $where;
			foreach($where as $k=>$v){
				if(empty($v)){
					unset($where[$k]);
				}
			}
		}
		$appendix = array('page'=>$page,'pageSize'=>$pageSize);
		$list = Template::getList($this->modelsManager , $where , $appendix);
		$page = $list->getPaginate();
		
		$page->pageSize = $appendix['pageSize'];
		$this->view->page = $page;
	}
	public function editAction($id = 0){
		if(isset($this->templateDir->sample)){
			$this->view->template_list = $this->templateDir->sample;
		}else{
			$this->view->message_info = array('默认模板配置不存在');
		}
		
		if($this->request->isPost()){
			$params = $this->request->getPost();
			$have_modify = false;
			if($params['id']){
				$oldinfo = Template::findFirst($params['id']);
				foreach($params as $k=>$v){
					if(isset($oldinfo->$k) && $oldinfo->$k !== $v){
						$have_modify = true;
						break;
					}
				}
			}
			if($params['id']>0 && $have_modify==false){
				echo json_encode(array('status'=>true,'msg'=>'你还没修改'));
				$this->view->disable();
				return ;
			}
			$last_id = Template::insert($params);
			$isError = true ;
			if(is_numeric($last_id)){
				$message_info = array('success'=>(isset($params['id']) && $params['id']) ? '修改模板成功' : '添加模板成功');
				$isError = false ; 
			}else{
				$message_info = $last_id;
			}
			if(isset($params['ajax']) && $params['ajax']==1){
				echo json_encode(array('status'=>$isError,'msg'=> !isset($message_info['success']) ?   '添加模板失败' : implode(',', $message_info)));
				$this->view->disable();
			
			}else{
				$this->view->message_info = $message_info;
			}
		}
		if($id>0){
			$info = Template::findFirst("id={$id}");
		}
		if(isset($info) && $info){
			$this->view->info = $info;
		}else{
			$this->view->info = Template::defaultObject();
		}
		
	}
	public function previewAction($template_id = 0, $category_id = 0 , $article_id = 0){
		$message_info = array();
		$preview_url= '';
		$type = '';
		if(!is_numeric($template_id)){
			$message_info[]='分类不存在';
		}
		if(!is_numeric($template_id)){
			$message_info[]='模板不存在 ';
			
		}elseif($template_id){
			
			$info = Template::findFirst("id={$template_id}");
			if(isset($info->type)){
				$type = strtolower($info->type);
				if(isset($this->templateDir->preview->$type)){
					$preview_url = $this->templateDir->preview->$type;
				}else{
					$message_info[] = array('模板预览配置不存在');
				}
				$type = $info->type;
			}else{
				$message_info[] = '无效的模板';
			}
		}
		$this->view->type = $type;
		$this->view->url = $preview_url;
		$this->view->urlAll = isset($this->templateDir->preview) ? $this->templateDir->preview : array();
		$this->view->urlAll_purge = isset($this->templateDir->purge) ? $this->templateDir->purge : array();
		
		
		if($message_info){
			$this->view->message_info = $message_info;
		}
		
		$this->view->getData = array('template_id'=>$template_id,'category_id'=>$category_id , 'article_id'=>$article_id);
		
	}
	public function purgeAction($template_id = 0, $category_id = 0 , $article_id = 0){
		$message_info = array();
		$preview_url= '';
		$type = '';
		if(!is_numeric($template_id)){
			$message_info[]='分类不存在';
		}
		if(!is_numeric($template_id)){
			$message_info[]='模板不存在 ';
				
		}elseif($template_id){
				
			$info = Template::findFirst("id={$template_id}");
			if(isset($info->type)){
				$type = strtolower($info->type);
				if(isset($this->templateDir->purge->$type)){
					$preview_url = $this->templateDir->purge->$type;
				}else{
					$message_info[] = array('模板预览配置不存在');
				}
				$type = $info->type;
			}else{
				$message_info[] = '无效的模板';
			}
		}
		if($this->request->getPost('ajax')){
			$status = false;
			$msg = null;
			$msg = '缓存更新成功';
			
			echo json_encode(array('status'=>$status,'msg'=>$msg));
			$this->view->disable();
			return;
		}
			
		$this->view->type = $type;
		$this->view->url = $preview_url;
		$this->view->urlAll = isset($this->templateDir->purge) ? $this->templateDir->purge : array();
		if($message_info){
			$this->view->message_info = $message_info;
		}
	
		$this->view->getData = array('template_id'=>$template_id,'category_id'=>$category_id , 'article_id'=>$article_id);
		
	}
	public function deleteAction($id = 0){
		if($id){
			$status = false;
			$cht = new CategoryHasTemplate();
			foreach($cht->find("template_id='{$id}'") as $item){
				if ($item->delete() == false) {
					$status = true ;
				} else {
					
				}
			}
			
			if($status == false){
				$template = new Template();
				if($template->find($id)){
					$template->id = $id;
					if($template->delete()){
						return $this->response->redirect("/template/list");
					}
				}
			}
			echo '删除失败';
		}
	}
	public function categoryAction($type='category'){
		
		if($this->request->isPost()){
			$msg = null;
			$params = $this->request->getPost();
			
			if(isset($params['category_id']) && is_numeric($params['category_id'])){
				if(isset($params['template_id']) && is_array($params['template_id'])){
					
					try {
						$transactionManager = new \Phalcon\Mvc\Model\Transaction\Manager();
						$transaction = $transactionManager->get();
						$hasErrors = 0 ;
						$isCommit = false;
						$i=0;
						foreach($params['template_id'] as $k=>$v){
							$category_ht = new CategoryHasTemplate;
							$category_ht->setTransaction($transaction);
							$category_ht->category_id = $params['category_id'];
							if($hasErrors === 0){
								$category_ht->template_id = $v;
								if(!$category_ht->count("template_id='{$category_ht->template_id}' AND category_id='{$category_ht->category_id}'")){
									if(!$category_ht->save()){
										$hasErrors +=1 ;
									}else{
										$isCommit =  true ;
										$i+=1;
									}
								}else{
									$msg = '关联已存在 ';
								}
							}
						}
						if(count($params['template_id']) === $hasErrors){
							$isCommit = false;
						}elseif($hasErrors){
							$msg = '忽略已经关联的模板 ';
						}
						
						if($isCommit === true){
							$successMessage = '模板关联成功';
							$msg = $transaction->commit() ? $successMessage : ($msg.' 模板关联失败');
							
						}else{
							$category_ht->getMessages();
							$msg = $msg ? $msg : $category_ht->getMessages();
							if(isset($category_ht->id)){
								$transaction->rollback();
							}
						}
					}catch(Phalcon\Mvc\Model\Transaction\Failed $e){
					  	$msg = $msg ? $msg : $e->getMessage();
					}
					
				}else{
					$hasErrors = 1 ;
					$msg = '模板不存在';
				}
			}else{
				$hasErrors = 1 ;
				$msg = '分类不存在';
			}
			if(isset($params['ajax']) && $params['ajax']==1){
				$this->view->disable();
				$list = null;
				if(isset($params['list'])){
					$list = categorylistAction();
				}
				
				echo json_encode(array('status'=>$hasErrors ? true : false ,'list'=>$list,'msg'=>$msg));
				return ;
			}
		}
		$this->view->type = strtoupper($type);
	}
	public function categorylistAction($page = 1 , $pageSize = 1000 ,$isPartView = false){
		
		$where = array();
		$appendix = array('page'=>$page,'pageSize'=>$pageSize);
		$this->view->list =  CategoryHasTemplate::getList($this->db , $where , $appendix);
		$this->view->urlAll = isset($this->templateDir->preview) ? $this->templateDir->preview : array();
		$this->view->urlAll_purge = isset($this->templateDir->purge) ? $this->templateDir->purge : array();
		
		
		if($isPartView){
			$this->view->partial('template/categorylist');
			$this->view->isPartView = 1;
			$this->view->disable();
		}
	}
	public function ajaxtemplateAction(){
		
		if($this->request->isPost()){
			$category_id = $this->request->getPost('category_id'); 
			$template_id = $this->request->getPost('template_id'); 
			
			$relation = $this->request->getPost('relation'); 
			
			$not = $relation ? '' : ' NOT ';
			$sql="SELECT id,name,`type` FROM template WHERE `status`='Enabled' AND division_id = '{$this->division_id}' AND id {$not} 
					in(SELECT template_id FROM category_has_template WHERE category_id = '{$category_id}')";
			$list = $this->db->fetchAll($sql,PDO::FETCH_ASSOC);
			$new_list = array();
			foreach($list as $k=>$v){
				$new_list[$v['id']]=$v['name'];
			}
			
			$this->view->list = empty($category_id) ? array(array(),array()) :  array($new_list,$list);
			$this->view->relation = $relation;
		}
		$this->view->partial('template/ajaxtemplate');
		
		$this->view->disable();
	}
	
	public function ajaxcategoryAction(){
	
		if($this->request->isPost()){
			$category_id = $this->request->getPost('category_id');
			$template_id = $this->request->getPost('template_id');
				
			$relation = $this->request->getPost('relation');
				
			$not = $relation ? '' : ' NOT ';
			$sql="SELECT id,name,`path` FROM category WHERE id {$not}
			in(SELECT category_id FROM category_has_template WHERE template_id = '{$template_id}')";
			$list = $this->db->fetchAll($sql,PDO::FETCH_ASSOC);
			$new_list = array();
			foreach($list as $k=>$v){
			$new_list[$v['id']]=$v['name'];
			}
				$this->view->list = array($new_list,$list);
				$this->view->relation = $relation;
		}
		$this->view->partial('template/ajaxcategory');
	
		$this->view->disable();
	}
	
	
	public function ajaxarticleAction($category_id = 0){
	
		if($this->request->isPost()){
			$category_id = $this->request->getPost('category_id');
			$article_id = $this->request->getPost('article_id');
			$this->view->table  = $this->request->getPost('table');
			$this->view->only_one_id = $this->request->getPost('only_one_id') ? $this->request->getPost('only_one_id') : 0;
		}
		
		$this->view->category_id = $category_id;
		$this->view->article_id = $article_id;
		$this->view->partial('template/ajaxarticle');
	
		$this->view->disable();
	}
	
	public function deleteHasCategoryAction($id = 0){
		$status = false;
		$msg = 0;
		if($id){
			$category_hx = new CategoryHasTemplate();
			if($category_hx->count($id)){
					$category_hx->id = $id;
					if($category_hx->delete()){
						$msg = '关联删除成功';
					}else{
						$status = true ; 
						$msg = '关联删除失败';
					}
			}else{
				$status = true ;
				$msg = '关联已经删除或不存在';
			}
			if($this->request->getPost('ajax')==1){
				$this->view->disable();
				echo json_encode(array('status'=>$status,'msg'=>$msg));
				return ;
			}
			return $this->response->redirect("/template/category");
		}
	}
	
	public function contentAction($id = 0){
	
		if($id){
			$info = Template::findFirst('id='.$id);
			echo $info->content;
			$this->view->disable();
		}
		exit();
	}
	public function hostnodeAction($url = null){
		if($this->request->isPost()){
			$url = $this->request->getPost('url');
		}
		$error_msg = array();
		$status = false ;
		$msg = '';
		if($this->templateDir->node){
			$rs = $this->multipleThreadsRequestAction($url , (array)$this->templateDir->node);
			
			
			$msg = '缓存更新<br/>';
			$i = 1;
			foreach($rs as $k=>$v){
				$flag = true;
				if($v['code'] != 200){
					$error_msg[] = $k.':'.$v['code'];
					$flag = false;
				}
				$msg .= '节点  '.$i .($flag ? '成功' : '失败') .'<br/>';
				$i+=1;
			}
		}
		echo json_encode(array('status'=>$status,'msg'=>$msg ,'info'=>$error_msg));
		$this->view->disable();
	}
	public function simpleGetContentsAction(){
		$opts = array(
				'http'=>array(
						'method'=>"GET",
						'header'=>"Host:lh.backend.inf.hx9999.com"
				)
		);
		$context = stream_context_create($opts);
		$ret = file_get_contents('http://127.0.0.1:81/', false, $context);
	}
	/**
	 * 
	 * @param unknown $surl
	 * @param unknown $nodes
	 * @return multitype:string
	 * 	#$nodes = array('127.0.0.1');
		#$this->url = 'http://lh.backend.inf.hx9999.com';
		#$url = 'http://lh.backend.inf.hx9999.com:81/template/list';
	 */
	public function multipleThreadsRequestAction($surl , $nodes){ 
		
		$mh = curl_multi_init();
        $curl_array = array();
        foreach($nodes as $i => $node)
        {
        	$url = str_replace($this->url, $node, 'http://'.$surl);
            $host = str_ireplace(array('http://'), array(''), $this->url);
            if(strpos($host,'/')!=false){
            	$host = strstr($host, '/',true);
            }
        	$curl_array[$i] = curl_init($url);
            curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true);
            curl_setopt ($curl_array[$i], CURLOPT_HEADER, 1);
            curl_setopt($curl_array[$i],CURLOPT_HTTPHEADER,array('Host: '.$host));
            curl_multi_add_handle($mh, $curl_array[$i]);
        }
        $running = NULL;
        do {
            usleep(100);
            curl_multi_exec($mh,$running);
        } while($running > 0);
        $res = array();
        foreach($nodes as $i => $url)
        {
             $content = curl_multi_getcontent($curl_array[$i]);
             $code = preg_replace('/HTTP\/[\d|\.]+\s+?([0-9]+)\s.+/is', '\\1',$content);
             $res[$url]= array('code'=>$code,'content'=>$content);
        }
       
        foreach($nodes as $i => $url){
            curl_multi_remove_handle($mh, $curl_array[$i]);
        }
        curl_multi_close($mh);       
        return $res; 
	}
	
}
?>