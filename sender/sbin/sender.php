<?php

class Logger {

	public function __construct(/*Logging $logger*/) {
	}

	public function logger($type, $message) {
		$log = sprintf ( "%s\t%s\t%s\n", date ( 'Y-m-d H:i:s' ), $type, $message );
		file_put_contents ( sprintf(__DIR__."/../log/sender.%s.log", date ( 'Y-m-d' )), $log, FILE_APPEND );
	}

}

class SenderWorker extends Worker {

	protected $config;
	protected static $dbh;
	protected static $amqp;

	public function __construct($config) {
		$this->config = $config;
		$this->logger = new Logger();
	}
	public function run() {

	}
	private function connect(){
		try {
			$dbhost = $this->config['database']['host'];
			$dbport = $this->config['database']['port'];
			$dbuser = $this->config['database']['user'];
			$dbpass = $this->config['database']['password'];
			$dbname = $this->config['database']['dbname'];

			self::$dbh = new PDO ( "mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass, array (
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
					PDO::MYSQL_ATTR_COMPRESS => true
					/*PDO::ATTR_PERSISTENT => true*/
			) );
			self::$dbh->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

		} catch ( PDOException $e ) {
			$this->logger ( 'Exception worker', $e->getMessage( ) );
		} catch ( Exception $e ) {
			$this->logger ( 'Exception worker', $e->getMessage( ) );
		}
	}
	public function getInstance() {

		if(!self::$dbh) {
			$this->connect();
			$this->logger ( 'Database', sprintf("Connect database %s, %s", $this->config['database']['dbname'], $this->getThreadId ()) );
		}else{
			// $this->logger ( 'Database', sprintf("Get instance database %s, %s", $this->config['database']['dbname'], $this->getThreadId ()) );
		}

		if(self::$dbh){
			return self::$dbh;
		}else{
			$this->logger ( 'Database', sprintf("Connect database is error %s, %s", $this->config['database']['dbname'], $this->getThreadId ()) );
			$this->logger ( 'Error', sprintf("Worker is shutdown %s", $this->getThreadId ()) );
			$this->shutdown();
		}
	}

	public function logger($type, $message) {
		$this->logger->logger($type, $message);
	}

	public function getAmqpInstance(){
		if(!self::$amqp){
			self::$amqp = new AMQPConnection(array(
				'host' 	=> $this->config['amqp']['host'],
				'port' 	=> $this->config['amqp']['port'],
				'vhost'	=> $this->config['amqp']['vhost'],
				'login' => $this->config['amqp']['login'],
				'password' => $this->config['amqp']['password']
			));
			$this->logger ( 'AMQP', sprintf("Connect amqp %s, %s", $this->config['amqp']['host'], $this->getThreadId ()) );
		}else{
			// $this->logger ( 'AMQP', sprintf("Get instance amqp %s, %s", $this->config['amqp']['host'], $this->getThreadId ()) );
		}
		return self::$amqp;
	}

    public function getContact($task_id, $field = 'email'){
            $dbh = $this->getInstance ();
            $sth = $dbh->prepare ( "select contact_id from queue where task_id = :task_id and status = :status limit 100" );
            $sth->bindValue ( ':task_id', $task_id );
            $sth->bindValue ( ':status', 'New' );
            $status = $sth->execute ();
            if($status){

                    $queues = $sth->fetchAll( PDO::FETCH_OBJ );
                    if(!empty($queues)){
                            foreach($queues as $queue){
                                    $contact[] = $queue->contact_id;
                            }
                            if($field == 'email'){
                                $sql = "select id, name, AES_DECRYPT(email, :key) as email from contact where status = 'Subscription' and id in (". implode(',', $contact) .")";
                            }else{
                                $sql = "select id, name, AES_DECRYPT(mobile, :key) as mobile from contact where status = 'Subscription' and id in (". implode(',', $contact) .")";
                            }
                            $sth = $dbh->prepare ($sql);
                            $sth->bindValue ( ':key', $this->config['database']['key']
);
                            $status = $sth->execute ();
                            //echo $sth->queryString;
                            return $sth->fetchAll( PDO::FETCH_OBJ );

                    }
            }

            return array();

    }
    public function publish($message) {
        $queueName 	= $this->config['amqp']['queue'];
        $exchangeName = $this->config['amqp']['exchange'];
        $routeKey 	= $this->config['amqp']['route'];

        $connection = $this->getAmqpInstance();

        $connection->connect() or die("Cannot connect to the broker!\n");

        $channel = new AMQPChannel($connection);
        $exchange = new AMQPExchange($channel);
        $exchange->setName($exchangeName);
        $queue = new AMQPQueue($channel);
        $queue->setName($queueName);
        $queue->setFlags(AMQP_DURABLE);
        $queue->declareQueue();
        $exchange->publish($message, $routeKey);
        #var_dump("[x] Sent $message");
        $connection->disconnect();
    }

    public function setQueueStatus($task_id, $contact_id, $status){
        $dbh = $this->getInstance ();
        if($status == 'Processing'){
            $sql = "update queue set status = :status where status = 'New' and task_id = :task_id and contact_id = :contact_id";
        }
        else{
            $sql = "update queue set status = :status where status = 'Processing' and task_id = :task_id and contact_id = :contact_id";
        }

        $sth = $dbh->prepare ( $sql );
        $sth->bindValue ( ':task_id', $task_id );
        $sth->bindValue ( ':contact_id', $contact_id );
        $sth->bindValue ( ':status', $status);
        $status = $sth->execute ();
        return $status;

    }

}

