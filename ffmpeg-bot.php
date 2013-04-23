<?php
	// FFmpeg encoder Bot for MediaCore
	// Code by Valerio Minetti 	
	// valerio.minetti@gmail.com

	// =====================================
	// Configuration: 
	// edit this to adapt bot to your environment/paths
	// =====================================
	$db_host="localhost";
	$db_user="root";
	$db_password="root";
	$db_name="mediacore";
	// Mediacore Media directory, note the trailing slash
	$mediacore_path="/opt/mediacore/data/media/"; 
	// Bot sleep duration in secs between checks
	$sleep_duration= 10;
	// End Of Configuration
	// =====================================
	
	
	
	// Bot Loop

while(true){
	// Db connect (change your connection credentials)
	$link = mysql_connect($db_host, $db_user, $db_password);
	if (!$link) {
	    die('Could not connect: ' . mysql_error());
	}
	mysql_select_db($db_name, $link);
	$select_query = "select media.author_email, media.id as mediaid,media_files.id,media_files.type,container,unique_id,media_files.storage_id from media_files left join media on (media_files.media_id = media.id) where (media.encoded=0) and (media_files.storage_id=1);";
	$unencoded_media = mysql_query($select_query) or die(mysql_error());
	//echo 'Connected successfully';

	// print unencoded media
	while($row = mysql_fetch_assoc($unencoded_media)){
	    foreach($row as $cname => $cvalue){
        	print "$cname: $cvalue\t";
	    }
	    print "\r\n";

	    $media_file_name= $row['unique_id'];
	    $media_id = $row['mediaid'];
	    $author_email = $row['author_email'];
	    // insert new media file into mediacore tables
	    // get width & height
		exec("ffmpeg -i ".$mediacore_path.$media_file_name." 2>&1 |grep '\[PAR'", $out);
                $dimension = explode(" ", trim(current(explode('[PAR', current($out)))))[6];
		$width= explode("x",$dimension)[0];
		$height= explode("x",$dimension)[1];
	    // get bitrate
		exec("ffmpeg -i ".$mediacore_path.$media_file_name." 2>&1 |grep 'bitrate:'", $out1);
		$brate = trim(str_replace('bitrate:', NULL, end(explode(',', current($out1)))));
		//echo "bitrate";
		//print_r($brate);

	    // extract filename without extension
	    // TODO: encoding presets support
	    $encoded_bitrate = "800k";
	    $encoded_width = 640;
	    $encoded_height = 360;
    	    $encoded_file_name = explode("-",pathinfo($media_file_name,PATHINFO_FILENAME))[1]."-". $encoded_width."x".$encoded_height."-".$encoded_bitrate.".mp4";
	    echo $encoded_file_name."\n";

	    // transcode file
	    // TODO: support for multiple profiles
	    echo "Starting ffmpeg with ".$media_file_name."...\n\n";
	    echo shell_exec("ffmpeg -y -i ".$mediacore_path.$media_file_name."  -vcodec libx264  -vpre slow -b:v ".$encoded_bitrate." -s ".$encoded_width."x".$encoded_height."  -acodec libvo_aacenc -ac 2 -ar 44.1k -b:a 128k ".$mediacore_path.$encoded_file_name." </dev/null >/dev/null 2>/var/log/ffmpeg.log ");
	    echo "Encoding Done.\n";

	    // insert media file in mediacore db
	    $insert_query="insert into mediacore.media_files ( media_id, storage_id, display_name, unique_id,size, created_on,modified_on,bitrate, width, height, type, container ) values ('".$media_id."','1','".$encoded_file_name."','".$encoded_file_name."','".filesize("/opt/mediacore/data/media/". $encoded_file_name)."','','','".$encoded_bitrate."','".$encoded_width."','".$encoded_height."', 'video', 'mp4')";
	    $result = mysql_query($insert_query) or die(mysql_error());

	    // TODO: figure out media_file id prior db insert, avoiding update
	    // get file id
	    $select_query="select id from mediacore.media_files where unique_id='".$encoded_file_name."'";
	    $result = mysql_query($select_query) or die(mysql_error());
	    $new_id = mysql_fetch_assoc($result);
	    // rename and update transcoded media file and db row
	    rename($mediacore_path.$encoded_file_name, $mediacore_path.$new_id['id']."-".$encoded_file_name );
	    $update_query= "update mediacore.media_files set unique_id='".$new_id['id']."-".$encoded_file_name."' where id='".$new_id['id']."';";
	    $update_query1="update mediacore.media set encoded='1' where id='".$media_id."';";
	    $result = mysql_query($update_query) or die(mysql_error());
	    $result = mysql_query($update_query1) or die(mysql_error());

	    // send email to media owner
	    $to = $author_email;
	    $subject = "Mediacore event: codifica video terminata";
	    $message = "Buongiorno, \n la codifica del video \n '".$media_file_name."' da lei caricato e' terminata con successo.";
	    $from = "mediacore@didattica.unimib.it";
	    $headers = "From:" . $from;
	    mail($to,$subject,$message,$headers);

	}
	
	mysql_close($link);

	// Sleep till next check
	sleep($sleep_duration);
}
?>
