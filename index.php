<?php

	/*	The following require has the effect of processing all of FrankJunior's code
		based on the current HTTP request. The FrankJunior object that is created by
		FrankJunior's code will store all the information about the request that it needs
		to run its functions, which you will call below. */
	require 'fj/fj.php';
	
	/*	The following require imports a php file which is stored outside our website's root
		that defines a functiuon that creates and returns a database connection. We
		store this information outside the web root for security reasons. The file's contents
		might look something like this:
		<?php
	        function db() {
	                $host = "localhost";
	                $user = "root";
	                $password = "W372Kh7JEbQf6Nc";
	                $database = "api_db";
	
	                return mysqli_connect($host, $user, $password, $database);
	        }
		?>
	*/
	require '/etc/apache2/sites-available/api_db.php';
		
	/*	The following function takes a PHP variable, and outputs it to the HTML response
		as a JSON object. You'll call it once you have the PHP data that you want 
		the API to return to the client. */
	function json_response ($data)
	{
		header('Content-Type: application/json;charset=utf-8');
	
		echo json_encode($data);
	}


/* -------------------- Basic HTTP request method handling examples --------------------- */

	/*	This is the way that FrankJunior works: You register routes with corresponding
		callback functions. Routes for each HTTP method are registered separately, using
		the registration function with the corresponding name. If the client makes an
		HTTP request that matches a registered route, the corresponding callback function
		is invoked. */

	// Registering a route of / for a GET request:
	get("/", function(){
		echo 'Welcome to FrankJunior! You just made a GET request.';
	});

	// Registering a route of /post for a POST request:	
	post("/post", function(){
		echo 'You just made a POST request.';
	});

	// Registering a route of /put for a PUT request:
	put("/put", function(){
		echo 'You just made a PUT request.';
	});

	// Registering a route of /delete for a DELETE request:
	delete("/delete", function(){
		echo 'You just made a DELETE request.';
	});

/* -------------------------- Error handling examples ---------------------------- */

	// Returning an error from within the registered callback function:
	get("/error", function(){
		error(500, array(), "Server error.");
	});

	// Registering a callback function to be executed when no registered route matches the request:
	not_found(function(){
		error(404, array(), "Not found.");
	});

/* --------------------- Other feature examples ----------------------- */

	// Using pass() to handle one request as if it were a different one:
	get("/pass", function(){
		pass('/');
	});
	
	/*	The :param-name syntax is used to tell FrankJunior that a particular path segment
		should be a named parameter. The value that the user supplies for this segment will be stored
		as the value for a key named param-name, and this key-value pair will be placed inside
		the $params array that is passed into the callback function when it is executed: */
	get("/hello/:name/:adjective", function($params){
		echo 'hello ' . $params['name'] . ', you are ' . $params['adjective'];
	});
	
	/*	The wildcard ( * ) tells FrankJunior to treat a particular path segment as
		a "splat". All the values that the user supplies for splat segments
		are passed to your callback function inside $params['splat']. Splats
		can be combined with named parameters, as shown in the following 
		example. You can include as many splat segments as you want. */
	get("/splat/*/something/*/:final", function($params){ // Matches /splat/whut/something/else/nice
		echo '<pre>'; print_r($params['splat']); echo '</pre>';
		echo '<pre>'; echo $params['final']; echo '</pre>';
	});
	
	/* 	You can also include path segments that are regex patterns. Segments in the
		request that match the regex patterns will be supplied to the callback function
		inside the $params['captures'] array. */
	get("/captures/(regex-.*)/bye", function($params){ // Matches /captures/regex-whut/bye.
		echo '<pre>'; print_r($params['captures']); echo '</pre>';
	});

/* -------------------------- Accessing the HTTP request ---------------------------- */

	// Accessing all HTTP request headers:
	get("/headers", function(){
		echo '<pre>'; echo print_r(getallheaders()); echo '</pre>';
	});

	// Accessing the body of a POST request:
	post("/postbody", function(){
		echo '<pre>'; file_get_contents('php://input'); echo '</pre>';
		echo $body;
	});

	// Accessing the body of a PUT request:
	put("/putbody", function(){
		echo '<pre>'; file_get_contents('php://input'); echo '</pre>';
	});	
	
/* -------------------------- Colors API examples ---------------------------- */
	
	/* 	Retrieves all the records from the colors table (and creates the table 
		if it doesn't exist, so visit this URL before trying any other
		examples). Note the trailing \/?, which is regex for "with
		or without a trailing slash". */
	get("/colors\/?", function(){
							
		$query = "CREATE TABLE IF NOT EXISTS `colors` ( 
				      `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
					  `name` tinytext NOT NULL DEFAULT '',
					  `favcolor` tinytext NOT NULL DEFAULT '',
					  `time` timestamp NOT NULL DEFAULT NOW()
					  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
				
		db()->query($query);
		
		$result = db()->query("SELECT * FROM `colors`");
		
		$rows = array();
		
		while ($row = mysqli_fetch_assoc($result)) {
			$rows[] = $row;
		}
		
		mysqli_close(db());
		
		// Unless the result set is empty, return them as JSON.
		if($rows != array())
			json_response($rows);
	});


	/* 	Adds a record to the colors table. The body of the POST request should 
		contain the JSON object to add, looking something like this:
		{
			"name": "John",
			"favcolor": "Green"
		}
	*/
	post("/color\/?", function(){
	
		$new_record = json_decode(file_get_contents('php://input'));
								
		db()->query("INSERT INTO `colors` VALUES (NULL, '$new_record->name', '$new_record->favcolor', NULL)");
		
		mysqli_close(db());
	});

	// Get all the records with a specific favorite color.
	get("/colors/color/:color", function($params){
	
		$color = $params['color'];
		
		$result = db()->query("SELECT * FROM `colors` WHERE `favcolor` = '$color'");
		
		$rows = array();
		
		while ($row = mysqli_fetch_assoc($result)) {
			$rows[] = $row;
		}
		
		mysqli_close(db());
		
		json_response($rows);
	});

	// Get all the records with a specific favorite name.
	get("/colors/name/:name", function($params){
	
		$name = $params['name'];
		
		$result = db()->query("SELECT * FROM `colors` WHERE `name` = '$name'");
		
		$rows = array();
		
		while ($row = mysqli_fetch_assoc($result)) {
			$rows[] = $row;
		}
		
		mysqli_close(db());
		
		json_response($rows);
	});

	// Get the single record with a specific ID.
	get("/colors/id/:id", function($params){
	
		$id = $params['id'];
		
		$result = db()->query("SELECT * FROM `colors` WHERE `id` = $id");
		
		// There can only ever be one row, since ID is the primary key.
		$record = mysqli_fetch_assoc($result);
				
		mysqli_close(db());
		
		json_response($record);
	});

	// Delete the single record with a specific ID.
	delete("/colors/id/:id", function($params){
	
		$id = $params['id'];
		
		db()->query("DELETE FROM `colors` WHERE `id` = $id");
		
		mysqli_close(db());
	});

	// Update the single record with a specific ID.
	put("/colors/id/:id", function($params){
	
		$id = $params['id'];
	
		$update = json_decode(file_get_contents('php://input'));
		
		db()->query("UPDATE `colors` SET `name` = '$update->name', `favcolor` = '$update->favcolor', `time` = NOW() WHERE `id` = $id");
		
		mysqli_close(db());
	});
