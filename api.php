<?php
// require_once '../model/auth.php';
// $conn = (new Auth())->conn;
include_once 'common_functions.php';
// printr($_POST);die;
if(!isset($_POST['api'])){
    return print_r("Api name is not defined.");
}
//--------------API NAME => CLASS , METHOD ------------------
$apis = [
            'auth_user'=>'auth_user',
            'update_password'=>'update_password',
            'update_user_manula_links'=>'update_user_manula_links',
            'get_items'=>'get_items',
            'save_inspect'=>'save_inspect',
            'get_inspect'=>'get_inspect',
            'get_user_inspect'=>'get_user_inspect',
            'get_order_dets'=>'get_order_dets',
            'get_sub_cat'=>'get_sub_cat',
            'send_email'=>'send_email',
            'assign_users'=>'assign_users',
            'get_users'=>'get_users',
            'get_users_access'=>'get_users_access',
            'save_users_access'=>'save_users_access',
            'save_users_creds'=>'save_users_creds',
            'create_new_user'=>'create_new_user',
            'submitted_get_users'=>'submitted_get_users',
            'save_tab'=>'save_tab',
            'upload_file'=>'upload_file',
            'create_new_nursery'=>'create_new_nursery',
            'get_nursery'=>'get_nursery',
            'create_new_cluster'=>'create_new_cluster',
            'get_cluster'=>'get_cluster',

        ];
//----------------------INVALID API ENDPINT CHECK---------------
if(!in_array($_POST['api'],$apis)){return print_r("Api name is not defined.");}
//----------------------
// session_start();
// if(!isset($_SESSION['login'])){
//     return print_r("Not logged in.");
// }
// require_once '../model/auth.php';
// $conn = (new Auth())->conn;
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
require_once 'common_functions.php';
//--------------API NAME => METHOD ------------------
return $apis[$_POST['api']]($_POST);

//---------------------------------------- GET ALL ITEMS ----------------
function get_inspect($post){
  // print_r($post);die;
  $filter = count(json_decode($post['filter'],true)) ? json_decode($post['filter'],true) : [];
  $filter['status'] = ['1','2'];
  // $fields = ['id','title','added_on','status','due_date','submit_after_due_date'];
  $fields = ["*"];
  if(isset($filter['id'])){
    $inspects = get_where_in_fk('inspection',$fields,$filter);
    $inspects[0]["users"] = get_query_result("SELECT DISTINCT `user_id` FROM `inspection_assign` WHERE `inspection_id` = '".$filter['id']."' AND  `status` = '1' ");
    // $tst = get_query_result("SELECT DISTINCT `user_id` FROM `inspection_assign` WHERE `inspection_id` = '".$filter['id']."' AND  `status` = '1' ");
    // print_r($tst);die;
    // $inspects[0]["users"] = get_where_in_fk('inspection_assign',["user_id"],["inspection_id"=>$filter['id'],"status"=>"1"]);
  }else{
    $inspects = get_where_in_fk('all_inspects',$fields,$filter);
  }
  close_DB_conn();
  echo json_encode($inspects);die;
}

function upload_file($post){
  $data = $post;
  // unset($data["api"]);
  // $static_dir =  (json_decode(file_get_contents(".under6"),true))["STATIC_DIR"];
  $doc_dir =  "uploads/".$post["user_id"];

  if(isset($post["user_id"])){
    $file_path = "Error";
    foreach ($_FILES as $k => $v) {
      // print_r($k."<br>");
      $file_path = save_doc($v,$doc_dir);
      // $data[$k] = str_replace($static_dir,"",$file_path);
    }
    print_r($file_path);
  }
  // foreach ($data as $k => $v) {
  //   if($k == "aadhar"  || $k ==  "CV" || $k == "image"){
  //     $k1 = strtolower("upload_".$k);
  //     $data[$k1] = $v;
  //     unset($data[$k]);
  //   }
  // }
  // $new_sdt_user_id = save_table('teachers_profile',$data,".under6");
  // if(!isset($post["id"])){
  //   foreach ($_FILES as $k => $v) {
  //       $file_path = save_doc($v,$static_dir."uploads/profile/".$new_sdt_user_id."/".$k);
  //       // print_r($file_path."<br>");
  //       if($k == "aadhar"  || $k ==  "CV" || $k == "image"){
  //         $data[strtolower("upload_".$k)] = str_replace($static_dir,"",$file_path);
  //       }else{
  //         $data[strtolower($k)] = str_replace($static_dir,"",$file_path);
  //       }
  //       $data["id"] = $new_sdt_user_id;
  //       save_table('teachers_profile',$data,".under6");
  //   }
  // }
  // save_doc($_FILES["file"]);
}



