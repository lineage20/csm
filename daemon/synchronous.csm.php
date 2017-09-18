<?php
#use Elasticsearch\ClientBuilder;
class SynchronousWorker extends Worker {

	// public function __construct(Logging $logger) {
	// $this->logger = $logger;
	// }

	protected $config;
	protected static $dbh_export, $dbh_import;
	public function __construct($config) {
		$this->config = $config;
	}
	public function run() {


	}

	private function connect($io){
		try {
			if ($io == 'export' ){
				$dbhost1 = $this->config['export']['dbhost'];
				$dbport1 = $this->config['export']['dbport'];
				$dbuser1 = $this->config['export']['dbuser'];
				$dbpass1 = $this->config['export']['dbpass'];
				$dbname1 = $this->config['export']['dbname'];
				self::$dbh_export = new PDO ( "mysql:host=$dbhost1;port=$dbport1;dbname=$dbname1", $dbuser1, $dbpass1, array (
						PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
						PDO::MYSQL_ATTR_COMPRESS => true
				) );
				self::$dbh_export->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			}
			else{
				$dbhost = $this->config['cf']['dbhost'];
				$dbport = $this->config['cf']['dbport'];
				$dbuser = $this->config['cf']['dbuser'];
				$dbpass = $this->config['cf']['dbpass'];
				$dbname = $this->config['cf']['dbname'];

				self::$dbh_import = new PDO ( "mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass, array (
						PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
						PDO::MYSQL_ATTR_COMPRESS => true
						/*PDO::ATTR_PERSISTENT => true*/
				) );
				self::$dbh_import->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			}

		} catch ( PDOException $e ) {
                        $this->logger ( 'Exception real_news', $e->getMessage( ) );
                } catch ( Exception $e ) {
                        $this->logger ( 'Exception real_news', $e->getMessage( ) );
                }
	}

	public function getInstance($io) {
		if ($io == 'export' ){
			if(!self::$dbh_export){
				$this->connect($io);
				$this->logger ( 'Database', sprintf("Connect database %s, %s", $this->config['export']['dbname'], $this->getThreadId ()) );
			}else{
				$this->logger ( 'Database', sprintf("Get instance database %s, %s", $this->config['export']['dbname'], $this->getThreadId ()) );
			}

			if(self::$dbh_export){
				return self::$dbh_export;
			}else{
				$this->logger ( 'Database', sprintf("Connect database is error %s, %s", $this->config['export']['dbname'], $this->getThreadId ()) );
				$this->logger ( 'Error', sprintf("Worker is shutdown %s", $this->getThreadId ()) );
				$this->shutdown();
			}
			//return self::$dbh_export;
		} else {
			if(!self::$dbh_import){
				$this->connect($io);
				$this->logger ( 'Database', sprintf("Connect database %s, %s", $this->config['cf']['dbname'], $this->getThreadId ()) );
			}else{
				$this->logger ( 'Database', sprintf("Get instance database %s, %s", $this->config['cf']['dbname'], $this->getThreadId ()) );
			}

			if(self::$dbh_import){
				return self::$dbh_import;
			}else{
				$this->logger ( 'Database', sprintf("Connect database is error %s, %s", $this->config['cf']['dbname'], $this->getThreadId ()) );
				$this->logger ( 'Error', sprintf("Worker is shutdown %s", $this->getThreadId ()) );
				$this->shutdown();
			}
			//return self::$dbh_import;
		}
	}
	public function logger($type, $message) {
		$log = sprintf ( "%s\t%s\t%s\t%s\n", date ( 'Y-m-d H:i:s' ), $this->getThreadId (), $type, $message );
		file_put_contents ( sprintf(__DIR__."/../log/cf.%s.log", date ( 'Y-m-d' )), $log, FILE_APPEND );
	}
	public function savepoints($division_id, $category_id, $type, $position) {
		$db = $this->getInstance ( 'cf' );
		//$sql = "REPLACE INTO `synchronous` (`division_id`, `category_id`, `type`, `position`) VALUES (:division_id, :category_id, :type, :position);";
		$sql = "Update `synchronous` set `position` = :position where `division_id` = :division_id and `category_id` = :category_id and `type` = :type";
		$sth = $db->prepare ( $sql );
		$sth->bindValue ( ':division_id', $division_id );
		$sth->bindValue ( ':category_id', $category_id );
		$sth->bindValue ( ':type', $type );
		$sth->bindValue ( ':position', $position );
		return $sth->execute ();
	}
	public function getpoints($division_id, $category_id, $type) {
		$db = $this->getInstance ( 'cf' );
		$sql = "select position from `synchronous` where division_id=:division_id and category_id=:category_id and type=:type";
		$sth = $db->prepare ( $sql );
		$sth->bindValue ( ':division_id', $division_id );
		$sth->bindValue ( ':category_id', $category_id );
		$sth->bindValue ( ':type', $type );
		$sth->execute ();
		$result = $sth->fetch ( PDO::FETCH_OBJ );
		if ($result) {
			return $result->position;
		} else {
			return 0;
		}
	}
}

