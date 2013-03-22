<?php
require_once("config.inc.php");
require_once("views/Render.class.php");

class views_RenderTest extends PHPUnit_Framework_TestCase
{
    public function testRender()
    {
		ob_start();
		
		views_Render::renderView("testview",array("foo"=>"bat","bar"=>"baz"));
		
		$output = ob_get_contents();
		
		ob_end_clean();
		
		$this->assertTrue(stripos($output,"test foo=bat bar=baz")!==false);
    }
	
	public function testNoViewName()
	{
		$exception = false;
		try
		{
			views_Render::renderView("",array("foo"=>"bat","bar"=>"baz"));
		}
		catch (Exception $e)
		{
			$this->assertEquals("view_name was not supplied.",$e->getMessage());
			$exception = true;
		}
		$this->assertTrue($exception);
	}
	
	public function testBogusViewName()
	{
		$exception = false;
		try
		{
			views_Render::renderView("bogus",array("foo"=>"bat","bar"=>"baz"));
		}
		catch (Exception $e)
		{
			$this->assertEquals("No view matching the view_name supplied (bogus) was found.",$e->getMessage());
			$exception = true;
		}
		$this->assertTrue($exception);
	}
}
