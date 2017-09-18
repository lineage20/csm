<?php

class Article extends \Phalcon\Mvc\Model
{
	public function initialize()
	{
		$this->hasMany("id", "Images", "article_id");
	}
	
	static function getList($appendix = array())
	{
		$paginator = new \Phalcon\Paginator\Adapter\Model(
				array(
						"data" => Article::find(),
						"limit"=> $appendix['pageSize'],
						"page" => $appendix['page']
				)
		);
		
		return $paginator;
	}
}
?>