class RealNewsWork extends Threaded {
	public $division_id;
	public function __construct($division_id, $lang, $dbmaps) {
		$this->dbmaps = $dbmaps;
		$this->lang = $lang == 'cn'?'zh':$lang;
		$this->division_id = $division_id;
	}
	public function run() {
		//$this->worker->logger('real_news', sprintf("%s executing in Thread #%lu", __CLASS__, $this->worker->getThreadId()));
		try {
			$db_export = $this->worker->getInstance ( 'export' );
			$db_import = $this->worker->getInstance ( 'import' );
			//$db_import->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			//$db_export->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			$position = 1;
			foreach ( $this->dbmaps as $division_category_id => $type ) {
				$division_id = $this->division_id;
				$category_id = $division_category_id;

				$position = $this->worker->getpoints ( $division_id, $category_id, $type, $position );

				$sql = "SELECT no as id, name as title, content, if(language='zh','cn',language) as language, newstime as ctime, SEO_KEYWORDS as keyword, SEO_DESCRIPTION as description FROM real_news WHERE LANGUAGE = '" . $this->lang . "' AND TYPE in (" . $type . ") AND no > '" . $position . "' ORDER BY no asc";
				$query = $db_export->query ( $sql );
				//$this->worker->logger ( 'SQL', $query->queryString );
                                $title_prefix = array("【亚股盘初】","【原油盘初】","【美债收盘】","【美股收盘】","【欧股收盘】","【欧债收盘】","【期金收盘】","【原油收盘】","【中国股市】","【美股盘初】","【图解北美行情】","【美股开盘】","【港股收盘】","【欧股早盘】","【美国股市】","【香港股市】","【欧股开盘】","【亚股收盘】","【欧股盘初】","【亚股早盘】");

				while ( $line = $query->fetch ( PDO::FETCH_OBJ ) ) {

                                        foreach($title_prefix as $vs){
					if(strstr($line->title, $vs)){
						$tag = $vs;
                                        	$title = str_replace($title_prefix,"",$line->title) ;
						break;
					}else{
						$tag = null;
						$title = $line->title;

                                        }

					}

					$sql = "insert into article (`division_id`, `division_category_id`,  `title`,  `content`, `author`,  `keyword`,  `description`,  `image`,  `language`,  `source`,  `share`,  `visibility`,  `status`,  `ctime`,  `mtime`,  `tag`) values(:division_id, :division_category_id, :title, :content, :author, :keyword, :description, :image,  :language, :source, :share, :visibility, :status, :ctime, :mtime, :tag)";
					$sth = $db_import->prepare ( $sql );
					$sth->bindValue ( ':division_id', $this->division_id );
					$sth->bindValue ( ':division_category_id', $division_category_id );
					$sth->bindValue ( ':title', $title);
					$sth->bindValue ( ':content', str_replace(array("金道"),"创富", $line->content) );
					$sth->bindValue ( ':author', null );
					$sth->bindValue ( ':keyword', $line->keyword );
					$sth->bindValue ( ':description', str_replace(array("金道"),"创富", $line->description) );
					$sth->bindValue ( ':image', null );
					$sth->bindValue ( ':language', $line->language );
					$sth->bindValue ( ':source', $line->id );
					$sth->bindValue ( ':share', 'N' );
					$sth->bindValue ( ':visibility', 'Visible' );
					$sth->bindValue ( ':status', 'Enabled' );
					$sth->bindValue ( ':ctime', $line->ctime );
					$sth->bindValue ( ':mtime', null );
                                        $sth->bindValue ( ':tag', $tag );
					$sth->execute ();

					$lastInsertId = $db_import->lastInsertId();
                                        //搜索
					include_once(dirname(__DIR__).'/Library/vendor/autoload.php');
					$hosts = array('so.cf88.com');
                                        $client = \Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();
                                        $params = array();
                                        $params['index'] = 'information';
                                        $params['type'] = 'news';
                                        $params['id'] = $lastInsertId;
                                        $params['body'] = array(
											'id'						=> $lastInsertId,
                                            'division_category_id'     => $division_category_id,
                                            'title'                    => $title,
                                            'content'                  => str_replace(array("金道"),"创富", $line->content),
                                            'tag'                      => $tag,
                                            'source'					=> $line->id,
                                            'ctime'                    => date("Y-m-d\TH:i:s", strtotime($line->ctime))
                                        );

                                        $ret = $client->index($params);

					$this->worker->logger ( 'real_news', sprintf ( "%s=>%s %s, %s, %s, %s", $division_category_id, $type, $line->ctime, $line->id, $line->language, $line->title ) );
					if ($line->id) {
						$position = $line->id;
					}
					$this->worker->savepoints ( $division_id, $category_id, $type, $position );
				}
			}
		} catch ( PDOException $e ) {
			$this->worker->logger ( 'Exception real_news', $e->getMessage( ) );
		} catch ( Exception $e ) {
			$this->worker->logger ( 'Exception real_news', $e->getMessage( ) );
		}
	}
	public function export() {
	}
}

