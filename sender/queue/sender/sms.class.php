<?php
class Sms{
    
    
    
    public function Diexin($mobile, $content,$option = null){
            $username = 'hengxin';
            $password = 'FsMIP8h4Ox35AR9Z';
            $url = 'http://114.215.130.61:8082/SendMT/SendMessage';
            if(empty($mobile) || empty($content))
            {
                return FALSE;
            }
            // $sms_content = iconv("UTF-8","GBK",$content); 
            $param = "UserName={$username}&UserPass={$password}&Mobile={$mobile}&Content={$content}";
            $data = $this->PostHttpStatusCode($url,$param);
            $ret = 0;
            if(substr($data['content'],0,2) =='03')
            {
                return TRUE;
            }else{
                return FALSE;
            }
    }
    /**
     * @param string
     * @param array
     * @return array
     * 实现CURL POST提交数据
     * */
     public  function PostHttpStatusCode($url, $data){
        	  $ch = curl_init();
        	  curl_setopt($ch, CURLOPT_URL, $url);  
        	  curl_setopt($ch, CURLOPT_POST, 1);
        	  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        	  curl_setopt($ch, CURLOPT_HEADER, false);
        	  #curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        	  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        	  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        	  curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        	  $list['content']=curl_exec($ch); 
        	  $list['status']= curl_getinfo($ch,CURLINFO_HTTP_CODE);
        	  curl_close($ch);
        	  return $list; 
        }
}

/*$sms = new Sms();
$moblile = $sms->Diexin('13698041116','您好，您本次的验证码为：123456，如有疑问可随时致电免费客服专线 4008112559。');
print_r($moblile);
*/

