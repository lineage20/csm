<?php

use Phalcon\Mvc\View,
    Phalcon\Mvc\Controller;

class ListController extends ControllerBase {

    public function indexAction() {

    }

    public function htmlAction($template_id, $category_id, $limit = 20, $page = 0) {

        $template_id = intval($template_id);
        $category_id = intval($category_id);
        $limit = intval($limit);
        $page = intval($page);

        $offset = $limit * $page;

        if (empty($category_id) || empty($template_id)) {
            $this->response->setStatusCode(404, 'Not Found');
        }

        if ($limit > 100) {
            $limit = 100;
        }

        $this->view->disable();

        $template_file = $this->basedir . "/template/list/" . $template_id . ".phtml";
        $categroy_file = $this->basedir . "/static/list/html/$template_id/$category_id.html";

        if (!is_file($template_file)) {

            $template = Template::findFirst(array(
                        "id = :template_id: AND status = :status:",
                        "bind" => array(
                            'template_id' => $template_id,
                            'status' => 'Enabled'
                        )
            ));

            if ($template) {
                if (!is_dir(dirname($template_file))) {
                    mkdir(dirname($template_file), 0755, TRUE);
                }
                file_put_contents($template_file, $template->content);
            } else {
                $this->response->setStatusCode(404, 'Template Not Found');
                echo 'Template Not Found';
                return;
            }
        }

        $conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND visibility = :visibility: AND status = :status:";
        //language = :language: AND

        $parameters = array(
            'category_id' => $category_id,
            'division_category_id' => $category_id,
            'status' => 'Enabled',
            'visibility' => 'Visible'
        );
        $key = sprintf(":list:html:%s:%s:%s:%s", $template_id, $category_id, $limit, $page);
        $articles = Article::find(array(
                    $conditions,
                    "bind" => $parameters,
                    'columns' => 'id,division_category_id,title,author,ctime,mtime,content',
                    "order" => "ctime DESC",
                    'limit' => array('number' => $limit, 'offset' => $offset),
                        #"cache" => array("service"=> 'cache', "key" => $key, "lifetime" => 60)
        ));

        if (count($articles) == 0) {
            $this->response->setStatusCode(404, 'Article List Not Found');
            echo 'Article List Not Found';
        } else {
            $pages = $this->paginator($category_id, $limit, $page);
            $pageNavigation = new PageNavigation();

            $view = new \Phalcon\Mvc\View();
            $view->setViewsDir($this->basedir . '/template');
            $view->setRenderLevel(Phalcon\Mvc\View::LEVEL_LAYOUT);
            $view->setVar('articles', $articles);
            $view->setVar('template_id', $template_id);
            $view->setVar('category_id', $category_id);
            $view->setVar('limit', $limit);
            $view->setVar('page', $page);
            $view->setVar('pages', $pages);
            $view->setVar('pagenumber', $pageNavigation->pagenumber(ceil($pages['count'] / $limit), $page));
            $view->start();
            $view->render("list", "$template_id");
            $view->finish();

            $content = $view->getContent();
            //     	if($content){
            // 	    	if(!is_dir(dirname($categroy_file))){
            // 	    		mkdir(dirname($categroy_file), 0755, TRUE);
            // 	    	}
            // 	    	file_put_contents($categroy_file, $content);
            //     	}
            $this->response->setHeader('Cache-Control', 'max-age=60');
            $expireDate = new DateTime();
            $expireDate->modify('+1 minutes');
            $this->response->setExpires($expireDate);
            $this->response->setHeader('ETag', $eTag = crc32($key));
            $this->response->setContent($content);
            return $this->response;
        }
    }

    public function pageAction($category_id, $limit, $page = 0) {
        $pager = $this->paginator($category_id, $limit, $page);
        print_r($pager);
    }

    public function paginator($category_id, $limit, $page = 1) {
        $category_id = intval($category_id);
        $limit = intval($limit);
        $page = intval($page);

        if (!$category_id) {
            $this->response->setStatusCode(404, 'Not Found');
        }

        $count = Article::count(array(
                    "(category_id = :category_id: OR division_category_id = :division_category_id:) AND visibility = :visibility:",
                    'bind' => array(
                        'category_id' => $category_id,
                        'division_category_id' => $category_id,
                        'visibility' => 'Visible'
                    )
        ));

        $total = ceil($count / $limit) - 1;
        $before = $page <= $total && $page > 1 ? $page - 1 : 0;
        $next = $page >= $total ? $total : $page + 1;
        $paginator = array(
            'count' => $count,
            'first' => 0,
            'last' => $total,
            'before' => $before,
            'current' => $page,
            'next' => $next,
            'total' => $total
        );
        return ($paginator);
    }