class QueueWork extends Threaded {

	private $status = false;
	private $task = null;

	public function __construct($task) {
		$this->task = $task;
	}
	public function run() {
		//$this->worker->logger('real_news', sprintf("%s executing in Thread #%lu", __CLASS__, $this->worker->getThreadId()));

		$dbh = $this->worker->getInstance();

		$dbh->beginTransaction();
		try {

			if($this->task->type == "Email"){
				$groupby = "email_digest";
			}else if($this->task->type == "SMS"){
				$groupby = "mobile_digest";
			}	
			if(empty($this->task->group_id)){
				$sql = "insert ignore into queue(task_id, contact_id) select ".$this->task->id.", id from contact where contact.status = 'Subscription' group by $groupby";
			}else{
				$sql = "insert ignore into queue(task_id, contact_id) select ".$this->task->id.", contact.id from contact, group_has_contact where group_has_contact.contact_id = contact.id and group_has_contact.group_id = :group_id and contact.status = 'Subscription' group by contact.$groupby";
			}

			$sth = $dbh->prepare ( $sql );
			if(!empty($this->task->group_id)){
				$sth->bindValue ( ':group_id', $this->task->group_id );
			}
			//print_r($sth);
			$status = $sth->execute();
			//echo $sth->queryString;
			if($status){

				//$this->worker->logger ( sprintf("Queue %s", $this->task->name) , "last Insert Id ".$dbh->lastInsertId() );
				$this->worker->logger ( 'Queue' , sprintf("Task %s -> Queue", $this->task->name) );

				$sql = "update task set total = (select count(*) from queue where task_id = :task_id), status = :status where status = 'New' and id = :id";
				$sth = $dbh->prepare ( $sql );
				$sth->bindValue ( ':task_id', $this->task->id );
				$sth->bindValue ( ':id', $this->task->id );
				$sth->bindValue ( ':status', 'Processing' );
				$status = $sth->execute ();
				if($status){

					$this->status = true;

					$sql = "update message set status = :status where status = 'New' and id = :id";
					$sth = $dbh->prepare ( $sql );
					$sth->bindValue ( ':id', $this->task->message_id );
					$sth->bindValue ( ':status', 'Sent' );
					$status = $sth->execute ();

					$this->worker->logger ( 'Message', sprintf("%s is locked.", $this->task->message_id));

				}

			}

			//$this->worker->logger ( 'SQL', $query->queryString );

			$dbh->commit();

		} catch ( PDOException $e ) {
			$this->worker->logger ( 'Exception queue', $e->getMessage( ) );
			$dbh->rollBack();
		} catch ( Exception $e ) {
			$this->worker->logger ( 'Exception queue', $e->getMessage( ) );
			$dbh->rollBack();
		}

	}
	private function getStatus(){
		return $this->status;
	}
}

class EmailWork extends Threaded {

	private $task = null;

