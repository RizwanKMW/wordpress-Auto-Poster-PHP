<?php
use Symfony\Component\Yaml\Yaml;
$base_url =  dirname(__FILE__);
require_once($base_url.'/wp-load.php');
require_once($base_url.'/wp-config.php'); 
require_once($base_url.'/wp-includes/wp-db.php'); 
require_once($base_url.'/wp-admin/includes/taxonomy.php'); 

function addqoute($v)
{
  return "'".$v."'";;
}
class wp_api{
	public $fileName; 
	public function __construct(){
		$this->fileName = 'api_oop.php';
	}
	public function wp_create_post($title,$content,$category,$author=1){ //
		$my_post = array(
		  'post_title'    => wp_strip_all_tags( $title ),
		  'post_content'  => $content,
		  'post_status'   => 'publish',
		  'post_author'   => $author,
		  'post_category' => $category,
		  'post_date' => date('Y-m-d H:i:s')
		);
		return wp_insert_post( $my_post );
	}
	public function wp_create_comment($email,$content,$post_id,$date='Y-m-d H:i:s'){
			$agent = $_SERVER['HTTP_USER_AGENT'];
			$ax = explode("@",$email);			
			$data = array(
				'comment_post_ID' => $post_id,
				'comment_author' => $ax[0],
				'comment_author_email' => $email,
				'comment_content' => $content,
				'comment_agent' => $agent,
				'comment_date' => date($date),
				'comment_approved' => 1,
			);

			$comment_id = wp_insert_comment($data);
			return $comment_id;
	}

	function wp_create_comment_reply($comment_parent_id,$post_id,$reply,$email = "rizikmw@gmail.com",$comment_author="olha",$date='Y-m-d H:i:s'){
		$agent = $_SERVER['HTTP_USER_AGENT'];
		$data = array(
			'comment_parent' => $comment_parent_id,
			'comment_post_ID' => $post_id,
			'comment_author' => $comment_author,
			'comment_author_email' => $email,
			'comment_content' => $reply,
			'comment_agent' => $agent,
			'comment_date' => date($date),
			'comment_approved' => 1
		);
		$comment_id = wp_insert_comment($data);
		return $comment_id;
	}

