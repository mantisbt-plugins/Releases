<?php

require_once('file_api.php');

function releases_plugin_page_path($p_page_name)
{
    return plugin_route_group() . '/pages/' . $p_page_name . '.php';
}

function releases_plugin_page_url($p_page_name)
{
    return helper_mantis_url(releases_plugin_page_path($p_page_name));
}
/*
*/
function releases_plugin_page_title($p_project_name, $p_page_title)
{
    //echo '<div width="500"><div class="row">';
    //echo '<div class="col-md-12 col-xs-12">';
    echo '<div class="page-header">';
    echo '<h1><strong>' . $p_project_name, '</strong> - ', $p_page_title  . '</h1>';
    echo '</div>';
    //echo '</div>';
    //echo '</div></div>';
}

function releases_plugin_release_title($p_project_name, $p_release_title, $p_release_version)
{
    $t_block_id = 'release_' . str_replace(' ', '_', $p_project_name) . '_' . $p_release_version;
    $t_collapse_block = is_collapsed($t_block_id);
    $t_block_css = $t_collapse_block ? 'collapsed' : '';
    $t_block_icon = $t_collapse_block ? 'fa-chevron-down' : 'fa-chevron-up';

    echo '<div id="' . $t_block_id . '" class="widget-box widget-color-blue2 no-border ' . $t_block_css . '">';
    echo '  <div class="widget-header widget-header-small">';
    echo '    <h4 class="widget-title lighter">';
    echo '    <i class="ace-icon fa fa-retweet"></i>';
    echo $p_release_title, lang_get('word_separator');
    echo '    </h4>';
    echo '    <div class="widget-toolbar">';
	echo '      <a data-action="collapse" href="#">';
	echo '        <i class="1 ace-icon fa ' . $t_block_icon . ' bigger-125"></i>';
	echo '      </a>';
	echo '    </div>';
    echo '  </div>';
    echo '  <div class="widget-body">';
	echo '    <div class="widget-main">';
}

function releases_plugin_section_title($p_title, $p_fa_icon, $p_block_id)
{
    $t_block_id = $p_block_id;
    $t_collapse_block = is_collapsed($t_block_id);
    $t_block_css = $t_collapse_block ? 'collapsed' : '';
    $t_block_icon = $t_collapse_block ? 'fa-chevron-down' : 'fa-chevron-up';

    echo '<div id="' . $t_block_id . '" class="widget-box widget-color-blue2  no-border ' . $t_block_css . '">';
    echo '  <div class="widget-header widget-header-small">';
    echo '    <h4 class="widget-title lighter">';
    echo '    <i class="ace-icon fa ' . $p_fa_icon . '"></i>';
    echo $p_title, lang_get('word_separator');
    echo '    </h4>';
	echo '    <div class="widget-toolbar">';
	echo '      <a data-action="collapse" href="#">';
	echo '        <i class="1 ace-icon fa ' . $t_block_icon . ' bigger-125"></i>';
	echo '      </a>';
	echo '    </div>';
    echo '  </div>';
    echo '  <div class="widget-body">';
	echo '    <div class="widget-main">';
}


function releases_plugin_config_section_title($p_title, $p_fa_icon)
{
    $t_block_css = $t_collapse_block ? 'collapsed' : '';
    $t_block_icon = $t_collapse_block ? 'fa-chevron-down' : 'fa-chevron-up';

    echo '<div id="' . $t_block_id . '" class="widget-box widget-color-blue2  no-border ' . $t_block_css . '">';
    echo '  <div class="widget-header widget-header-small">';
    echo '    <h5 class="widget-title lighter">';
    echo '    <i class="ace-icon fa ' . $p_fa_icon . '"></i>';
    echo $p_title, lang_get('word_separator');
    echo '    </h5>';
    echo '  </div>';
    echo '</div>';
}


function releases_plugin_config_section_subtitle($p_title, $p_fa_icon)
{
    $t_block_css = $t_collapse_block ? 'collapsed' : '';
    $t_block_icon = $t_collapse_block ? 'fa-chevron-down' : 'fa-chevron-up';

    echo '<div id="' . $t_block_id . '" class="widget-box widget-color-blue2  no-border ' . $t_block_css . '">';
    echo '  <div class="widget-header widget-header-small">';
    echo '    <h5 class="widget-title lighter">';
    echo '    <i class="ace-icon fa ' . $p_fa_icon . '"></i>';
    echo $p_title, lang_get('word_separator');
    echo '    </h5>';
    echo '  </div>';
    echo '</div>';
}

function releases_max_upload_size()
{
        $t_max_sizes = array(
            'max_file_ini_upload' => ini_get_number('upload_max_filesize'),
            'max_file_ini_post'   => ini_get_number('post_max_size'),
//            'max_file_mantis_cfg' => config_get('max_file_size'),
       );
        $t_max_key = '';
        $t_max_size = 0x7FFFFFFF;
        foreach($t_max_sizes as $key => $size)
        {
            if($size < $t_max_size)
            {
                $t_max_size = $size;
                $t_max_key  = $key;
            }
        }
        return array($t_max_size, $t_max_key);
}


