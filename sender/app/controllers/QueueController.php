<?php
class QueueController extends ControllerBase
{
    public function indexAction(){
        $search_key = 'template_list_search';
        $this->session->remove($search_key);
        $this->listAction(1,25);
        $this->view->partial('queue/list');
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
            $list = Queue::getList($this->modelsManager , $where , $appendix);


            $page = $list->getPaginate();

            $page->pageSize = $appendix['pageSize'];
            $taskId = Task::find(array(
                "order" => "id desc"
            ));
            $this->view->page = $page;
            $this->view->taskId = $taskId;
    }
}

