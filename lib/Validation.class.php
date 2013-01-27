<?php
/**
 * A class of validation functions
 * @author Justin Laing <justin@merchantos.com>
 * @package helpers
 */
class helpers_Validation
{
	/**
	 * Validates that the passed value is a UPC or EAN barcode.
	 * @param string $value The value to validate.
	 * @return boolean
	 */
	public static function isValidUPCEAN($value)
	{
		require_once('helpers/Options.class.php');
		$options = helpers_Options::singleton();
		// might want to add \d{5,6} here to catch zero stripped UPC barcodes
		if ($options->get('label_size') == '1.25x1.00' || $options->get('scan_small_labels'))
		{
			if (preg_match('/^(\d{7,8})$/',$value)) // removed \d{7,8}| -> EAN-8 standard, we can add it back as a module if someone needs it
			{
				return true;
			}
		}
		if (preg_match('/^(\d{11,18})$/',$value)) // removed \d{7,8}| -> EAN-8 standard, we can add it back as a module if someone needs it
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Gets the Luhn checksum value for the passed EAN7
	 * @param string
	 * @return string
	 */
	public static function getEan7Checksum($value)
	{
		if (strlen($value) != 7)
		{
			return false;
		}
		return self::getBarcodeChecksum($value);
	}
	
	/**
	 * Preforms the Luhn Check on the passed value
	 * @param string $value The value to preform the check on
	 * @return string
	 * @link http://en.wikipedia.org/wiki/Luhn_algorithm
	 */
	public static function getBarcodeChecksum($value)
	{
		if (!is_numeric($value))
		{
			return false;
		}
		// Get the length of the string
		$str_len = mb_strlen($value);
		$total = 0;
		// Go through the values and preform the calculation
		for ($i=1; $i<=$str_len; $i++)
		{
			// EANs are special
			if ($str_len == 12)
			{
				// Even place digits are mulitplied by 3 and then added
				if ($i % 2 == 0)
				{
					$total += 3 * $value{$i-1};
				}
				// Odd place digits are  added
				else
				{
					$total += $value{$i-1};
				}
			}
			// UPCs go here
			else
			{
				// Even place digits are added
				if ($i % 2 == 0)
				{
					$total += $value{$i-1};
				}
				// Odd place digits are mulitplied by 3 and then added
				else
				{
					$total += 3 * $value{$i-1};
				}
			}
		}
		// The resulting total is mod 10 and the remainder subtracted from 10
		return 10 - $total % 10;
	}
	
	/**
	 * Check that a string looks roughly like an email address should
	 * Static so it can be used without instantiation
	 * Tries to use PHP built-in validator in the filter extension (from PHP 5.2), falls back to a reasonably competent regex validator
	 * Conforms approximately to RFC2822
	 * @link http://www.hexillion.com/samples/#Regex Original pattern found here
	 * @param string $address The email address to check
	 * @return boolean
	 * @static
	 * @access public
	 */
	public static function ValidateAddress($address)
	{
		$address = trim($address);
		if (function_exists('filter_var'))
		{ //Introduced in PHP 5.2
			if(filter_var($address, FILTER_VALIDATE_EMAIL) === FALSE)
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		else
		{
			return preg_match('/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/', $address);
		}
	}
}
