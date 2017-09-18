<?php

use Phalcon\Mvc\User\Component;

class PageNavigation extends Component {

    //put your code here
    public function pagenumber($total, $page) {
        $begin = 0;
        $end = 0;
        $size = 10;

        if ($page < $size) {
            $begin = 0;
			if($total<$size){
				$end = $total;
			}else{
				$end = $size;
			}
            
        } else {
            if ($page + 5 < $total) {
                $end = $page + 5;
            } else {
                $end = $total;
            }

            $begin = $page - 5;
        }

        $pages = array();
        for ($i = $begin; $i < $end; $i++) {
            $pages[] = $i;
        }
 
        return $pages;
    }
    public function paginator($total, $limit, $page = 0) {
        $total = intval($total);
        $limit = intval($limit);
        $page = intval($page);

        $count = $total;

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

}
