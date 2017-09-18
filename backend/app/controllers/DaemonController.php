<?php 
class DaemonController extends ControllerBase
{
	public function initialize(){
		parent::initialize();
		$this->division_id = $this->Division_id;
		$this->view->division_id = $this->division_id;
		
	}
	public function indexAction(){	
		
	}
	public function mapAction($type = 0  , $page = 1 , $eNum = 1  , $entranceUrl = null){
		
		if($type == 'clear'){
			$this->session->remove('maplist');
			$this->session->remove('mappage');
			return ;
		}
		//print_r($this->session->get('mappage'));
		//exit();
		
		$map = array();
		$map['209'] = array( #inf中的分类,公司活动,'division_category_id'=>208,
						'title'=>null,
						'content'=>null,
						'division_id'=>1,//$this->division_id,//当前的部门
						'language'=>'cn',
						'source'=>'HXPM',
						'visibility'=>'Visible',
						'status'=>'Enabled',
						'share'=>'N',
						'author'=>'computer',
						'picker' =>array(
										'entranceUrl'=>'http://www.hx9999.com/cn/intro_227.html',
										'page'=>null,
										'orderby'=>'DESC',
										'list'=>'/<a href="(?P<pic_url>\S+?)"><img src="(?P<pic>\S+?)">\s*<\/a>\s+<h2><a href="(?P<url>\S+?)">(?P<title>.+?)<\/a><\/h2>\s*<p>(?P<description>.+?)(<span|\.\.\.)/is',
										'content'=>'/<h1>(?P<title>.+?)<\/h1>\s+?<div class="content">(?P<content>.+?<div class="clear"><\/div>\s*<\/div>)/is',
									)
			
		);
		
		$map['210'] = array( #inf中的分类,公司活动,'division_category_id'=>208,(手机版)
				'title'=>null,
				'content'=>null,
				'division_id'=>1,//$this->division_id,//当前的部门
				'language'=>'cn',
				'source'=>'HXPM',
				'visibility'=>'Visible',
				'status'=>'Enabled',
				'share'=>'N',
				'author'=>'computer',
				'picker' =>array(
						'entranceUrl'=>'http://m.hx9999.com/about/activity',
						'page'=>null,
						'orderby'=>'DESC',
						'list'=>'/<div class="newsItem">\s+<a href="(?P<url>\S+?)">\s*<div class="thumb">\s*<img src="(?P<pic>\S+?)".+?>\s*<\/div>.+?<div>(?P<description>.+?)\.\.\./is',
						'content'=>'/<h1 class="news_title">(?P<title>.+?)<\/h1>\s+?<div class="news_date">(?P<ctime>(\d|\:|\s|\-)+?)<\/div>.+?<div class="content">(?P<content>.+?<div class="clear"><\/div>)/is',
				)
					
		);
		/** 最新公告PC **/
		$map['212'] = array(
				'subs' => array('/休市/is'=>'215','/升级|维护|升级/is'=>'216','/公告/is'=>'214'), //内容需要区分子类
			
				'title'=>null,
				'content'=>null,
				'division_id'=>1,//$this->division_id,//当前的部门
				'language'=>'cn',
				'source'=>'HXPM',
				'visibility'=>'Visible',
				'status'=>'Enabled',
				'share'=>'N',
				'author'=>'computer',
				'picker' =>array(
						'entranceUrl'=>'http://info.hx9999.com/cn/affiche_list.html?page=26',
						 
						/** 动态数据 **/
						'ajaxUrl'=>'http://info.hx9999.com/affiche_fetch/ajax.fetch_list.php?page={page}&lang=cn',
						'ajaxPageUrl'=>'http://www.hx9999.com/info/cn/affiche/{id}.html',
						'ajaxDataType'=>'json',
						/** 动态数据格式 **/
						
						'page'=>'/当前 \d+\/(?P<allpage>.+?) 页\:/is',
						'orderby'=>'DESC',
						'list'=>'/<span class="title">\s*<a href="(?P<url>.+?)"/is',
						'content'=>'/<div class="news_title">(?P<title>.+?)\s*<span class="date">(?P<ctime>(\d|\:|\s|\-)+?)<\/span>\s*<\/div>\s*<div class="newsContent">(?P<content>.+?)<div class="share"/is',
				)
		);
		
		$doPicker = 1;
		$doSuccess = 0;
		
		if(isset($map[$type]['picker'])){
			
			$picker = $map[$type]['picker'];
			
			unset($map[$type]['picker']);
			$map[$type]['division_category_id'] = $type;
			
			$pickerBase = empty($entranceUrl) ? $picker['entranceUrl'] : base64_decode($entranceUrl); //总入口文件
			echo 'entranceUrl：'.$pickerBase.'<br/>';
			
			$base_url_arr = parse_url($pickerBase);
			$base_url_arr['dir'] = (isset($base_url_arr['path']) && $base_url_arr['path']!='/') ? dirname($base_url_arr['path']).'/' : '';
			$pages = array();
			$allPage = 0;
			
			isset($base_url_arr['query']) && parse_str($base_url_arr['query'],$pages);
			
			
			/** 使用缓存 **/
			if($this->session->get('maplist')){
				$maplist = $this->session->get('maplist');
				if(isset($maplist[$pickerBase])){
					$r = $maplist[$pickerBase];
				}
				isset($maplist['allPage']) && $allPage = $maplist['allPage'];
			}
			
			/** 缓存结束 **/
			
			if(!isset($r['url']) || empty($r['url'])){
				
				
				$baseContent = $this->curl($pickerBase); 
				
				if(isset($picker['ajaxUrl']) && $picker['ajaxUrl'] && isset($base_url_arr['query'])){
					
					parse_str($base_url_arr['query'],$pages);
					if(isset($pages['page']) && $pages['page']){
						
						$picker['ajaxUrl'] = str_replace('{page}', $pages['page'], $picker['ajaxUrl']);
						$ajaxData = $this->curl($picker['ajaxUrl']);
						$ajaxData = json_decode($ajaxData , true);
						foreach($ajaxData['list'] as $kk=>$kv){
							$r['url'][]= str_replace('{id}', $kv['id'], $picker['ajaxPageUrl']);
						}
					}
				}
			}
			
			
			
			if(isset($picker['list']) && $picker['list']){
				if(!isset($r)){
					preg_match_all($picker['list'], $baseContent,$r);
					if(isset($picker['page'])){
						preg_match($picker['page'], $baseContent,$rp);
						$allPage = isset($rp['allpage']) ? $rp['allpage'] : 0;
					}
				}
				
				if(isset($r['url']) && $r['url']){
			
					!isset($maplist) && array($pickerBase=>$r,'allPage'=>$allPage);
					$maplist[$pickerBase] = $r;
					$allPage && $maplist['allPage'] = $allPage;
					$this->session->set('maplist', $maplist);
					
					/** 列表数据 **/
					$bNum = ($page-1)*$eNum; //[0->10),[10,20)
					
					if($picker['orderby'] == 'DESC'){
						krsort($r['url']);
						isset($r['ctime']) && krsort($r['ctime']);
						isset($r['description']) && krsort($r['description']);
						isset($r['pic']) && krsort($r['pic']);
	
					}
					$allUrlCount = count($r['url']);
					
					$r['url'] = array_splice($r['url'], $bNum,$eNum);
					isset($r['ctime']) && $r['ctime'] = array_splice($r['ctime'], $bNum,$eNum);
					isset($r['description']) && $r['description'] = array_splice($r['description'], $bNum,$eNum);
					isset($r['pic']) && $r['pic'] = array_splice($r['pic'], $bNum,$eNum);
					
					$nowUrlCount = count(array_filter($r['url'])); ## 判断当前页面支付执行完成
					
					echo '<br />===========================================<br/>';
					print_r($r['url']);
					
					echo '<br />===========================================<br/>';
					
					
					if($doPicker>0){
						foreach($r['url'] as $k=>$v){
							
							if(empty($v)){
								continue;
							}
							
							$contengUrl = stripos($v,'http') ===0 ? $v : ($base_url_arr['scheme'].'://'
									.(substr($v,0,1)=='/' 
										? ($base_url_arr['host']) 
											: ($base_url_arr['host'].$base_url_arr['dir'])) . $v);
							
							/** 页面缓存 **/
							$doPages = $this->session->get('mappage') ? $this->session->get('mappage') : array();
							if(in_array($contengUrl, $doPages)){
								continue;
							}
							
							$iExecTime=ini_get("max_execution_time");
							ini_set("max_execution_time",300);
							$content  = $this->curl($contengUrl);
							ini_set("max_execution_time",$iExecTime);
						
							preg_match($picker['content'], $content,$ri);
							
							if(isset($ri['content'])){
								
								
								/** 如果成功则清除队列 **/
								$doPages[] = $contengUrl;
								$this->session->set('mappage', $doPages);
									
								$maplist[$pickerBase]['url'][$k] = null;
								$this->session->set('maplist', $maplist);
								/** 清空队列结束 **/
								
								
								/** 文章内容 **/
								$map[$type][$contengUrl] = $map[$type];
								$art = new Article(true);
								
								if(is_array($map[$type])){
									foreach($map[$type][$contengUrl] as $ki=>$vi){
										$art->{$ki} = $vi;
									}
									
									/** 子类 **/
									if(isset($map[$type][$contengUrl]['subs'])){
										$subs = $map[$type][$contengUrl]['subs'];
										unset($map[$type][$contengUrl]['subs']);
										$ctype =end($subs);
										foreach($subs as $sk=>$sv){
											if(preg_match($sk, $ri['title'])){
												$ctype = $sv;
												break;
											}
										}
										$art->division_category_id = $ctype;
									}
									
									
									$art->title = $ri['title'];
									$art->content = $ri['content'];
									
									/** 时间 **/
									if(isset($ri['ctime']) && $ri['ctime']){
										$art->ctime = $ri['ctime'];
									}
									/** 描述 **/
									if(isset($r['description'][$k]) && $r['description'][$k]){
										$art->description = $r['description'][$k];
									}
									
								}
								if($art->save()){
									echo $art->id.'<br/>';
									
									$nowUrlCount -=1;
									
									if(isset($r['pic'][$k])){
										
										$img = new Images;
										$img->article_id = $art->id;
										$img->url = $r['pic'][$k];
										
										if($img->save()){
											echo $img->id.'<br>';
										}else{
											print_r($img->getMessages());
										}
									}
								}else{
									print_r($art->getMessages());
								}
							}
						}
						
					}
					
					sleep(2);
					
					/** 页面列表执行完毕 **/
					if($bNum+$eNum>=$allUrlCount){
						/** 是否分页 **/
						if(isset($picker['page'])){
								
							$pages['page'] = ($picker['orderby'] == 'DESC') ? ($pages['page']-1) : ($pages['page']+1);
							
							$nextEntranceUrl = $base_url_arr['scheme'].'://'.$base_url_arr['host'].$base_url_arr['path'].'?'.http_build_query($pages);
							$nextUrl = '/daemon/map/'.$type.'/1/'.$eNum.'/'.base64_encode($nextEntranceUrl);
							
							if($pages['page']<1){
								exit('分页为0了:'.$pickerBase);
							}
							echo 'nextEntranceUrl：'.$nextEntranceUrl.'<br/>';
							echo 'nextUrl：'.$nextUrl;
						}else{
							/** 执行完毕 **/
							echo 'doComplete';
							exit();
						}
					}else{
						$nextEntranceUrl = $base_url_arr['scheme'].'://'.$base_url_arr['host'].$base_url_arr['path'].'?'.http_build_query($pages);
						$nextUrl = '/daemon/map/'.$type.'/%s/'.$eNum.'/'.base64_encode($nextEntranceUrl);
						$nextUrl = sprintf($nextUrl,$page+1);
					}
					if($nowUrlCount>0){
						echo 'error ------ nowUrlCount:'.$nowUrlCount.'<br/><a href="'.$nextUrl.'">'.$nextUrl.'</a>';
						exit();
					}
					?>
					<script type="text/javascript"> document.location.href='<?php echo $nextUrl ?>'</script>
					<?php 
					exit();
				}
			}
		}
		return $map;
		
	}
	
