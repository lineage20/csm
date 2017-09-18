<?php
class Sms{
    
    
    
    public function send($mobile, $content,$option = null){
            $username = 'hengxin';
            $password = 'FsMIP8h4Ox35AR9Z';
            $url = 'http://msg2.as1010.com/SendSms.asp?id=30188115&to=0086'.$mobile.'&msg='.urlencode($content);
            if(empty($mobile) || empty($content))
            {
                return FALSE;
            }
	return file_get_contents($url);
    }
}
/*
$sms = new Sms();
$rev = $sms->send('13143997793','您好，您本次的验证码为：123456，如有疑问可随时致电免费客服专线 4008112559。');
print_r($rev);
*/
