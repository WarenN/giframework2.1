<?php

// initialize the whole thing
require('../private/core/initializator/initializator.php');

/*

# WHERE THE FUCK IS MY INDEX ?! 
-> read bellow to quickly grasp the proccess.

Process of handling a request :
0) the browser sends a request to /some-random-url/with-subfolders/a-page
1) the webserver redirects everything to index.php?[/some-random-url/with-subfolders/a-page] except /css/* /js/* /fonts/*
2) the general initializator (required above) includes everything needed by the framework (db, locales…)
3) the framework calls initializors of each plugins to generate a list of acceptable urls (static or dynamic, ex static /flip/flap.html, ex dynamic /flip/[param1]/[param2]/…
4) the url is parsed by the giRequest and parameters — if any — are kept by the giRequest for further use
5) if the url matches one of a plugin handler, that handler is executed. That handler can request url parameters from the giRequest
6) the giOutput is asked to generate a page/output with the content present that your handler put in the ob buffer
7) giOutput dumps its content to the browser, execution ends

To get the basic hello world you'll have to :
0) create a dummy plugin
1) tel it's initializator that /hello-world point to a hello-world handler (in that same plugin)
2) put a nice hello world message in your handler, maybe specify a page title to the giOutput object
3) let the framework build the rest of the html

Rewrite rule :
You need a proper rewrite rule for the framework to work, here it is for Lighttpd

------------------------------------------------------------------------------------------

	$HTTP["host"] == "devname.local" {
	        server.document-root = "/var/www/devname/public/"
	        url.rewrite-once = (
	                "^/(?!fonts/)(?!css/)(?!img/)(?!js/)([a-z0-9\-\/]{1,255})\.*([a-z0-9]{1,5}$)*" => "/?"
	        )
	}

------------------------------------------------------------------------------------------

with « devname.local » pointing to « 127.0.0.1 » in your « /etc/hosts »
and « /var/www/devname/ » being the place where you put the framework
Make sure that « /private/ » is writable by the webserver.

*/


?>