function get_users_access($post){

  $filter = count(json_decode($post['filter'],true)) ? json_decode($post['filter'],true) : [];
  $filter['status'] = ['1'];
  $fields = ["*"];
  // print_r($filter);die;
  $user_access = get_where_in_fk('user_access',$fields,$filter);

  close_DB_conn();

  echo json_encode($user_access);die;

}

function update_password($post){

  $data = count(json_decode($post['data'],true)) ? json_decode($post['data'],true) : [];
  // print_r($data);die;
  if(count($data)){
    $cols = ["id","name","email","password"];
    $data["password"] = md5($data["password"]);
    $values[] = $data;
    $resp = save_batch('users',$cols,$values);
  }

  echo json_encode($resp);die;
}

function create_new_user($post){

  $data = count(json_decode($post['data'],true)) ? json_decode($post['data'],true) : [];
  $cols = count(json_decode($post['cols'],true)) ? json_decode($post['cols'],true) : [];

  // print_r($data);die;
  if(count($data)){
    // $cols = ["id","name","email","is_admin","team","password","manula_links","status"];
    isset($data["password"]) ? $data["password"] = md5($data["password"]) : FALSE ;
    $values[] = $data;
    $resp = save_batch('users',$cols,$values);
  }

  echo json_encode($resp);die;
}

function create_new_nursery($post){

  $data = count(json_decode($post['data'],true)) ? json_decode($post['data'],true) : [];
  $cols = count(json_decode($post['cols'],true)) ? json_decode($post['cols'],true) : [];

  // print_r($data);die;
  if(count($data)){
    // $cols = ["id","name","email","is_admin","team","password","manula_links","status"];
    // isset($data["password"]) ? $data["password"] = md5($data["password"]) : FALSE ;
    $values[] = $data;
    $resp = save_batch('nursery',$cols,$values);
  }

  echo json_encode($resp);die;
}

function create_new_cluster($post){

  $data = count(json_decode($post['data'],true)) ? json_decode($post['data'],true) : [];
  $cols = count(json_decode($post['cols'],true)) ? json_decode($post['cols'],true) : [];

  // print_r($data);die;
  if(count($data)){
    // $cols = ["id","name","email","is_admin","team","password","manula_links","status"];
    // isset($data["password"]) ? $data["password"] = md5($data["password"]) : FALSE ;
    $values[] = $data;
    $resp = save_batch('cluster',$cols,$values);
  }

  echo json_encode($resp);die;
}

function update_user_manula_links($post){

  $data = count(json_decode($post['data'],true)) ? json_decode($post['data'],true) : [];
  $data["manula_links"] = json_encode($data["manula_links"]);
  // print_r($data);die;
  if(count($data)){
    // $cols = ["id","manula_links"];
    // $values[] = $data;
    $where = ["id"=>$data["id"]];
    unset($data['id']);
    $resp = update_fields('users',$data,$where);
  }
  echo json_encode($resp);die;
}


