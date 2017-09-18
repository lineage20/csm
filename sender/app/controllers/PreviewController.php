<?php
class PreviewController extends ControllerBase
{
    public function emailAction($message_id,$template_id){
        $this->view->disable();
        $template = Template::findFirst("id = '{$template_id}'");
        $message = Message::findFirst("id = '{$message_id}'");
        $keyword = array("{{title}}","{{content}}","{{date}}"); 
        $value = array($message->title, $message->content, $message->ctime); 			
        $message->content = str_replace($keyword, $value, $template->content);
        echo $message->content;
        
    }
}

