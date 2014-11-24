<?php

// first is the url (using :param_name for dynamic pages), second is plugin/controller, third is minimum security level, fourth is security module
$this->Router->route('/demo/:action/:format/','demo/test');
$this->Router->route('/','demo/index');
$this->Router->route('/secure/:action/:format/','demo/secure',1);
$this->Router->route('/login/','demo/login');

// 404 route (can be in any plugin but you have to declare the url in the common.ini)
$this->Router->route('/404/','demo/not-found');


?>