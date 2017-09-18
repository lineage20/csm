<?php

class Album extends \Phalcon\Mvc\Model
{
    public function initialize(){
        Album::skipAttributes(array('ctime','mtime'));
    }
}

