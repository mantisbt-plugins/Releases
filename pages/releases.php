<?php

function size_display($bytes)
{
    $unit = intval(log($bytes, 1024));
    $units = array('B', 'KB', 'MB', 'GB');

    if (array_key_exists($unit, $units) === true)
        return sprintf('%4.1f %s', $bytes / pow(1024, $unit), $units[$unit]);

    return $bytes;
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }
    return (substr($haystack, -$length) === $needle);
}

require_once('core.php');
require_once('bug_api.php');
require_once('releases_api.php');
require_once('constant_api.php');

layout_page_header(plugin_lang_get('display_page_title'));
layout_page_begin(plugin_page('releases'));

$t_user_id = auth_get_current_user_id();
$t_project_id = helper_get_current_project();
$t_releases = null;

if ($t_project_id != ALL_PROJECTS)
{
    $t_releases = version_get_all_rows($t_project_id, 1);
}
else
{
    $projects = project_get_all_rows();
    $project_ids = array();
    foreach ($projects as $proj) {
        array_push($project_ids, $proj['id']);
    }
    $t_releases = version_get_all_rows($project_ids, 1);
}

$t_project_name = project_get_name($t_project_id);

$t_user_has_upload_level = user_get_access_level($t_user_id, $t_project_id) >= plugin_config_get('upload_threshold_level', PLUGINS_RELEASES_UPLOAD_THRESHOLD_LEVEL_DEFAULT);
$t_user_has_download_level = user_get_access_level($t_user_id, $t_project_id) >= plugin_config_get('download_threshold_level', PLUGINS_RELEASES_VIEW_THRESHOLD_LEVEL_DEFAULT);

releases_plugin_page_title(string_display_line($t_project_name), plugin_lang_get('display_page_title'));

echo '<div hidden title="' . plugin_lang_get('confirm_delete_file') . '" id="releases_confirm_delete_file"></div>';
echo '<div hidden title="' . plugin_lang_get('confirm_delete_version') . '" id="releases_confirm_delete_version"></div>';

