<?php

# Copyright (c) 2019 Scott Meesseman
# Licensed under the MIT License

class ReleasesPlugin extends MantisPlugin
{
    public function register()
    {
        $this->name = plugin_lang_get("title");
        $this->description = plugin_lang_get("description");
        $this->page = 'config';

        $this->version = "1.1.3";
        $this->requires = array(
            "MantisCore" => "2.0.0",
        );

        $this->author = "Scott Meesseman, Vincent DEBOUT, Jiri Hron, Igor Kozin";
        $this->contact = "spmeesseman@gmail.com";
        $this->url = "https://github.com/mantisbt-plugins/Releases";
    }

    function init() 
    {
        $t_inc = get_include_path();
        $t_core = config_get_global('core_path');
        $t_path = config_get_global('plugin_path'). plugin_get_current() . DIRECTORY_SEPARATOR . 'core'. DIRECTORY_SEPARATOR;
        if (strstr($t_inc, $t_core) == false) {
            set_include_path($t_inc . PATH_SEPARATOR . $t_core . PATH_SEPARATOR . $t_path);
        }
        else {
            set_include_path($t_inc .  PATH_SEPARATOR . $t_path);
        }
    }

    public function hooks()
    {
        return array(
            "EVENT_MENU_MAIN" => "menu",
            'EVENT_LAYOUT_RESOURCES'=> 'resources'
        );
    }

    public function menu()
    {
        $links = array();
        $links[] = array(
          'title'=> plugin_lang_get("title"),
          'url'=> plugin_page("releases", false),
          'access_level'=> plugin_config_get('view_threshold_level', UPDATER),
          'icon'=> 'fa-download'
        );
        return $links;
    }

    function schema() 
    {
        return array(
            array('CreateTableSQL', 
                array( plugin_table('file', 'Releases'), "
                    id                 I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
                    release_id         I       NOTNULL UNSIGNED,
                    project_id         I       NOTNULL UNSIGNED,
                    version_id         I       NOTNULL UNSIGNED,
                    title              C(250)  NOTNULL DEFAULT '',
                    description        X       NOTNULL,
                    diskfile           C(250)  NOTNULL DEFAULT '',
                    filename           C(250)  NOTNULL DEFAULT '',
                    folder             C(250)  NOTNULL DEFAULT '',
                    filesize           I       NOTNULL DEFAULT '0',
                    file_type          C(250)  NOTNULL DEFAULT '',
                    date_added         T       NOTNULL DEFAULT '1970-01-01 00:00:01',
                    content            B       NOTNULL,
                    enabled            L       NOTNULL DEFAULT 1,
                    release_type       L       NOTNULL DEFAULT 0"
                )
            ),
            array('CreateTableSQL', 
                array( plugin_table('release', 'Releases'), "
                    id                 I       NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
                    project_id         I       NOTNULL UNSIGNED,
                    version_id         I       NOTNULL UNSIGNED,
                    title              C(250)  NOTNULL DEFAULT '',
                    description        X       NOTNULL,
                    date_created       T       NOTNULL DEFAULT '1970-01-01 00:00:01'"
                )
            ),
            array('AddColumnSQL', 
                array( plugin_table('file'), "
                    user              C(30)    NOTNULL DEFAULT ''",
                    array( "mysql" => "DEFAULT CHARSET=utf8" ) 
                )
            ),
            array('AddColumnSQL', 
                array( plugin_table('release'), "
                    user              C(30)    NOTNULL DEFAULT ''",
                    array( "mysql" => "DEFAULT CHARSET=utf8" ) 
                )
            )
            // Example for adding columns after initial release... v.1.x.x
            //array('AddColumnSQL', 
            //    array( plugin_table('file'), "
            //        enabled            L       NOTNULL DEFAULT 1",
            //        array( "mysql" => "DEFAULT CHARSET=utf8" ) 
            //    )
            //)
        );
    }

    function config() {
        return array(
            'download_requires_login'  => true
        );
    }

    function resources($event) {
        return '<link rel="stylesheet" type="text/css" href="'.plugin_file("releases.css").'"/>';
    }

}
