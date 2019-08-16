<?php

require_once( __DIR__ . '/../../../../vendor/erusev/parsedown/Parsedown.php');
require_once('bug_api.php' );
require_once('constant_api.php');
require_once('releases_api.php');
require_once('releases_email_api.php');

$g_app->group('/releases', function() use ($g_app) 
{
	$g_app->get('/{project}/{id}', 'releases_get');
	$g_app->get('/{project}/{id}/', 'releases_get');

	$g_app->post('/{project}', 'release_add');
	$g_app->post('/{project}/', 'release_add');

	$g_app->put('/{project}/{id}', 'release_update');
	$g_app->put('/{project}/{id}/', 'release_update');

	$g_app->delete('/{project}/{id}', 'release_delete');
	$g_app->delete('/{project}/{id}/', 'release_delete');
});


function releases_get(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	#
	# Ensure valid project was provided by client
	#
	$project = isset($p_args['project']) ? $p_args['project'] : $p_request->getParam('project');
	if (is_blank($project)) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'project_id' is missing.");
	} 
	else {
		$project_id = project_get_id_by_name($project, false);
		if ($project_id == null) {
			return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "The field 'project' is invalid.");
		}
	}

	$t_release_id = isset($p_args['id']) ? $p_args['id'] : $p_request->getParam('id');
	if (is_blank($t_release_id)) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'id' is missing.");
	}

	$t_release_id = (int)$t_release_id;
	if ($t_release_id < 1) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Invalid release id.");
	}

	$t_user_id = auth_get_current_user_id();
	$t_lang = mci_get_user_lang($t_user_id);

	$project_ids = user_get_all_accessible_projects($t_user_id, $project_id);
	$t_projects = array();

	foreach($project_ids as $project_id) {
		$t_project = mci_project_get($project_id, $t_lang, /* detail */ true);
		$t_subproject_ids = user_get_accessible_subprojects($t_user_id, $project_id);
		if (!empty($t_subproject_ids)) {
			$t_subprojects = array();
			foreach($t_subproject_ids as $t_subproject_id) {
				$t_subprojects[] = mci_project_as_array_by_id($t_subproject_id);
			}

			$t_project['subProjects'] = $t_subprojects;
		}

		$t_projects[] = $t_project;
	}

	$t_result = array('projects' => $t_projects);

	return $p_response->withStatus(HTTP_STATUS_SUCCESS)->withJson($t_result);
}


function getTempDir() 
{
	if (!function_exists('sys_get_temp_dir'))
	{
		function sys_get_temp_dir() 
		{
			if (($tmp = getenv('TMPDIR')) || ($tmp = getenv('TMP')) ||
				($tmp = getenv('TEMP')) || ($tmp = ini_get('upload_tmp_dir')))
				return $tmp;
			$tmp = tempnam(__FILE__, '');
			if (file_exists($tmp)) {
				unlink($tmp);
				return dirname($tmp);
			}
			return null;
		}
	}
	return sys_get_temp_dir();
}


function get_mime_type($filename) 
{
    $idx = explode( '.', $filename );
    $count_explode = count($idx);
    $idx = strtolower($idx[$count_explode-1]);

	$mimet = array( 
        'txt' => 'text/plain',
        'md' => 'text/markdown',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.ms-powerpoint',


        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );

    if (isset( $mimet[$idx] )) {
     return $mimet[$idx];
    } else {
     return 'application/octet-stream';
    }
}


