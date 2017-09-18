<?php

class Tree
{
    public function __construct () {
        
    }
    public function _tree($arr,$parent_id=0,$level = 0){
        static $tree = array();
        foreach($arr as $v){
            if($v['parent_id'] == $parent_id){
                $v['level'] = $level;
                $tree[] = $v;
                $this->_tree($arr,$v['id'],$level+1);
            }
        }
        return $tree;
    }
    /**
    * 得到子级数组
    * @param int
    * @return array
    */
//    function get_child($arr,$parent_id)
//    {
//        $a = $newarr = array();
//        if(is_array($arr))
//        {
//            foreach($arr as $id => $a)
//            {
//                if($a['parent_id'] == $parent_id) $newarr[$id] = $a;
//            }
//        }
//        return $newarr ? $newarr : false;
//    }
   
}

