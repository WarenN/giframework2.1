<?php

// first is the url (using :param_name for dynamic pages), second is plugin/controller, third is minimum security level, fourth is security module
$this->Router->route('/demo/:action/:format/','demo/test');
$this->Router->route('/','demo/index');
$this->Router->route('/users/:action/:id/','demo/users',5);
$this->Router->route('/statistics/:action/:id/','demo/statistics',10,'MOD_STAT');
$this->Router->route('/a-propos/informations-legales.html','demo/info-legales');
$this->Router->route('/controller/main.ajax=','demo/main',2);

// 404 route (can be in any plugin but you have to declare the url in the common.ini)
$this->Router->route('/404/','demo/not-found');


// runtime data example
$data = array('test'=>'sampledata');

// set the runtime data
$this->Router->runtime($data,'demo/main');

?>