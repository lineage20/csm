<?php

use Phalcon\Mvc\View,
    Phalcon\Mvc\Controller;

class SearchController extends ControllerBase {

    private $total = 0;
    public $pageNavigation;

    public function initialize() {
        $this->pageNavigation = new PageNavigation();
    }

    public function indexAction() {

    }

    public function htmlAction($template_id, $category_id, $query, $limit = 20, $page = 0) {

        $template_id = intval($template_id);
        $category_id = intval($category_id);
        $query = strval($query);
        $limit = intval($limit);
        $page = intval($page);

        $offset = $limit * $page;

        if (empty($category_id) || empty($template_id) || empty($query)) {
            $this->response->setStatusCode(404, 'Not Found');
        }

        if ($limit > 100) {
            $limit = 100;
        }

        $this->view->disable();

        $template_file = $this->basedir . "/template/search/" . $template_id . ".phtml";
        $categroy_file = $this->basedir . "/static/search/html/$template_id/$category_id.html";

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
        //Elasticsearch-PHP 搜索文章
        $articles = $this->elasticsearch($query, $limit, $page);

        $key = sprintf(":search:html:%s:%s:%s:%s:%s", $template_id, $category_id, $query, $limit, $page);
        if (count($articles) == 0) {
            $this->response->setStatusCode(404, 'Article List Not Found');
            echo 'Article List Not Found';
        } else {
            $pages = $this->pageNavigation->paginator($this->total, $limit, $page);

            $view = new \Phalcon\Mvc\View();
            $view->setViewsDir($this->basedir . '/template');
            $view->setRenderLevel(Phalcon\Mvc\View::LEVEL_LAYOUT);
            $view->setVar('articles', $articles);
            $view->setVar('template_id', $template_id);
            $view->setVar('category_id', $category_id);
            $view->setVar('limit', $limit);
            $view->setVar('pagenumber', $this->pageNavigation->pagenumber(ceil($this->total / $limit), $page));
            $view->setVar('pages', $pages);
            $view->setVar('page', $page);
            $view->setVar('query', $query);
            $view->start();
            $view->render("search", "$template_id");
            $view->finish();

            $content = $view->getContent();

            $this->response->setHeader('Cache-Control', 'max-age=60');
            $expireDate = new DateTime();
            $expireDate->modify('+1 minutes');
            $this->response->setExpires($expireDate);
            $this->response->setHeader('ETag', $eTag = crc32($key));
            $this->response->setContent($content);
            //print($content);
            return $this->response;
        }
    }

    public function pageAction($category_id, $limit, $page = 0) {
        $pager = $this->pageNavigation->paginator($category_id, $limit, $page);
        print_r($pager);
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

        $key = sprintf(":search:json:%s:%s:%s", $category_id, $limit, $offset);
        $json = null;
        $json = $this->cache->get($key);
        if (empty($json)) {
            $articles = $this->elasticsearch($query, $limit, $page);

            $result = array();
            foreach ($articles as $article) {
                //unset($article->status);
                //unset($article->from);
                //unset($article->from);
                $result[] = $article;
            }
            $result['pages'] = $this->paginator($category_id, $limit, $offset);

            $json = json_encode($result);
            $this->cache->save($key, $json, 60);
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

    private function elasticsearch($query, $size, $from) {
        //Elasticsearch-PHP 搜索文章
        include_once(dirname(__DIR__) . '/../../Library/vendor/autoload.php');
        $hosts = array('so.cf88.com');
        $client = \Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();
        $params = array();
        $params = [
            "size" => $size,
            'from' => $size * $from,
            'index' => 'information',
            'type' => 'news',
            '_source' => ['id', 'division_category_id', 'title', 'ctime','tag'],
            'body' => [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'type' => 'most_fields',
                        'fields' => ['tag', 'content', 'title'],
                        'operator' => 'and'
                    ]
                ],
                'sort' => [
                    ["ctime" => ["order" => "desc"]]
                ]
            ]
        ];
        error_log(json_encode($params));
        $ret = $client->search($params);
        $results = $ret['hits']['hits'];
        $this->total = $ret['hits']['total'];
        /*
          for($i=0;$i<count($articles);$i++){
          unset($articles[$i]['_index']);
          unset($articles[$i]['_type']);
          unset($articles[$i]['_id']);
          unset($articles[$i]['_score']);
          }
         */
        $articles = array();
        foreach ($results as $result) {
            $articleObject = new stdClass();
            $articleObject->id = $result['_id'];
            $articleObject->title = $result['_source']['title'];
            $articleObject->division_category_id = $result['_source']['division_category_id'];
            $articleObject->ctime = $result['_source']['ctime'];
            $articleObject->tag = $result['_source']['tag'];
            $articles[] = $articleObject;
        }
        return $articles;
    }

}
