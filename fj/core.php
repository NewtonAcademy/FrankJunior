<?php

	class FrankJunior {

		/* -------------------------- Private properties ---------------------------- */
				
		// Everything in the request URL after http://www.example.com/api.
		private static $request = '';
		
		// The HTTP method of the request.
		private static $method = '';
		
		// The HTTP status code to return to the client.
		private static $status = 200;

		// An array of HTTP headers to return to the client.
		private static $headers = array();

		// The HTTP response body to return to the client.
		private static $body = '';
	
		// The function to invoke if no matching route is found.
		private static $not_found;

		// Arrays of registered routes, one per request method.
		private static $routes = array(
			'get' => array(),
			'post' => array(),
			'put' => array(),
			'delete' => array()
		);
		
		/* ----------------------- Public methods used by library.php ------------------------- */

		// Adds a route. Used by the library.php functions get(), post(), put(), and delete().
		public static function add_route($method, $route, $function){
			self::$routes[$method][$route] = $function;
		}
		
		// Rewrites the request. Used for internal redirection by the library.php function pass().
		public static function set_request($request){
			self::$request = $request;
		}
		
		// Sets the callback function invoked when no matching route is found. Used by the library.php function not_found().
		public static function set_not_found($function){
			self::$not_found = $function;
		}

		// Explicitly sets the HTTP response status code, headers, and body. Used by the library.php function error().				
		public static function set_output($status, $headers=array(), $body=''){
			self::$status = $status;
			self::$headers = $headers;
			self::$body = $body;
		}
			
		/* -------------------------- Private helper methods ---------------------------- */

		// Extracts our request path from index.php?q=original_request_path and returns it.
		private static function get_request(){
		    if(!self::$request) {
						
  				// Get the query string value into which the .htaccess file rewrote the request path.
  				$query = $_GET['q'];
  				
  				// Get the path to the current directory: Take the path to the current file, and remove the website's root directory from it.
  				$current_dir = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname(__FILE__));
  				
  				// Remove the path to the current directory from the request path, storing the result as our final request.
  		    self::$request = str_replace($current_dir, '', $query);
        }
			return self::$request;	
		}

		// Finds out and stores the HTTP method of the current request.
		private static function get_method(){
			if(!self::$method)
				self::$method = $_SERVER['REQUEST_METHOD'];
				
			return strtolower(self::$method);
		}		
		
		/*	The following method accepts:
		
			$string 		- A string to be matched. (This will be the user's request).
			$regex_array 	- An array of strings that will be treated as regex patterns
							  to match to $string. (This will be all the routes you've
							  registered for the current request's HTTP method).
			$also_match 	- An array of regex pattern snippets that, if found within one of
							  the routes, will be replaced with .*?, which means "anything
							  and everything". (We will replace path segments of * and : with
							  .*? so that named parameters and splats are treated as matches,
							  even though regex-wise they don't actually match the request. */
		private static function reverse_preg_match_array($string, $regex_array, $also_match=array()){
		
			// Create an empty array to store the matches.
			$matches = array();
			
			// For each string in the regex array:
			foreach($regex_array as $regex){
				
				// Copy the regex to a new variable.
				$new_regex = $regex;
				
				// For each $also_match,
				foreach($also_match as $match)
					// modify the regex by substituting "anything and everything" for each $also_match.
					$new_regex = preg_replace($match, '.*?', $new_regex);
				
				// If the user's original request matches the modified regex,
				if(preg_match("#^$new_regex$#", $string))
				
					// add the original regex (i.e. the route as entered by us into FrankJunior) to the array of matches.
					$matches[] = $regex;
			}
				
			if(count($matches) > 0)
				return $matches;
			else
				return false;
		}
	
		// This method accepts two paths and returns the path segments that don't match.		
		private static function url_diff($url_1, $url_2){
		
			// This should never happen, but just in case:
			if($url_1 == $url_2)
				return array();
			
			// Create arrays of the path segments in the request and route:
			$url_1 = explode('/', $url_1);
			$url_2 = explode('/', $url_2);
			
			// Create an empty array to store the segments that differ.
			$differences = array();
			
			// Loop through the array of path segments, comparing the corresponding segments:
			foreach($url_1 as $key => $url_1_item){
			
				// When they differ, add them to the array as a key-value pair.
				if($url_2[$key] !== $url_1_item)
					$differences[] = array($url_2[$key] => $url_1_item);
			}
			
			return $differences;
		}
		
		/*	When supplied with the request method and the request,
			this method finds a matching route, and returns its callback function
			and the parameters to supply it with when executed. */
		private static function route($method, $request){
		    $params = array(
							'splat' => array(),
							'captures' => array()
							);
			
			// If the HTTP method and the request exactly match an existing route,
			if(isset(self::$routes[$method][$request])){
			
				// Then we store the anonymous function defined for that route.
				$function = self::$routes[$method][$request];
			
			/*	If there is no exact match, we check to see if there are any routes that match when you treat
				them as regexes. We also treat named parameter and splat segments as matches. */
			} elseif( ($route = self::reverse_preg_match_array($request, array_keys(self::$routes[$method]), array('#\*(/|$)#', '/:[A-Za-z0-9]+/'))) && $route !== false ) {
			
				// If multiple matching routes were returned, we pick the last one.
				$route = end($route);
				
				// Once we've found the matching route, we retrieve and store its anonymous function.
				$function = self::$routes[$method][$route];
				
				/*	Next, we need to construct the $params array to pass into the anonymous function. In
					order to do that, we need to extract all the path segments where the user's request
					matched a regex pattern, named parameter, or splat segment. We call a helper
					function to extract these path segments for us. Since these path segments are the
					only ones for which the request differs from the defined route, it's simple to
					extract them. */
				$changes = self::url_diff($request, $route);
				
				// We go through each difference, and store the appropriate value in $params:
				foreach($changes as $change){
				
					// A difference is stored as an array that contains the route segment as its only key, with the corresponding request segment as the value.
					foreach($change as $index => $value) {
						
						// If the current route segment is a named parameter (i.e. it starts with a colon ):
						if(preg_match('/^:/', $index)){
							
							// Strip off the leading colon,
							$index = preg_replace('/^:/', '', $index);
							
							// and add the key-value pair to the $params array.
							$params[$index] = $value;
						
						// If the current route segment is a splat,
						} elseif ($index == '*'){
							
							// add the request segment to the end of the $params['splat'] array.
							$params['splat'][] = $value;
						
						// If the current route segment is a regex (or, if the request has more segments than the route
						} else {
							// add the request segment to the end of the $params['captures'] array.
							$params['captures'][] = $value;
						}
					}
				}
			}
			
			// If we've found a matching route, then $function will already be set. If we haven't, we'll use the not_found function.
			if(!isset($function)){
				$function = self::$not_found;
			}
			
			// Return the function for matched route, and the $params to pass into it when executed. 
			return array($function, $params);
		}

		/* ---------------------- Primary execution methods called from fj.php ------------------------ */
		
		// Retrieves the matching route and executes its callback method. 
		public static function call(){
			$request = self::get_request();
			$method = self::get_method();
			
			$callback_information = self::route($method, $request);
			$callback = $callback_information[0];
			$params = $callback_information[1];

			// Start an output buffer, execute the callback function for our route, and store all the captured output in $body:
			ob_start();

				if(count($params) == 0)
					call_user_func($callback);
				else
					call_user_func($callback, $params);
				
				if (self::$body == '')
					self::$body = ob_get_contents();
				
			ob_end_clean();
		}
			
		// Outputs the HTTP response. 
		public static function output($options=array()) {
			
			// List of status codes for ease of mapping $status
			$status_codes = array(
								// Informational 1xx
								100 => 'Continue',
								101 => 'Switching Protocols',
								// Successful 2xx
								200 => 'OK',
								201 => 'Created',
								202 => 'Accepted',
								203 => 'Non-Authoritative Information',
								204 => 'No Content',
								205 => 'Reset Content',
								206 => 'Partial Content',
								// Redirection 3xx
								300 => 'Multiple Choices',
								301 => 'Moved Permanently',
								302 => 'Found',
								303 => 'See Other',
								304 => 'Not Modified',
								305 => 'Use Proxy',
								307 => 'Temporary Redirect',
								// Client Error 4xx
								400 => 'Bad Request',
								401 => 'Unauthorized',
								402 => 'Payment Required',
								403 => 'Forbidden',
								404 => 'Not Found',
								405 => 'Method Not Allowed',
								406 => 'Not Acceptable',
								407 => 'Proxy Authentication Required',
								408 => 'Request Timeout',
								409 => 'Conflict',
								410 => 'Gone',
								411 => 'Length Required',
								412 => 'Precondition Failed',
								413 => 'Request Entity Too Large',
								414 => 'Request-URI Too Long',
								415 => 'Unsupported Media Type',
								416 => 'Request Range Not Satisfiable',
								417 => 'Expectation Failed',
								// Server Error 5xx
								500 => 'Internal Server Error',
								501 => 'Not Implemented',
								502 => 'Bad Gateway',
								503 => 'Service Unavailable',
								504 => 'Gateway Timeout',
								505 => 'HTTP Version Not Supported'
			                );
			
			// Get the description for the current status code.
			$status_message = $status_codes[self::$status];
			
			// Write the HTTP response status line.
			header('HTTP/1.1 ' . self::$status . " $status_message");
			
			// Write the HTTP response headers.
			foreach(self::$headers as $type => $header)
				header("$type: $header", self::$status);
			
			// Write the HTTP response body.
			echo(self::$body);
		}	
	}	
