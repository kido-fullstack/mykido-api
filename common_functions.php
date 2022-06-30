<?php
$creds = json_decode(file_get_contents(".creds"),true);
$user = $creds["DB_USER"];$pass = $creds["DB_PASS"];$dbname = $creds["DB_NAME"];
// $user = "smduser";$pass = "@smduser123#";
$GLOBALS['conn'] = new mysqli("localhost", $user,$pass,$dbname);
$GLOBALS['info_sch'] = new mysqli("localhost", $user,$pass, 'information_schema');
// $this->info_sch = new mysqli("localhost", getenv('db_user'), getenv('db_pass'), "information_schema");

 //---------------################################## COMMON FUNCTION #############################--------------
 //---------------------------  GET USER NAMES BY ID ARRAY ---------------
 function get_user_names_by_ids($user_id_arr){
    $conn = $GLOBALS['conn'];
    $vals = '("'.implode('","',$user_id_arr).'")';
    $sql = "SELECT `id`,`name`,`middle_name`,`last_name` FROM `user` WHERE `id` in $vals";
    $result = $conn->query($sql);$out = [];
    while($v = mysqli_fetch_assoc($result)){
        $out[$v['id']]    = $v['name'].' '. $v['middle_name'].' '. $v['last_name'];
    }
    return $out;
}
 //---------------------------  GET SUBJECT NAMES BY ID (kEY VALUE)ARRAY ---------------
 function get_subject_names_by_ids($user_id_arr){
    $conn = $GLOBALS['conn'];
    $vals = '("'.implode('","',$user_id_arr).'")';
    $sql = "SELECT `id`,`subject_name`,`optional_sub_set_id` FROM `institute_class_subject` WHERE `id` in $vals";
    $result = $conn->query($sql);$out = [];
    while($v = mysqli_fetch_assoc($result)){
        $out[$v['id']]    = $v['subject_name'];
    }
    return $out;
}
     //---------------------------  GET SUBJECT TYPE (OPTIONAL/REGULAR)  BY type = 0 its regular ---------------
     function get_subject_type_by_ids($subject_id_arr){
        $conn = $GLOBALS['conn'];
        $vals = '("'.implode('","',$subject_id_arr).'")';
        $sql = "SELECT `id`,`subject_name`,`optional_sub_set_id` FROM `institute_class_subject` WHERE `id` in $vals";
        $result = $conn->query($sql);$out = [];
        while($v = mysqli_fetch_assoc($result)){
            $out[$v['id']]    = $v['optional_sub_set_id'];
        }
        return $out;
    }


 //---------------------------  GET SUBJECT NAMES BY ID WITH SUBJECT TYPE (REGULAR OR OPTIONAL)ARRAY ---------------
 function get_subject_names_by_ids_with_subject_type($user_id_arr){
    $conn = $GLOBALS['conn'];
    $vals = '("'.implode('","',$user_id_arr).'")';
    $sql = "SELECT `id`,`subject_name`,`optional_sub_set_id` FROM `institute_class_subject` WHERE `id` in $vals";
    $result = $conn->query($sql);$out = [];
    while($v = mysqli_fetch_assoc($result)){
        $out[]    = $v;
    }
    return $out;
}
// ---------------------------- GET CLASS NAME BY CLASS ID

function get_class_name_by_id_arr($class_id_arr){
    $institute_id = ($_SESSION['institute_id']);
    $conn = $GLOBALS['conn'];
    $vals = '("'.implode('","',$class_id_arr).'")';
   $sql = "SELECT `id`,`standard`,`section` FROM `institute_class` WHERE `id` in $vals AND  `is_active` = '1'";
   $result = $conn->query($sql);$out = [];
    while($v = mysqli_fetch_assoc($result)){
        $out[$v['id']]  = $v['standard'].'-'. $v['section'];
    }
    return $out;
}

// --------------GET CLASS NAME WITH USER ID
function get_class_id_by_user_id_arr($user_id_arr){
    $institute_id = ($_SESSION['institute_id']);
    $conn = $GLOBALS['conn'];
    $vals = '("'.implode('","',$user_id_arr).'")';
   $sql = "SELECT `id`,`institute_class_id`,`user_id` FROM `institute_class_user` WHERE `user_id` in $vals AND  `is_active` = '1'";
   $result = $conn->query($sql);$out = [];
    while($v = mysqli_fetch_assoc($result)){
       // print_r($v);
        $out[$v['user_id']]  = $v['institute_class_id'];
      //  print_r($out[$v['user_id']]);
    }
    return $out;

}


 //---------------------------  GET USER ROLE BY ID ARRAY ---------------
 function get_user_roll_by_ids($user_id_arr){
    $conn = $GLOBALS['conn'];
    $vals = '("'.implode('","',$user_id_arr).'")';
    $sql = "SELECT `id`,`institute_id`,`roll`,`user_id` FROM `institute_user` WHERE `user_id` in $vals";
    $result = $conn->query($sql);$out = [];
    while($v = mysqli_fetch_assoc($result)){
        $out[$v['user_id']]    = $v['roll'];
    }
    return $out;
}

function save_institute_class($posted){
    $inst_id = $_SESSION['institute_id'];
    $ins_cls = json_decode($posted['inst_class'],true);
    //---------- SAVE INSTITUTE CLASS  FIELD -----------  
    $inst_class_arr = ['institute_id' => $inst_id ,'division' => $ins_cls['division'] ,'standard' => $ins_cls['standard'],'section' => $ins_cls['section'] ,'class_teacher_user_id' => $ins_cls['class_teacher_user_id']];
    $ic_id =  save_table('institute_class',time_addins($inst_class_arr));
    return  $ic_id;
}

