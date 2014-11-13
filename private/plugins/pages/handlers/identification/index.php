<form action="/secure/" method="post">

	<label>
	Login
	<?php echo giInput('login',null,array('placeholder'=>'Enter login')); ?>
	</label>
	
	<label>
	Password
	<?php echo giPassword('password',null,array('placeholder'=>'Enter password')); ?>
	</label>

	<input type="submit" value="Auth" />

</form>