    public function rssAction($template_id, $category_id, $limit = 50) {

        if ($limit > 100) {
            $limit = 100;
        }
        $conditions = "category_id = :category_id: AND language = :language: AND visibility = :visibility:";

        $parameters = array(
            'category_id' => $category_id,
            'language' => 'cn',
            'visibility' => 'Visible'
        );
        $articles = Article::find(array(
                    $conditions,
                    "bind" => $parameters,
                    'limit' => $limit
        ));

        $this->view->setVar('articles', $articles);
        $this->view->disableLevel(View::LEVEL_MAIN_LAYOUT);
    }

    public function jsonAction($category_id, $limit = 20, $offset = 0) {

        $category_id = intval($category_id);
        $limit = intval($limit);
        $offset = intval($offset);

        if (empty($category_id)) {
            $this->response->setStatusCode(404, 'Not Found');
        }

        if ($limit > 100) {
            $limit = 100;
        }

        $this->view->disable();

        $key = sprintf(":list:json:%s:%s:%s", $category_id, $limit, $offset);
        $json = null;
        $json = $this->cache->get($key);
        if (empty($json)) {
            $conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND language = :language: AND visibility = :visibility:";

            $parameters = array(
                'category_id' => $category_id,
                'division_category_id' => $category_id,
                'language' => 'cn',
                'visibility' => 'Visible'
            );
            $articles = Article::find(array(
                        $conditions,
                        "bind" => $parameters,
                        'columns' => 'id,division_category_id,title,author,ctime',
                        "order" => "ctime DESC",
                        'limit' => array('number' => $limit, 'offset' => $limit * $offset)
                        , "cache" => array("service" => 'cache', "key" => sprintf(":list:json:array:%s:%s:%s", $category_id, $limit, $offset), "lifetime" => 60)
            ));

            $result = array();
            foreach ($articles as $article) {
                //unset($article->status);
                //unset($article->from);
                //unset($article->from);
                $result[] = $article;
            }
            $result['pages'] = $this->paginator($category_id, $limit, $offset);

            $json = json_encode($result);
            $this->cache->save($key, $json, 120);
        }
        $response = new Phalcon\Http\Response();
        $response->setHeader('Cache-Control', 'max-age=60');
        $response->setContentType('application/json', 'utf-8');
        $response->setContent($json);
        return $response;
    }

    public function json2appAction($category_id, $limit = 20, $offset = 0) {

        $category_id = intval($category_id);
        $limit = intval($limit);
        $offset = intval($offset);
        $key = sprintf(":list:json2app:%s:%s:%s", $category_id, $limit, $offset);
        $json = null;

        if (empty($category_id)) {
            //$this->response->setStatusCode(404, 'Not Found');
            $json_array['code'] = 'fail';
            $json_array['num'] = 0;
            $json_array['datas'] = null;
            $json_array['pages'] = null;
            $json = json_encode($json_array);
            $this->cache->save($key, $json, 120);
        }

        if ($limit > 100) {
            $limit = 100;
        }

        $this->view->disable();

        $json = $this->cache->get($key);
        if (empty($json)) {
            $conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND language = :language: AND visibility = :visibility:";

            $parameters = array(
                'category_id' => $category_id,
                'division_category_id' => $category_id,
                'language' => 'cn',
                'visibility' => 'Visible'
            );
            $articles = Article::find(array(
                        $conditions,
                        "bind" => $parameters,
                        'columns' => 'id,division_category_id,title,author,ctime',
                        "order" => "ctime DESC",
                        'limit' => array('number' => $limit, 'offset' => $limit * $offset)
                        , "cache" => array("service" => 'cache', "key" => sprintf(":list:json:array:%s:%s:%s", $category_id, $limit, $offset), "lifetime" => 60)
            ));

            $result = array();
            $result['code'] = 'success';
            $result['num'] = $limit;
            foreach ($articles as $article) {
                //unset($article->status);
                //unset($article->from);
                //unset($article->from);
                $icon = ($article->id % 120);
                $article->icon = 'http://inf.gwfx.com/img/list/small/' . $icon . '.png';
                $result['datas'][] = $article;
            }
            $result['pages'] = $this->paginator($category_id, $limit, $offset);

            $json = json_encode($result);
            $this->cache->save($key, $json, 120);
        }
        $response = new Phalcon\Http\Response();
        $response->setHeader('Cache-Control', 'max-age=60');
        $response->setContentType('application/json', 'utf-8');
        $response->setContent($json);
        return $response;
    }