function save_ics_icst($posted,$ic_id){
   $ins_cls_sub = json_decode($posted['inst_class_sub'],true);
   // Removing dublicate values (In case of one subject assigned to multiple teachers)
    $rmv_dup_sub = remove_duplicate_values($ins_cls_sub,'subject_name');
    foreach ($rmv_dup_sub as $v) {
        $inst_cls_sub_arr = ['institute_class_id'=>$ic_id,'subject_name'=>$v['subject_name'],'optional_sub_set_id'=>$v['optional_sub_set_id'],'is_active'=>1];
        $new_sub_id =   save_table('institute_class_subject',time_addins($inst_cls_sub_arr));         
    }
    // ------------Query to get the subject id with SUBJECT NAME-------
    $ics_flds = get_where_in_flds('institute_class_subject',['id','subject_name'],['institute_class_id'=>$ic_id,'is_active'=>1]);
    $out = [];
    foreach ($ics_flds as $k => $v) {
        $out[$v['subject_name']] = $v['id'];
    }
    // -------------------- SAVE INSTITUTE CLASS TEACHER FIELDS ----------------------------
    foreach ($ins_cls_sub as $v) {
        $inst_cls_teach_arr = ['subject_id'=> $out[$v['subject_name']],'teacher_user_id'=>$v['teacher_user_id'],'is_active'=>1];
        $new_teach_id= save_table('institute_class_subject_teacher',time_addins($inst_cls_teach_arr));
    } 
    return $new_teach_id;   
}