class NewsWork extends Threaded {
	public $division_id;
	public function __construct($division_id, $lang, $dbmaps) {
		$this->dbmaps = $dbmaps;
		$this->lang = $lang == 'cn'?'zh':$lang;
		$this->division_id = $division_id;
	}
	public function run() {
		//$this->worker->logger('Thread - news', "%s executing in Thread #%lu", __CLASS__, $this->worker->getThreadId() );
		try {
			$db_import = $this->worker->getInstance ( 'cf' );
			$db_export = $this->worker->getInstance ( 'export' );
			//$db_export->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			//$db_import->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			$position = 1;
			foreach ( $this->dbmaps as $division_category_id => $type ) {
				$division_id = $this->division_id;
				$category_id = $division_category_id;

				$position = $this->worker->getpoints ( $division_id, $category_id, $type, $position );

				$sql = "SELECT no as id, title, description as content, author, if(language='zh','cn',language) as language, SEO_KEYWORDS as keyword, SEO_DESCRIPTION as description, updatetime as ctime, updatetime as mtime, image_b1, image_b2, image_b3, image_b4, image_s1, image_s2, image_s3, image_s4,video,audio FROM news WHERE kind in (" . $type . ") AND NO IS  NOT NULL  AND display = 0 AND  LANGUAGE = '".$this->lang."' AND no > '" . $position . "' ORDER BY NO asc";
				// AND  (equipment IS NULL OR  equipment!='mobile')
				$query = $db_export->query ( $sql );
				//$this->worker->logger ( 'SQL', $query->queryString );

				while ( $line = $query->fetch ( PDO::FETCH_OBJ ) ) {

					$sql = "insert into article (`division_id`, `division_category_id`,  `title`,  `content`, `author`,  `keyword`,  `description`,  `image`,  `language`,  `source`,  `share`, `attribute`, `visibility`,  `status`,  `ctime`,  `mtime`) values(:division_id, :division_category_id, :title, :content, :author, :keyword, :description, :image,  :language, :source, :share, :attribute, :visibility, :status, :ctime, :mtime)";
					$sth = $db_import->prepare ( $sql );

					$attribute = serialize ( array(
						'image_b1' 	=> $line->image_b1,
						'image_b2' 	=> $line->image_b2,
						'image_b3' 	=> $line->image_b2,
						'image_b4' 	=> $line->image_b4,
						'image_s1' 	=> $line->image_s1,
						'image_s2' 	=> $line->image_s2,
						'image_s3' 	=> $line->image_s3,
						'image_s4' 	=> $line->image_s4,
						'video'		=> $line->video,
						'audio'		=> $line->audio
						));

					$sth->bindValue ( ':division_id', $this->division_id );
					$sth->bindValue ( ':division_category_id', $division_category_id );
					$sth->bindValue ( ':title', $line->title );
					$sth->bindValue ( ':content', $line->content );
					$sth->bindValue ( ':author', $line->author );
					$sth->bindValue ( ':keyword', $line->keyword );
					$sth->bindValue ( ':description', $line->description );
					$sth->bindValue ( ':image', null );
					$sth->bindValue ( ':language', $line->language );
					$sth->bindValue ( ':source', '创富金融' );
					$sth->bindValue ( ':share', 'N' );
					$sth->bindValue ( ':attribute', $attribute );
					$sth->bindValue ( ':visibility', 'Visible' );
					$sth->bindValue ( ':status', 'Enabled' );
					$sth->bindValue ( ':ctime', $line->ctime );
					//$sth->bindValue ( ':mtime', $line->mtime );
					$sth->bindValue ( ':mtime', null );
					$sth->execute ();

					$this->worker->logger ( 'news', sprintf ( "%s=>%s %s, %s, %s, %s", $division_category_id, $type, $line->ctime, $line->id, $line->language, $line->title ) );
					if ($line->id) {
						$position = $line->id;
					}
					$this->worker->savepoints ( $division_id, $category_id, $type, $position );
				}
			}
		} catch ( PDOException $e ) {
			$this->worker->logger ( 'Exception real_news', $e->getMessage( ) );
		} catch ( Exception $e ) {
			$this->worker->logger ( 'Exception real_news', $e->getMessage( ) );
		}
	}
	public function import() {
	}
	public function export() {
	}
}




