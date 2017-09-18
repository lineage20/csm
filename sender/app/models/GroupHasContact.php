<?php

class GroupHasContact extends \Phalcon\Mvc\Model
{
    public function initialize(){
        $this->belongsTo("contact_id", "Contact", "id");
        GroupHasContact::skipAttributes(array('ctime'));
    }
}