/**
 *
 * @todo Not yet converted to the new plugin system
 */
function plugins_releases_file_ftp_connect() {
    $conn_id = ftp_connect(config_get('plugins_releases_ftp_server', PLUGINS_RELEASES_FTP_SERVER_DEFAULT));
    $login_result = ftp_login($conn_id, config_get('plugins_releases_ftp_user', PLUGINS_RELEASES_FTP_USER_DEFAULT), config_get('plugins_releases_ftp_pass', PLUGINS_RELEASES_FTP_USER_DEFAULT));

    if ((!$conn_id) || (!$login_result)) {
        trigger_error(ERROR_FTP_CONNECT_ERROR, ERROR);
    }

    return $conn_id;
}

function plugins_releases_file_get_field($p_file_id, $p_field_name) {
    $c_field_name = db_prepare_string($p_field_name);
    $t_file_table = plugin_table('file'); #, 'Releases');

    $query = "SELECT $c_field_name
				  FROM $t_file_table
				  WHERE id=" . $p_file_id;
    $result = db_query($query);
    return db_result($result);
}

function plugins_releases_file_delete_all($p_release_id) 
{
    $t_query = 'SELECT id FROM ' . plugin_table('file') . ' WHERE release_id=' . $p_release_id;
    $t_result = db_query($t_query);
    if (db_num_rows($t_result) == 0)
        return true;
    while ($t_row = db_fetch_array($t_result)) {
        plugins_releases_file_delete($t_row['id']);
    }
    return true;
}

function plugins_releases_file_delete($p_file_id) {
    $t_upload_method = plugin_config_get('upload_method', UPLOAD_METHOD_DEFAULT); #, false, null, null, 'Releases');

    $t_filename = plugins_releases_file_get_field($p_file_id, 'filename');
    $t_diskfile = plugins_releases_file_get_field($p_file_id, 'diskfile');

    if((DISK == $t_upload_method) || (FTP == $t_upload_method)) {
        /* IK! FTP can't be used with DISK
        if (FTP == $t_upload_method) {
            $ftp = plugins_releases_file_ftp_connect();
            file_ftp_delete($ftp, $t_diskfile);
            file_ftp_disconnect($ftp);
        }
        */

        if (file_exists($t_diskfile)) {
            file_delete_local($t_diskfile);
        }
    }

    $t_file_table = plugin_table('file'); #, 'Releases');
    $query = "DELETE FROM $t_file_table
				WHERE id=" . $p_file_id;
    $result = db_query($query);
    return true;
}

function plugins_releases_file_enable($p_file_id, $p_enable) {
    $t_file_table = plugin_table('file'); #, 'Releases');
    $query = "UPDATE $t_file_table"
	   . ' SET enabled=' . ($p_enable ? 1 : 0)
	   . ' WHERE id=' . $p_file_id;
    $result = db_query($query);
    return true;
}

function plugins_releases_file_generate_unique_name($p_seed, $p_filepath) {
    $t_string = $p_seed;
    while (!plugins_releases_diskfile_is_name_unique($t_string , $p_filepath))
    {
        $t_string = file_generate_unique_name($p_seed);
    }
    return $t_string;
}

function plugins_releases_diskfile_is_name_unique($p_name, $p_filepath) {
    $t_file_table = plugin_table ('file'); #, 'Releases');

    $c_name = db_prepare_string($p_filepath . $p_name);

    $query = "SELECT COUNT(*)
				  FROM $t_file_table
				  WHERE diskfile='" . $c_name . "'";
    $result = db_query($query);
    $t_count = db_result($result);

    return $t_count < 1;
}

function plugins_releases_file_is_name_unique($p_name, $p_project_id, $p_version_id) {
    $t_file_table = plugin_table('file'); #, 'Releases');

    $c_name = db_prepare_string($p_name);
    $c_project_id = db_prepare_int($p_project_id);
    $c_version_id = db_prepare_int($p_version_id);

    $query = "SELECT COUNT(*)
				  FROM $t_file_table
				  WHERE filename='" . $c_name . "' AND project_id=" . $p_project_id . " AND version_id=" . $p_version_id;
    $result = db_query($query);
    $t_count = db_result($result);

    return $t_count < 1;
}

