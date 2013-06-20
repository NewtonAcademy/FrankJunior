<?php
 
	require 'core.php';
	require 'library.php';

	function run(){
		FrankJunior::call();
		FrankJunior::output();
	}

	register_shutdown_function('run', E_ALL);
