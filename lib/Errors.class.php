<?php
require_once("lib/Airbrake.class.php");
/**
 * A class to catch and handle errors in our PHP.
 * @codeCoverageIgnore
 */
class helpers_Errors
{
	protected static $_isSetup = false;
	
	public static function isSetup()
	{
		return self::$_isSetup;
	}
	
	public function setup()
	{
		self::$_isSetup = true;
		
		if (DEVELOPMENT_STACK and SHOW_NOTICE)
		{
			set_error_handler('helpers_Errors::nonfatalHandler',E_ALL);
		}
		else
		{
			set_error_handler("helpers_Errors::nonfatalHandler",E_ALL & ~E_NOTICE & ~E_WARNING);
		}
		set_exception_handler("helpers_Errors::exceptionHandler");
	}
	
	public static function nonfatalHandler($type,$msg,$file,$line)
	{
		if (error_reporting()==0) // error_reporting==0 if the statement has an @, so we don't want to mess with those errors
		{
			return true;
		}
		switch ($type)
		{
			case E_USER_WARNING:
			case E_USER_ERROR:
			case E_USER_NOTICE:
			case E_RECOVERABLE_ERROR:
				break; // we will handle these!
			
			// these won't be caught by this function, they have to be caught in the output buffer
			case E_WARNING: // things like include with a file that doesn't exist returns an error, and rely on this in the processor forms stuff
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_CORE_WARNING:
			case E_COMPILE_ERROR:
			case E_COMPILE_WARNING:
			// these we want to ignore
			case E_STRICT:
			case E_NOTICE:
			default:
				if (DEVELOPMENT_STACK)
				{
					break;
				}
				return; // ignore
		}
		
		try
		{
			$airbrake = helpers_Airbrake::singleton();
			$airbrake->errorHandler($type,$msg,$file,$line);
		}
		catch (Exception $e)
		{
			self::_logError("Failed to log error within Airbrake.");
		}
		
		self::_logError("PHP Error ($type): $msg in $file on $line.");
	   
		ob_end_clean();
		
		echo self::_display($type,$msg,$file,$line);
		
		exit(0);
	}
	
	public static function exceptionHandler($exception)
	{
		try
		{
			$type = E_USER_ERROR;
			$msg = $exception->getMessage();
			$file = $exception->getFile();
			$line = $exception->getLine();
			
			try
			{
				$airbrake = helpers_Airbrake::singleton();
				$airbrake->exceptionHandler($exception);
			}
			catch (Exception $e)
			{
				self::_logError("Failed to log error within Airbrake.");
			}
			
			self::_logError("PHP Exception: $msg in $file on $line.");
			
			ob_end_clean();
			
			echo self::_display($type,$msg,$file,$line);
		}
		catch (Exception $e)
		{
			echo get_class($e)." thrown within the exception handler. Message: ".$e->getMessage()." on line ".$e->getLine();
		}
		exit(0);
	}
	
	public static function fatalHandler()
	{
		$error = error_get_last();
		if (!$error)
		{
			return false;
		}
		switch ($error['type'])
		{
			case E_WARNING: // things like include with a file that doesn't exist returns an error, and rely on this in the processor forms stuff
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_CORE_WARNING:
			case E_COMPILE_ERROR:
			case E_COMPILE_WARNING:
				break; // we will handle these
			default:
				return false; // ignore
		}
		
		$type = $error["type"];
		$msg = $error["message"];
		$file = $error["file"];
		$line = $error["line"];
		
		try
		{
			$airbrake = helpers_Airbrake::singleton();
			$airbrake->fatalErrorHandler($error);
		}
		catch (Exception $e)
		{
			self::_logError("Failed to log error within Airbrake.");
		}
		
		self::_logError("PHP Error ($type): $msg in $file on $line.");
		
		return self::_display($type,$msg,$file,$line);
	}
	
	protected static function _logError($msg)
	{
		error_log(APPLICATION_NAME . ": " . $msg,0);
	}
	
	protected static function _display($type,$msg,$file,$line)
	{
		if (defined('DISPLAY_ALL_ERRORS') && DISPLAY_ALL_ERRORS)
		{
			if (self::_isJSON())
			{
				return '{"error":"Error ['.$type.']: '.htmlentities($msg,ENT_QUOTES).' in '.$file.' on line '.$line.'"}';
			}
			else
			{
				return "<html><body><pre>Error [$type]: $msg in <b>$file</b> on line <b>$line</b>\n</pre></body></html>";
			}
			return;
		}
		
		$message = "An error has occured and MerchantOS has been notified. Please try again and if this problem continues please call support at (866) 554-2453.";
		
		if (self::_isJSON())
		{
			if (!defined("MERCHANTOS_ERROR"))
			{
				return '{"error":"'.htmlentities($message,ENT_QUOTES).'"}';
			}
		}
		else
		{
			return "<div>$message</div>";
		}
	}
	
	protected static function _isJSON()
	{
		$headers = getallheaders();
		foreach ($headers as $key=>$value)
		{
			if ($key == "Accept" || $key == "Content-Type")
			{
				if (stripos($value,"application/json")!==false)
				{
					return true;
				}
			}
		}
		return false;
	}
}