	public function __construct($task, $message) {
		$this->task = $task;
		//$this->contact = $contact;
		$this->message = $message;


	}
	public function run() {
		//$this->worker->logger('Thread - news', "%s executing in Thread #%lu",__CLASS__, $this->worker->getThreadId() );
		$this->worker->getInstance ();
		//$dbh->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
		try {
			$contacts = $this->worker->getContact($this->task->id,'email');
			foreach($contacts as $contact){
                                $status = $this->worker->setQueueStatus($this->task->id,$contact->id,"Processing");
				if($status){
					$message = str_replace("{{name}}", $contact->name,$this->message->content);
					$title = str_replace("{{name}}", $contact->name,$this->message->title);
					$this->worker->logger ( 'Queue Email', sprintf ( "Processing %s %s<%s>, %s", $this->task->name, $contact->name, $contact->email, $title ) );

					$this->send($contact->email, $title, $message);
                                        $this->worker->setQueueStatus($this->task->id,$contact->id,"Completed");
					$this->worker->logger ( 'Queue Email', sprintf ( "Completed %s %s", $this->task->name, $contact->email ) );
				}
			}
		} catch ( Exception $e ) {
			$this->worker->logger ( 'Exception Email ', $e->getMessage( ) );
		}
	}

	public function send($to, $subject, $msg) {

		$queueName 	= $this->worker->config['amqp']['queue'];
		$exchangeName = $this->worker->config['amqp']['exchange'];
		$routeKey 	= $this->worker->config['amqp']['route'];

		$connection = $this->worker->getAmqpInstance();

		$connection->connect() or die("Cannot connect to the broker!\n");

		$channel = new AMQPChannel($connection);
		$exchange = new AMQPExchange($channel);
		$exchange->setName($exchangeName);
		$queue = new AMQPQueue($channel);
		$queue->setName($queueName);
		$queue->setFlags(AMQP_DURABLE);
		$queue->declareQueue();

		$message = json_encode(array(
			'Namespace'=>'sender',
			"Class"=>"Email",
			"Method"=>"group",
			"Param" => array(
				$to, $subject, $msg, null
			)
		));

		$exchange->publish($message, $routeKey);
		#var_dump("[x] Sent $message");
		$connection->disconnect();
	}
}

class SmsWork extends Threaded {
    private $task = null;

    public function __construct($task, $message) {
            $this->task = $task;
            //$this->contact = $contact;
            $this->message = $message;
    }
    public function run() {
        try {
                $contacts = $this->worker->getContact($this->task->id,"sms");
                foreach($contacts as $contact){
                        $status = $this->worker->setQueueStatus($this->task->id,$contact->id,"Processing");
                        if($status){
                                $msg = str_replace("{{name}}", $contact->name,$this->message->content);
                                $title = str_replace("{{name}}", $contact->name,$this->message->title);
                                $this->worker->logger ( 'Queue SMS', sprintf ( "Processing %s %s<%s>, %s", $this->task->name, $contact->name, $contact->mobile, $title ) );
                                $message = json_encode(array(
                                    'Namespace'=>'sender',
                                    "Class"=>"Sms",
                                    "Method"=>"send",
                                    "Param" => array($contact->mobile, $msg, null)
                                ));
                                $this->worker->publish($message);
                                $this->worker->setQueueStatus($this->task->id,$contact->id,"Completed");
                                $this->worker->logger ( 'Queue SMS', sprintf ( "Completed %s %s", $this->task->name, $contact->mobile ) );
                        }
                }
        } catch ( Exception $e ) {
                $this->worker->logger ( 'Exception SMS', $e->getMessage( ) );
        }
    }

}

class Task extends Logger{

	const MAXCONN 	= 32;

	protected $dbh = array();

	public function __construct($config) {

		$this->config = $config;

		$dbhost = $this->config['database']['host'];
		$dbport = $this->config['database']['port'];
		$dbuser = $this->config['database']['user'];
		$dbpass = $this->config['database']['password'];
		$dbname = $this->config['database']['dbname'];

		$this->dbh = new PDO ( "mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass, array (
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
			PDO::MYSQL_ATTR_COMPRESS => true
			/*PDO::ATTR_PERSISTENT => true*/
		));

