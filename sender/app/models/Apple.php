<?php

use Phalcon\Mvc\Collection;
class Apple extends Collection
{
    public function initialize(){
        $this->setConnectionService('mongo');
    }
}