function save_stud($posted){   // COMMON VALUES
    // print_r($posted);die;
    $now =  date('Y-m-d H:i:s');
    $admin_id =$_SESSION['user']['id'];
    $inst_id = $_SESSION['institute_id'];
    //----------------------- STUDENTS VALUES
    // print_r( $posted);die;
    $data_sdt['basic_sdt'] = json_decode($posted['basic_sdt'],true);
    $data_sdt['extra_sdt'] = json_decode($posted['extra_sdt'],true);
    $roll_no =  $posted['roll_no'];
    $ic_id =  $posted['ic_id'];
    $roll_sdt =  $posted['roll_sdt'];
    $is_active=1;
    //----------------------- CLASS SUBJECT VALUE
    $class_sub['class_sub'] = json_decode($posted['class_sub'],true);  
   
    //----------------------- PARENTS VALUES
    $data =['basic_prt'=>json_decode($posted['basic_prt'],true),'auth_fields'=>json_decode($posted['auth_fields'],true)];
    $data['extra_prt'] = json_decode($posted['extra_prt'],true);
    $data_usr_prt =['user_prt'=>json_decode($posted['user_prt'],true)]; 
    $roll_prt =  $posted['roll_prt'];

   //----------------------- AUTHENTICATION VALUES
    $email = $data['auth_fields']['email'];
    $phone = $data['auth_fields']['phone'];  
    $name  = $data['basic_prt']['name'];
    //----------------------- USER PARENT TABLE VALUES
    $profession =$data_usr_prt['user_prt']['profession'];
    $relation =$data_usr_prt['user_prt']['relation'];
     //----------------------- CHECK FOR ALREADY EXISTING PARENT WITH ITS FIRST NAME N MOBILE NO SAME --------------
        if(!isset($data['basic_prt']['id'])){
            // print_r( $data_sdt['basic_sdt']['name']);die;
            $chk = check_user_exists(['email'=>$email,'phone'=>$phone,'username'=>$name]);
            // print_r( $chk);die;
            if($chk['user_pass'] == FALSE ){
                echo $chk['message'] ;return;
            }
            if($chk['message'] == 'Same User' ){
                $data['basic_prt']['id'] = $chk['user_id'];
                if(!isset($data_sdt['basic_sdt']['id'])){
                    //--------------------------------------------CHECK SAME CHILD OF PARENT -----------------
                    $chld_chk = same_child_chk($data['basic_prt']['id'],$data_sdt['basic_sdt']['name']);
                    // print_r( $chk);return;
                    if($chld_chk['same_child'] == TRUE)
                        {echo '<b>'. $data_sdt['basic_sdt']['name'].'</b> Parent:<b> '.$name.'</b> added previously.<br>' ;return;}
                }
            }
        }
     //------------------------- ADD STUDENT -------------------------
         // if user id is coming it will update the user else it will add new user..
        $new_sdt_user_id = save_table('user',time_addins($data_sdt['basic_sdt']));
                //------SAVE EXTRA FIELDS -----------
                // if user_id , institue_id ,  extra_field_id are unique , if same value it will update
        foreach ($data_sdt['extra_sdt'] as $k => $v) {
            $ext_arr = ['user_id' => $new_sdt_user_id ,'institue_id' => $inst_id ,'extra_field_id' => $k ,'value' => $v];
            save_table('user_extra_field',time_addins($ext_arr));        
        }
            // check if its coming for update no need to update institute_user table
        if(!isset($data_sdt['basic_sdt']['id'])){
        //---------- SAVE INSTITUTE USER FIELDS IF IT NOT EXIST -----------  
        $inst_user_arr = ['institute_id' => $inst_id ,'user_id' => $new_sdt_user_id ,'roll' => $roll_sdt];
        save_table('institute_user',time_addins($inst_user_arr));  
        }
     
        // ----------------------------ADD STUDENT CLASS USER (institute_class_user)----------------------------
           // user_id field is unique, if it exist it will update the table else new entry will be done
       $inst_std_class_user_arr = ['institute_class_id' => $ic_id ,'user_id' => $new_sdt_user_id ,'roll' => $roll_sdt,'roll_no' => $roll_no];      
       $new_std_class_user_id = save_table('institute_class_user',time_addins($inst_std_class_user_arr));
       
        // ---------------------------- ADD STUDENT CLASS SUBJECT ----------------------------
        //  if request is coming with user id we will do update and make all subject inactive
        if(isset($data_sdt['basic_sdt']['id'])){
            get_query_run("UPDATE `institute_class_subject_student` SET `is_active` = 0 WHERE `user_id` = '$new_sdt_user_id' ");
            // $conn->query();
        }
        // 	subject_id, user_id- Unique key , if same it will update else add new entry.
        foreach ($class_sub['class_sub'] as $k => $v) {
            $class_sub__arr = ['subject_id' => $k ,'user_id' => $new_sdt_user_id,'is_active' =>$is_active];
            save_table('institute_class_subject_student',time_addins($class_sub__arr));        
        }
                    //---------- SAVE IMAGE IF UPLOADED -----------

       // (isset($_FILES['pro_pic_file_sdt'])) ? save_image($_FILES['pro_pic_file_sdt'],$new_sdt_user_id) : FALSE;
       if(isset($_FILES['pro_pic_file_sdt'])){
        $data_sdt['basic_sdt']['id'] = $new_sdt_user_id;
        $data_sdt['basic_sdt']['image_url'] =  save_image($_FILES['pro_pic_file_sdt'],$new_sdt_user_id);
        save_table('user',time_addins($data_sdt['basic_sdt']));
    } 

     // ------------------------------------------- ADD PARENTS -------------------------------------------
        //---------------- ADD OR UPDATE PARENT DETAILS --------------
        $prt_user_id = save_table('user',time_addins($data['basic_prt']));
        $auth_arr = ['user_id'=> $prt_user_id,'username'=> $name,'email'=> $email,'phone'=> $phone];
        !isset($data['basic_prt']['id']) ? $auth_arr['password'] = md5('123') : FALSE; //------SET DEFAULT PASSWORD NEW PARENT USER  ---------
        save_table('user_logins',time_addins($auth_arr));
        //---------- SAVE EXTRA FIELDS -----------
        foreach ($data['extra_prt'] as $k => $v) {
            $ext_arr = ['user_id' => $prt_user_id ,'institue_id' => $inst_id ,'extra_field_id' => $k ,'value' => $v ];
            save_table('user_extra_field',time_addins($ext_arr));        
        }
        //---------- UPDATE AUTH FIELDS -----------
        $auth_arr = ['user_id'=> $prt_user_id,'username'=> $name,'email'=> $email,'phone'=> $phone,'password'=> md5('123')];
        save_table('user_logins',time_addins($auth_arr));
       
        // check if its coming for update no need to update institute_user table
       if(!isset($data['basic_sdt']['id'])){
        //---------- SAVE PARENT INSTITUTE ROLL -----------  
            $inst_user_arr = ['institute_id' => $inst_id ,'user_id' => $prt_user_id ,'roll' => $roll_prt];
            save_table('institute_user',time_addins($inst_user_arr)); 
        }
        //-------------------------------------- STUDENT PARENTS RELATIONSHIP TABLE/ FIELDS -------------------------------------------
                 //student_user_id & parent_user_id- Unique key
        $user_parents_fields = ['student_user_id'=> $new_sdt_user_id,'parent_user_id'=> $prt_user_id,'profession'=> $profession,'relation'=> $relation];
        save_table('user_parent',time_addins($user_parents_fields));  

        //---------- SAVE IMAGE IF UPLOADED -----------
       // (isset($_FILES['pro_pic_file_prt'])) ? save_image($_FILES['pro_pic_file_prt'],$prt_user_id) : FALSE;
         if(isset($_FILES['pro_pic_file_prt'])){
            $data['basic_prt']['id'] = $prt_user_id;
            $data['basic_prt']['image_url'] =  save_image($_FILES['pro_pic_file_prt'],$prt_user_id);
            save_table('user',time_addins($data['basic_prt']));
        }
    //------------------ RESPONSE ------------        
    // print_r($prt_user_id);die;
     if($prt_user_id){
       // return "Student and Parents details saved successfully with Parent Id=" . $prt_user_id ."<br>"; 
        return '<b>'. $data_sdt['basic_sdt']['name'].' '.$data_sdt['basic_sdt']['middle_name'].' '.$data_sdt['basic_sdt']['last_name'].'</b> '.'and Parents:<b>'. $name .' </b>details saved successfully with Parent Id=<b>' . $prt_user_id ."</b><br>";
    }
    else{
        return "Error: " . $sql . "<br>" . mysqli_error($conn); 
    } 
}

//---------------------------  SAME CHILD CHECK FUNCTION -----------------------------------
function same_child_chk($prt_id,$stud_name){
    $sql = "SELECT student_user_id FROM `user_parent` join user on user.id = student_user_id where parent_user_id = '$prt_id' AND name = '$stud_name' ";
    $stud_ids = get_query_result($sql);
    return count($stud_ids) ? ['same_child'=>TRUE] : ['same_child'=>FALSE];
}

