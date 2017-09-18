<?php

 /**
     *@uses 金额大小写转换 
     *@name Transform
     *@param 
     *@return 
     */
class Transform{
    private static $basical=array(0=>'零','壹','贰','叁','肆','伍','陆','柒','捌','玖');//基数
    private static $advanced=array(1=>'拾','佰','仟');
    public  function convert($number){
    $number=trim($number);
    if(!is_numeric($number) && intval($number)>999999999999) return '输入金额不合法！';
    if($number==0) return '零';
//    $data = array();
    if(strpos($number,'.')){//查找小数点
      $number=round($number,2);//四舍五入，并保留2位小数
      $data=explode('.',$number);
      $data[0]=self::intParse($data[0]);
      $data[1]=isset($data[1]) ? self::decParse($data[1]):'';
      return $data[0].$data[1];
    }else{
      return self::intParse($number).'整';
    }
  }
  
  /**
     *@uses 整数部分转换
     *@name intParse
     *@param 
     *@return 
     */
  public static function intParse($number){
    $arr=array_reverse(str_split($number));//将金额转换为数组后反转数组
    $data='';
    $zero=false;
    $zero_num=0;
    foreach($arr as $k=>$v){
      $_chinese='';
      $zero=($v==0)?true:false;
      $x=$k%4;
      if($x && $zero && $zero_num>1)continue;
      switch($x){
        case 0:
          if($zero){
            $zero_num=0;
          }else{
            $_chinese=self::$basical[$v];
            $zero_num=1;
          }
          if($k==8){
            $_chinese.='亿';
          }elseif($k==4){
            $_chinese.='万';
          }
          break;  
        default:
          if($zero){//$v==0
            if($zero_num==1){
              $_chinese=self::$basical[$v];
              $zero_num++;
            }
          }else{
            $_chinese=self::$basical[$v];
            $_chinese.=self::$advanced[$x];
          }
      }
      $data=$_chinese.$data;
    }
    return $data.'元';
  }
  
   /**
     *@uses 小数部分转换
     *@name decParse
     *@param 
     *@return 
     */
  public static function decParse($number){
    if(strlen($number)<2) $number.='0';
    $arr=array_reverse(str_split($number));
    $data='';
    $zero_num=false;
    foreach($arr as $k=>$v){
      $zero=($v==0)?true:false;
      $_chinese='';
      if($k==0){
        if(!$zero){
          $_chinese=self::$basical[$v];
          $_chinese.='分';
          $zero_num=true;
        }
      }else{
        if($zero){
          if($zero_num){
            $_chinese=self::$basical[$v];
          }
        }else{
          $_chinese=self::$basical[$v];
          $_chinese.='角';
        }
      }
      $data=$_chinese.$data;
    }
    return $data;
  }
}
//$obj=new Transform();
//$a=$_GET['val'];
//$value=$obj->convert($a);
//echo "<form action='' method='get'><input type='text' value='' name='val'><input type='submit' value='提交'></form>";
//echo $value;
?>