	public function discountAction($type , $page = 1 , $eNum = 5){
		$map = array();
		$map['140'] = array( #inf中的分类,公司活动,'division_category_id'=>208,
				'title'=>null,
				'content'=>null,
				'division_id'=>1,//$this->division_id,//当前的部门
				'language'=>'cn',
				'source'=>'HXPM',
				'visibility'=>'Visible',
				'status'=>'Enabled',
				'share'=>'N',
				'picker' =>array(
						'entranceUrl'=>'http://www.hx9999.com/cn/intro_act.html',
						'page'=>null,
						'orderby'=>'DESC',
						//'list'=>'/<a href="(?P<pic_url>\S+?)" target="_blank"><img src="(?P<pic>\S+?)" alt=""></a>/is',//'list'=>'/<a href="(?P<pic_url>\S+?)"><img src="(?P<pic>\S+?)"><\/a>\s+<h2><a href="(?P<url>\S+?)">/is',
						'title'=>'/<div class="act_desc">\s+<p>(?P<title>.+?)<\/p>/is',
						'contentall'=>'/<div class="content introact_list">\s+<ul>(?P<content>.+?)\<\/ul>/is',
						'content'=>'/<li>(?P<content>.+?)\s+<div class="clear">/is',
				)
					
		);
		$map['141'] = array( #inf中的分类,公司活动,'division_category_id'=>208,
				'title'=>null,
				'content'=>null,
				'division_id'=>1,//$this->division_id,//当前的部门
				'language'=>'cn',
				'source'=>'HXPM',
				'visibility'=>'Visible',
				'status'=>'Enabled',
				'share'=>'N',
				'picker' =>array(
						'entranceUrl'=>'http://m.hx9999.com/about/companyact',
						'page'=>null,
						'orderby'=>'DESC',
						//'list'=>'/<a href="(?P<pic_url>\S+?)" target="_blank"><img src="(?P<pic>\S+?)" alt=""></a>/is',//'list'=>'/<a href="(?P<pic_url>\S+?)"><img src="(?P<pic>\S+?)"><\/a>\s+<h2><a href="(?P<url>\S+?)">/is',
						'title'=>'/<div class="act_desc">\s+<p>(?P<title>.+?)<\/p>/is',
						'content'=>'/<li>(?P<content>.+?)\s+<div class="clear">/is',
				)
					
		);
		if(isset($map[$type]['picker'])){
				
			$picker = $map[$type]['picker'];
				
			unset($map[$type]['picker']);
			$map[$type]['division_category_id'] = $type;
				
			$pickerBase = $picker['entranceUrl'] ; //总入口文件
				
			$base_url_arr = parse_url($pickerBase);
			$base_url_arr['dir'] = (isset($base_url_arr['path']) && $base_url_arr['path']!='/') ? dirname($base_url_arr['path']).'/' : '';
				
			$baseContent = $this->curl($pickerBase);
				
			preg_match_all($picker['title'], $baseContent, $ri);
			$contentAll = array();
			$content = array();
			if(isset($picker['contentall'])){
				preg_match($picker['contentall'], $baseContent, $contentAll);
				$baseContent = $contentAll['content'];
				preg_match_all($picker['content'], $baseContent, $content);
			}
			else{
				preg_match_all($picker['content'], $baseContent, $content);
			}
			if(isset($ri['title']) && isset($content['content'])){
	
				/** 文章内容 **/
				for($i = (sizeof($ri['title']) - 1), $size = 0; $i >= $size; $i--){
					$art = new Article(true);
					foreach ($map[$type] as $key => $val){
						$art->{$key} = $val;
					}
					$art->title = $ri['title'][$i];
					$art->content = $content['content'][$i];
					$art->division_category_id = $type;
					if($art->save()){
	
					}else{
						print_r($art->getMessages());
					}
				}
	
			}
		}
		return $map;
	
	}
	
	private function curl($url, $fields = array(), $auth = false) {
		$url_arr = parse_url($url);
		$curl = curl_init($url);
		$headers = array(
				'Accept: text/plain, */*; q=0.01',
				'Accept-Encoding: gzip, deflate',
				'Accept-Language: zh-CN,zh;q=0.8,en;q=0.6,vi;q=0.4,zh-TW;q=0.2',
				'Connection: keep-alive',
				'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
		);
		if(isset($url_arr['host'])){
			$headers[] = 'Host: ' . $url_arr['host'];
			$headers[] = 'Origin: https://' . $url_arr['host'];
		}
		$headers[] = 'X-Requested-With: XMLHttpRequest';
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_REFERER, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		
		$jar = $this->cookieFile();
		curl_setopt($curl, CURLOPT_COOKIEFILE, $jar);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $jar);
		
		if ($fields) {
			$fields_string = http_build_query($fields);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_string);
		}
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}
	private function cookieFile(){
		$dir = 'cookie/test.txt';
		!file_exists($dir) && touch($dir) && chmod($dir, 0777);
		return $dir;
	}
}