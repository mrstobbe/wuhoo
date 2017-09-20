<?php

function __bootstrap() {
	//#TODO: Anything else here? Probably not. Such a tiny and super-simple app
	require dirname(__FILE__) . '/App.class.php';
	$app = App::init();
	$app->run();
}

__bootstrap();

return;
?>
