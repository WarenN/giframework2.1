<?php

$this->Router->route('/demo/:action/:id/:section/','demo/test');
$this->Router->route('/users/:action/:id/','demo/users',5);
$this->Router->route('/statistics/:action/:id/','demo/statistics',10,'MOD_STAT');
$this->Router->route('/a-propos/informations-legales.html','demo/infolegales');
$this->Router->route('/controller/main.ajax=','demo/main',2);

$data = array('test'=>'testconfig');

$this->Router->runtime($data,'demo/main');

?>