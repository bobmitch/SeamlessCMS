<?php
defined('CMSPATH') or die; // prevent unauthorized access

ob_end_clean(); // IMPORTANT - empty output buffer from template to ensure on JSON is returned

// TODO: endure logged in user is allowed to actually perform these tasks!

// TODO: remove front-end images api model????

$action = Input::getvar('action','STRING');
$mimetypes = array_filter(explode(',',Input::getvar('mimetypes'))); // array_filter ensures empty array if mimetypes is null
// TODO sanitize mimetypes against whitelist

if ($action=='tag_media') {
	$image_ids_string = Input::getvar('id_list','STRING');
	$image_ids = explode(",", $image_ids_string);
	$tag_id = Input::getvar('tag_id',"INT");
	$image_ids_tagged=[];
	$image_ids_failed=[];
	$pdo = CMS::Instance()->pdo;
	foreach ($image_ids as $image_id) {
		// check if already tagged
		$query = "select count(tag_id) as c from tagged where content_id=? and content_type_id=-1 and tag_id=?";
		$stmt = $pdo->prepare($query);
		$stmt->execute(array($image_id, $tag_id));
		$c = $stmt->fetch()->c;
		if (!$c) {
			// not tagged, insert
			$query = "insert into tagged (content_id, tag_id, content_type_id) values (?,?,-1)";
			$stmt = $pdo->prepare($query);
			$ok = $stmt->execute(array($image_id, $tag_id));
			if ($ok) {
				$image_ids_tagged[] = $image_id;
			}
			else {
				$image_ids_failed[] = $image_id;
			}
		}
		else {
			$image_ids_failed[] = $image_id;
		}
	}
	echo '{"success":1,"message":"Tagging finished","tagged":'.json_encode($image_ids_tagged).',"failed":'.json_encode($image_ids_failed).'}';
	exit(0);
}

if ($action=='cleartags_media') {
	$image_ids_string = Input::getvar('id_list','STRING');
	$image_ids = explode(",", $image_ids_string);
	$image_ids_tagged=[];
	$image_ids_failed=[];
	$pdo = CMS::Instance()->pdo;
	foreach ($image_ids as $image_id) {
			$query = "delete from tagged where content_id=? and content_type_id=-1";
			$stmt = $pdo->prepare($query);
			$ok = $stmt->execute(array($image_id));
			if ($ok) {
				$image_ids_tagged[] = $image_id;
			}
			else {
				$image_ids_failed[] = $image_id;
			}
	}
	echo '{"success":1,"message":"Untagging finished","untagged":'.json_encode($image_ids_tagged).',"failed":'.json_encode($image_ids_failed).'}';
	exit(0);
}

if ($action=='delete_media') {
	$image_ids_string = Input::getvar('id_list','STRING');
	$image_ids = explode(",", $image_ids_string);
	$image_ids_tagged=[];
	$image_ids_failed=[];
	$pdo = CMS::Instance()->pdo;
	foreach ($image_ids as $image_id) {
		// clear tags
		$query = "delete from tagged where content_id=? and content_type_id=-1";
		$stmt = $pdo->prepare($query);
		$ok = $stmt->execute(array($image_id));
		// clear media table
		$query = "delete from media where id=?";
		$stmt = $pdo->prepare($query);
		$ok = $stmt->execute(array($image_id));
		// TODO: remove file(s) from /processed or any other thumbnail/resolution cache created in future
		if ($ok) {
			$image_ids_tagged[] = $image_id;
		}
		else {
			$image_ids_failed[] = $image_id;
		}
	}
	echo '{"success":1,"message":"Deleting finished","untagged":'.json_encode($image_ids_tagged).',"failed":'.json_encode($image_ids_failed).'}';
	exit(0);
}

if ($action=='untag_media') {
	// clear single tag from media list
	$image_ids_string = Input::getvar('id_list','STRING');
	$image_ids = explode(",", $image_ids_string);
	$image_ids_untagged=[];
	$image_ids_failed=[];
	$tag_id = Input::getvar('tag_id','NUM');
	$pdo = CMS::Instance()->pdo;
	foreach ($image_ids as $image_id) {
		// clear tags
		$query = "delete from tagged where content_id=? and content_type_id=-1 and tag_id=?";
		$stmt = $pdo->prepare($query);
		$ok = $stmt->execute(array($image_id, $tag_id));
		if ($ok) {
			$image_ids_untagged[] = $image_id;
		}
		else {
			$image_ids_failed[] = $image_id;
		}
	}
	echo '{"success":1,"message":"Untagging finished","untagged":'.json_encode($image_ids_untagged).',"failed":'.json_encode($image_ids_failed).'}';
	exit(0);
}

