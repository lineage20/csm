<?php

class Contact extends \Phalcon\Mvc\Model
{
    public function initialize(){
        Contact::skipAttributes(array('ctime'));
        Contact::skipAttributes(array('mtime'));
        $this->hasMany("id", "Queue", "contact_id");
        $this->hasMany("id", "GroupHasContact", "contact_id");
    }
    public function findGroupByMobileOrEmail($dbkey, $data,$action){
        $group = array();
        if($action == 'upload'){
            $contact = Contact::findFirst(
                "AES_DECRYPT(mobile,'{$dbkey}') = '{$data[1]}' or AES_DECRYPT(email,'{$dbkey}') = '{$data[2]}' or mobile_digest = md5('{$data[1]}') or email_digest = md5('{$data[2]}')"
            );
        }
        if($action == 'add' ||  $action == 'edit'){
            $contact = Contact::findFirst(
                "AES_DECRYPT(mobile,'{$dbkey}') = '{$data['mobile']}' or AES_DECRYPT(email,'{$dbkey}') = '{$data['email']}' or mobile_digest = md5('{$data['mobile']}') or email_digest = md5('{$data['email']}')"
            );
        }
        if($contact){
            $grouphascontacts = $contact->GroupHasContact;
        }
        if(isset($grouphascontacts)){
           foreach($grouphascontacts as $grouphascontact){
                $group[] = $grouphascontact->group_id;
            }
        }

        return $group;
    }
    public function getList($modelsManager , $where , $appendix = null ,$dbkey){
            $limit = isset($appendix['pageSize'])  ? $appendix['pageSize'] : 25;
            $page = isset($appendix['page']) ? $appendix['page'] : 1;
            $offset 	 = $limit * ($page-1);
            $strWhere = null;
            if($where){
                    foreach($where as $k=>$v){
                        if($k == 'email' || $k == 'mobile'){
                            $strWhere[]  =  "{$k} = AES_ENCRYPT('{$v}','{$dbkey}')";
                        }else{
                            $strWhere[]  =  "{$k} = '{$v}'";
                        }
                    }
                    $strWhere = implode(' AND ', $strWhere);
            }
             $data['builder'] = $modelsManager->createBuilder()
                   ->columns('Contact.id as id,Contact.name as name,Contact.mobile_digest as mobile_digest,Contact.email_digest as email_digest,Contact.description as description,Contact.status as status,Contact.ctime as ctime,Contact.mtime as mtime')
                   ->from('Contact')
                   ->leftjoin('GroupHasContact')
                   ->where($strWhere)
                   ->limit($limit, $offset)
                   ->getQuery()
                   ->execute();
            return $data;
    }
//    public function paginator($modelsManager,$strWhere,$limit, $page = 1){
//    	$limit 		= intval($limit);
//    	$page 	= intval($page);
//        $count = $modelsManager->createBuilder()
//                   ->columns('count(*) as count')
//                   ->from('Contact')
//                   ->leftjoin('GroupHasContact')
//                   ->where($strWhere)
//                   ->getQuery()
//                   ->execute();
//        foreach($count as $counts){
//            $total 	= ceil($counts->count / $limit)-1;
//            $all = $counts->count;
//        }
//
//    	$before = $page<=$total && $page > 1?$page-1:0;
//    	$next 	= $page>=$total?$total:$page+1;
//    	$paginator = array(
//                        'all'           => $all,
//    			'count' 	=> $count,
//    			'first' 	=> 0,
//    			'last' 		=> $total,
//    			'before' 	=> $before,
//    			'current' 	=> $page,
//    			'next' 		=> $next,
//    			'total' 	=> $total,
//                        'pageSize'      => $limit
//    	);
//    	return ($paginator);
//    }

 public function paginator($group_id,$limit, $page = 1){
    	$limit 		= intval($limit);
    	$page 	= intval($page);
        if($group_id != ''){
           $count = GroupHasContact::count(array(
    			"group_id = :group_id:",
    			'bind' => array(
                                'group_id' => $group_id,
                            )
    			));
        }else{
            $count = GroupHasContact::count();
        }

        $total 	= ceil($count / $limit);
    	$before = $page<=$total && $page > 1?$page-1:1;
    	$next 	= $page>=$total?($total==0 || $total==-1?1:$total):$page+1;
    	$paginator = array(
    			'count' 	=> $count,
    			'first' 	=> 0,
    			'last' 		=> $total==0 || $total==-1?1:$total,
    			'before' 	=> $before,
    			'current' 	=> $page,
    			'next' 		=> $next,
    			'total' 	=> $total==0 || $total==-1?1:$total,
                        'pageSize'      => $limit
    	);
    	return ($paginator);
    }

}