foreach ($t_releases as $t_release) 
{
    $t_prj_id = $t_release['project_id'];
    
    $t_query = 'SELECT id,user,description,date_created,title FROM ' . plugin_table('release') . '
                 WHERE project_id=' . $t_prj_id . ' AND version_id=' . $t_release['id'];
    $t_result = db_query($t_query);

    if (db_num_rows($t_result) == 0)
        continue;

    if ($t_row = db_fetch_array($t_result))
    {
        $t_project_name = project_get_field($t_prj_id, 'name');
        $t_release_title = '';
        
        if (!is_blank($t_row['title'])) {
            $t_release_title = $t_row['title'];
            if ($t_project_id == ALL_PROJECTS) {
                $t_release_title = string_display($t_project_name) . ' - ' . $t_release_title;
            }
        }
        else {
            $t_release_title = string_display($t_project_name) . ' - ' . string_display($t_release['version']);
        }

        releases_plugin_release_title($t_project_name, $t_release_title, $t_release['version']);

        # Release created by
        #
        if (!is_blank($t_row['user'])) 
        {
            $user_id = user_get_id_by_name($t_row['user']);
            echo '<table align="center" width="100%" style="margin-bottom:5px;margin-left:10px;"><tr>';
            if (config_get_global('show_avatar')) 
            {
                if (access_has_project_level(config_get('show_avatar_threshold'), null, $user_id)) 
                {
                    echo '<td height="35" width="40">';
                    print_avatar($user_id, 'releases', 30 );
                    echo '</td>';
                }
            }
            echo '</td><td height="35" valign="middle"><b>';
            echo $t_row['user'] . '</b> released this on ' . $t_row['date_created'];
            echo '</td>';

            echo '<td width="40"><a class="btn btn-xs btn-primary btn-white btn-round" href="changelog_page.php?project_id=' . $t_prj_id . '"> ' . string_display_line($t_project_name) . ' </a></td>';
            echo '<td width="40"><a class="btn btn-xs btn-primary btn-white btn-round" href="changelog_page.php?version_id=' . $t_release['id'] . '"> ' . string_display_line($t_release['version']) . ' </a></td>';
            
            echo '<td width="40"><a class="btn btn-xs btn-primary btn-white btn-round" ';
            echo 'href="view_all_set.php?type=1&temporary=y&' . FILTER_PROPERTY_PROJECT_ID . '=' . $t_project_id .
                '&' . filter_encode_field_and_value(FILTER_PROPERTY_FIXED_IN_VERSION, $t_release['version']) .
                '&' . FILTER_PROPERTY_HIDE_STATUS . '=' . META_FILTER_NONE . '">';
            echo lang_get('view_bugs_link');
            echo '</a></td>';

            $t_version_id = $t_release['id'];
            if (plugin_is_installed("GanttChart2"))
            {
                if (plugin_is_installed("IFramed"))
                {
                    echo '<td width="40"><a class="btn btn-xs btn-primary btn-white btn-round" href="plugin.php?page=IFramed/main&title=Gantt%20Chart&url=' . urlencode(plugin_page( 'summary_gantt_chart.php', false, "GanttChart2" ) . "&project_id=$t_prj_id&version_id=$t_version_id&v_str=fixed_in_version&inherited=") . "\">" . plugin_lang_get( 'gantt_bug_page_link', 'GanttChart2' ) . '</a></td>';
                }
                else {
                    echo '<td width="40"><a class="btn btn-xs btn-primary btn-white btn-round" href="' . plugin_page( 'summary_gantt_chart.php', false, "GanttChart2" ) . "&project_id=$t_prj_id&version_id=$t_version_id&v_str=fixed_in_version&inherited=\">" . plugin_lang_get( 'gantt_bug_page_link', 'GanttChart2' ) . "</a></td>";
                }
            }

            echo '<td width="40"><a class="btn btn-xs btn-primary btn-white btn-round" href="#releases_upload"> ' . plugin_lang_get('upload_title') . ' </a></td>';

            if ($t_user_has_upload_level) {
                echo '<td width="60"><a class="btn btn-xs btn-primary btn-white btn-round version_delete" href="' . plugin_page('delete') . '&release=true&id=' . $t_row['id'] . '" title=" ' . lang_get('delete_link') . '">' . lang_get('delete_link') . '</a></td>';
            }

            echo '</tr></table>';
        }
        #
        # Changelog
        #
        echo '<div class="page-header">';
        echo '<h1><small>Change Log</small></h1>';
        echo '</div>';
        echo '<table align="center" width="95%"><tr><td width="100%">';
        echo $t_row['description'];
        if (!endsWith($t_row['description'], '<br>')) {
            echo('<br>');
        }
        echo '</td></tr><tr><td height="7"></td></tr></table>';
    }
    else {
        continue;
    }

    if ($t_user_has_download_level) // && $t_project_id != ALL_PROJECTS) 
    {
        $t_query = 'SELECT id,title,filesize,description,enabled,release_type,date_added,user
                FROM ' . plugin_table('file') . ' WHERE project_id=' . db_param() . ' AND version_id=' . db_param();
        if (!$t_user_has_upload_level) {
            $t_query .= ' AND enabled>0';
        }
        $t_query .= ' ORDER BY title ASC';
        $t_result = db_query_bound($t_query, array((int) $t_prj_id, (int) $t_release['id']));
        
        if (db_num_rows($t_result) == 0) {
            echo "</div></div></div><br />\n";
            continue;
        }

        #
        # Assets/files
        #
        echo '<div>';
        echo '<div class="page-header">';
        echo '<h1><small>Assets</small></h1>';
        echo '</div>';
        echo '</div>'; 
        while ($t_row = db_fetch_array($t_result)) 
        {
            $t_file_class = 'releases-enabled-file';
            if ($t_user_has_upload_level && $t_row['enabled'] == 0) {
                $t_file_class = 'releases-disabled-file';
            }

            echo '<span class="asset-table"><table align="center" width="95%">';
            // Do not use plugin_page() for download link.  It causes security header to be submitted prior plugin has
            // a chance to disable default header submission.  As result "download" header can't be submitted properly
            // So, we are using releases_plugin_page_url() instead
            echo '<tr>';
            if (!is_blank($t_row['description'])) {
                echo '<td width="175">' . $t_row['description'] . '</td>';
            }
            echo '<td>' . '<a class="' . $t_file_class . '"  href="' . releases_plugin_page_url('download') . '?id=' . $t_row['id'] . '" title="' . plugin_lang_get('download_link') . '">' .
                 '<i class="fa fa-download"></i> &nbsp;' . $t_row['title'] . '</a></td>';
            echo '<td width="335">' . plugin_lang_get('uploaded_by') . ' ' . $t_row['user'] . ' @ ' . $t_row['date_added'] . '</td>';
            echo '</td><td width="80">' . size_display($t_row['filesize']) . '</td>';
            if ($t_user_has_upload_level) {
                $t_enable_text =  plugin_lang_get($t_row['enabled'] ? 'disable_link' : 'enable_link');
                echo '<td width="55"><a class="btn btn-xs btn-primary btn-white btn-round releases_enable" href="' . plugin_page('enable') . '&id=' . $t_row['id'] . '&enbl=' . ($t_row['enabled'] ? '0' : '1') . '" title=" ' . $t_enable_text . '">' . $t_enable_text . '</a></td>';
                echo '<td width="55"><a class="btn btn-xs btn-primary btn-white btn-round releases_delete" href="' . plugin_page('delete') . '&id=' . $t_row['id'] . '" title=" ' . lang_get('delete_link') . '">' . lang_get('delete_link') . '</a></td>';
            }
            echo '</tr></table></span>';
        }
    }
    echo "</div></div></div><br />\n"; // original developers leave open-ended double-<div> in releases_plugin_release_title()
}