function check_user_exists($data){
    // $conn = $GLOBALS['conn'];
    $name  = strtolower($data['username']);
    $email = $data['email'];
    $phone = $data['phone'];
    $res = [];
    $res['message'] = "New User";
    $res['user_pass'] = TRUE;
    //--------------- PHONE & EMAIL BOTH PROVIDED -------------
    $sql = "SELECT `user_id`,`username`,`email`,`phone` FROM `user_logins` WHERE `phone` = '$phone' OR `email` = '$email' AND `is_active` = '1' ";
    //--------------- ONLY PHONE PROVIDED -------------
    if($email == ''){
        $sql = "SELECT `user_id`,`username`,`email`,`phone` FROM `user_logins` WHERE `phone` = '$phone' AND `is_active` = '1' ";
    }
    //--------------- ONLY EMAIL PROVIDED -------------
    if($phone == ''){
        $sql = "SELECT `user_id`,`username`,`email`,`phone` FROM `user_logins` WHERE `email` = '$email' AND `is_active` = '1' ";
    }
    $result = get_query_result($sql);
    // $conn = (new Auth())->conn;
    // $result = $conn->query($sql);
    $out = [];
    foreach ($result as $k => $v) {
    // }
    // while($v = mysqli_fetch_assoc($result)){
        $res = $v;
        $v['username'] = strtolower($v['username']);
        if($v['username'] == $name && $v['phone'] == $phone) {
            $res['message'] = "Same User";
           // $res['message'] = $name .' '.' User already exist <br>';
            $res['user_pass'] = TRUE;
        }

        if($v['username'] == $name && $v['email'] == $email){
            $res['message'] = "Same User";
           // $res['message'] = $name .' '.' User already exist <br>';
            $res['user_pass'] = TRUE;
        }

        if($v['username'] != $name && $v['phone'] == $phone){
            $res['message'] = "Phone No. is in use by other user <br>";
          //  $res['message'] = $name .', Phone No: '.$v['phone'] .' ' .'is in use by other user <br>';
            $res['user_pass'] = FALSE;break;
        }

        if($v['username'] != $name && $v['email'] == $email) {
            $res['message'] = "Email Address is in use by other user <br>";
          //  $res['message'] = $name .' '. 'Email Address is in use by other user <br>';
            $res['user_pass'] = FALSE;break;
        }
    }
    return $res;
}

function get_where_in_fk($tabl,$fields,$where,$limit=0){
    $flds = '';
    $lmt = ($limit) ? "LIMIT ".$limit." " : "";
    $wher = (count($where)) ? 'WHERE ' : '';
    $fk_vals = [];
    foreach ($where as $k => $v){                                  //-------   $k = column name ---------
        if(is_array($v)){                                          //----- CHECK for Where OR Where_in
            $vals = '("'.implode('","',$v).'")';
            $wher .= "`$k` in $vals AND ";
        }else{
            $wher .= "`$k` = '$v' AND ";
        }
    }

    foreach ($fields as $k => $v) {
        $col = $v;
        if(strpos($v, '.') !== false) {
            $arr = explode(".",$v);
            $col = $arr[0];
            $fk_vals[] = ['col_name'=>$arr[0],'fk_name'=>$arr[1],'vals'=>[]];
        }
        $flds .= "`$col`,";
    }

    $wher = substr($wher,0, -4);
    $flds = substr($flds,0, -1);
    $fields[0] == '*' ? $flds = '*' : FALSE;
    $sql = "SELECT $flds FROM `$tabl` $wher $lmt";
    // echo $sql;// echo '<br>';
    // die;
    // $creds = json_decode(file_get_contents(".creds"),true);
    // $user = $creds["DB_USER"];$pass = $creds["DB_PASS"];$dbname = $creds["DB_NAME"];
    // $user = "smduser";$pass = "@smduser123#";
    // $conn = mysqli_connect("localhost","root","root","kvdb");
    // $conn = new mysqli("localhost", $user,$pass,$dbname);
    $conn = $GLOBALS['conn'];
    // // $conn = (new Auth())->conn;
    // print_r($conn);// echo '<br>';
    // phpinfo();
    //  die;
    $result = $conn->query($sql);$out = [];
    while($v = mysqli_fetch_assoc($result)){
        $out[]    = $v;
        foreach ($fk_vals as $k2 => $v2) {
            $fk_vals[$k2]['vals'][$v[$v2['col_name']]] = '';
        }
    }
    // print_r($fk_vals);die;
    if(count($fk_vals)){
        // $fk_conn = (new Auth())->info_sch;
        $fk_conn = $GLOBALS['info_sch'];
        foreach($fk_vals as $k1 => $v1) {
            $fk_sql = "SELECT `REFERENCED_TABLE_NAME` FROM `KEY_COLUMN_USAGE` WHERE `TABLE_NAME` = '$tabl' AND `COLUMN_NAME` = '".$v1['col_name']."' AND `REFERENCED_TABLE_NAME` != 'null' " ;
            $fk_result = $fk_conn->query($fk_sql);
            $row = mysqli_fetch_assoc($fk_result);
            $fk_tab_name = $row['REFERENCED_TABLE_NAME'];
            if($fk_tab_name != 'null'){
                $fk_ids = "'".implode("','",array_keys($v1['vals']))."'";
                $fk_val_sql = "SELECT `id`,`".$v1['fk_name']."` FROM  $fk_tab_name WHERE `id` in ($fk_ids) ";
                // print_r($fk_val_sql);die;
                $fk_val_res = $conn->query($fk_val_sql);
                while($v3 = mysqli_fetch_assoc($fk_val_res)){
                    $fk_vals[$k1]['vals'][$v3['id']] = $v3[$v1['fk_name']];
                }
            }
        }
        // mysqli_close($fk_conn);
        foreach ($out as $k4 => $v4) {
            foreach ($fk_vals as $k5 => $v5) {
                if(isset($v4[$v5['col_name']])){
                    $out[$k4][$v5['col_name'].'.'.$v5['fk_name']] = $v5['vals'][$v4[$v5['col_name']]];
                }
            }
        }
    }
    // mysqli_close($conn);
    // print_r($out);die;
    return $out;
 }


