<?php

class views_Render
{
	public static function renderView($view_name, $locals = array()) {
		// Scrub everything from view name except alpha-numeric, dash or underscore
		$view_name = preg_replace("/[^a-z0-9_-]+/i", '', (string)$view_name);
		
		// Full path to file to be included
		$viewfilename = 'views/'. $view_name .'.php';
		
		// Check that view_name is valid length ( > 0)
		if(strlen($view_name) <= 0)
		{
			throw new RuntimeException("view_name was not supplied.");
		}
	
		// Check that primary view file exists    
		if(!file_exists($viewfilename))
		{
			throw new RuntimeException("No view matching the view_name supplied ($view_name) was found.");
		}
	
		try {
			// Extra locals array into variables in local scope but don't overwrite 
			// any existing variables.
			extract($locals,EXTR_SKIP);
			
			include('views/_head.php');
			include($viewfilename);
			include('views/_foot.php');
		} catch (Exception $e) {
			// @codeCoverageIgnoreStart
			throw new RuntimeException("Couldn't load view $view_name");
			// @codeCoverageIgnoreEnd
		}
	}
}