function save_users_access($post){

  $data = count(json_decode($post['user_access'],true)) ? json_decode($post['user_access'],true) : [];
  // foreach ($post["users"] as $k => $v) {
  //   $data[] = ["inspection_id"=> $post["form_id"],"email"=>$v ];
  // }
  $cols = ["user_id","access_name","link","status","username","password"];

  $resp = save_batch('user_access',$cols,$data);

  echo json_encode($resp);die;
}


function save_users_creds($post){

  $data = count(json_decode($post['user_access'],true)) ? json_decode($post['user_access'],true) : [];
  // foreach ($post["users"] as $k => $v) {
  //   $data[] = ["inspection_id"=> $post["form_id"],"email"=>$v ];
  // }
  $cols = ["user_id","access_name","link","username","password"];

  $resp = save_batch('user_access',$cols,$data);

  echo json_encode($resp);die;

}

function get_users($post){
  $filter = [];
  if(isset($post['filter'])){
    $filter = count(json_decode($post['filter'],true)) ? json_decode($post['filter'],true) : [];
  }
  // $filter['status'] = ['1','2'];
  $fields = ['id','name','updated_on','email','is_admin','team','manula_links','status','level','country'];
  if(isset($filter['id'])){
    $fields = ["*"];
  }
  // print_r($filter);die;
  (isset($filter['password'])) ? $filter['password'] = md5($filter['password']) : false;
  // print_r($filter);die;
  $inspects = get_where_in_fk('users',$fields,$filter);
  close_DB_conn();
  echo json_encode($inspects);die;
}

function get_nursery($post){
  $filter = [];
  if(isset($post['filter'])){
    $filter = count(json_decode($post['filter'],true)) ? json_decode($post['filter'],true) : [];
  }
  // $filter['status'] = ['1','2'];
  $fields = ['id','name','cluster_id','state','city','pincode','lat','long','status'];
  if(isset($filter['id'])){
    $fields = ["*"];
  }
  // print_r($filter);die;
  // (isset($filter['password'])) ? $filter['password'] = md5($filter['password']) : false;
  // print_r($filter);die;
  $inspects = get_where_in_fk('all_nursery',$fields,$filter);
  close_DB_conn();
  echo json_encode($inspects);die;
}

function get_cluster($post){
  $filter = [];
  if(isset($post['filter'])){
    $filter = count(json_decode($post['filter'],true)) ? json_decode($post['filter'],true) : [];
  }
  // $filter['status'] = ['1','2'];
  $fields = ['id','name','country','state','city','status'];
  if(isset($filter['id'])){
    $fields = ["*"];
  }
  // print_r($filter);die;
  // (isset($filter['password'])) ? $filter['password'] = md5($filter['password']) : false;
  // print_r($filter);die;
  $inspects = get_where_in_fk('cluster',$fields,$filter);
  close_DB_conn();
  echo json_encode($inspects);die;
}



//---------------------------------------- GET AUTH USER ----------------

function get_user_inspect($post){

  $filter = count(json_decode($post['filter'],true)) ? json_decode($post['filter'],true) : [];
  // $filter['status'] = ['1','2'];
   
  $filtids = [];
  $filtids['id'] = array_column(get_where_in_fk('inspection_assign',["inspection_id"],$filter),"inspection_id");

  // print_r($inspects);die;

  // if(isset($filter['id'])){
  //   $fields = ["*"];
  // }
  $fields = ['id','title','added_on','status'];
  $inspects = get_where_in_fk('inspection',$fields,$filtids);
  close_DB_conn();
  echo json_encode($inspects);die;

}
//---------------------------------------- GET AUTH USER ----------------

function save_inspect($post){
  // $filter = count(json_decode($post['filter'],true)) ? json_decode($post['filter'],true) : [];
  $data["content"] = $post["content"];  $data["title"] = $post["title"];$data["due_date"] = $post["due_date"];$data["schedule"] = $post["schedule"];$data["team"] = $post["team"];
  $data["submit_after_due_date"] = $post["submit_after_due_date"];
  $data['updated_on'] =  date('Y-m-d H:i:s');
  isset($post["id"]) ? $data["id"] = $post["id"] :$data["added_on"] = date('Y-m-d H:i:s'); 
  $inspec= save_table('inspection',$data);
  close_DB_conn();
  echo json_encode($inspec);die;
}