//-----------------------array_key_values----------
function arr_key_map($arr,$key){
    $out = [];
    foreach ($arr as $k1 => $v1) {
        $out[$v1[$key]] = $v1;
    }
    return $out;
}

//---------------------------  INSERT TEACHERS / STAFF RECORDS MAIN FUNCTION ---------------
function add_staff($posted){
    $inst_id = $_SESSION['institute_id'];
    $data =['basic'=>json_decode($posted['basic'],true),'auth_fields'=>json_decode($posted['auth_fields'],true)];
    $data['extra'] = json_decode($posted['extra'],true);
    $email = $data['auth_fields']['email'];
    $phone = $data['auth_fields']['phone'];
    $roll =  $posted['roll'];
    $name = $data['basic']['name'];
    //---------------- CHECK FOR ALREADY EXISTING TEACHER --------------
    //----------------------- CHECK FOR ALREADY EXISTING PARENT WITH ITS FIRST NAME N MOBILE NO SAME --------------
    //  if(!isset($data['basic_prt']['id'])){
          $chk = check_user_exists(['email'=>$email,'phone'=>$phone,'username'=>$name]);
        if($chk['user_pass'] == FALSE ){
            echo $chk['message'] ;exit;
        }
        if($chk['message'] == 'Same User' ){
            $data['basic']['id'] = $chk['user_id'];
        } 
    $new_user_id = save_table('user',time_addins($data['basic']));  
    //---------- SAVE EXTRA FIELDS -----------
        foreach ($data['extra'] as $k => $v) {
            $ext_arr = ['user_id' => $new_user_id ,'institue_id' => $inst_id ,'extra_field_id' => $k ,'value' => $v];
            save_table('user_extra_field',time_addins($ext_arr));        
        }
        //---------- SAVE AUTH FIELDS -----------    
        $auth_arr = ['user_id'=> $new_user_id,'username'=> $name,'email'=> $email,'phone'=> $phone,'password'=> md5('123')];
        save_table('user_logins',time_addins($auth_arr));    
      //  if(!isset($data['basic']['id'])){    
            //---------- SAVE INSTITUTE USER FIELDS -----------  
            $inst_user_arr = ['institute_id' => $inst_id ,'user_id' => $new_user_id ,'roll' => $roll];
            save_table('institute_user',time_addins($inst_user_arr));
     //   }
    //---------- SAVE IMAGE IF UPLOADED -----------
    if(isset($_FILES['image'])){
        $data['basic']['id'] = $new_user_id;
        $data['basic']['image_url'] =  save_image($_FILES['image'],$new_user_id);
        save_table('user',time_addins($data['basic']));
    }
    //------------------ RESPONSE ------------
        if($new_user_id){
            return "Added with id:=" . $new_user_id ; 
        }
        else{
            return "Error: " . $sql . "<br>" . mysqli_error($conn); 
        } 
}


function get_timetable($posted){
    //  $ic_id=$_POST['ic_id'];
      $ic_id=$posted;   
      $out = []; 
      $ics_flds = get_where_in_flds('institute_class_subject',['id','subject_name'],['institute_class_id'=>$ic_id,'is_active'=>1]); 
      $ics_ids = array_column($ics_flds,'id');     // Get all subjects id of Selected class       
    //  print_r($ics_ids);
      $icst_flds =  get_where_in_flds('institute_class_subject_teacher',['id','subject_id','teacher_user_id'],['subject_id'=>$ics_ids,'is_active'=>1]);
     
      $subject = get_subject_names_by_ids(array_unique(array_column($icst_flds,'subject_id')));
      $subject[''] = ''; // this is because in case of break we have null subject id so to avoid error ;
      $teacher = get_user_names_by_ids(array_unique(array_column($icst_flds,'teacher_user_id')));
      $teacher[''] =''; // for null subject id we will get no teacher name;

      $icst_ids = array_column($icst_flds,'id');
      $lect_set_flds = get_where_in_flds('lecture_set',['id','institute_class_subject_teacher_id','institute_class_id','lecture_no','day','start_time','end_time','type'],['institute_class_id'=>$ic_id,'is_active'=>1]);        
   //   print_r($lect_set_flds);    
      // Getting Subject and Teacher from icst table
      $icst_id_arr = [];
        foreach ($icst_flds as $k => $v) {
            $icst_id_arr[$v['id']] =  ['subject_id'=>$v['subject_id'],'subject_name'=>$subject[$v['subject_id']],'teacher_id'=>$v['teacher_user_id'],'teacher_name'=>$teacher[$v['teacher_user_id']]];
        }
      // if icst_id is null it will be assigned
      $icst_id_arr[''] =['subject_id'=>'','subject_name'=>'','teacher_id'=>'','teacher_name'=>''];
    //  print_r($icst_id_arr);  
        foreach ($lect_set_flds as $k => $v) {
            $out[$v['id']] = ['id'=>$v['id'],'icst_id'=>$v['institute_class_subject_teacher_id'],'institute_class_id'=>$v['institute_class_id'],'lecture_no'=>$v['lecture_no'],'subject_id'=>$icst_id_arr[$v['institute_class_subject_teacher_id']]['subject_id'],'subject_name'=> $icst_id_arr[$v['institute_class_subject_teacher_id']]['subject_name'] ,'teacher_name'=> $icst_id_arr[$v['institute_class_subject_teacher_id']]['teacher_name'] ,'teacher_id'=>$icst_id_arr[$v['institute_class_subject_teacher_id']]['teacher_id'],'day'=>$v['day'],'start_time'=>$v['start_time'],'end_time'=>$v['end_time'],'type'=>$v['type']]; 
        }
   //   print_r($out); 
      array_multisort(array_column($lect_set_flds, 'lecture_no'),SORT_ASC, $out);
     // echo json_encode($out);die;
      return  $out;
    }



