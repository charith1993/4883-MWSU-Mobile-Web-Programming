<?php
error_reporting (0);
 
//debug or test backend.php by running the following commands from the browser:
//your.ip.address/backend.php?debug=true&action=select
//your.ip.address/backend.php?debug=true&action=login&email=someemailinthedatabase@gmail.com
//your.ip.address/backend.php?debug=true&action=busy&email=someemailinthedatabase@gmail.com
 
$fp = fopen("log.txt","a");
fwrite($fp,print_r($_POST,1)."\n");
 
if(isset($_GET['action'])){ 
	$_POST['action'] = $_GET['action'];
	if($_POST['action'] == 'insert'){
		//Change info to test...
		$_POST['First'] = 'Mark';
		$_POST['Last'] = 'Twain';
		$_POST['Thumb'] = '';
		$_POST['Lat'] = '33.12343';
		$_POST['Lon'] = '-98.234563';
		$_POST['Sex'] = 'M';
		$_POST['Phone'] = '9403687777';
		$_POST['Email'] = 'mark.twain@imabadasswriter.com';
		$_POST['Busy'] = '0'; 
		$_POST['LoggedIn'] = '1';
	}
}
 
 
//Gets us connected to our mysql database on the LOCAL server.
//$db = new PDO('mysql:host=localhost;dbname=mobile_web;charset=utf8', 'mobile', 'mobile');
try {
	$db = new PDO('mysql:host=localhost;dbname=mobile_web;charset=utf8', 'mobile', '33r9ghijk');
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$conn = true;
} catch(PDOException $e) {
	$Result = array("Success"=>0,"Message"=>"Database connection failed!");
	$conn = false;
}
 
//We have a connection, so continue
if($conn){
	//What action are we taking?
	switch($_POST['action']){
		
		//Adding a new user into our most splendid spy program
		case "insert":
			fwrite($fp,"inserting\n");
			if(AddUser($db,$fp)){
				$Result = array("Success"=>1);
			}else{
				$Result = array("Success"=>0,"Message"=>"Add user query failed!");
			}
		break;
		
		//Switching a user from either logged in or busy to vice versa.
		case "login":
			if(LoginUser($db)){
				$Result = array("Success"=>1);
			}else{
				$Result = array("Success"=>0,"Message"=>"Login query failed!");
			}
			break;
		
		//Make a user busy.
		case "busy":
			if(BusyUser($db)){
				$Result = array("Success"=>1);
			}else{
				$Result = array("Success"=>0,"Message"=>"Busy user query failed!");
			}
			break;
					
		//Remove a user completely from our spy program
		case "delete":
			if(DeleteUser($db)){
				$Result = array("Success"=>1);
			}else{
				$Result = array("Success"=>0,"Message"=>"Delete query failed!");
			}
			break;
		
		//Getting all logged in users (busy or not)
		case "select":
			$Result = GetLoggedInUsers($db);
			if($Result){
				$Result['Success'] = 1;		//Add success to the result object.
			}else{
				$Result = array("Success"=>0,"Message"=>"Get logged in users query failed!");
			}
			break;	
			case "image":
    define('UPLOAD_DIR', 'images/');    
    $img = $_POST['img'];
    $img = str_replace('data:image/png;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);
    $file = UPLOAD_DIR . $_POST['uid'] . '.png';
    $success = file_put_contents($file, $data);
    $Result =  array("Path"=>$file,"FileSize"=>$success,"Image"=>$_POST['img']);
	$Result =  array("Success"=>0,"Message"=>"We got the Picture!");	
    break;
		default:
			$Result =  array("Success"=>0,"Message"=>"No action set!");	
	}
}
 
//Print out json for our html page
//Remember, whatever is printed is sent to our web page
echo json_encode($Result);
 
/*
* Returns all logged in users.
* @Params: 
* 	$db - database connection resource
* @Returns:
*	bool - success or fail 
*/
function GetLoggedInUsers($db){
	$sth = $db->prepare("SELECT *  FROM Active_Users WHERE LoggedIn = '1'");
	$sth->execute();
	return $sth->fetchAll();	
}
 
/*
* Deletes a user from the database. We probably won't implement this from the web site.
* @Params: 
* 	$db - database connection resource
* @Returns:
*	bool - success or fail 
*/
function DeleteUser($db){
	$sth = $db->prepare("DELETE FROM Active_Users WHERE Email = {$_POST['Email']} ");
	return $sth->execute(); //return true if query ran
}
 
/*
* Adds a user to the database.
* @Params: 
* 	$db - database connection resource
*	$_POST - POST array is a SUPER GLOBAL.
* @Returns:
*	bool - success or fail 
*/
function AddUser($db,$fp){
	$sth = $db->prepare("SELECT MAX(Id) as Max FROM Users");
	$sth->execute();
	$result = $sth->fetchAll();
	
	$max = $result[0]['Max'] + 1;
	
	fwrite($fp,"Max: {$max}\n");
 
	$sql = "INSERT INTO `mobile_web`.`Users` (`Id`, `First`, `Last`, `Thumbnail`, `Lat`, `Lon`, `Sex`, `Phone`, `Email`, `Busy`, `LoggedIn`, `Password`) 
			VALUES ('{$max}', '{$_POST['First']}', 
			'{$_POST['Last']}', '{$_POST['Thumb']}',
			'{$_POST['Sex']}', '{$_POST['Phone']}', 
			'{$_POST['Email']}', '{$_POST['Busy']}', '{$_POST['LoggedIn']}' ,md5({$_POST['Password']}) );";
	fwrite($fp,"\n".$sql."\n");
			
	$sth = $db->prepare($sql);
	return $sth->execute();		//return true if query ran
}
 
/*
* Sets a users status to busy.
* @Params: 
* 	$db - database connection resource
* @Returns:
*	bool - success or fail 
*/
function BusyUser($db){
	$sth = $db->prepare("UPDATE Active_Users SET Busy = '1' WHERE Email = {$_POST['Email']} ");
	return $sth->execute();    //return true if query ran
}
 
/*
* Sets a users status to logged in.
* @Params: 
* 	$db - database connection resource
* @Returns:
*	bool - success or fail 
*/
function LoginUser($db){
	$sth = $db->prepare("UPDATE Active_Users SET LoggedIn = '1' WHERE Email = {$_POST['Email']} ");
	return $sth->execute();    //return true if query ran
}
