Hello,<br />
<br />
<?php if ( $t_template['files_count'] == 1 ) { ?>A new file has been uploaded.<?php } else { ?>New files have been uploaded.<?php } ?><br />
<br />
<ul><?php for( $i=0; $i<$t_template['files_count']; $i++ ) { ?><li> <b><?php echo $t_template['files'][$i]['file_name']; ?></b><br />
<?php echo $t_template['files'][$i]['file_html_description']; ?><br />
<br />
You can download it at the following address: <a href="<?php echo $t_template['files'][$i]['file_url']; ?>" title="Download">Click here</a><br />
</li><?php } ?></ul>