//##################################### SMS SERVICE ############################################
    function send_SMS_fn($string_mob_no,$sms_textMsg,$sms_sender_id){
            $api_key = '45D52668880C5B';
            // $contacts = '97656XXXXX,97612XXXXX,76012XXXXX,80012XXXXX,89456XXXXX,88010XXXXX,98442XXXXX';
            $contacts =$string_mob_no;
            // print_r($contacts);
            //  $from = 'AQHSCH';
            $from = $sms_sender_id;
            //  print_r($from);
            //  print_r($string_mob_no);

            //  $from =$_SESSION['institute_name'];
            //  $sms_text = urlencode('Hello People, have a great day');
            $sms_text = urlencode($sms_textMsg);
            //Submit to server
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, "http://kutility.in/app/smsapi/index.php");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "key=".$api_key."&routeid=415&type=text&contacts=".$contacts."&senderid=".$from."&msg=".$sms_text);
            $response = curl_exec($ch);
            curl_close($ch); 
            // use of explode 
            //  eg: response --SMS-SHOOT-ID/afreen5d528b7622dca
            $response_arr = explode ("/", $response);  
            $sms_shoot_id =$response_arr[1];
            // echo $response;
    return $sms_shoot_id;  
    }

    function get_sms_credit_balance(){
            $api_key = '45D52668880C5B';
            $api_url = "http://kutility.in/app/miscapi/".$api_key."/getBalance/true/";
            //Submit to server
            $credit_balance = file_get_contents( $api_url);
            // echo $credit_balance;
            return json_decode($credit_balance,true);

            }
            function get_sms_details($sms_shoot_id){
            $api_key = '45D52668880C5B';
            // $sms_shoot_id = 'nick51fa4816d8043';
            //  $sms_shoot_id = 'afreen5d528b7622dca';
            $api_url = "http://kutility.in/app/miscapi/".$api_key."/getDLR/".$sms_shoot_id;
            //Submit to server
            $response = file_get_contents( $api_url);
            $dlr_array = json_decode($response,true);
            //  print_r($dlr_array);
            return $dlr_array;
    }
//################################### END SMS SERVICE #########################################

/*########################################## COMMON FUNCTIONS #####################################*/

function close_DB_conn(){
    mysqli_close($GLOBALS['conn']);
    mysqli_close($GLOBALS['info_sch']);
    return TRUE;
}

function get_query_run($query){
    $conn = $GLOBALS['conn'];
    $result = $conn->query($query);$out = [];
    // mysqli_close($conn);
    return TRUE;
}
function get_query_result($query){
    $conn = $GLOBALS['conn'];
    $result = $conn->query($query);$out = [];
  //  print_r($result);
     while($v = mysqli_fetch_assoc($result)){
        $out[]    = $v;
    }
    // mysqli_close($conn);
    return $out; 
}

 function get_where_flds($tabl,$fields,$where){
    $flds = '';
    $wher = '';
    foreach ($where as $k => $v) {                                                       //-------   $k = column name ---------
        $wher .= "`$k` = '$v' AND ";
    }
    foreach ($fields as $k => $v) {
        $flds .= "`$v`,";
    }
    $wher = substr($wher,0, -4);
    $flds = substr($flds,0, -1);
    
    $fields[0] == '*' ? $flds = '*' : FALSE;
    $sql = "SELECT $flds FROM `$tabl` WHERE $wher";
    // echo $sql; 
    $conn = $GLOBALS['conn'];
    $result = $conn->query($sql);$out = [];
    while($v = mysqli_fetch_assoc($result)){
        $out[]    = $v;
    }
    return $out;
 }
//  print_r($v);

//--------------------------------------------NEW VERSION OF get_where_flds -----------
function get_where_in_flds($tabl,$fields,$where){
    $flds = '';
    $wher = '';
    foreach ($where as $k => $v){                                  //-------   $k = column name ---------

        if(is_array($v)){                                          //----- CHECK for Where OR Where_in
            $vals = '("'.implode('","',$v).'")';
            $wher .= "`$k` in $vals AND ";
        }else{
            $wher .= "`$k` = '$v' AND ";
        }
    }
    foreach ($fields as $k => $v) {
        $flds .= "`$v`,";
    }
    $wher = substr($wher,0, -4);
    $flds = substr($flds,0, -1);
    
    $fields[0] == '*' ? $flds = '*' : FALSE;
    $sql = "SELECT $flds FROM `$tabl` WHERE $wher";
    // echo $sql; die;
    $conn = $GLOBALS['conn'];
    $result = $conn->query($sql);$out = [];
    while($v = mysqli_fetch_assoc($result)){
        $out[]    = $v;
    }
    return $out;
 }

 //--------------------------------------------get_where_not_in_flds -----------
