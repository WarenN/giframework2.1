<form action="/secure/" method="post">
<label>
login
<?php echo giHelper::input('login'); ?>
</label>
<label>
password
<?php echo giHelper::password('password'); ?>
</label>
<input type="submit" value="Try" />
</form>