//---------------------------------------- GET LAST ORDER ----------------
function assign_users($post){
  $now =  date('Y-m-d H:i:s');
  foreach ($post["users"] as $k => $v) {
    $data[] = ["inspection_id"=> $post["form_id"],"user_id"=>$v,"assigned_on"=>$now];
  }
  $cols = ["inspection_id","user_id","assigned_on"];
  $res = save_batch('inspection_assign',$cols,$data);
  close_DB_conn();
  echo json_encode($res);die;
}

//---------------------------------------- SAVE TO TABLE COMMON ----------------
function save_tab($post){
  $cols = count(json_decode($post['cols'],true)) ? json_decode($post['cols'],true) : [];
  $data = count(json_decode($post['data'],true)) ? json_decode($post['data'],true) : [];
  $tbl_name = $post["tbl_name"];
  $res = save_batch($tbl_name,$cols,$data);
  close_DB_conn();
  echo json_encode($res);die;
}


//---------------------------------------- GET LAST ORDER ----------------
function send_email($post){
  require_once __DIR__ . '/email/send_email.php';  //-------  php_mailer() defined here.
  // print_r($post);die;
  $post['message'] = "<h3>Please click following link,</h3><br><a href='".$post['link']."'>Click here</a>";
  $post['type'] = "inquiry";
  $send_type= [
    "inquiry" =>[
      "to"=>"ziauddin.sayyed@kido.school",
      "receiver_name"=>"Mohamed Maseeh",
      "subject"=>"Kido Inspection Request",
      "message"=>$post["message"],
    ]
  ];
  php_mailer($send_type[$post["type"]]);
}
//---------------------------------------- GET ALL ITEMS ----------------
function get_order_dets($post){
  // $order_ids = array_column(get_where_in_fk('order',['id','user_id'],[]),"id");
  // print_r($post['filter']);die;
  $filter = count(json_decode($post['filter'],true)) ? json_decode($post['filter'],true) : [];
  $orders_q = get_where_in_fk('order',['id','user_id.name','user_id.phone','status','updated_on'],$filter);
  $order_ids = $orders = [];
  foreach ($orders_q as $k => $v) {
    $order_ids[] = $v['id'];
    $orders[$v['id']] = $v;
    $orders[$v['id']]['items'] = [];
  }
  // print_r($orders);die;
  unset($orders_q);
  $ord_dets = get_where_in_fk('order_item',['item_id.name','order_id','price','status'],['order_id'=>$order_ids]);
  unset($order_ids);
  foreach ($ord_dets as $k => $v) {
    $oid = $v['order_id']; unset($v['order_id'],$v['item_id']);
    $orders[$oid]['items'][] = $v;
  }
  unset($ord_dets);
  // print_r($orders);die;
  close_DB_conn();
  echo json_encode($orders);die;
}

//---------------------------------------- SAVE ORDER ----------------

function submitted_get_users($post){
  $filter = [];
  if(isset($post['filter'])){
    $filter = count(json_decode($post['filter'],true)) ? json_decode($post['filter'],true) : [];
  }
  $filter['status'] = '1';
  // $fields = ['id','name','updated_on','email','is_admin','team','manula_links'];
  // if(isset($filter['id'])){
  $fields = ["*"];
  // }
  // print_r($filter);die;
  $limit = isset($post['limit']) ? $post['limit'] : 1;
  // (isset($filter['submitted_on'])) ? $filter['submitted_on'] = md5($filter['submitted_on']) : false;
  // print_r($filter);die;
  $inspects = get_where_in_fk('inspection_assign',$fields,$filter,$limit);
  close_DB_conn();
  echo json_encode($inspects);die;
}
