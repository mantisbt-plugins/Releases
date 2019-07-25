<?php

echo $t_template['files_count'] == 1 ? "A new file has been" : $t_template['files_count'] . " new files have been";
echo " UPLOADED for the " . $t_template['project_name'] . " project.";
echo PHP_EOL . "==========================================" . PHP_EOL;

for( $i=0; $i<$t_template['files_count']; $i++ )
{
	echo PHP_EOL . ($t_template['files_count'] > 1 ? PHP_EOL . ($i+1) . ". file: " : "File: ")
		. $t_template['files'][$i]['file_name'] . PHP_EOL;
	echo PHP_EOL . "Description:" . PHP_EOL . $t_template['files'][$i]['file_description'] . PHP_EOL;
	echo PHP_EOL . "You can download it at the following address:"
		. PHP_EOL . $t_template['files'][$i]['file_url'] . PHP_EOL . PHP_EOL;
}
