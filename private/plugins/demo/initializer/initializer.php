<?php

$giRouter->route('/demo/:action/:id/:section/','demo/test');
$giRouter->route('/users/:action/:id/','demo/users',5);
$giRouter->route('/statistics/:action/:id/','demo/statistics',10,'MOD_STAT');
$giRouter->route('/a-propos/informations-legales.html','demo/infolegales');
$giRouter->route('/controller/main.ajax=','demo/main',2);

?>