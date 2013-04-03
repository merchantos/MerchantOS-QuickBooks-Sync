<?php
define("NO_ERROR_HANDLING",true);
require_once("config.inc.php");
require_once("Sync/Database.class.php");
require_once("Sync/SyncRunner.class.php");
require_once("Tests/mock_SessionAccess.class.php");

/**
 * class Sync_SyncRunnerTest
 */
class Sync_SyncRunnerTest extends PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$sync_runner = new Sync_SyncRunner('foo','bar');
		$this->assertTrue(is_object($sync_runner));
	}
	
	public function testinitFromSession()
	{
		$mock_runner = $this->getMock("Sync_SyncRunner",array('_getSessionAccess','_getIntuitAnywhereAccess','_getMOSQBSync'),array('foo','bar'));
		
		$mock_runner->expects($this->exactly(4))
			->method("_getSessionAccess")
			->will($this->returnValue((object)array('api_key'=>'bat')));
		
		$mock_runner->expects($this->once())
			->method("_getIntuitAnywhereAccess")
			->will($this->returnValue('baz'));
		
		$mock_runner->expects($this->once())
			->method("_getMOSQBSync")
			->will($this->returnValue('qux'));
		
		$mock_runner->initFromSession();
	}
	
	public function testinitFromDatabase()
	{
		$mock_db = $this->getMock("Sync_Database",array('readSyncSetup','readOAuth','getAPIKeyFromAccountID'));
		
		$mock_db->expects($this->once())
			->method("readSyncSetup")
			->will($this->returnValue('couchpotato'));
		
		$mock_db->expects($this->once())
			->method("readOAuth")
			->will($this->returnValue(array('qb'=>array('foo'=>'qux'),'oauth'=>array('bar'=>'quux'))));
		
		$mock_db->expects($this->once())
			->method("getAPIKeyFromAccountID")
			->will($this->returnValue('beanbag'));
		
		$mock_runner = $this->getMock("Sync_SyncRunner",array('_getIntuitAnywhereAccess','_getMOSQBSync'),array($mock_db,'bar'));
		
		$mock_runner->expects($this->once())
			->method("_getIntuitAnywhereAccess")
			->will($this->returnValue('baz'));
		
		$mock_runner->expects($this->once())
			->method("_getMOSQBSync")
			->will($this->returnValue('qux'));
		
		$mock_runner->initFromDatabase();
	}
	
	public function testinitFromDatabaseExceptionOAuth()
	{
		$mock_db = $this->getMock("Sync_Database",array('readSyncSetup','readOAuth'));
		
		$mock_db->expects($this->once())
			->method("readSyncSetup")
			->will($this->returnValue('couchpotato'));
		
		$mock_db->expects($this->once())
			->method("readOAuth")
			->will($this->returnValue(array('foo'=>'bar')));
		
		$mock_runner = $this->getMock("Sync_SyncRunner",null,array($mock_db,'bar'));
		
		$this->setExpectedException("Exception","IntuitAnywhere OAuth account setup is incomplete.");
		
		$mock_runner->initFromDatabase();
	}
	
	protected function _initWithAccountSetup($account_setup,$override_methods)
	{
		if (!is_array($account_setup))
		{
			$account_setup = array();
		}
		$as_defaults = array(
				// what to sync
				'send_sales'=>'On',
				'send_inventory'=>'On',
				'send_orders'=>'On',
				// shops to sync
				'setup_shops'=>array(1=>'On',2=>'Off'),
				// sales taxes
				'setup_tax'=>array('footax'=>420,'bartax'=>430),
				// when
				'start_date'=>'2013-01-01',
				'data_delay'=>'-1 day',
				// accounts
				'sales'=>'selas',
				'discounts'=>'stnuocsid',
				'accounts_receivable'=>'elbaviecer_stnuocca',
				'credit_accounts'=>'stnuocca_tiderc',
				'gift_cards'=>'sdrac_tfig',
				'cogs'=>'sgoc',
				'inventory'=>'yrotnevni',
				'orders_shipping'=>'gnippihs,sredro',
				'orders_other'=>'rehto_sredro',
			);
		
		// merge defaults and specified account_setup options, account_setup will override defaults
		$account_setup = (object)array_merge($as_defaults,$account_setup);
		
		$mock_db = $this->getMock("Sync_Database");
		
		if (!is_array($override_methods))
		{
			$override_methods = array();
		}
		$override_methods[] = '_getSessionAccess';
		$override_methods[] = '_getIntuitAnywhereAccess';
		$override_methods[] = '_getMOSQBSync';
		
		$mock_runner = $this->getMock("Sync_SyncRunner",$override_methods,array($mock_db,42));
		
		$mock_runner->expects($this->exactly(4))
			->method("_getSessionAccess")
			->will($this->returnValue(
				$account_setup,
				(object)array('foo'=>'bar'),
				(object)array('bat'=>'baz'),
				(object)array('api_key'=>'beanbag')
			));
		
		$mock_runner->expects($this->once())
			->method("_getIntuitAnywhereAccess")
			->will($this->returnValue('baz'));
		
		$mock_qbsync = $this->getMock('Sync_MerchantOStoQuickBooks',array(),array('bogus','bogus','bogus'));
		
		$mock_runner->expects($this->once())
			->method("_getMOSQBSync")
			->will($this->returnValue($mock_qbsync));
		
		$mock_runner->initFromSession();
		
		$mock_runner->mock_db = $mock_db;
		$mock_runner->mock_qbsync = $mock_qbsync;
		$mock_runner->mock_account_setup = $account_setup;
		
		return $mock_runner;
	}
	
	public function testRunSetTypeAll()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->expects($this->once())
			->method('_setShopsToSync');
		$mock_runner->expects($this->once())
			->method('_setTaxAccounts');
		$mock_runner->expects($this->once())
			->method('_setAccountMapping');
		$mock_runner->expects($this->once())
			->method('_setCosting');
		
		// test set to all
		$mock_runner->mock_qbsync->expects($this->never())
			->method('setNoSales');
		$mock_runner->mock_qbsync->expects($this->never())
			->method('setNoCOGS');
		$mock_runner->mock_qbsync->expects($this->never())
			->method('setNoOrders');
		
		$mock_runner->expects($this->once())
			->method('_syncSalesInventory');
		$mock_runner->expects($this->once())
			->method('_syncOrders');
		
		$mock_runner->run(null,'all');
	}
	
	public function testRunSetTypeSales()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->exactly(2))
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->expects($this->exactly(2))
			->method('_setShopsToSync');
		$mock_runner->expects($this->exactly(2))
			->method('_setTaxAccounts');
		$mock_runner->expects($this->exactly(2))
			->method('_setAccountMapping');
		$mock_runner->expects($this->exactly(2))
			->method('_setCosting');
		
		// test set to all
		$mock_runner->mock_qbsync->expects($this->never())
			->method('setNoSales');
		$mock_runner->mock_qbsync->expects($this->once())
			->method('setNoCOGS');
		$mock_runner->mock_qbsync->expects($this->exactly(2))
			->method('setNoOrders');
		
		$mock_runner->expects($this->exactly(2))
			->method('_syncSalesInventory');
		$mock_runner->expects($this->never())
			->method('_syncOrders');
		
		$mock_runner->run(null,'sales');
		
		$mock_runner->mock_account_setup->send_orders = 'Off';
		
		$mock_runner->run(null,'all');
	}

	public function testRunSetTypeCOGS()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->exactly(2))
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->expects($this->exactly(2))
			->method('_setShopsToSync');
		$mock_runner->expects($this->exactly(2))
			->method('_setTaxAccounts');
		$mock_runner->expects($this->exactly(2))
			->method('_setAccountMapping');
		$mock_runner->expects($this->exactly(2))
			->method('_setCosting');
		
		// test set to all
		$mock_runner->mock_qbsync->expects($this->exactly(2))
			->method('setNoSales');
		$mock_runner->mock_qbsync->expects($this->never())
			->method('setNoCOGS');
		$mock_runner->mock_qbsync->expects($this->exactly(2))
			->method('setNoOrders');
		
		$mock_runner->expects($this->exactly(2))
			->method('_syncSalesInventory');
		$mock_runner->expects($this->never())
			->method('_syncOrders');
		
		$mock_runner->run(null,'cogs');
		
		$mock_runner->mock_account_setup->send_sales = 'Off';
		$mock_runner->mock_account_setup->send_orders = 'Off';
		
		$mock_runner->run(null,'all');
	}
	
	public function testRunSetTypeOrders()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->exactly(2))
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->expects($this->exactly(2))
			->method('_setShopsToSync');
		$mock_runner->expects($this->exactly(2))
			->method('_setTaxAccounts');
		$mock_runner->expects($this->exactly(2))
			->method('_setAccountMapping');
		$mock_runner->expects($this->exactly(2))
			->method('_setCosting');
		
		// test set to all
		$mock_runner->mock_qbsync->expects($this->exactly(2))
			->method('setNoSales');
		$mock_runner->mock_qbsync->expects($this->exactly(2))
			->method('setNoCOGS');
		$mock_runner->mock_qbsync->expects($this->never())
			->method('setNoOrders');
		
		$mock_runner->expects($this->never(2))
			->method('_syncSalesInventory');
		$mock_runner->expects($this->exactly(2))
			->method('_syncOrders');
		
		$mock_runner->run(null,'orders');
		
		$mock_runner->mock_account_setup->send_sales = 'Off';
		$mock_runner->mock_account_setup->send_inventory = 'Off';
		
		$mock_runner->run(null,'all');
	}
	
	public function testRunSetShopsNone()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array("setup_shops"=>array()), // use default options
			array(
				'_setType',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->expects($this->once())
			->method('_setType');
		$mock_runner->expects($this->once())
			->method('_setTaxAccounts');
		$mock_runner->expects($this->once())
			->method('_setAccountMapping');
		$mock_runner->expects($this->once())
			->method('_setCosting');
		
		$mock_runner->mock_qbsync->expects($this->never())
			->method('setSyncShop');
		
		$mock_runner->run(null,'all');
	}
	
	public function testRunSetShopsOneGoodOneBad()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array("setup_shops"=>array(1=>'On',2=>'Off')), // use default options
			array(
				'_setType',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->expects($this->once())
			->method('_setType');
		$mock_runner->expects($this->once())
			->method('_setTaxAccounts');
		$mock_runner->expects($this->once())
			->method('_setAccountMapping');
		$mock_runner->expects($this->once())
			->method('_setCosting');
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('setSyncShop')
			->with($this->equalTo(1));
		
		$mock_runner->run(null,'all');
	}
	
	public function testRunSetAccountsNone()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array('setup_tax'=>array()), // use default options
			array(
				'_setType',
				'_setShopsToSync',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->expects($this->once())
			->method('_setType');
		$mock_runner->expects($this->once())
			->method('_setShopsToSync');
		$mock_runner->expects($this->once())
			->method('_setAccountMapping');
		$mock_runner->expects($this->once())
			->method('_setCosting');
		
		$mock_runner->mock_qbsync->expects($this->never())
			->method('addTaxAccount');
		
		$mock_runner->run(null,'all');
	}
	
	public function testRunSetAccountsGood()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array('setup_tax'=>array('footax'=>420)), // use default options
			array(
				'_setType',
				'_setShopsToSync',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->expects($this->once())
			->method('_setType');
		$mock_runner->expects($this->once())
			->method('_setShopsToSync');
		$mock_runner->expects($this->once())
			->method('_setAccountMapping');
		$mock_runner->expects($this->once())
			->method('_setCosting');
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('addTaxAccount')
			->with($this->equalTo('footax'),$this->equalTo(420));
		
		$mock_runner->run(null,'all');
	}
	
	public function testRunSetAccountMapping()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array("setup_shops"=>array()), // use default options
			array(
				'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setCosting',
				'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->expects($this->once())
			->method('_setType');
		$mock_runner->expects($this->once())
			->method('_setShopsToSync');
		$mock_runner->expects($this->once())
			->method('_setTaxAccounts');
		$mock_runner->expects($this->once())
			->method('_setCosting');
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('setAccountMapping')
			->with($this->equalTo(array(
				'sales'=>'selas',
				'discounts'=>'stnuocsid',
				'accounts_receivable'=>'elbaviecer_stnuocca',
				'credit_accounts'=>'stnuocca_tiderc',
				'gift_cards'=>'sdrac_tfig',
				'cogs'=>'sgoc',
				'inventory'=>'yrotnevni',
				'orders_shipping'=>'gnippihs,sredro',
				'orders_other'=>'rehto_sredro'
			)));
		
		$mock_runner->run(null,'all');
	}
	
	public function testRunSetCosting()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_syncSalesInventory',
				'_syncOrders',
				'_getMOSOptions',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->exactly(2))
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->expects($this->exactly(2))
			->method('_setType');
		$mock_runner->expects($this->exactly(2))
			->method('_setShopsToSync');
		$mock_runner->expects($this->exactly(2))
			->method('_setTaxAccounts');
		$mock_runner->expects($this->exactly(2))
			->method('_setAccountMapping');
		
		$mock_runner->expects($this->exactly(2))
			->method('_getMOSOptions')
			->will($this->onConsecutiveCalls(array('cost_method'=>'average'),array('cost_method'=>'fifo')));
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('setFIFOCosting');
		
		$mock_runner->run(null,'all');
		$mock_runner->run(null,'all');
	}
	
	public function testRunCheckSyncSettingsFailure()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(false));
		
		$this->setExpectedException("Exception","QuickBooks or MerchantOS settings have changed, you need to check your SyncSettings.");
		$mock_runner->run(null,'all');
	}
	
	public function testRunWriteSalesLogEntry()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				//'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->expects($this->once())
			->method('_syncSalesInventory')
			->will($this->returnValue(array('foo'=>'bar')));
		
		$mock_runner->mock_db->expects($this->once())
			->method('writeAccountLogEntries')
			->with($this->equalTo(42),$this->equalTo(array('foo'=>'bar')));
		
		$mock_runner->run(null,'all');
	}
	
	public function testRunWriteOrdersLogEntry()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				//'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->expects($this->once())
			->method('_syncOrders')
			->will($this->returnValue(array('foo'=>'bar')));
		
		$mock_runner->mock_db->expects($this->once())
			->method('writeAccountLogEntries')
			->with($this->equalTo(42),$this->equalTo(array('foo'=>'bar')));
		
		$mock_runner->run(null,'all');
	}
	
	public function testRunWriteObjectsWritten()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				//'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('getObjectsWritten')
			->will($this->returnValue(array('foo'=>'bar')));
		
		$mock_runner->mock_db->expects($this->once())
			->method('writeQBObjects')
			->with($this->equalTo(42),$this->equalTo(array('foo'=>'bar')));
		
		$mock_runner->run(null,'all');
	}
	
	public function testRunStartEndDateNoSuccessNoGivenDate()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				//'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				//'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->mock_db->expects($this->once())
			->method('hasSyncSuccessDurring')
			->will($this->returnValue(false));
			
		$mock_runner->mock_db->expects($this->once())
			->method('getLastSuccessfulDataDate')
			->will($this->returnValue(null));
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('syncSales')
			->with($this->equalTo(new DateTime('2013-01-01')),$this->equalTo(new DateTime('-1 day')))
			->will($this->returnValue(null));
		
		$mock_runner->run(null,'all');
	}
	public function testRunStartEndDateWithLastSuccess()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				//'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				//'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->mock_db->expects($this->once())
			->method('hasSyncSuccessDurring')
			->will($this->returnValue(false));
		
		$last_success = new DateTime('2013-01-20');
		
		$mock_runner->mock_db->expects($this->once())
			->method('getLastSuccessfulDataDate')
			->will($this->returnValue($last_success));
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('syncSales')
			->with($this->equalTo(new DateTime($last_success->format('c') . ' + 1 day')),$this->equalTo(new DateTime('-1 day')))
			->will($this->returnValue(null));
		
		$mock_runner->run(null,'all');
	}
	public function testRunStartEndDateWithGivenDate()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				//'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				//'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->mock_db->expects($this->once())
			->method('hasSyncSuccessDurring')
			->will($this->returnValue(false));
		
		$last_success = new DateTime('2013-01-20');
		$one_date = new DateTime('2013-01-25');
		
		$mock_runner->mock_db->expects($this->once())
			->method('getLastSuccessfulDataDate')
			->will($this->returnValue($last_success));
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('syncSales')
			->with($this->equalTo($one_date),$this->equalTo($one_date))
			->will($this->returnValue(null));
		
		$mock_runner->run('2013-01-25','all');
	}
	public function testRunStartEndDateBadDateException()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				//'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				//'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$last_success = new DateTime('2013-01-20');
		$one_date = new DateTime('2020-01-25');
		
		$mock_runner->mock_db->expects($this->once())
			->method('getLastSuccessfulDataDate')
			->will($this->returnValue($last_success));
		
		$this->setExpectedException("Exception","Sync date is beyond your data delay setting.");
		
		$mock_runner->run('2020-01-25','all');
	}
	
	public function testSyncSalesInventoryDateException()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				//'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				//'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$last_success = new DateTime('2013-01-20');
		$one_date = new DateTime('2013-01-25');
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->mock_db->expects($this->once())
			->method('hasSyncSuccessDurring')
			->with('sales',42,$one_date,$one_date)
			->will($this->returnValue(true));
		
		$mock_runner->mock_db->expects($this->once())
			->method('getLastSuccessfulDataDate')
			->will($this->returnValue($last_success));
		
		$this->setExpectedException("Exception","Date range has already been synced.");
		
		$mock_runner->run('2013-01-25','all');
	}
	public function testSyncSalesInventoryLogSyncException()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				//'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				//'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$last_success = new DateTime('2013-01-20');
		$one_date = new DateTime('2013-01-25');
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->mock_db->expects($this->once())
			->method('hasSyncSuccessDurring')
			->with('sales',42,$one_date,$one_date)
			->will($this->returnValue(false));
		
		$mock_runner->mock_db->expects($this->once())
			->method('getLastSuccessfulDataDate')
			->will($this->returnValue($last_success));
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('syncSales')
			->with($this->equalTo($one_date),$this->equalTo($one_date))
			->will($this->throwException(new Exception("foo")));
		
		$mock_runner->mock_db->expects($this->once())
			->method('writeAccountLogEntries');
		
		$mock_runner->run('2013-01-25','sales');
	}
	public function testSyncSalesInventoryGood()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				//'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				//'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$last_success = new DateTime('2013-01-20');
		$one_date = new DateTime('2013-01-25');
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->mock_db->expects($this->once())
			->method('hasSyncSuccessDurring')
			->with('sales',42,$one_date,$one_date)
			->will($this->returnValue(false));
		
		$mock_runner->mock_db->expects($this->once())
			->method('getLastSuccessfulDataDate')
			->will($this->returnValue($last_success));
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('syncSales')
			->with($this->equalTo($one_date),$this->equalTo($one_date))
			->will($this->returnValue(array("foo"=>"bar")));
		
		$mock_runner->mock_db->expects($this->once())
			->method('writeAccountLogEntries')
			->with($this->equalTo(42),$this->equalTo(array("foo"=>"bar")));
		
		$mock_runner->run('2013-01-25','sales');
	}
	public function testSyncSalesInventoryGoodCOGS()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				//'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				//'_syncSalesInventory',
				'_syncOrders',
			)
		);
		
		$last_success = new DateTime('2013-01-20');
		$one_date = new DateTime('2013-01-25');
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->mock_db->expects($this->once())
			->method('hasSyncSuccessDurring')
			->with('cogs',42,$one_date,$one_date)
			->will($this->returnValue(false));
		
		$mock_runner->mock_db->expects($this->once())
			->method('getLastSuccessfulDataDate')
			->will($this->returnValue($last_success));
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('syncSales')
			->with($this->equalTo($one_date),$this->equalTo($one_date))
			->will($this->returnValue(array("foo"=>"bar")));
		
		$mock_runner->mock_db->expects($this->once())
			->method('writeAccountLogEntries')
			->with($this->equalTo(42),$this->equalTo(array("foo"=>"bar")));
		
		$mock_runner->run('2013-01-25','cogs');
	}
	
	public function testSyncOrdersDateException()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				//'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				//'_syncOrders',
			)
		);
		
		$last_success = new DateTime('2013-01-20');
		$one_date = new DateTime('2013-01-25');
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->mock_db->expects($this->once())
			->method('hasSyncSuccessDurring')
			->with('orders',42,$one_date,$one_date)
			->will($this->returnValue(true));
		
		$mock_runner->mock_db->expects($this->once())
			->method('getLastSuccessfulDataDate')
			->will($this->returnValue($last_success));
		
		$this->setExpectedException("Exception","Date range has already been synced.");
		
		$mock_runner->run('2013-01-25','all');
	}
	public function testSyncOrdersLogSyncException()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				//'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				//'_syncOrders',
			)
		);
		
		$last_success = new DateTime('2013-01-20');
		$one_date = new DateTime('2013-01-25');
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->mock_db->expects($this->once())
			->method('hasSyncSuccessDurring')
			->with('orders',42,$one_date,$one_date)
			->will($this->returnValue(false));
		
		$mock_runner->mock_db->expects($this->once())
			->method('getLastSuccessfulDataDate')
			->will($this->returnValue($last_success));
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('syncOrders')
			->with($this->equalTo($one_date),$this->equalTo($one_date))
			->will($this->throwException(new Exception("foo")));
		
		$mock_runner->mock_db->expects($this->once())
			->method('writeAccountLogEntries');
		
		$mock_runner->run('2013-01-25','orders');
	}
	public function testSyncOrdersGood()
	{
		$mock_runner = $this->_initWithAccountSetup(
			array(), // use default options
			array(
				//'_setType',
				'_setShopsToSync',
				'_setTaxAccounts',
				'_setAccountMapping',
				'_setCosting',
				'_syncSalesInventory',
				//'_syncOrders',
			)
		);
		
		$last_success = new DateTime('2013-01-20');
		$one_date = new DateTime('2013-01-25');
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('checkSyncSettings')
			->will($this->returnValue(true));
		
		$mock_runner->mock_db->expects($this->once())
			->method('hasSyncSuccessDurring')
			->with('orders',42,$one_date,$one_date)
			->will($this->returnValue(false));
		
		$mock_runner->mock_db->expects($this->once())
			->method('getLastSuccessfulDataDate')
			->will($this->returnValue($last_success));
		
		$mock_runner->mock_qbsync->expects($this->once())
			->method('syncOrders')
			->with($this->equalTo($one_date),$this->equalTo($one_date))
			->will($this->returnValue(array("foo"=>"bar")));
		
		$mock_runner->mock_db->expects($this->once())
			->method('writeAccountLogEntries')
			->with($this->equalTo(42),$this->equalTo(array("foo"=>"bar")));
		
		$mock_runner->run('2013-01-25','orders');
	}
}