    public function purgeAction($template_id, $category_id) {
        $template_id = intval($template_id);
        $parent_id = intval($parent_id);

        $template_file = $this->basedir . "/template/list/" . $template_id . ".phtml";

        unlink($template_file);

        if ($category_id > 0) {
            $categroy_path = $this->basedir . "/static/list/html/$template_id/$category_id.html";
        } else {
            $categroy_path = $this->basedir . "/static/list/html/$template_id/*";
        }

        array_map('unlink', glob($categroy_path));

        $this->cache->flush();
    }

    public function newsJsonAction($category_id, $limit = 10, $page = 0) {

        $category_id = intval($category_id);
        $limit = intval($limit);
        $page = intval($page);
        $offset = $limit * $page;
        if (empty($category_id)) {
            $this->response->setStatusCode(404, 'Not Found');
        }
        if ($limit > 100) {
            $limit = 100;
        }
        $this->view->disable();
        $conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND visibility = :visibility: AND status = :status:";
        $parameters = array(
            'category_id' => $category_id,
            'division_category_id' => $category_id,
            'status' => 'Enabled',
            'visibility' => 'Visible'
        );
        $articles = Article::find(array(
                    $conditions,
                    "bind" => $parameters,
                    'columns' => 'id,division_category_id,title,author,ctime,mtime,content',
                    "order" => "ctime DESC",
                    'limit' => array('number' => $limit, 'offset' => $offset),
        ));
        $articles = $this->objToArray->ohYeah($articles);
        foreach ($articles as $article) {
            $result[] = $article;
        }
        foreach ($result as $k => $vs) {
            $result[$k]['title'] = mb_strlen($vs['title'], 'utf-8') > 28 ? mb_substr($vs['title'], 0, 28, 'utf-8') . '....' : $vs['title'];
            $result[$k]['content'] = mb_strlen($vs['content'], 'utf-8') > 80 ? strip_tags(mb_substr($vs['content'], 0, 80, 'utf-8')) . '....' : strip_tags($vs['content']);
        }
        // $result['pages'] = $this->paginator($category_id, $limit, $offset);
        if (count($articles) == 0) {
            echo 'Article List Not Found';
        } else {
            echo json_encode($result);
            exit();
        }
    }
    //新网站评论列表页
    public function tagAction($template_id, $category_id, $limit = 10, $page = 0) {
        if($this->request->isPost()){
                $params = $this->request->getPost('tag');
        }
        $template_id = intval($template_id);
        $category_id = intval($category_id);
        $limit = intval($limit);
        $page = intval($page);
        $offset = $limit * $page;
        if (empty($category_id) || empty($template_id)) {
            $this->response->setStatusCode(404, 'Not Found');
        }
        if ($limit > 100) {
            $limit = 100;
        }
        $this->view->disable();
        $template_file = $this->basedir . "/template/list/" . $template_id . ".phtml";
        $categroy_file = $this->basedir . "/static/list/html/$template_id/$category_id.html";
        if (!is_file($template_file)) {
            $template = Template::findFirst(array(
                        "id = :template_id: AND status = :status:",
                        "bind" => array(
                            'template_id' => $template_id,
                            'status' => 'Enabled'
                        )
            ));
            if ($template) {
                if (!is_dir(dirname($template_file))) {
                    mkdir(dirname($template_file), 0755, TRUE);
                }
                file_put_contents($template_file, $template->content);
            } else {
                $this->response->setStatusCode(404, 'Template Not Found');
                echo 'Template Not Found';
                return;
            }
        }
        if($params == 'all' || $params == ''){
            $conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND visibility = :visibility: AND status = :status:";
            $parameters = array(
                'category_id' => $category_id,
                'division_category_id' => $category_id,
                'status' => 'Enabled',
                'visibility' => 'Visible'
            );
        }elseif($params == 'other'){
            $conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND visibility = :visibility: AND status = :status: AND tag is null OR tag = :tag:";
            $parameters = array(
                'category_id' => $category_id,
                'division_category_id' => $category_id,
                'status' => 'Enabled',
                'visibility' => 'Visible',
                'tag' => 'other'
            );
        }else{
            $conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND visibility = :visibility: AND status = :status: AND tag = :tag:";
            $parameters = array(
                'category_id' => $category_id,
                'division_category_id' => $category_id,
                'status' => 'Enabled',
                'tag' => $params,
                'visibility' => 'Visible'
            );
        }

        $key = sprintf(":list:html:%s:%s:%s:%s", $template_id, $category_id, $limit, $page);

        $articles = Article::find(array(
                    $conditions,
                    "bind" => $parameters,
                    'columns' => 'id,division_category_id,title,author,ctime,mtime,content,tag',
                    "order" => "ctime DESC",
                    'limit' => array('number' => $limit, 'offset' => $offset),
                        #"cache" => array("service"=> 'cache', "key" => $key, "lifetime" => 60)
        ));

        $pages = $this->paginator($category_id, $limit, $page);
        $pageNavigation = new PageNavigation();

        $view = new \Phalcon\Mvc\View();

        $view->setViewsDir($this->basedir . '/template');
        $view->setRenderLevel(Phalcon\Mvc\View::LEVEL_LAYOUT);
        if($params){
                $where = $params;
                $view->setVar('where', $where);
        }
        $view->setVar('articles', $articles);
        $view->setVar('template_id', $template_id);
        $view->setVar('category_id', $category_id);
        $view->setVar('limit', $limit);
        $view->setVar('page', $page);
        $view->setVar('pages', $pages);
        $view->setVar('pagenumber', $pageNavigation->pagenumber(ceil($pages['count'] / $limit), $page));
        $view->start();
        $view->render("list", "$template_id");
        $view->finish();

        $content = $view->getContent();
        $this->response->setHeader('Cache-Control', 'max-age=60');
        $expireDate = new DateTime();
        $expireDate->modify('+1 minutes');
        $this->response->setExpires($expireDate);
        $this->response->setHeader('ETag', $eTag = crc32($key));
        $this->response->setContent($content);
        return $this->response;

    }
    //新网站获取更多评论
    public function commentJsonAction($category_id, $limit = 10, $page = 0, $tag = 'all') {

        $category_id = intval($category_id);
        $limit = intval($limit);
        $page = intval($page);
        $offset = $limit * $page;
        if (empty($category_id)) {
            $this->response->setStatusCode(404, 'Not Found');
        }
        if ($limit > 100) {
            $limit = 100;
        }
        $this->view->disable();
        if($tag == 'all'){
            $conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND visibility = :visibility: AND status = :status:";
            $parameters = array(
                'category_id' => $category_id,
                'division_category_id' => $category_id,
                'status' => 'Enabled',
                'visibility' => 'Visible'
            );
        }elseif($tag == 'other'){
            $conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND visibility = :visibility: AND status = :status: AND tag is null OR tag = :tag:";
            $parameters = array(
                'category_id' => $category_id,
                'division_category_id' => $category_id,
                'status' => 'Enabled',
                'visibility' => 'Visible',
                'tag' => 'other'
            );
        }else{
            $conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND visibility = :visibility: AND status = :status: AND tag = :tag:";
            $parameters = array(
                'category_id' => $category_id,
                'division_category_id' => $category_id,
                'status' => 'Enabled',
                'tag' => $tag,
                'visibility' => 'Visible'
            );
        }

        $articles = Article::find(array(
                    $conditions,
                    "bind" => $parameters,
                    'columns' => 'id,division_category_id,title,author,ctime,mtime,content,tag',
                    "order" => "ctime DESC",
                    'limit' => array('number' => $limit, 'offset' => $offset),
        ));

        $articles = $this->objToArray->ohYeah($articles);
        foreach ($articles as $article) {
            $result[] = $article;
        }
        foreach ($result as $k => $vs) {
            $result[$k]['title'] = mb_strlen($vs['title'], 'utf-8') > 28 ? mb_substr($vs['title'], 0, 28, 'utf-8') . '....' : $vs['title'];
            $result[$k]['content'] = mb_strlen($vs['content'], 'utf-8') > 80 ? mb_substr(strip_tags(str_replace('&nbsp;','',$vs['content'])), 0, 80, 'utf-8') . '....' : strip_tags($vs['content']);
        }
        // $result['pages'] = $this->paginator($category_id, $limit, $offset);
        if (count($articles) == 0) {
            echo 'Article List Not Found';
        } else {
            echo json_encode($result);
            exit();
        }
    }

}
