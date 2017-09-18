<?php
/**
 * Description of ObjToArray
 *
 * @author gary-stork
 */
class objToArray {
    
    public function __construct () {
        
    }
    /**
     * @uses 把对象转化为数组
     * @name ohYeah
     * @param $obj
     * @return $data
     */
    public function ohYeah ($obj) {
        $data = array();
       	if($obj){
	        foreach ($obj as  $key1=> $vals) {
	        	if($vals){
		            foreach ($vals as $key2=>$val) {
		                $data[$key1][$key2]=$val;
		            }
	        	}
	        }
       	}
        return $data;
    }
    
    public function tran ($obj) {
        return $this->ohYeah($obj);
    }
}

?>