	function wp_upload_file($file_path){ // upload file to uploads folder, insert to wp post, add meta tags
		$filename = basename($file_path);
		$attachment_id = "";
		$upload_file = wp_upload_bits($filename, null, file_get_contents($file_path)); //Create a file in the upload folder, return [file, url, error]
		$fileNameReadable = preg_replace('/\.[^.]+$/', '', $filename);
		if (empty($upload_file['error'])) {
			$wp_filetype = wp_check_filetype($filename, null ); //image/jpeg
			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title' => $fileNameReadable,
				'post_content' => '',
				'post_status' => 'inherit'
			);
			$attachment_id = wp_insert_attachment( $attachment, $upload_file['file']); //to wp_posts table insert
			if (!is_wp_error($attachment_id)) {
				require_once(ABSPATH . "wp-admin" . '/includes/image.php');
				$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] ); //subsize create 
				wp_update_attachment_metadata( $attachment_id,  $attachment_data );
			}
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $fileNameReadable);
		}
		return $attachment_id;
	}
	
	function wp_set_post_image($attachmentid,$postid){
		return set_post_thumbnail( $postid, $attachmentid );
	}

	function checkRemoteFile($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_NOBODY, 1);			// don't download content
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		//$resp = curl_getinfo($ch);
		curl_close($ch);
		if($result !== FALSE)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	function is_string_contains($array,$string){
		$str = "";
		foreach ($array as $key => $value) {
			if(!empty($value)){
				$str = $str.$value."|";			
			}
		}
		$str = rtrim($str, "|");
		$str = "(".$str.")";
		echo $str;
		if(preg_match($str, $string) === 1) { 
       		 return true;
    	}else{
    		return false;
    	}
    }
	
	function get_gravatar_image_link($email='filereal@live.com',$size=100){
		$grav_url = "https://www.gravatar.com/avatar/" . md5( strtolower( trim($email) ) ) ."?s=" . $size."&d=404";		
		if (!$this->checkRemoteFile($grav_url)){
			return false;
		}else{
			return $grav_url;
		}
	}
	

	function image_resize_and_fit($im,$img_to_resize,$xs,$yx,$resizePixels){
		$size = getimagesize($img_to_resize);
		$background_size = getimagesize($im);
		$ratio = $size[0] / $size[1];
		if ($ratio > 1) {
		    $width = $resizePixels;
		    $height = $resizePixels / $ratio;
		    $dst_y = ($resizePixels - $height) / 2;
		} else {
		    $width = $resizePixels * $ratio;
		    $height = $resizePixels;
		    $dst_x = ($resizePixels - $width) / 2;
		}
		//where to put after resize
		$actual_container_width = $background_size[0];
		$actual_container_height = $background_size[1];
		$xs = ($actual_container_width-round($width))/2; 
		$yx = ($actual_container_height-round($height))/2; 
		//where to put after resize end

		$src = imagecreatefromstring(file_get_contents($img_to_resize));
		$dst = imagecreatetruecolor($width, $height);
		$im = imagecreatefromjpeg($im);
		imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
		imagecopymerge($im, $dst, $xs, $yx, 0, 0, imagesx($dst), imagesy($dst), 100);
		return $im;
	}

	function get_random_file_from_folder($folder_path){
		$folderName = trim($folder_path); 
		$path =$folderName;
		$files = array_diff(scandir($path), array('.', '..','link.txt'));
		if(count($files)>0){
			$randomfile = array_rand($files,1);
			return $files[$randomfile];			
		}else{
			return false;
		}
	}

	function get_random_directory($parent_folder=null){
		$allSubFiles = glob($parent_folder."/*");
		//$allSubFiles = scandir($parent_folder."/");
		$dirs = array_filter($allSubFiles, 'is_dir');
		if(count($dirs)>0){
			$random_dir_id = array_rand($dirs,1);
			$random_dir = $dirs[$random_dir_id];
			$random_dir = trim(str_replace($parent_folder."/","", $random_dir));
			return $random_dir;
		}else{
			return false;
		}
	}
	function get_random_names($count=3){
		$names = explode("\n",file_get_contents('data/first-names.txt'));
		$random_keys = array_rand($names,$count);
		$retarray= array();
		foreach($random_keys as $key=>$value){
			array_push($retarray, $names[$value]);
		}
		shuffle($retarray);
		return $retarray;
	}

	function wp_get_users_ids(){
		$users = get_users( array( 'fields' => array( 'ID' ) ) );
		$id_arr=array();
		foreach($users as $value){
			array_push($id_arr, $value->ID);
		}
		return $id_arr;
	}
	function wp_get_one_random_user_id(){
		$users = wp_get_users_ids();
		$random_user = array_rand($users,1);
		return $random_user;
	}
	function wp_update_post_modified($id){
		$update = array( 'ID' => $id );
		wp_update_post( $update );
	}

	function read_config(){ //json //yaml or any other data type
		include_once('data/vendor/autoload.php');
		
		try {
		    $value = Yaml::parse(file_get_contents('data/yaml.yaml'));
		    print_r($value);
		} catch (ParseException $exception) {
		    printf('Unable to parse the YAML string: %s', $exception->getMessage());
		}
	}
	function image_generate(){
		$pathofImagefolder = "data/handle_images";
		require_once($pathofImagefolder.'/PHPImage.php');
		$bg = $pathofImagefolder.'/background.png';
		$overlay = $pathofImagefolder.'/logo.png';

		$image = new PHPImage();
		$image->setDimensionsFromImage($bg);
		$image->setFont($pathofImagefolder.'/baloo.ttf');
		$image->draw($bg);
		$image->draw($overlay, '1020', '515'); //file, xs,ys in % or px
		$image->rectangle(115, 305, 300, 75, array(0, 0, 0), 0.5);
		$image->setTextColor(array(255, 255, 255));
		$image->text('WHAT ARE', array('fontSize' => 35, 'x' => 160, 'y' => 325));

		$image->text('VIDEO', array(
			'fontSize' => 190, // Desired starting font size, IF to many text it font size auto decrease
			'x' => 120,
			'y' => 430,
			'width' => 650,
			'height' => 200,
			'alignHorizontal' => 'center',
			'alignVertical' => 'center',
			'debug' => true
		));
		
		
		$image->textBox('And why do they matter?', array( // Multiline, if space end ,then text small
			'width' => 650,
			'height' => 150,
			'fontSize' => 75, // Desired starting font size
			'x' => 120,
			'y' => 810
		));
		
		$image->save($pathofImagefolder."/output.png",true, true);
	}
	
	################################ DATABASE RELATED ################################
	#                                                                                #
	#                                                                                #
	#                                                                                #
	#                                      Rizi                                      #
	#                                                                                #
	#                                                                                #
	#                                                                                #
	##################################################################################

	//create $obj->wp_db_create_record('wp_posts',array('post_author'=>2));
	//read $obj->wp_db_get_all_results('wp_posts',array('post_author'=>2),10);
	//update $obj->wp_db_update_record('wp_posts',array('post_author'=>2),array('ID'=>2,'post_author'=>1)); //table,update data, where
	//delete $obj->wp_db_delete_record('wp_posts',array('post_author'=>44));

	public function wp_db_get_all_results($table=null,$where=null,$limit=50){
		global $wpdb;
		if($table==null){
			$error = json_encode(array("result"=>false,"message"=>"table can't be empty"));
			die($error);
		}
		
		$sql="SELECT * FROM $table ";
		if($where){
			$sql.="WHERE ";
			foreach ($where as $key => $value) {
				$sql.=" $key = '$value' AND";
			}
		}
		$sql = rtrim($sql,"AND");
		$sql.=" LIMIT $limit";
		$results = $wpdb->get_results($sql);
		if(count($results)>0){
			return $results;
		}else{
			$error = json_encode(array("result"=>false,"message"=>"No record found with command $sql"));
			die($error);
		}
	}
	public function wp_db_create_record($table=null,$array=null){
		global $wpdb;
		if($table==null){
			$error = json_encode(array("result"=>false,"message"=>"table can't be empty"));
			die($error);
		}elseif(!is_array($array)){
			$error = json_encode(array("result"=>false,"message"=>"Array is invalid "));
			die($error);
		}
		$columns = implode(",",array_keys($array));
		$escaped_values = array_map("addqoute", array_values($array));
		$values = implode(',', $escaped_values);
		$sql = "INSERT INTO $table ($columns) VALUES ($values)";

		if($wpdb->query($sql)){
			return true;
		}else{
			return false;
		}
	}
	public function wp_db_update_record($table=null,$array=null,$where=null){
		global $wpdb;
		if($table==null){
			$error = json_encode(array("result"=>false,"message"=>"table can't be empty"));
			die($error);
		}elseif(!is_array($array)){
			$error = json_encode(array("result"=>false,"message"=>"Array is invalid "));
			die($error);
		}elseif(!is_array($where) || empty($where)){
			$error = json_encode(array("result"=>false,"message"=>"Where Array is invalid "));
			die($error);
		}
		$sql = "UPDATE $table SET ";
		foreach ($array as $key => $value) {
			$sql.=" $key = '$value' ,";
		}
		$sql = rtrim($sql,",");

		$sql.="WHERE ";
		foreach ($where as $key => $value) {
			$sql.=" $key = '$value' AND";
		}
		$sql = rtrim($sql,"AND");
		if($wpdb->query($sql)){
			return true;
		}else{
			return false;
		}

	}
	public function wp_db_delete_record($table=null,$where=null){
		global $wpdb;
		if($table==null){
			$error = json_encode(array("result"=>false,"message"=>"table can't be empty"));
			die($error);
		}elseif(!is_array($where) || empty($where)){
			$error = json_encode(array("result"=>false,"message"=>"Where Array is invalid "));
			die($error);
		}
		
		$sql="DELETE FROM $table ";
		$sql.="WHERE ";
		foreach ($where as $key => $value) {
			$sql.=" $key = '$value' AND";
		}
		$sql = rtrim($sql,"AND");
		if($wpdb->query($sql)){
			return true;
		}else{
			return false;
		}
	}
	public function wp_db_query($sql){
		global $wpdb;
		$results = $wpdb->get_results($sql);
		if(count($results)>0){
			return $results;
		}else{
			return false;
		}
	}





}


$obj = new wp_api();
$obj->wp_create_post("Post title","Hello world",array(1));
