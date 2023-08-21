<?php
defined('MYAAC') or die('Direct access not allowed!');

if(!is_file(PLUGINS . 'characters-client-preview/config.php')) {
	copy(
		PLUGINS . 'characters-client-preview/config.php.dist',
		PLUGINS . 'characters-client-preview/config.php'
	);
	success('Copied config.php.dist to config.php');
}

success('You can configure the script in following file: plugins/characters-client-preview/config.php');
