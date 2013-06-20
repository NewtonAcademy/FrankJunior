<?php	

	// Registers a route and callback function for a GET request.
	function get($route, $function){
		FrankJunior::add_route('get', $route, $function);
	}

	// Registers a route and callback function for a POST request.	
	function post($route, $function){
		FrankJunior::add_route('post', $route, $function);
	}

	// Registers a route and callback function for a PUT request.
	function put($route, $function){
		FrankJunior::add_route('put', $route, $function);
	}

	// Registers a route and callback function for a DELETE request.
	function delete($route, $function){
		FrankJunior::add_route('delete', $route, $function);
	}

	// Registers the callback function invoked when no matching route is found.
	function not_found($function){
		FrankJunior::set_not_found($function);
	}
		
	// Calls FrankJunior again with the supplied route. Used to handle route forwarding.
	function pass($route){
		FrankJunior::set_request($route);
		FrankJunior::call();
	}

	// Sets FrankJunior's output explicitly. Used to return non-200 status code and error message.
	function error($status, $headers, $body){
 		FrankJunior::set_output($status, $headers, $body);
	}