function get_where_not_in_flds($tabl,$fields,$where){
    $flds = '';
    $wher = '';
    foreach ($where as $k => $v){                                  //-------   $k = column name ---------

        if(is_array($v)){                                          //----- CHECK for Where OR Where_in
            $vals = '("'.implode('","',$v).'")';
            $wher .= "`$k` NOT IN $vals AND ";
        }else{
            $wher .= "`$k` = '$v' AND ";
        }
    }
    foreach ($fields as $k => $v) {
        $flds .= "`$v`,";
    }
    $wher = substr($wher,0, -4);
    $flds = substr($flds,0, -1);
    
    $fields[0] == '*' ? $flds = '*' : FALSE;
    $sql = "SELECT $flds FROM `$tabl` WHERE $wher";
    $conn = $GLOBALS['conn'];
    $result = $conn->query($sql);$out = [];
    while($v = mysqli_fetch_assoc($result)){
        $out[]    = $v;
    }
    return $out;
 }

 // ####################### REMOVING DUBLICATE VALUE FROM ARRAY ################

    function remove_duplicate_values($data,$col_key){
        $rdata = array_reverse($data);
        $result = array_reverse( // Reverse array to the initial order.
            array_values( // Get rid of string keys (make array indexed again).
                array_combine( // Create array taking keys from column and values from the base array.
                    array_column($rdata,$col_key), 
                    $rdata
                )
            )
        );
        return $result;

    }

//----------------- WRITE ARRAY TO CSV ------------------
function write_array_to_csv($data)
{
        $fname = "Empty Array";
        if(count($data)){
            $ms = round(microtime(true) * 1000);
            $fname = date('Y-m-d')."_$ms.csv";
            $flink = "dump/".$fname;
            $file = fopen($flink,"w");
            //---------- HEADING IF ARRAY COUNT IS 2 OR GREATER ----------
            (isset($data[0]) && count($data) > 1) ? fputcsv($file, array_keys($data[0])) : FALSE;
            foreach ($data as $line)
            {
                fputcsv($file,$line);
            }
            fclose($file);
        }

        return $fname;
}


//------------------ DATE RANGE ARRAY -------------

function get_date_range($first, $last, $step = '+1 day', $output_format = 'Y-m-d' ) {
    $dates = [];
    $current = strtotime($first);
    $last    = strtotime($last);
    while( $current <= $last ) {
        $dates[] = date($output_format, $current);
        $current = strtotime($step, $current);
    }
    return $dates;
}

//------------------ -------------
function time_addins($arr){
    $arr['added_on'] = date('Y-m-d H:i:s') ;
    $arr['added_by'] = $_SESSION['user']['id'];
    $arr['updated_by'] = $_SESSION['user']['id'];
    return $arr;
}
//------------------ -------------

function timstmp($arr){
    $arr['updated_on'] = date('Y-m-d H:i:s') ;
    // $arr['added_by'] = $_SESSION['user']['id'];
    // $arr['updated_by'] = $_SESSION['user']['id'];
    return $arr;
}
//------------------ -------------

function save_table($tab,$arr)           //---------  [FILED = ARRAY[VALUE1,VALUE2]]
{
    $keys = $updt_k = '';
    $val_arr = [];
    foreach ($arr as $k => $v) {                                                       //-------   $k = column name ---------
        $keys   .= "`$k`,";                                                            //---------------FOR COLUMNS 
        $val_arr[] = "'".str_replace("'", "\'", $v)."'";
        $updt_k .= "`$k` = VALUES(`$k`),";                                             //---------------FOR DUPLICATE UPDATE
    }
    $keys = substr($keys,0, -1);           //------REMOVE LAST comma
    $updt_k = substr($updt_k,0, -1);       //------REMOVE LAST comma
    $vals = implode(',',$val_arr);
    $str = "INSERT INTO  `$tab` ($keys) VALUES ($vals) ON DUPLICATE KEY UPDATE $updt_k;";
    // echo $str;die;
    // print_r($str);
    // $conn = (new Auth())->conn;
    $conn = $GLOBALS['conn'];
    //-------------------QUERY IN DB------------------
    $res = (!mysqli_query($conn,$str)) ? "Error description: " . mysqli_error($conn) : $conn->insert_id;
    // mysqli_close($conn);
    return $res;
}

function save_batch($tab,$cols,$vals)           //---------  [FILED = ARRAY[VALUE1,VALUE2]]
{
    $keys = $updt_k = '';
    $val_str ='';
    //----COLS-----
    foreach ($cols as $k => $v) {                                                       //-------   $k = column name ---------
        $keys   .= "`$v`,";                                                            //---------------FOR COLUMNS 
        $updt_k .= "`$v` = VALUES(`$v`),";                                             //---------------FOR DUPLICATE UPDATE
    }
    //-----VALS-----
    foreach ($vals as $k => $v) {
        $val_str .= "('".implode("','",str_replace("'", "\'", $v))."'),";
    }
    $keys = substr($keys,0, -1);           //------REMOVE LAST comma
    $updt_k = substr($updt_k,0, -1);       //------REMOVE LAST comma
    $val_str = substr($val_str,0, -1);           //------REMOVE LAST comma
    $str = "INSERT INTO  `$tab` ($keys) VALUES $val_str ON DUPLICATE KEY UPDATE $updt_k;";
    // echo $str;
    // die;
    // $conn = (new Auth())->conn;
    $conn = $GLOBALS['conn'];
    //-------------------QUERY IN DB------------------
    $res = (!mysqli_query($conn,$str)) ? "Error description: " . mysqli_error($conn) : $conn->insert_id;
    // mysqli_close($conn);
    return $res;
}
//---------------------------  UPDATE FUNCTION ---------------
function update_fields($tab,$vals,$where){           //---------  [FILED = ARRAY[VALUE1,VALUE2]]

    $wher = (count($where)) ? 'WHERE ' : '';
    foreach ($where as $k => $v){                                  //-------   $k = column name ---------
        $wher .= "`$k` = '$v' AND ";
    }
    $wher = substr($wher,0, -4);
    //-----VALS-----
    $val_str = "";
    foreach ($vals as $k => $v) {
        $val_str .= "`$k` = '$v' AND ";
    }
    $val_str = substr($val_str,0, -4);
    // echo $val_str;die;
    // $str = "INSERT INTO  `$tab` ($keys) VALUES $val_str ON DUPLICATE KEY UPDATE $updt_k;";
    $str =  " UPDATE $tab SET $val_str $wher ; ";
    // echo $str;die;
    $conn = $GLOBALS['conn'];
    //-------------------QUERY IN DB------------------
    // print_r($where);die;
    $res = (!mysqli_query($conn,$str)) ? "Error description: " . mysqli_error($conn) : $where;
    // mysqli_close($conn);
    // print_r($vals);die;
    return $res;
}
//---------------------------  IMAGE UPLOAD FUNCTION ---------------
function save_image($img,$new_user_id){
    $ms = round(microtime(true) * 1000);
    $ext = '.'.pathinfo(basename($img['name']))['extension'];
    $img_dir = "assets/images";
    !(is_dir($img_dir)) ? (mkdir($img_dir, 0755, true)) : FALSE;
    $move_to = $img_dir."/".$new_user_id.$ext;
    file_exists($move_to) ? rename($move_to,$img_dir."/".$new_user_id.'_'.$ms.$ext) : FALSE;
    $img = resize_image($img['tmp_name'],600,800,$ext);
    $ext == ".png"  ? imagepng($img, $move_to) : imagejpeg($img, $move_to);
    return $move_to;
}

