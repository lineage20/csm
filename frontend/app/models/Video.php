<?php
class Video extends \Phalcon\Mvc\Model
{
	public function initialize()
	{
		
	}
	
	static function getList($appendix = array())
	{
		$paginator = new \Phalcon\Paginator\Adapter\Model(
				array(
						"data" => Video::find(),
						"limit"=> $appendix['pageSize'],
						"page" => $appendix['page']
				)
		);
		
		return $paginator;
	}
}
?>