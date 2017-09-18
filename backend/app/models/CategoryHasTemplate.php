<?php

class CategoryHasTemplate extends \Phalcon\Mvc\Model
{
    public function initialize(){
        CategoryHasTemplate::skipAttributes(array('ctime'));
       
    }
    static function insert($params = null){
    	
    }
	static function getList($db , $where , $appendix = null ){
		$num = isset($appendix['pageSize'])  ? $appendix['pageSize'] : 10;
		$page = isset($appendix['page']) ? $appendix['page'] : 1;
		
		$sql = 'SELECT H.*,category.name,template.name AS tname,template.`status` AS tstatus,template.`type` AS ttype FROM category_has_template AS H ';
		$sql .= 'LEFT JOIN category ON category.id = H.category_id ';
		$sql .= 'LEFT JOIN template ON template.id = H.template_id ';
		//$sql .= 'WHERE category.`visibility`=\'Visible\' AND category.`status`=\'Enabled\' AND template.`status`=\'Enabled\' ';
		
		//$sql .= ' ORDER BY H.category_id,H.template_id';
		
		$list = $db->fetchAll($sql,PDO::FETCH_ASSOC);
		if(is_array($list)){
			$new_list = array();
			foreach($list as $k=>$v){
				$new_list[$v['category_id']][]=$v;
			}
		}
		return $new_list;
	}
}