class Task {

	const MAXCONN 	= 32;

	protected $result = array();

	public function __construct($division_id, $config) {
		$this->division_id = $division_id;
		$this->config = $config;
	}
	public function main(){

		$dbhost = $this->config['cf']['dbhost'];
		$dbport = $this->config['cf']['dbport'];
		$dbuser = $this->config['cf']['dbuser'];
		$dbpass = $this->config['cf']['dbpass'];
		$dbname = $this->config['cf']['dbname'];

		$dbh = new PDO ( "mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass, array (
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
			PDO::MYSQL_ATTR_COMPRESS => true
			/*PDO::ATTR_PERSISTENT => true*/
		) );

//		$sql = "select * from `synchronous` where division_id=:division_id";
//		$sth = $dbh->prepare ( $sql );
//		$sth->bindValue ( ':division_id', $this->division_id );
//		$sth->execute ();
//
//		$syncs = $sth->fetchAll ( PDO::FETCH_OBJ );
                
                //插入所有维修中心数据
                $sql_websites = " select website from sys_website where is_delete = 0  order by id desc ";
                $sth_websites = $dbh->prepare ( $sql_websites );
		$sth_websites->execute ();
		$sys_websites = $sth_websites->fetchAll ( PDO::FETCH_ASSOC );
                
                $sql_sku_all  = " select sku from goods_price group by sku ";
                $sth_sku_all = $dbh->prepare ( $sql_sku_all );
		$sth_sku_all->execute ();
		$sku_all = $sth_sku_all->fetchAll ( PDO::FETCH_ASSOC );
                
                $sql_sys_product  = " SELECT sp.goods_sn,sp.goods_name,sp.sale_price_base FROM sys_product as sp left join goods_price as gp on sp.goods_sn = gp.sku WHERE is_delete = 0 AND `type` = '6665' AND gp.id is null ";
                $sth_sys_product = $dbh->prepare ( $sql_sys_product );
		$sth_sys_product->execute ();
		$sys_product = $sth_sys_product->fetchAll ( PDO::FETCH_ASSOC );
                
                $insert_string_two  = $this->insertProductData($sys_websites,$sys_product,$dbh);
                $insert_string  = $this->insertData($sys_websites,$sku_all,$dbh);
                if($insert_string != ""){
                    if($insert_string_two != ""){
                        $insert_string = $insert_string.",".$insert_string_two;
                    }
                }else{
                    $insert_string = $insert_string_two;
                }