function release_add(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	$rtnMessage = '';

	#
	# Ensure valid project was provided by client
	#
	$project = isset($p_args['project']) ? $p_args['project'] : $p_request->getParam('project');
	if (is_blank($project)) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'project' is missing.");
	} 
	else {
		$project_id = project_get_id_by_name($project, false);
		if ($project_id == null) {
			return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "The field 'project' is invalid.");
		}
	}

	#
	# Get current user and check if upload access is granted
	#
	$current_user_id = auth_get_current_user_id();
	if( !access_has_project_level(plugin_config_get('upload_threshold_level', PLUGINS_RELEASES_UPLOAD_THRESHOLD_LEVEL_DEFAULT), $project_id, $current_user_id)) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Denied, user does not have upload access");
	}
	$current_user = user_get_username($current_user_id);

	#
	# Parse payload
	#
	$payload = $p_request->getParsedBody();
	if ($payload === null) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Unable to parse body, specify content type");
	}

	#
	# Dry run?
	#
	$dryRun = isset($payload['dryrun']) && (bool)$payload['dryrun'];
	#
	# If that isnt a dry run...
	#
	if (!$dryRun) {
		$rtnMessage = "Success";
	}
	else {
		$rtnMessage = "Dry Run Results: ";
	}

	#
	# Get version provided by client
	#
	if (!isset($payload['version'])) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'version' is missing in payload.");
	}
	$version = $payload['version'];

	#
	# Get tag provided by client
	#
	$tag = "";
	if (isset($payload['tag'])) {
		$tag = $payload['tag'];
	}

	#
	# Get changelog/release description provided by client
	#
	if (!isset($payload['notes'])) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'notes' is missing in payload.");
	}
	$notes = $payload['notes'];
	#
	# Convert markdown changelog to HTML content for display, if specified by client
	#
	if (isset($payload['notesismd']) && (bool)$payload['notesismd']) {
		$Parsedown = new Parsedown();
		$notes = $Parsedown->text($notes);
	}

	#
	# Convert ticket numbers to links
	# Must be in the form [fixes #24], [closes #3554,#3521], [references #436]
	#
	#$t_regex = "/\[(&nbsp;| )*(closes|fixes|resolves|fix|close|refs|references|ref|reference){1}(&nbsp;| )*#[0-9]+((&nbsp;| )*,(&nbsp;| )*#[0-9]+){0,}(&nbsp;| )*\]/i";
	#preg_match_all($t_regex, $notes, $t_matches_all);
	#foreach ($t_matches_all[0] as $t_substring) 
	#{
	#	$value = $t_substring;
	#	$vi1 = strpos($value, "#");
	#	$vi2 = strpos($value, "]");
	#	$bugid = substr($value, $vi1, $vi2 - $vi1);
	#	$bugids = explode(",", $bugid);
	#	for ($i = 0; $i < count($bugids); $i++) {
	#		$bid = str_replace("&nbsp;", "", $bugids[$i]); # with # i.e. #1919
	#		$bid_num = str_replace("#", "", $bid); # w/o # i.e. 1919
	#		$value = str_replace($bid, "<a href=\"view.php?id=$bid_num\">$bid</a>", $value);
	#	}
	#	# color resolved tags gray, create links to tickets
	#	$notes = str_replace($t_substring, "<font style=\"color:gray\">$value</font>", $notes);
	#}

	#
	# Convert ticket numbers to links
	#
	$t_regex = "/(?:bugs?|issues?|refs?|references?|reports|fixe?d?s?|closed?s?|resolved?s?)+\s*:?\s+(?:#(?:\d+)[,\.\s]*)+/i";
	preg_match_all( $t_regex, $notes, $t_matches_all );
	foreach( $t_matches_all[0] as $t_substring ) {
		$value = $t_substring;
		$vi1 = strpos($value, "#");
		$bugid = substr($value, $vi1, strlen($value) - $vi1);
		$bugids = explode(",", $bugid);
		for ($i = 0; $i < count($bugids); $i++) {
			$bid = str_replace("&nbsp;", "", $bugids[$i]); # with # i.e. #1919
			$bid_num = str_replace("#", "", $bid); # w/o # i.e. 1919
			$value = str_replace($bid, "<a href=\"view.php?id=$bid_num\">$bid</a>", $value);
		}
		# color resolved tags gray, create links to tickets
		$notes = str_replace($t_substring, "<font style=\"color:gray\">$value</font>", $notes);
	}

	#
	# Check version and get version id
	#
	# Create version if it doesnt exist, update 'released' to true if it does
	#
	if (version_is_unique($version, $project_id)) {
		$version_id = version_add($project_id, $version, VERSION_RELEASED);
	}
	else {
		$version_id = version_get_id($version, $project_id);
		$version_info = version_get($version_id);
		$version_info->released = VERSION_RELEASED;
		$version_info->date_order = db_now();
		if (!$dryRun) {
			version_update($version_info);
		}
		else {
			$rtnMessage = $rtnMessage . "Version update $version to 'released'\n";
		}
	}

	#
	# Get the database table for 'release'
	#
	$dbTable = plugin_table('release');

	#
	# Check to make sure a release for this version does not already exist
	# If it doesnt, then create it, if it does, get the release id, we will still add assets
	# to the existing release next
	#
	$query = "SELECT COUNT(*) FROM $dbTable WHERE version_id=".$version_id;
    $result = db_query($query);
	$rowCount = db_result($result);
	
	$t_version_name = 'Version ' . $version;
	$t_date_fmt = date("Y-m-d H:i:s");

	#
	# Create the release in database
	#
	if ($rowCount < 1)
	{
		$query = "INSERT INTO $dbTable (project_id, version_id, title, description, date_created, user) VALUES (?, ?, ?, ?, ?, ?)";
		if (!$dryRun) {
			db_query($query, array($project_id, $version_id, $t_version_name, $notes, $t_date_fmt, $current_user));
			$release_id = db_insert_id($dbTable);
		}
		else {
			$query = "INSERT INTO $dbTable (project_id, version_id, title, description, date_created, user) VALUES ".
				     "(" . $project_id . ", " . $version_id . ", '" . $t_version_name . "', ?, '" . $t_date_fmt . "', '" . $current_user . "')";
			$release_id = 0;
			$rtnMessage = $rtnMessage . "Release SQL: " . $query . "\n";
		}
	}
	elseif (!empty($notes))
	{
		$query = "SELECT id FROM $dbTable WHERE version_id=" . $version_id . " ORDER BY id DESC";
		if ($dryRun) {
			$release_id = 0;
			$rtnMessage = $rtnMessage . "Release SQL: " . $query . "\n";
		}
		else {
			$result = db_query($query);
			$release_id = db_result($result);
		}
		#
		# Update description, date_modified
		#
		$query = "UPDATE $dbTable SET description=? WHERE id=" . $release_id;
		if ($dryRun) {
			$rtnMessage = $rtnMessage . "Release SQL: " . $query . "\n";
		}
		else {
			db_query($query, array($notes));
		}
	}

	#
	# Process files/assets
	#
	if (isset($payload['assets']) && !empty($payload['assets'])) 
	{
		$fileCount = 0;

		$assets = $payload['assets'];
		foreach ($assets as $asset)
		{
			$fileCount++;
			#
			# Check file mime type, set if not provided by client
			#
			if (!isset($asset['type'])) 
			{
				if (strpos($asset['name'], ".") == FALSE) {
					return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Field 'asset.type' is required for extensionless filenames.");
				}
				$asset['type'] = get_mime_type($asset['name']);
			}
			if (strpos($asset['type'], "/") == FALSE) {
				return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Field 'asset.type' is invalid for '".$asset['name']."'");
			}
			#
			# Write data to temp file for a call to plugins_releases_file_add()
			# File data received from the client should be Base64 encoded
			#
			$fileName = getTempDir().'/'.$asset['name'];
			$fileData = base64_decode($asset['data']);
			file_put_contents($fileName, $fileData);

			#
			# Add asset to database
			# Code 1001 forces use of rename() instead of move_uploaded_file()
			#
			if (!$dryRun) {
				plugins_releases_file_add($fileName, $asset['name'], $asset['type'], $project_id, $version_id, $release_id, $asset['desc'], 1001);
			}
			else {
				$rtnMessage = $rtnMessage . "Add asset " . $asset['name'] . " with mime type " . $asset['type'] . "\n";
			}
		}

		#
		# TODO - Send email notification if required
		#
		#if (!$dryRun && $fileCount > 0 && plugin_config_get('notification_enable', PLUGINS_RELEASES_NOTIFICATION_ENABLE_DEFAULT) == ON) {
			# following fn needs to be re-worked as the orig function sent email "per" asset uploaded in the html form (huh?)
			# releases_plugin_send_email( $project_id, $version, $t_file, $cptDesc, $t_file_id );
		#}
	}

	$response = array(
		'id' => $release_id,
		'msg' => $rtnMessage
	);

	return $p_response->withStatus(HTTP_STATUS_CREATED, "Release created with id $release_id")->withJson($response);
}


