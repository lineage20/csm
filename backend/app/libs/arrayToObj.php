<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of arrayToObj
 *
 * @author gary-stork
 */
class arrayToObj {
    function tran($e){
        if( gettype($e)!='array' ) return;
        foreach($e as $k=>$v){
            if( gettype($v)=='array' || getType($v)=='object' )
                $e[$k]=(object)$this->tran($v);
        }
        return (object)$e;
    }
}

?>
