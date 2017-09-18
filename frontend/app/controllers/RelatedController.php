<?php
use Phalcon\Mvc\View,
	Phalcon\Mvc\Controller;

class RelatedController extends ControllerBase
{

    public function indexAction()
    {

    }
    public function jsonAction($category_id, $id, $before = 2, $after = 2){

        $category_id = intval($category_id);
        $id          = intval($id);
        $before      = intval($before);
        $after       = intval($after);

        if(empty($category_id) || empty($id) ){
                $this->response->setStatusCode(404, 'Not Found');
        }

        if($before > 10){
                $before = 10;
        }
        if($after > 10){
                $after = 10;
        }

        $this->view->disable();

        $key = sprintf(":related:json:%s:%s:%s:%s", $category_id, $id, $before, $after );
        $json = null;
        $json = $this->cache->get($key);
        if(empty($json)){

            $conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND id > :id: AND language = :language: AND visibility = :visibility: AND status = :status:";
            $parameters = array(
                            'category_id' => $category_id,
                            'division_category_id' => $category_id,
                            'id' => $id,
                            'language' => 'cn',
                            'status' => 'Enabled',
                            'visibility' => 'Visible'
            );
            $articles_before = Article::find(array(
                            $conditions,
                            "bind"          => $parameters,
                            'columns'=>'id,division_category_id,title,author,ctime',
                            "order"         => "ctime ASC",
                            'limit'         => array('number'=>$before)
                            //, "cache"       => array("service"=> 'cache', "key" => sprintf(":list:json:array:%s:%s:%s", $category_id, $limit, $offset ), "lifetime" => 60)
            ));
            $count_before = count($articles_before);
            if( $count_before == $before){
                $articles_after = Article::find(array(
                            "(category_id = :category_id: OR division_category_id = :division_category_id:) AND id < :id: AND language = :language: AND visibility = :visibility: AND status = :status:",
                            "bind"          => $parameters,
                            'columns'=>'id,division_category_id,title,author,ctime',
                            "order"         => "ctime DESC",
                            'limit'         => array('number'=>$after)
                            //, "cache"       => array("service"=> 'cache', "key" => sprintf(":list:json:array:%s:%s:%s", $category_id, $limit, $offset ), "lifetime" => 60)
                ));
            }else if( $count_before <$before){
                $articles_after = Article::find(array(
                            "(category_id = :category_id: OR division_category_id = :division_category_id:) AND id < :id: AND language = :language: AND visibility = :visibility: AND status = :status:",
                            "bind"          => $parameters,
                            'columns'=>'id,division_category_id,title,author,ctime',
                            "order"         => "ctime DESC",
                            'limit'         => array('number'=>$after+$before-$count_before)
                            //, "cache"       => array("service"=> 'cache', "key" => sprintf(":list:json:array:%s:%s:%s", $category_id, $limit, $offset ), "lifetime" => 60)
                ));
            }
           $articles_before = array_reverse($this->objToArray->tran($articles_before));
$articles_after = $this->objToArray->tran($articles_after);
           $articles = array_merge($articles_before,$articles_after);

//	print_r($articles);die;
            $result = array();
            foreach ($articles as $article){
                    //unset($article->status);
                    //unset($article->from);
                    //unset($article->from);
                    $result[]=$article;
            }
            $json = json_encode($result);
            $this->cache->save($key, $json, 120);
        }
        $response = new Phalcon\Http\Response();
        $response->setHeader('Cache-Control', 'max-age=60');
        $response->setContentType('application/json', 'utf-8');
        $response->setContent($json);
        return $response;
    }

}