function release_update(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	#
	# Ensure valid project was provided by client
	#
	$project = isset($p_args['project']) ? $p_args['project'] : $p_request->getParam('project');
	if (is_blank($project)) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'project_id' is missing.");
	} 
	else {
		$project_id = project_get_id_by_name($project, false);
		if ($project_id == null) {
			return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "The field 'project' is invalid.");
		}
	}

	$release_id = isset($p_args['id']) ? $p_args['id'] : $p_request->getParam('id');
	if (is_blank($release_id)) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'id' is missing.");
	}

	$release_id = (int)$release_id;
	if ($release_id < 1) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Invalid release id.");
	}

	$payload = $p_request->getParsedBody();

	#
	# Dry run?
	#
	$dryRun = isset($payload['dryrun']) && (bool)$payload['dryrun'];

	#if (isset($payload['id']) && $payload['id'] != $project_id) {
	#	return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Project id mismatch");
	#}

	#$t_user_id = auth_get_current_user_id();
	#$t_lang = mci_get_user_lang($t_user_id);

	#$t_project = mci_project_get($project_id, $t_lang, /* detail */ true);
	#$t_project = array_merge($t_project, $payload);

	#$success = mc_project_update(/* username */ '', /* password */ '', $project_id, (object)$t_project);
	#ApiObjectFactory::throwIfFault($success);

	return $p_response->withStatus(HTTP_STATUS_SUCCESS, "Release with id $release_id Updated")
		->withJson(array('release' => $release_id));
}


function release_delete(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args) 
{
	#
	# Ensure valid project was provided by client
	#
	$project = isset($p_args['project']) ? $p_args['project'] : $p_request->getParam('project');
	if (is_blank($project)) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'project_id' is missing.");
	} 
	else {
		$project_id = project_get_id_by_name($project, false);
		if ($project_id == null) {
			return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "The field 'project' is invalid.");
		}
	}

	$release_id = isset($p_args['id']) ? $p_args['id'] : $p_request->getParam('id');
	if (is_blank($release_id)) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Mandatory field 'release_id' is missing.");
	}

	$release_id = (int)$release_id;
	if ($release_id < 1) {
		return $p_response->withStatus(HTTP_STATUS_BAD_REQUEST, "Invalid release id.");
	}

	#$t_user_id = auth_get_current_user_id();
	#if (!project_exists($project_id) || !access_has_project_level(config_get('delete_project_threshold', null, $t_user_id, $project_id), $project_id)) {
	#	return $p_response->withStatus(HTTP_STATUS_FORBIDDEN, "Access denied for deleting project.");
	#}

	#project_delete($project_id);

	return $p_response->withStatus(HTTP_STATUS_SUCCESS, "Release with id $release_id deleted.");
}