                $params_key_string = "(website,sku,goods_name,price,currency,create_time)";
                $sql_insert        = "insert into {$this->table_price} {$params_key_string} values {$insert_string}";
                if($insert_string != "" && $dbh->query( $sql_insert )){

                }

		
	}
        public function insertData($sys_websites,$sku_all,$dbh){
        $insert_string = "";
        $create_time = time();
        $price = 0;
        $currency = "USD";
        foreach($sys_websites as $key_web=>$val_web){
            foreach($sku_all as $key_sku=>$val_sku){
                $sql_goods_name = "SELECT goods_name FROM `goods_price` where sku=:sku limit 1";
		$sth_goods_name = $dbh->prepare ( $sql_goods_name );
		$sth_goods_name->bindValue ( ':sku', $val_sku['sku'] );
		$sth_goods_name->execute ();
		$goods_name = $sth_goods_name->fetch ( PDO::FETCH_OBJ );
                
                $sql = "SELECT id FROM `goods_price` where sku=:sku and website=:website";
		$sth = $dbh->prepare ( $sql );
		$sth->bindValue ( ':sku', $val_sku['sku'] );
		$sth->bindValue ( ':website', $val_web['website'] );
		$sth->execute ();
		$result = $sth->fetch ( PDO::FETCH_OBJ );
                if ($result->id > 0) {
                    
                } else {
                    $insert_string .= "("."'{$val_web['website']}'".","."'{$val_sku['sku']}'".","."'{$goods_name->goods_name}'".","."{$price}".","."'{$currency}'".","."{$create_time}".")".",";
                }
            }
        }
        
        return rtrim($insert_string, ",");
    }
    
    public function insertProductData($sys_websites,$sys_product,$dbh){
        $insert_string = "";
        $create_time = time();
        $currency = "USD";
        foreach($sys_websites as $key_web=>$val_web){
            foreach($sys_product as $key_sku=>$val_sku){
                $sql = "SELECT id FROM `goods_price` where sku=:sku and website=:website";
		$sth = $dbh->prepare ( $sql );
		$sth->bindValue ( ':sku', $val_sku['sku'] );
		$sth->bindValue ( ':website', $val_web['website'] );
		$sth->execute ();
		$result = $sth->fetch ( PDO::FETCH_OBJ );
                if ($result->id > 0) {
                    
                } else {
                    $price = round($val_sku['sale_price_base'],2);
                    $insert_string .= "("."'{$val_web['website']}'".","."'{$val_sku['goods_sn']}'".","."'{$val_sku['goods_name']}'".","."{$price}".","."'{$currency}'".","."{$create_time}".")".",";
                }
            }
        }
        
        return rtrim($insert_string, ",");
    }

}


class Synchronous {
	/* config */
	const LISTEN = "tcp://192.168.2.15:5555";
	const pidfile 	= __CLASS__;
	const uid		= 80;
	const gid		= 80;
	const sleep	= 60;

	protected $pool 	= NULL;
	protected $config	= array();

	public function __construct() {
		$this->pidfile = '/var/run/'.basename(__FILE__, '.php').'.pid';
		$this->config = include_once(__DIR__."/config.php");
	}
	private function daemon(){
		if (file_exists($this->pidfile)) {
			echo "The file $this->pidfile exists.\n";
			exit();
		}

		$pid = pcntl_fork();
		if ($pid == -1) {
			 die('could not fork');
		} else if ($pid) {
			 // we are the parent
			 //pcntl_wait($status); //Protect against Zombie children
			exit($pid);
		} else {
			// we are the child
			file_put_contents($this->pidfile, getmypid());
			posix_setuid(self::uid);
			posix_setgid(self::gid);
			return(getmypid());
		}
	}
	private function start(){
		$pid = $this->daemon();
		for(;;){
			$task = new Task (1, ($this->config));
			$task->main();
			sleep(self::sleep);
		}
	}
	private function stop(){

		if (file_exists($this->pidfile)) {
			$pid = file_get_contents($this->pidfile);
			posix_kill($pid, 9);
			unlink($this->pidfile);
		}
	}
	private function status(){
		if (file_exists($this->pidfile)) {
			$pid = file_get_contents($this->pidfile);
			system(sprintf("ps ax | grep %s | grep -v grep", $pid));
		}
	}
	private function help($proc){
		printf("%s start | stop | restart | status | help \n", $proc);
	}
	public function main($argv){

		if(count($argv) < 2){
			$this->help($argv[0]);
			printf("please input help parameter\n");
			exit();
		}
		if($argv[1] === 'stop'){
			$this->stop();
		}else if($argv[1] === 'start'){
			$this->start();
                }else if($argv[1] === 'restart'){
			$this->stop();
                        $this->start();
		}else if($argv[1] === 'status'){
			$this->status();
		}else{
			$this->help($argv[0]);
		}
	}
}

$synchronous = new Synchronous();
$synchronous->main($argv);
?>