//---------------------------  IMAGE RESIZING FUNCTION ---------------
function resize_image($file, $w, $h,$ext) {
	//------------------- CALCULATION FOR MAINTAIN RATIO -------------
    list($wid, $hgt) = getimagesize($file);
    $r = $wid / $hgt;
	if ($w/$h > $r) {
		$newwidth = $h*$r;  $newheight = $h;
	} else {
		$newheight = $w/$r;  $newwidth = $w;
	}
    //------------------- IMAGE CREATE -------------
  //  echo $ext;
    $src = ($ext == '.png') ?  imagecreatefrompng($file)  : imagecreatefromjpeg($file)   ;
    $dst = imagecreatetruecolor($newwidth, $newheight);

    if( $ext == '.png'  ||  $ext == '.gif'   ){
        $background = imagecolorallocate($dst , 0, 0, 0);
        imagecolortransparent($dst, $background);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }

   imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $wid, $hgt);
  return $dst;
}

//---------------------------  DOCUMENT UPLOAD FUNCTION ---------------
function save_doc($doc,$doc_dir){
        // $user_id = $_SESSION['user_id'];
        $ms = round(microtime(true) * 1000);
        $ext = '.'.pathinfo(basename($doc['name']))['extension'];
        $newfilename = pathinfo($doc['name'], PATHINFO_FILENAME);
        // $newfilename =$filename . '_'.$user_id ;
        // $doc_dir = "../assets/assignment_doc";
        !(is_dir($doc_dir)) ? (mkdir($doc_dir, 0755, true)) : FALSE;
        $move_to = $doc_dir."/".$newfilename.$ext;
        // print_r($move_to);
        file_exists($move_to) ? rename($move_to,$doc_dir."/".$newfilename.'_'.$ms.$ext) : FALSE;
        $file_type=$doc['type'];
        // print_r($ext);
        // die;
        $ext_types = ['.jpg', '.jpeg', '.bmp', '.gif', '.png', '.svg', '.pdf', '.doc', '.docx', '.xls', '.xlsx'];
        // if ($file_type=="application/pdf" || $file_type=="image/gif" || $file_type=="image/jpeg" ||  $file_type=="image/png" || $file_type=="application/vnd.ms-excel") {
        if(in_array($ext,$ext_types)){            
            if(move_uploaded_file($doc['tmp_name'], $move_to)){
                return $move_to;}
            else {return '';}
        }
        else {
             echo "You may only upload PDFs, EXCEL, PNG,JPEGs or GIF files.<br>"; }
    }

function get_mobile_no_with_student_id($student_user_id){
    $is_active=1;
    $out =[];
    $std_details = get_where_flds('user',['name','middle_name','last_name'],['id'=>$student_user_id, 'is_active'=>$is_active]);
    $user_prt_rel = get_where_flds('user_parent',['id','student_user_id','parent_user_id'],['student_user_id'=>$student_user_id, 'is_active'=>$is_active]);   
      if($user_prt_rel!= null){
        $prt_id = $user_prt_rel[0]['parent_user_id'];
      //  $out['basic_prt'] = get_where_flds('user',['id','name','middle_name','last_name','dob','gender','current_address','image_url'],['id'=> $prt_id,'is_active'=>$is_active]);  
         $auth_fields  = get_where_flds('user_logins',['email','phone'],['user_id'=>$prt_id,'is_active'=>$is_active]);       
         $out[$student_user_id] =['name'=>$std_details[0]['name'] .' '.$std_details[0]['last_name'] ,'phone'=>$auth_fields[0]['phone']];
      }
         return $out;
}

function get_fee_amount_with_ficid($fee_institute_class_id){
    $is_active=1;
    $out =[];
    $amount  = get_where_flds('fee_amounts',['amount'],['fic_id'=>$fee_institute_class_id,'is_active'=>$is_active]);       
    $out[$fee_institute_class_id]=['amount'=>$amount[0]['amount']];
    return $out;
}

function printr($arg){
    echo '<pre>';
    print_r($arg);
    echo '</pre>';
}