		$this->dbh->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

	}

	private function newTask(){

		$sql = "select * from task where status = :status";
		$sth = $this->dbh->prepare ( $sql );
		$sth->bindValue ( ':status', 'New' );
		$sth->execute ();

		$tasks = $sth->fetchAll ( PDO::FETCH_OBJ );

		$pool = new Pool ( self::MAXCONN , \SenderWorker::class, array($this->config) );

		foreach($tasks as $task){

			$pool->submit ( new QueueWork ( $task ));
			$this->logger ( 'Task', sprintf("%s is starting.", $task->name) );

		}

		$pool->shutdown();

	}
	private function processingTask(){

		$pool = new Pool ( self::MAXCONN , \SenderWorker::class, array($this->config) );

		$sql = "select * from task where status = :status";
		$sth = $this->dbh->prepare ( $sql );
		$sth->bindValue ( ':status', 'Processing' );
		$sth->execute ();

		$tasks = $sth->fetchAll ( PDO::FETCH_OBJ );

		$pool = new Pool ( self::MAXCONN , \SenderWorker::class, array($this->config) );

		foreach($tasks as $task){

			$templateStatement = $this->dbh->prepare ( "select * from template where status = :status and id = :id and `type` = :type" );
			$templateStatement->bindValue ( ':id', $task->template_id );
			$templateStatement->bindValue ( ':type', $task->type );
			$templateStatement->bindValue ( ':status', 'Enabled' );
			$templateStatement->execute ();
			$template = $templateStatement->fetch( PDO::FETCH_OBJ );

			//print_r($templateStatement);
			$messageStatement = $this->dbh->prepare ( "select * from message where status = :status and id = :id" );
			$messageStatement->bindValue ( ':id', $task->message_id );
			$messageStatement->bindValue ( ':status', 'Sent' );
			$messageStatement->execute ();

			$message = $messageStatement->fetch( PDO::FETCH_OBJ );
			//print_r($task);
			//print_r($template);
			if(empty($template)){
				$this->logger ( 'Template', sprintf("%s isn't found.", $task->template_id) );
				continue;
			}else if(empty($message)){
				$this->logger ( 'Message', sprintf("%s isn't found.", $task->message_id) );
				continue;
			}else{
				$keyword = array("{{title}}","{{content}}","{{date}}");
				$value = array($message->title, $message->content, $message->ctime);
				$message->content = str_replace($keyword, $value, $template->content);

				if($task->type == 'Email'){
					$pool->submit ( new EmailWork ( $task, $message ));
					$this->logger ( 'Email', sprintf("Queue %s is starting.", $task->name) );
				}
				if($task->type == 'SMS'){
					$pool->submit ( new SMSWork ( $task, $message ));
					$this->logger ( 'SMS', sprintf("Queue %s is starting.", $task->name) );
				}
			}
		}

		$pool->shutdown ();

	}
	private function completedTask(){
		$sql = "select * from task where status = :status";
		$sth = $this->dbh->prepare ( $sql );
		$sth->bindValue ( ':status', 'Processing' );
		$sth->execute ();

		$tasks = $sth->fetchAll ( PDO::FETCH_OBJ );

		foreach($tasks as $task){

			/*
			$queue = $this->dbh->prepare ( "select count(*) as count from queue where task_id = :task_id" );
			$queue->bindValue ( ':task_id', $task->id );
			$queue->execute ();
			$queueCount = $queue->fetch( PDO::FETCH_OBJ );
			*/

			$queue = $this->dbh->prepare ( "select count(*) as count from queue where task_id = :task_id and status = :status" );
			$queue->bindValue ( ':task_id', $task->id );
			$queue->bindValue ( ':status', 'Completed' );
			$queue->execute ();

			$queueCompleted = $queue->fetch( PDO::FETCH_OBJ );

			//print_r($queueCount);
			//print_r($queueCompleted);

			if($task->total == $queueCompleted->count){
				$sql = "update task set status = :status where status = 'Processing' and id = :id";
				$sth = $this->dbh->prepare ( $sql );
				$sth->bindValue ( ':id', $task->id );
				$sth->bindValue ( ':status', 'Completed' );
				$status = $sth->execute ();
				if($status){
					$this->logger ( 'Task', sprintf("%s is Completed.", $task->name) );
				}
			}
		}
	}
	public function main(){

		$this->newTask();
		$this->processingTask();
		$this->completedTask();

	}

}

class Sender {
	/* config */
	const LISTEN = "tcp://192.168.2.15:5555";
	const pidfile 	= __CLASS__;
	const uid		= 80;
	const gid		= 80;
	const sleep	= 5;

	protected $pool 	= NULL;
	protected $config	= array();

	public function __construct() {
		$this->pidfile = '/var/run/'.basename(__FILE__, '.php').'.pid';
		$this->config = parse_ini_file('sender.ini', true); //include_once(__DIR__."/config.php");

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
	private function run(){

			$task = new Task ($this->config);
			$task->main();

	}
	private function foreground(){
		$this->run();
	}
	private function start(){
		$pid = $this->daemon();
		for(;;){
			$this->run();
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
		printf("%s start | stop | restart | status | foreground | help \n", $proc);
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
		}else if($argv[1] === 'foreground'){
			$this->foreground();
		}else{
			$this->help($argv[0]);
		}
	}
}

$sender = new Sender();
$sender->main($argv);
?>


