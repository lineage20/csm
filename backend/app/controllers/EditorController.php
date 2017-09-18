<?php
class EditorController extends ControllerBase {
	
	public function initialize() {
        parent::initialize();
        /*$connection = new MongoClient( "mongodb://neo:chen@192.168.6.1/test" );
        $this->mongodb = $connection->selectDB('test');*/
    }
    
	public function uploadAction($dir){
    	/*//返回json格式数据*/
    	$response = new Phalcon\Http\Response();
    	//$dir = $this->request->getPost('dir');
		$savePath = $this->imagesPath;
		$domain = $this->imagesUri;
    	if(php_uname('s')=='Windows NT'){//本地测试时使用
    		$savePath = dirname($_SERVER["DOCUMENT_ROOT"]).'/images/';
    	}
		if(!file_exists($savePath)){
			mkdir($savePath, 0777, true);
		}
		//定义允许上传的文件扩展名
		$extArr = array(
			'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
			'flash' => array('swf', 'flv'),
			'media' => array('swf', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'),
			'file' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2'),
		);
		//最大文件大小
		$maxSize = 1000000;
		//检查目录名
		$dirName = empty($dir) ? 'image' : urldecode(trim($dir));
		if (empty($extArr[$dirName])) {
			$this->alert("目录名不正确。");
		}
		/*if($dirName!=''){
			$savePath .= $dirName . "/".date("Ymd").'/';
			if (!file_exists($savePath)) {
				mkdir($savePath, 0777, true);
			}
		}*/
		
		$result = $this->mongodb->upload($this->request, $dirName);
		if($result['status'] === false){
			$imgUrl = $domain.'/image/raw/'.$dirName.'/'.$result['data']['filename'];
			$response->setJsonContent(array('error' => 0, 'url' => $imgUrl));
		    return $response;
		}
		else{
			$return['error'] = '1';
			switch ($result['code']){
				case '1':
					$return['message'] = '请选择上传图片';
					break;
				case '2':
					$return['message'] = '上传图片扩展名是不允许的扩展名';
					break;
				case '3':
					$return['message'] = '上传图片大小超过限制';
					break;
				case '4':
					$return['message'] = '不能重复上传图片';
					break;
				case '5':
					$return['message'] = '未知错误';
					break;
			}
			$response->setJsonContent($return);
		    return $response;
		}
		die;
		
		/*$grid = $this->mongodb->getGridFS($dirName);
		//Check if the user has uploaded files
		if ($this->request->hasFiles() == true) {
			//Print the real file names and their sizes
			foreach ($this->request->getUploadedFiles() as $file){
				//echo $file->getName(), " ", $file->getSize(), "\n";
				//获得文件扩展名
				$tempArr = explode(".", $file->getName());
				$fileExt = array_pop($tempArr);
				$fileExt = trim($fileExt);
				$fileExt = strtolower($fileExt);
				//检查扩展名
				if (in_array($fileExt, $extArr[$dirName]) === false) {
					$this->alert("上传文件扩展名是不允许的扩展名。\n只允许" . implode(",", $extArr[$dirName]) . "格式。");
				}
				if($file->getSize()>$maxSize){
					$response->setJsonContent(array('error' => 1, 'message' => '上传文件大小超过限制。'));
    				return $response;
				}
				else{
					if($file->isUploadedFile()){
						$fileUrl = $savePath.$file->getName();
						$imgUrl = $domain.$file->getName();
						if($file->moveTo($fileUrl)){
							$result = $grid->find(array('md5'=>md5_file($fileUrl)));
                            foreach ($result as $doc) {
                                $doc_arr = $this->objToArray->ohYeah($doc);
                                $doc_arr['md5'] = $doc_arr['file']['md5'];
                            }
                            if(!$doc_arr['md5']){
                                $storedfile = $grid->storeFile($fileUrl, array('filename'=>$file->getName(),"date" => new MongoDate()));
                                if($storedfile){
									//echo $fileUrl;exit;
									$response->setJsonContent(array('error' => 0, 'url' => $imgUrl));
		    						return $response;
    							}else{
                                    $msg = $file->getError();
                                    $response->setJsonContent(array('error' => 1, 'message' => $msg));
                                    return $response;
    							}
                            }
							else{
                                //echo "<script>parent.callback('不能重复上传图片',false)</script>";  
                                //return ;
                                $response->setJsonContent(array('error' => 1, 'message' => '不能重复上传图片'));
                                return $response;
                            }
						}
						else{
							$response->setJsonContent(array('error' => 1, 'message' => $file->getError()));
    						return $response;
						}
					}
					else{
						$response->setJsonContent(array('error' => 1, 'message' => $file->getError()));
    					return $response;
					}
				}
			}
		}
		else{
			$response->setJsonContent(array('error' => 1, 'message' => '请选择文件。'));
    		return $response;
		}*/
	}
	
	public function fileManagerAction(){
		$response = new Phalcon\Http\Response();
		$path = urldecode($this->request->getPost('path'));//echo $path;exit;
	    $order = urldecode($this->request->getPost('order'));//'NAME', 
	    $dir = urldecode($this->request->getPost('dir'));
		//根目录路径，可以指定绝对路径，比如 /var/www/attached/
		$rootPath = $this->imagesPath;
		//根目录URL，可以指定绝对路径，比如 http://www.yoursite.com/attached/
		$rootUrl = $this->imagesUri;//'http://infimage.hx9999.com/';
    	if(php_uname('s')=='Windows NT'){//本地测试时使用
    		$rootPath = dirname($_SERVER["DOCUMENT_ROOT"]).'/images/';
    	}
    	//图片扩展名
    	$extArr = array('gif', 'jpg', 'jpeg', 'png', 'bmp');
		//目录名
		$dirName = empty($dir) ? '' : trim($dir);
		if (!in_array($dirName, array('', 'image', 'flash', 'media', 'file'))) {
			echo "Invalid Directory name.";
			exit;
		}
		if ($dirName !== '') {
			$rootPath .= $dirName . "/";
			$rootUrl .= $dirName . "/";
			if (!file_exists($rootPath)) {
				mkdir($rootPath, 0777, true);
			}
		}
		//exit('path='.$path);
		//根据path参数，设置各路径和URL
		if (empty($path)) {
			$currentPath = realpath($rootPath) . '/';
			$currentUrl = $rootUrl;
			$currentDirPath = '';
			$moveupDirPath = '';
		} else {
			$currentPath = realpath($rootPath) . '/' . $path;
			$currentUrl = $rootUrl . $path;
			$currentDirPath = $path;
			$moveupDirPath = preg_replace('/(.*?)[^\/]+\/$/', '$1', $currentDirPath);
		}
		//排序形式，name or size or type
		$order = empty($order) ? 'name' : strtolower($order);
		
		//不允许使用..移动到上一级目录
		if (preg_match('/\.\./', $currentPath)) {
			echo 'Access is not allowed.';
			exit;
		}
		//最后一个字符不是/
		if (!preg_match('/\/$/', $currentPath)) {
			echo 'Parameter is not valid.';
			exit;
		}
		//目录不存在或不是目录
		if (!file_exists($currentPath) || !is_dir($currentPath)) {
			echo 'Directory does not exist.';
			exit;
		}
		//遍历目录取得文件信息
		$fileList = array();
		if ($handle = opendir($currentPath)) {
			$i = 0;
			while (false !== ($filename = readdir($handle))) {
				if ($filename{0} == '.') continue;
				$file = $currentPath . $filename;
				if (is_dir($file)) {
					$fileCount = $this->mongodb->getCount($filename);
					$fileList[$i]['is_dir'] = true; //是否文件夹
					$fileList[$i]['has_file'] = ($fileCount > 0); //文件夹是否包含文件
					$fileList[$i]['filesize'] = 0; //文件大小
					$fileList[$i]['is_photo'] = false; //是否图片
					$fileList[$i]['filetype'] = ''; //文件类别，用扩展名判断
					$fileList[$i]['has_file_count'] = $fileCount;
				} else {
					$fileList[$i]['is_dir'] = false;
					$fileList[$i]['has_file'] = false;
					$fileList[$i]['filesize'] = filesize($file);
					$fileList[$i]['dir_path'] = '';
					$fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
					$fileList[$i]['is_photo'] = in_array($fileExt, $extArr);
					$fileList[$i]['filetype'] = $fileExt;
				}
				$fileList[$i]['filename'] = $filename; //文件名，包含扩展名
				$fileList[$i]['datetime'] = date('Y-m-d H:i:s', filemtime($file)); //文件最后修改时间
				$i++;
			}
			closedir($handle);
		}
		
		function cmp_func($a, $b) {
			global $order;
			if ($a['is_dir'] && !$b['is_dir']) {
				return -1;
			} else if (!$a['is_dir'] && $b['is_dir']) {
				return 1;
			} else {
				if ($order == 'size') {
					if ($a['filesize'] > $b['filesize']) {
						return 1;
					} else if ($a['filesize'] < $b['filesize']) {
						return -1;
					} else {
						return 0;
					}
				} else if ($order == 'type') {
					return strcmp($a['filetype'], $b['filetype']);
				} else {
					return strcmp($a['filename'], $b['filename']);
				}
			}
		}
		
		usort($fileList, 'cmp_func');

		$result = array();
		//相对于根目录的上一级目录
		$result['moveup_dir_path'] = $moveupDirPath;
		//相对于根目录的当前目录
		$result['current_dir_path'] = $currentDirPath;
		//当前目录的URL
		$result['current_url'] = $currentUrl;
		//文件数
		$result['total_count'] = count($fileList);
		//文件列表数组
		$result['file_list'] = $fileList;
		
		$response->setJsonContent($result);
    	return $response;
	}
	
	private function alert($msg){
		$response = new Phalcon\Http\Response();
    	$response->setJsonContent(array('error' => 1, 'message' => $msg));
    	return $response;
    	exit;
	}
	
	//排序
	private function cmp_func($a, $b) {
		global $order;
		if ($a['is_dir'] && !$b['is_dir']) {
			return -1;
		} else if (!$a['is_dir'] && $b['is_dir']) {
			return 1;
		} else {
			if ($order == 'size') {
				if ($a['filesize'] > $b['filesize']) {
					return 1;
				} else if ($a['filesize'] < $b['filesize']) {
					return -1;
				} else {
					return 0;
				}
			} else if ($order == 'type') {
				return strcmp($a['filetype'], $b['filetype']);
			} else {
				return strcmp($a['filename'], $b['filename']);
			}
		}
	}
	
}
?>