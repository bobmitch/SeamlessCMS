<?php
defined('CMSPATH') or die; // prevent unauthorized access

ob_end_clean(); // IMPORTANT - empty output buffer from template to ensure on JSON is returned

// TODO: endure logged in user is allowed to actually perform these tasks!

// TODO: Has this moved to /admin/images/api ???????

$action = Input::getvar('action','STRING');

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
	echo '{"success":1,"message":"Untagging finished","untagged":'.json_encode($image_ids_tagged).',"failed":'.json_encode($image_ids_failed).'}';
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
	$query = "DELETE FROM images where id in ({$idlist})"; // relatively safe - ids already filtered to be INTs only
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
	// todo: pagination
	$query = "select * from images";
	$stmt = $pdo->prepare($query);
	$stmt->execute(array($image_id, $tag_id));
	$list = $stmt->fetchAll();
	echo '{"success":1,"msg":"Images found ok","images":'.json_encode($list).'}';
}

echo '{"success":0,"msg":"Unknown operation requested"}';
exit(0);

