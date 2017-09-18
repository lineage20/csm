<?php

class ElasticSearchWorker extends Worker {

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

	public function logger($type, $message) {
		$log = sprintf ( "%s\t%s\t%s\t%s\n", date ( 'Y-m-d H:i:s' ), $this->getThreadId (), $type, $message );
		file_put_contents ( sprintf(__DIR__."/../log/%s.%s.log", __CLASS__, date ( 'Y-m-d' )), $log, FILE_APPEND );
	}

}

class ElasticSearchWork extends Stackable {
	public $article;
	public function __construct($article) {
		$this->article = $article;
	}
	public function run() {
		
		try {

			include_once(dirname(__DIR__).'/Library/vendor/autoload.php');
			$hosts = array('so.cf88.com');
			$client = \Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();
			$params = array();
			$params['index'] = 'information';
			$params['type'] = 'news';
			$params['id'] = $this->article->id;
			$params['body'] = array(
				'id'						=> $this->article->id,
				'division_category_id'     => $this->article->division_category_id,
				'title'                    => $this->article->title,
				'content'                  => $this->article->content,
				'tag'                      => $this->article->tag,
				'source'					=> $this->article->source,
				'ctime'                    => date("Y-m-d\TH:i:s", strtotime($this->article->ctime))
			);

			$ret = $client->index($params);

			$this->worker->logger ( __CLASS__, sprintf ( "%s", json_encode($ret) ) );

		} catch ( Exception $e ) {
			$this->worker->logger ( __CLASS__, $e->getMessage( ) );
		}
	}
}

class ElasticSearchTask {

	const MAXCONN 	= 32;
	
	protected $division_category_id = 2;

	protected $result = array();

	public function __construct($config) {
		$this->config = $config;
	}
	public function main(){

		$dbhost = $this->config['cf']['dbhost'];
		$dbport = $this->config['cf']['dbport'];
		$dbuser = $this->config['cf']['dbuser'];
		$dbpass = $this->config['cf']['dbpass'];
		$dbname = $this->config['cf']['dbname'];

		$dbh = new PDO ( "mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass, array (
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
			PDO::MYSQL_ATTR_COMPRESS => true,
			PDO::ATTR_PERSISTENT => true
		) );

		$sql = "select  id, division_category_id, title, content, tag, source, ctime from `article` where division_category_id=:division_category_id";
		$sth = $dbh->prepare ( $sql );
		$sth->bindValue ( ':division_category_id', $this->division_category_id );
		$sth->execute ();

		$articles = $sth->fetchAll ( PDO::FETCH_OBJ );

		$pool = new Pool ( self::MAXCONN , \ElasticSearchWorker::class, array($this->config) );

		foreach($articles as $article){

			$pool->submit ( new ElasticSearchWork ( $article ));

		}

		$pool->shutdown ();
	}

}

class ElasticSearch {
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
			exit($pid);
		} else {
			file_put_contents($this->pidfile, getmypid());
			posix_setuid(self::uid);
			posix_setgid(self::gid);
			return(getmypid());
		}
	}
	private function start(){
		$pid = $this->daemon();
		//for(;;){
			$task = new ElasticSearchTask (($this->config));
			$task->main();
			//sleep(self::sleep);
		//}
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

$elasticsearch = new ElasticSearch();
$elasticsearch->main($argv);
?>


