<?php
date_default_timezone_set('PRC'); //设置中国时区 
ini_set('date.timezone','Asia/Shanghai'); // 'Asia/Shanghai' 为上海时区 
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
			if($io == 'export'){
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
				$dbhost = $this->config['import']['dbhost'];
				$dbport = $this->config['import']['dbport'];
				$dbuser = $this->config['import']['dbuser'];
				$dbpass = $this->config['import']['dbpass'];
				$dbname = $this->config['import']['dbname'];

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
	protected function getInstance($io) {
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
				$this->logger ( 'Database', sprintf("Connect database %s, %s", $this->config['import']['dbname'], $this->getThreadId ()) );
			}else{
				$this->logger ( 'Database', sprintf("Get instance database %s, %s", $this->config['import']['dbname'], $this->getThreadId ()) );
			}
		
			if(self::$dbh_import){
				return self::$dbh_import;
			}else{
				$this->logger ( 'Database', sprintf("Connect database is error %s, %s", $this->config['import']['dbname'], $this->getThreadId ()) );
				$this->logger ( 'Error', sprintf("Worker is shutdown %s", $this->getThreadId ()) );
				$this->shutdown();
			}
			//return self::$dbh_import;
		}
	}
	public function logger($type, $message) {
		$log = sprintf ( "%s\t%s\t%s\t%s\n", date ( 'Y-m-d H:i:s' ), $this->getThreadId (), $type, $message );
		file_put_contents ( sprintf(__DIR__."/../log/gwfx.%s.log", date ( 'Y-m-d' )), $log, FILE_APPEND );
	}
	public function savepoints($division_id, $category_id, $type, $position) {
		$db = $this->getInstance ( 'import' );
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
		$db = $this->getInstance ( 'import' );
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

class RealNewsWork extends Stackable {
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

				$sql = "SELECT no as id, name as title, content, if(language='zh','cn',language) as language, newstime as ctime, SEO_KEYWORDS as keyword, SEO_DESCRIPTION as description FROM real_news WHERE LANGUAGE = '" . $this->lang . "' AND TYPE='" . $type . "' AND no > '" . $position . "' ORDER BY no asc";
				$query = $db_export->query ( $sql );
				//$this->worker->logger ( 'SQL', $query->queryString );

				while ( $line = $query->fetch ( PDO::FETCH_OBJ ) ) {

					$sql = "insert into article (`division_id`, `division_category_id`,  `title`,  `content`, `author`,  `keyword`,  `description`,  `image`,  `language`,  `source`,  `share`,  `visibility`,  `status`,  `ctime`,  `mtime`) values(:division_id, :division_category_id, :title, :content, :author, :keyword, :description, :image,  :language, :source, :share, :visibility, :status, :ctime, :mtime)";
					$sth = $db_import->prepare ( $sql );
					$sth->bindValue ( ':division_id', $this->division_id );
					$sth->bindValue ( ':division_category_id', $division_category_id );
					$sth->bindValue ( ':title', $line->title );
					$sth->bindValue ( ':content', $line->content );
					$sth->bindValue ( ':author', null );
					$sth->bindValue ( ':keyword', $line->keyword );
					$sth->bindValue ( ':description', $line->description );
					$sth->bindValue ( ':image', null );
					$sth->bindValue ( ':language', $line->language );
					$sth->bindValue ( ':source', 'GWFX' );
					$sth->bindValue ( ':share', 'N' );
					$sth->bindValue ( ':visibility', 'Visible' );
					$sth->bindValue ( ':status', 'Enabled' );
					$sth->bindValue ( ':ctime', $line->ctime );
					$sth->bindValue ( ':mtime', null );
					$sth->execute ();

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

class NewsWork extends Stackable {
	public $division_id;
	public function __construct($division_id, $lang, $dbmaps) {
		$this->dbmaps = $dbmaps;
		$this->lang = $lang == 'cn'?'zh':$lang;
		$this->division_id = $division_id;
	}
	public function run() {
		//$this->worker->logger('Thread - news', "%s executing in Thread #%lu", __CLASS__, $this->worker->getThreadId() );
		try {
			$db_import = $this->worker->getInstance ( 'import' );
			$db_export = $this->worker->getInstance ( 'export' );
			//$db_export->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			//$db_import->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			$position = 1;
			foreach ( $this->dbmaps as $division_category_id => $type ) {
				$division_id = $this->division_id;
				$category_id = $division_category_id;

				$position = $this->worker->getpoints ( $division_id, $category_id, $type, $position );

				$sql = "SELECT no as id, title, description as content, author, if(language='zh','cn',language) as language, SEO_KEYWORDS as keyword, SEO_DESCRIPTION as description, updatetime as ctime, updatetime as mtime, image_b1, image_b2, image_b3, image_b4, image_s1, image_s2, image_s3, image_s4,video,audio FROM news WHERE kind='".$type."' AND NO IS  NOT NULL  AND display = 0 AND  LANGUAGE = '".$this->lang."' AND no > '" . $position . "' ORDER BY NO asc";
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
					$sth->bindValue ( ':source', 'GWFX' );
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


class VideoWork extends Stackable {
	public $division_id;
	public function __construct($division_id, $lang, $dbmaps) {
		$this->dbmaps = $dbmaps;
		$this->lang = $lang == 'cn'?'zh':$lang;
		$this->division_id = $division_id;
	}
	public function run() {
		//$this->worker->logger('Thread - news', "%s executing in Thread #%lu", __CLASS__, $this->worker->getThreadId() );
		try {
			$db_import = $this->worker->getInstance ( 'import' );
			$db_export = $this->worker->getInstance ( 'export' );
			//$db_export->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			//$db_import->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			$position = 1;
			foreach ( $this->dbmaps as $division_category_id => $type ) {
				$division_id = $this->division_id;
				$category_id = $division_category_id;

				$position = $this->worker->getpoints ( $division_id, $category_id, $type, $position );

				$sql = "SELECT no as id, video, title, description, if(source = '2' , 'youku','JW Player') as player , if(display = 0 , 'Visible' ,'Hidden' ) as visibility,author, if(language='zh','cn',language) as language, smallimage as thumbnail, largeimage as image, updatetime as ctime, updatetime as mtime , mis , sort , equipment , expertsId , kind FROM video WHERE kind='".$type."' AND NO IS  NOT NULL AND video !='' AND display = 0 AND  LANGUAGE = '".$this->lang."' AND no > '" . $position . "' ORDER BY NO asc";
				//AND  (equipment IS NULL OR  equipment!='mobile')
				$query = $db_export->query ( $sql );
				//$this->worker->logger ( 'SQL', $query->queryString );

				while ( $line = $query->fetch ( PDO::FETCH_OBJ ) ) {

					$sql = "insert into video (`division_id`, `category_id`,  `title`,  `description`, `thumbnail`,  `image`,  `video`,  `author`,  `language`,  `player`,  `visibility`, `ctime`, `mtime`) values(:division_id, :category_id, :title, :description, :thumbnail, :image, :video, :author, :language, :player, :visibility, :ctime, :mtime)";
					$sth = $db_import->prepare ( $sql );

					$sth->bindValue ( ':division_id', $this->division_id );
					$sth->bindValue ( ':category_id', $division_category_id );
					$sth->bindValue ( ':title', $line->title );
					$sth->bindValue ( ':description', $line->description );
					$sth->bindValue ( ':thumbnail', $line->thumbnail );
					$sth->bindValue ( ':image', $line->image );
					$sth->bindValue ( ':video', $line->video );
					$sth->bindValue ( ':author', $line->author );
					$sth->bindValue ( ':language', $line->language );
					$sth->bindValue ( ':player', $line->player );
					$sth->bindValue ( ':visibility', 'Visible' );
					$sth->bindValue ( ':ctime', $line->ctime );
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
		
		$dbhost = $this->config['import']['dbhost'];
		$dbport = $this->config['import']['dbport'];
		$dbuser = $this->config['import']['dbuser'];
		$dbpass = $this->config['import']['dbpass'];
		$dbname = $this->config['import']['dbname'];

		$dbh = new PDO ( "mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass, array (
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
			PDO::MYSQL_ATTR_COMPRESS => true
			/*PDO::ATTR_PERSISTENT => true*/
		) );		
		
		$sql = "select * from `synchronous` where division_id=:division_id";
		$sth = $dbh->prepare ( $sql );
		$sth->bindValue ( ':division_id', $this->division_id );
		$sth->execute ();
		
		$syncs = $sth->fetchAll ( PDO::FETCH_OBJ );
		
		$pool = new Pool ( self::MAXCONN , \SynchronousWorker::class, array($this->config) );
		
		foreach($syncs as $sync){

			if($sync->table == 'real_news'){
				if(in_array($sync->lang, array('cn','tw'))){
					$pool->submit ( new RealNewsWork ( $this->division_id, $sync->lang , array( $sync->category_id => $sync->type)));
				}
			}

			if($sync->table == 'news'){
				if(in_array($sync->lang, array('cn','tw'))){
					$pool->submit ( new NewsWork ( $this->division_id, $sync->lang , array( $sync->category_id => "$sync->type")));
				}
			}
			
			if($sync->table == 'video'){
				if(in_array($sync->lang, array('cn','tw'))){
					$pool->submit ( new VideoWork ( $this->division_id, $sync->lang , array( $sync->category_id => "$sync->type")));
				}
			}
			
		}

		$pool->shutdown ();	
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
			$task = new Task (3, ($this->config));
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

