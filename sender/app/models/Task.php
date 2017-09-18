<?php

class Task extends \Phalcon\Mvc\Model
{
    public function initialize(){
        Task::skipAttributes(array('mtime','gateway','ctime','total'));
        $this->belongsTo("group_id", "Group", "id");
        $this->belongsTo("template_id", "Template", "id");
        $this->belongsTo("message_id", "Message", "id");
        $this->hasMany("id","Queue","task_id");
    }
}

