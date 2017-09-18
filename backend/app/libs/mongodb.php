<?php 
namespace libs;

class mongodb
{
	public $db = false ;
	public $config = null;
	function __construct($config = null){
		if($this->db === false){
//			$connection = new \MongoClient($config->host);
//			$this->db = $connection->selectDB($config->dbname);
                    $this->db = new MongoDB\Driver\Manager("mongodb://localhost:27017/".$config->dbname);
		}
		$this->config = $config;
	}
	public function get($where){
		$data =$this->db->getGridFS->findOne($where);
	}
	/**
	 * 上传文件
	 * @param unknown $request 上传的对象，是为控制器中的$this->request
	 * @param unknown $folder 文件夹
	 * @param string $checkmd5 是否允许重复文件
	 * @return multitype:boolean Ambigous <NULL, number> Ambigous <NULL, string>
	 */
	public function upload($request , $folder , $checkmd5 = true){
            
		$status = true;
		$msg = null;
		$code = null;
		$data = array();
		$grid = $this->db->getGridFS($folder);
		
		$this->config->fileinfo->savePath .=$folder.'/';
		if(!file_exists($this->config->fileinfo->savePath)){
			mkdir($this->config->fileinfo->savePath, 0777, true);
		}
		if ($request->hasFiles() == true) {
			
			foreach ($request->getUploadedFiles() as $file) {
				
				$type = $file->getType();
				
				/** 类型 **/
				$type = $this->config->fileinfo->type;//
				
				if(!preg_match('/image/i', $type)){
					$code = 2;
					$msg = 'type invalid';
					break;
				}
				
				/** 文件大小 **/
				if($file->getSize()>$this->config->fileinfo->maxSize){
					$code = 3;
					$msg = 'size too big';
					break;
				}
				$fileUrl = $this->config->fileinfo->savePath.$file->getName();
				if($file->isUploadedFile()){
					if($file->moveTo($fileUrl)){
						$result = $grid->find(array('md5'=>md5_file($fileUrl)));
						$have = false;
						if($checkmd5 = true){
							foreach($result as $k=>$v){
								$have = true ;
							}
						}
						/** 文件MD5是否重复 **/
						if(!$have){
							
							$storedfile = $grid->storeFile($fileUrl, array('filename'=>$file->getName(),'date' => new \MongoDate()));
							
							if(empty($storedfile)){
								$code = 5;
								$msg = $file->getError();
							}else{
								$data = array('filename'=>$file->getName());
								$status = false;
							}
						}else{
							$code = 4;
							$msg = 'file isExit';
							break;
						}
					}
				}
			}
		}else{
			$code = 1;
			$msg = 'do not upload';
		}
		return array('status'=>$status,'code'=>$code,'msg'=>$msg,'data'=>$data);
	}
	/**
	 * 显示图片
	 * @param unknown $action 显示类型,目前有raw和thumbnail
	 * @param unknown $folder 文件夹
	 * @param unknown $filename 文件名称
	 * @param unknown $attribute 属性，主要传递控制器里面的两个参数array('response'=>,'basedir'=>)
	 * @return unknown
	 */
	public function view($action, $folder, $filename , $attribute){
		
		$grid = $this->db->getGridFS($folder);
		$image = $grid->findOne($filename);
		
		$response = $attribute['response'];
		
		if ($image) {	
			$image_file = sprintf("%s/image/%s/%s/%s", $attribute['basedir'], $action, $folder, $filename);
			$content = $image->getBytes();
			if(!is_dir(dirname($image_file))){
				mkdir(dirname($image_file), 0755, TRUE);
			}
			file_put_contents($image_file , $content);
			$response->setHeader('Cache-Control', 'max-age=60');
			if(function_exists('mime_content_type')){
				$response->setHeader('Content-type', mime_content_type($image_file));
			}else{
				$response->setHeader('Content-type', $this->mime_content_type($image_file));
			}
			$response->setContent($content);
			echo $content;
		}else{
			$response->setStatusCode(404, 'Image Not Found');
			$response->setContent('Image Not Found');
		}
		return $response;
	}
	
	/**
	 * 获取图片数量
	 * @param unknown_type $folder
	 */
	public function getCount($folder){
		$grid = $this->db->getGridFS($folder);
		return $grid->count();
	}
	
	public function mime_content_type($filename){
		
				$mime_types = array(
		
						'txt' => 'text/plain',
						'htm' => 'text/html',
						'html' => 'text/html',
						'php' => 'text/html',
						'css' => 'text/css',
						'js' => 'application/javascript',
						'json' => 'application/json',
						'xml' => 'application/xml',
						'swf' => 'application/x-shockwave-flash',
						'flv' => 'video/x-flv',
		
						// images
						'png' => 'image/png',
						'jpe' => 'image/jpeg',
						'jpeg' => 'image/jpeg',
						'jpg' => 'image/jpeg',
						'gif' => 'image/gif',
						'bmp' => 'image/bmp',
						'ico' => 'image/vnd.microsoft.icon',
						'tiff' => 'image/tiff',
						'tif' => 'image/tiff',
						'svg' => 'image/svg+xml',
						'svgz' => 'image/svg+xml',
		
						// archives
						'zip' => 'application/zip',
						'rar' => 'application/x-rar-compressed',
						'exe' => 'application/x-msdownload',
						'msi' => 'application/x-msdownload',
						'cab' => 'application/vnd.ms-cab-compressed',
		
						// audio/video
						'mp3' => 'audio/mpeg',
						'qt' => 'video/quicktime',
						'mov' => 'video/quicktime',
		
						// adobe
						'pdf' => 'application/pdf',
						'psd' => 'image/vnd.adobe.photoshop',
						'ai' => 'application/postscript',
						'eps' => 'application/postscript',
						'ps' => 'application/postscript',
		
						// ms office
						'doc' => 'application/msword',
						'rtf' => 'application/rtf',
						'xls' => 'application/vnd.ms-excel',
						'ppt' => 'application/vnd.ms-powerpoint',
		
						// open office
						'odt' => 'application/vnd.oasis.opendocument.text',
						'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
				);
				$temp = explode('.',$filename);
				$temp_arr = array_pop($temp);
				$ext = strtolower($temp_arr);
				if (array_key_exists($ext, $mime_types)) {
					return $mime_types[$ext];
				}
				elseif (function_exists('finfo_open')) {
					$finfo = finfo_open(FILEINFO_MIME);
					$mimetype = finfo_file($finfo, $filename);
					finfo_close($finfo);
					return $mimetype;
				}
				else {
					return 'application/octet-stream';
				}
			}

}


?>