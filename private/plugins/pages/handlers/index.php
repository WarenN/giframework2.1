<?php
$giOutput->setType('html');
$giOutput->setTitle('giFramework 2');
?>
<h1 style="text-align:center;font-family:arial;font-size:36px;padding-top:20px;">
	giFramework 2
</h1>
<p style="text-align:center;color:#999999;font-family:arial;">
	It seems to be working !<br />
	<img src="https://raw.githubusercontent.com/AnnoyingTechnology/giframework2/master/giFramework2.jpg" style="width: 80%;" />
</p>


<?php


// sample code to create a user and user the database
/*
$anAccount = $giDatabase->insert('Accounts',array('login' => 'root','id_level' => 0,'is_enabled'=> 1));
$giAuthentication->setUserPassword($anAccount->get('id'),'toor');
$allAccounts = $giDatabase->select('Account',array('id_level'=>'1'));
*/


?>