if ($action=='toggle') {
	// NOT APPLICABLE TO IMAGES!
	$query = "UPDATE tags SET state = (CASE state WHEN 1 THEN 0 ELSE 1 END) where id=?";
	$stmt = CMS::Instance()->pdo->prepare($query);
	$result = $stmt->execute(array($id[0])); // id always array even with single id being passed
	if ($result) {
		CMS::Instance()->queue_message('Toggled state of tag','success', $_SERVER['HTTP_REFERER']);
	}
	else {
		CMS::Instance()->queue_message('Failed to toggle state of tag','danger', $_SERVER['HTTP_REFERER']);
	}
}

if ($action=='publish') {
	// NOT APPLICABLE TO IMAGES!
	$idlist = implode(',',$id);
	$query = "UPDATE tags SET state = 1 where id in ({$idlist})"; // relatively safe - ids already filtered to be INTs only
	$stmt = CMS::Instance()->pdo->prepare($query);
	$result = $stmt->execute(array()); 
	if ($result) {
		CMS::Instance()->queue_message('Published tags','success', $_SERVER['HTTP_REFERER']);
	}
	else {
		CMS::Instance()->queue_message('Failed to publish tags','danger', $_SERVER['HTTP_REFERER']);
	}
}

if ($action=='unpublish') {
	// NOT APPLICABLE TO IMAGES!
	$idlist = implode(',',$id);
	$query = "UPDATE tags SET state = 0 where id in ({$idlist})"; // relatively safe - ids already filtered to be INTs only
	$stmt = CMS::Instance()->pdo->prepare($query);
	$result = $stmt->execute(array()); 
	if ($result) {
		CMS::Instance()->queue_message('Unpublished tags','success', $_SERVER['HTTP_REFERER']);
	}
	else {
		CMS::Instance()->queue_message('Failed to unpublish tags','danger', $_SERVER['HTTP_REFERER']);
	}
}

if ($action=='delete') {
	$idlist = implode(',',$id);
	$query = "DELETE FROM media where id in ({$idlist})"; // relatively safe - ids already filtered to be INTs only
	$stmt = CMS::Instance()->pdo->prepare($query);
	$result = $stmt->execute(array()); 
	if ($result) {
		//CMS::Instance()->queue_message('Deleted tags','success', $_SERVER['HTTP_REFERER']);
		echo '{"success":1,"msg":"Image(s) deleted"}';
		exit(0);
	}
	else {
		//CMS::Instance()->queue_message('Failed to delete tags','danger', $_SERVER['HTTP_REFERER']);
		echo '{"success":0,"msg":"Unable to remove image(s) from database"}';
		exit(0);
	}
}

if ($action=='list_images') {
	// todo: pagination / search
	
	$searchtext = Input::getvar('searchtext','STRING');
	if ($searchtext=='null') {
		$searchtext=null;
	}
	if ($searchtext) {
		$query = "select * from media where title like ? or alt like ?";
		if ($mimetypes) {
			$query.=" AND mimetype in (";
			for ($n=0; $n<sizeof($mimetypes); $n++) {
				if ($n>0) {
					$query .= ",";
				}
				$query .= CMS::Instance()->pdo->quote($mimetypes[$n]);
			}
			$query.=") ";
		}
		
		$stmt = CMS::Instance()->pdo->prepare($query);
		$stmt->execute(array("%$searchtext%","%$searchtext%"));
	}
	else {
		$query = "select * from media";
		if ($mimetypes) {
			$query.=" where id>0 ";
		}
		if ($mimetypes) {
			$query.=" AND mimetype in (";
			for ($n=0; $n < sizeof($mimetypes); $n++) {
				if ($n>0) {
					$query .= ",";
				}
				$query .= CMS::Instance()->pdo->quote($mimetypes[$n]);
			}
			$query.=") ";
		} 
		$stmt = CMS::Instance()->pdo->prepare($query);
		$stmt->execute(array());
	}
		
	$list = $stmt->fetchAll();
	echo '{"success":1,"msg":"Images found ok","images":'.json_encode($list).'}';
	exit(0);
}

if ($action=='rename_image') {
	$title = Input::getvar('title','STRING');
	$alt = Input::getvar('alt','STRING');
	$image_id = Input::getvar('image_id','NUM');
	$query = "update media set title=?, alt=? where id=?";
	$stmt = CMS::Instance()->pdo->prepare($query);
	$result = $stmt->execute(array($title, $alt, $image_id));
	if ($result) {
		echo '{"success":1,"msg":"Image renamed"}';
		exit(0);
	}
	else {
		echo '{"success":0,"msg":"Problem renaming image"}';
		exit(0);
	}
}

echo '{"success":0,"msg":"Unknown operation requested"}';
exit(0);

