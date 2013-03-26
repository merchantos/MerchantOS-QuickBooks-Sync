<?php
require_once("config.inc.php");
require_once("lib/SessionAccess.class.php");
require_once("Tests/mock_SessionAccess.class.php");

class lib_SessionAccessTest extends PHPUnit_Framework_TestCase
{
    public function testMagicGet()
    {
		$sess_access = new mock_SessionAccess();
		
		$sess_access->foo = "bar";
		
		$this->assertEquals("bar",$sess_access->foo);
		$this->assertEquals("bar",$sess_access->__get("foo"));
	}
	
    public function testMagicSet()
    {
		$sess_access = new mock_SessionAccess();
		
		$sess_access->foo = "bar";
		
		$this->assertEquals("bar",$sess_access->foo);
		$this->assertEquals("bar",$sess_access->__get("foo"));
		
		$sess_access->__set("bat","baz");
		
		$this->assertEquals("baz",$sess_access->bat);
		$this->assertEquals("baz",$sess_access->__get("bat"));
	}
	
    public function testMagicIsSet()
    {
		$sess_access = new mock_SessionAccess();
		
		$sess_access->foo = "bar";
		
		$this->assertTrue(isset($sess_access->foo));
		$this->assertTrue($sess_access->__isset("foo"));
		
		$this->assertFalse(isset($sess_access->bat));
		$this->assertFalse($sess_access->__isset("bat"));
	}
	
	
    public function testMagicUnSet()
    {
		$sess_access = new mock_SessionAccess();
		
		$sess_access->foo = "bar";
		
		$this->assertTrue(isset($sess_access->foo));
		$this->assertTrue($sess_access->__isset("foo"));
		
		unset($sess_access->foo);
		
		$this->assertFalse(isset($sess_access->foo));
		$this->assertFalse($sess_access->__isset("foo"));
		
		$sess_access->bat = "baz";
		
		$this->assertTrue(isset($sess_access->bat));
		$this->assertTrue($sess_access->__isset("bat"));
		
		$sess_access->__unset("bat");
		
		$this->assertFalse(isset($sess_access->bat));
		$this->assertFalse($sess_access->__isset("bat"));
	}
	
	public function testGetArray()
	{
		$sess_access = new mock_SessionAccess();
		
		$sess_access->foo = "bar";
		$sess_access->bat = "baz";
		
		$arr = $sess_access->getArray();
		
		$this->assertEquals("bar",$arr['foo']);
		$this->assertEquals("baz",$arr['bat']);
	}
	
	public function testLoadArray()
	{
		
		$sess_access = new mock_SessionAccess();
		
		$arr = array();
		$arr["foo"] = "bar";
		$arr["bat"] = "baz";
		
		$sess_access->loadArray($arr);
		
		$this->assertEquals("bar",$sess_access->foo);
		$this->assertEquals("baz",$sess_access->bat);
	}
	
	public function testStoreCache()
	{
		$sess_access = new mock_SessionAccess();
		
		$sess_access->storeCache("foo","bar");
		
		$this->assertEquals("bar",$sess_access->foo_cache["value"]);
		$this->greaterThan(time()-2,$sess_access->foo_cache["time"]);
	}
	
	public function testGetCache()
	{
		$sess_access = new mock_SessionAccess();
		
		$sess_access->storeCache("foo","bar");
		
		// wrong name, should be null
		$this->assertNull($sess_access->getCache("bat",1));
		
		// right name, bad timeout, should be null
		$this->assertNull($sess_access->getCache("foo",0));
		
		// right name, good timeout
		$this->assertEquals("bar",$sess_access->getCache("foo",1));
	}
	
	public function testClear()
	{
		
		$sess_access = new mock_SessionAccess();
		
		$arr = array();
		$arr["foo"] = "bar";
		$arr["bat"] = "baz";
		
		$sess_access->loadArray($arr);
		
		$this->assertEquals("bar",$sess_access->foo);
		$this->assertEquals("baz",$sess_access->bat);
		
		$sess_access->clear();
		
		$this->assertFalse(isset($sess_access->foo));
		$this->assertFalse(isset($sess_access->bat));
	}
}
