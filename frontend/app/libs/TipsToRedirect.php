<?php
class TipsToRedirect {
	
	private function countDownScript(){
		$script = '<script type="text/javascript">'.
				  '   var i=5;'.
				  '   var intervalid;'.
				  '   intervalid = setInterval("countDown()", 1000);'.
				  '   function countDown() {'.
				  '      if(i==0){'.
				  '        clearInterval(intervalid);'.
				  '      }';
				  '      $(".timer").text(i);'.
				  '      i--;'.
				  '   }';
		return $script;
	}
	
	/**
	 * 页面显示提示，带倒计时跳转
	 * @param $status
	 * @param $times
	 * @param $message
	 * @param $url
	 */
	function tipsHtml($status, $times, $message, $url){
		$html = '<div class="box-content alerts">';
		if($status=='success'){
			$html .= '	<div class="alert alert-success">';
		}
		elseif($status=='error'){
			$html .= '	<div class="alert alert-error">';
		}
		$html .= '		<button type="button" class="close" data-dismiss="alert">×</button>';
		if($message){
			$msg = '';
			foreach ($message as $k => $v){
				$msg .= '		<strong>'.$v.'</strong> <br />';
			}
			$html .= $msg;	
		}
		$html .= '		页面将在<span class="timer">'.$times.'</span>后自动跳转，请点击<a href="'.$url.'">这里</a>跳转'.
				 '  </div>'.
		 		 '</div>';
		return $html;
	}
	
	/**
	 * 操作成功提示，点确定后跳转
	 * @param $message
	 * @param $url
	 */
	function modalSuccessTips($message, $url){
		$script = '$("#successModal>.modal-body>p").html("'.$message.'");'.
				  '$("#successModal>.modal-header>.success,#successModal>.modal-footer>.success").click(function(){'.
				  '    location.href = "'. $url.'";'.
				  '});'.
				  '$("#successModal").modal("show");';
		return $script;
	}
	
	/**
	 * 简单提示
	 * @param $message
	 */
	function modalTips($message){
		$script = '$("#myModal>.modal-body>p").html("'.$message.'");'.
				  '$("#successModal").modal("show");';
		return $script;
	}
}
?>