function plugins_releases_file_add($p_tmp_file, $p_file_name, $p_file_type, $p_project_id, $p_version_id, $p_release_id, $p_description, $p_file_error) {
    if (version_compare(PHP_VERSION, '4.2.0') >= 0) {
        switch ((int) $p_file_error) {
          case UPLOAD_ERR_INI_SIZE:
          case UPLOAD_ERR_FORM_SIZE:
            trigger_error(ERROR_FILE_TOO_BIG, ERROR);
            break;
          case UPLOAD_ERR_PARTIAL:
          case UPLOAD_ERR_NO_FILE:
            trigger_error(ERROR_FILE_NO_UPLOAD_FAILURE, ERROR);
            break;
          default:
            break;
        }
    }

    if (('' == $p_tmp_file) || ('' == $p_file_name)) {
        trigger_error(ERROR_FILE_NO_UPLOAD_FAILURE, ERROR);
    }
    if (!is_readable($p_tmp_file)) {
        trigger_error(ERROR_UPLOAD_FAILURE, ERROR);
    }

    if (!plugins_releases_file_is_name_unique($p_file_name, $p_project_id, $p_version_id)) {
        trigger_error(ERROR_DUPLICATE_FILE, ERROR);
    }

    $c_file_type = db_prepare_string($p_file_type);
    $c_title = db_prepare_string($p_file_name);
    $c_desc = db_prepare_string($p_description);

    $t_file_path = dirname(plugin_config_get('disk_dir', PLUGINS_RELEASES_DISK_DIR_DEFAULT) /*, false, null, null, 'Releases')*/ . DIRECTORY_SEPARATOR . '.') . DIRECTORY_SEPARATOR;

    $c_file_path = db_prepare_string($t_file_path);
    $c_new_file_name = db_prepare_string($p_file_name);

    $t_file_hash = $p_version_id . '-' . $p_project_id;
    $t_disk_file_name = $t_file_path . plugins_releases_file_generate_unique_name($t_file_hash . '-' . $p_file_name, $t_file_path);
    $c_disk_file_name = db_prepare_string($t_disk_file_name);

    $t_file_size = filesize($p_tmp_file);
    if (0 == $t_file_size) {
        trigger_error(ERROR_FILE_NO_UPLOAD_FAILURE, ERROR);
    }
    //$t_max_file_size = (int)min(ini_get_number('upload_max_filesize'), ini_get_number('post_max_size'), config_get('max_file_size'));
    $t_max_file_size = (int)min(ini_get_number('upload_max_filesize'), ini_get_number('post_max_size'));
    if ($t_file_size > $t_max_file_size) {
        trigger_error(ERROR_FILE_TOO_BIG, ERROR);
    }

    $t_method = plugin_config_get('upload_method', PLUGINS_RELEASES_UPLOAD_METHOD_DEFAULT); #, false, null, null, 'Releases');

//echo "DBG: -3, $t_file_path, method=$t_method<BR>\n";
    switch ($t_method) {
      //case FTP:
      case DISK:
        file_ensure_valid_upload_path($t_file_path);
//echo "DBG: -2, $t_file_path<BR>\n";

        if (!file_exists($t_disk_file_name)) {
//echo "DBG: -1, $t_file_path<BR>\n";
/* IK. FTP can't be used here because in Mantis FTP === DISK
            if (FTP == $t_method) {
echo "DBG: 0:0, $t_method, FTP<BR>\n";
                $conn_id = plugins_releases_file_ftp_connect();
                file_ftp_put($conn_id, $t_disk_file_name, $p_tmp_file);
                file_ftp_disconnect($conn_id);
            }
*/
//echo "DBG: 1<BR>\n";
            if ((int)$p_file_error != 1001) {
                if (!move_uploaded_file($p_tmp_file, $t_disk_file_name)) {
                    trigger_error(FILE_MOVE_FAILED, ERROR);
                }
            }
            else if (!rename($p_tmp_file, $t_disk_file_name)) {
                trigger_error(FILE_MOVE_FAILED, ERROR);
            }
//echo "DBG: 2<BR>\n";

            chmod($t_disk_file_name, 0644);
//echo "DBG: 3<BR>\n";

            $c_content = '';
        } else {
            trigger_error(ERROR_FILE_DUPLICATE, ERROR);
        }
        break;
      case DATABASE:
        $c_content = db_prepare_binary_string(fread(fopen($p_tmp_file, 'rb'), $t_file_size));
        break;
      default:
        trigger_error(ERROR_GENERIC, ERROR);
    }

    #
	# Get current user
	#
	$current_user_id = auth_get_current_user_id();
	$current_user = user_get_username($current_user_id);
    $t_date_fmt = date("Y-m-d H:i:s");
    $t_file_table = plugin_table('file');
    $query = "INSERT INTO $t_file_table (release_id,project_id,version_id,user,title,description,diskfile,filename,folder,filesize, " .
			                            "file_type, date_added, content) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
    db_query($query, array($p_release_id, $p_project_id, $p_version_id, $current_user,  $c_title, $c_desc, $c_disk_file_name, 
                           $c_new_file_name, $c_file_path, $t_file_size, $c_file_type, $t_date_fmt, $c_content));
    $t_file_id = db_insert_id($t_file_table);
    return $t_file_id;
}

/**
 * Retaken function form print_api.php but it prints redirection message everytime
 * @param type $p_redirect_to
 */
function release_mgt_successful_redirect($p_redirect_to, $p_version = "") {
    print_successful_redirect(plugin_page($p_redirect_to, true) . ($p_version != "" ? "#release_app-publisher_$p_version" : ""));
}
