giFramework is a PHP micro framework that provides everything you need to code small to medium web applications.


a static page ?

```
$this->Router->route('/my-url.html','myplugin/mycontroller')
```

in /plugins/myplugin/controllers/mycontroller.php

```
public function indexAction() {
	
	echo "Hello world";
	
}
```

a dynamic page ?

```
$this->Router->route('/admin/:action/:user/:section/','myplugin/mycontroller')
```

in /plugins/myplugin/controllers/mycontroller.php
```
public function createAction() {
	echo "I'm triggered by /admin/create/something/somethingelse/";	
	echo "user in my url is ".$this->Core->Router->Parameters->User;
	echo "section in my url is ".$this->Core->Router->Parameters->Section;
}
```



a select query ?
```
public function defaultAction() {

	$this->Database->Query()
	->select('name','age','address')
	->from('users')
	->where(array('is_enabled'=>'1'))
	->execute()

}
```


a more serious query ?
```
public function defaultAction() {
	
	$this->Database->Query()
	->select(array('max'=>'age'))
	->from('users')
	->whereNull('driving_license')
	->execute();

}
```


By default those headers are provided
X-Memory-Usage
X-Execution-Time