if ($t_user_has_upload_level && $t_project_id != ALL_PROJECTS) 
{
    //$t_max_file_size = (int)min( ini_get_number( 'upload_max_filesize' ), ini_get_number( 'post_max_size' ), config_get( 'max_file_size' ) );
    $t_max_file_size = releases_max_upload_size()[0];
    echo '<br /><hr />' . "\n";
    releases_plugin_section_title(plugin_lang_get('upload_title'), 'fa-upload',  'releases_upload');
?>

    <form action="<?php echo plugin_page('upload'); ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="plugin" value="Releases" />
        <input type="hidden" name="display" value="upload" />
        <input type="hidden" name="max_file_size" value="<?php echo $t_max_file_size ?>" />
        <table class="width100 table table-striped table-bordered table-condensed" cellspacing="1">
            <tr class="row-1">
                <td class="category" width="15%">
                    <?php echo lang_get('product_version') ?>
                </td>
                <td width="85%">
                    <select name="release">
                        <?php foreach ($t_releases as $t_release) {
                            echo '<option value="' . $t_release['id'] . '">' . $t_release['version'] . '</option>';
                        } ?>
                    </select>
                </td>
            </tr>
            <tr class="row-2">
                <td class="category" width="15%">
                    <span class="required">*</span><?php echo plugin_lang_get('file_count') ?>
                </td>
                <td width="85%">
                    <input name="file_count" id="file_count" type="text" size="3" maxlength="1" value="<?php echo plugin_config_get('file_number', PLUGINS_RELEASES_FILE_NUMBER_DEFAULT); ?>">
                </td>
            </tr>
            <tr class="row-1">
                <td class="category" width="15%">
                    <span class="required">*</span><?php echo lang_get('select_file') ?><br />
                    <?php echo '<span class="small">(' . lang_get('max_file_size_label') . ' ' . number_format($t_max_file_size / 1000) . 'kB)</span>' ?>
                </td>
                <td width="85%">
                    <div id="FileField"></div>
                </td>
            </tr>
            <tr class="row-2">
                <td class="category" width="15%">
                    <?php echo lang_get('description') ?>
                </td>
                <td width="85%">
                    <div id="DescriptionField">
                    </div>
                </td>
            </tr>
            <tr class="row-1">
                <td class="category" width="15%">
                    <?php echo plugin_lang_get('release_description') . '<br /><span class="small">' . plugin_lang_get('overwrites_release_descrip') . '</span>' ?>
                </td>
                <td width="85%">
                    <textarea class="form-control" name="description" rows="10" style="width:100% !important;"></textarea>
                </td>
            </tr>
            <tr>
                <td class="left">
                    <span class="required"> * <?php echo lang_get('required') ?></span>
                </td>
                <td class="center">
                </td>
            </tr>
        </table>
        <input type="submit" class="button" value="<?php echo lang_get('upload_files_button') ?>" />
        <script src="<?php echo plugin_file('releases.js') ?>"></script>
    </form>

<?php
    echo "</div></div></div>\n"; // original developers leave open-ended double-<div> in releases_plugin_section_title()
}

layout_page_end();
