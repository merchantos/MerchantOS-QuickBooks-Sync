<?php
require_once("config.inc.php");
require_once("IntuitAnywhere/IntuitAnywhere.class.php");
require_once("MerchantOS/Accounting.class.php");
require_once("MerchantOS/Shop.class.php");
require_once("Sync/MerchantOStoQuickBooks.class.php");

/**
 * class Sync_MerchantOStoQuickBooksTest
 */
class Sync_MerchantOStoQuickBooksTest extends PHPUnit_Framework_TestCase
{
	protected $_mock_sync;
	
	public function setUp()
	{
		$this->_setupMock();
	}
	
	protected function _setupMock($set_return_data=true)
	{
		$mock_ianywhere = $this->getMock("IntuitAnywhere",array(),array(new stdClass()));
		$mock_mos_accounting = $this->getMock("MerchantOS_Accounting",array(),array('foo','bar'));
		$mock_mos_shop = $this->getMock("MerchantOS_Shop",array(),array('foo','bar'));
		
		$mock_mos_shop->expects($this->any())
			->method("listAll")
			->will($this->returnValue(
				array(
					array('shopID'=>142,'name'=>'fooshop'),
					array('shopID'=>143,'name'=>'barshop'),
				)
			)
		);
		
		if ($set_return_data)
		{
			$mock_mos_accounting->expects($this->any())
				->method("getOrdersByTaxClass")
				->will($this->returnValue(
					array(
						(object)array('shopID'=>142,'date'=>'01/01/2013','taxClassID'=>242,'taxClassName'=>'footax','vendorID'=>342,'vendorName'=>'Foo Vendor','cost'=>42.43,'orderID'=>342,'totalShipCost'=>43.44,'totalOtherCost'=>44.45),
						(object)array('shopID'=>142,'date'=>'01/01/2013','taxClassID'=>243,'taxClassName'=>'bartax','vendorID'=>342,'vendorName'=>'Foo Vendor','cost'=>42.43,'orderID'=>342,'totalShipCost'=>43.44,'totalOtherCost'=>44.45),
						(object)array('shopID'=>142,'date'=>'01/02/2013','taxClassID'=>242,'taxClassName'=>'footax','vendorID'=>343,'vendorName'=>'Bar Vendor','cost'=>42.43,'orderID'=>343,'totalShipCost'=>43.44,'totalOtherCost'=>44.45),
						(object)array('shopID'=>143,'date'=>'01/03/2013','taxClassID'=>243,'taxClassName'=>'bartax','vendorID'=>344,'vendorName'=>'Bat Vendor','cost'=>42.43,'orderID'=>344,'totalShipCost'=>43.44,'totalOtherCost'=>44.45),
					)
				)
			);
			$mock_mos_accounting->expects($this->any())
				->method("getTaxClassSalesByDay")
				->will($this->returnValue(
					array(
						(object)array('shopID'=>142,'date'=>'01/01/2013','taxClassName'=>'footax','subtotal'=>42.01,'fifoCost'=>4.2,'avgCost'=>5.2),
						(object)array('shopID'=>142,'date'=>'01/02/2013','taxClassName'=>'footax','subtotal'=>43.01,'fifoCost'=>4.3,'avgCost'=>5.3),
						(object)array('shopID'=>142,'date'=>'01/03/2013','taxClassName'=>'footax','subtotal'=>44.01,'fifoCost'=>4.4,'avgCost'=>5.4),
						(object)array('shopID'=>143,'date'=>'01/02/2013','taxClassName'=>'footax','subtotal'=>45.01,'fifoCost'=>4.5,'avgCost'=>5.5),
						(object)array('shopID'=>143,'date'=>'01/03/2013','taxClassName'=>'footax','subtotal'=>-10,'fifoCost'=>-4,'avgCost'=>-5),
					)
				)
			);
			$mock_mos_accounting->expects($this->any())
				->method("getDiscountsByDay")
				->will($this->returnValue(
					array(
						(object)array('shopID'=>142,'date'=>'01/01/2013','discount'=>4.2),
						(object)array('shopID'=>142,'date'=>'01/03/2013','discount'=>5.2),
						(object)array('shopID'=>143,'date'=>'01/02/2013','discount'=>6.2),
					)	
				)
			);
			$mock_mos_accounting->expects($this->any())
				->method("getTaxesByDay")
				->will($this->returnValue(
					array(
						(object)array('shopID'=>142,'date'=>'01/01/2013','taxCategoryName'=>'foo sales tax','tax'=>4.2),
						(object)array('shopID'=>142,'date'=>'01/01/2013','taxCategoryName'=>'bar sales tax','tax'=>4.3),
						(object)array('shopID'=>142,'date'=>'01/02/2013','taxCategoryName'=>'foo sales tax','tax'=>4.4),
						(object)array('shopID'=>143,'date'=>'01/02/2013','taxCategoryName'=>'foo sales tax','tax'=>4.5),
					)	
				)
			);
			$mock_mos_accounting->expects($this->any())
				->method("getPaymentsByDay")
				->will($this->returnValue(
					array(
						(object)array('shopID'=>142,'date'=>'01/01/2013','paymentTypeName'=>'foo payment type','amount'=>(32.01-4.2)), // shop142 covers 32.01 of subtotal of 01/01 and 4.2 discounts
						(object)array('shopID'=>142,'date'=>'01/01/2013','paymentTypeName'=>'bar payment type','amount'=>(10.00+4.2+4.3)), // shop142 covers 10.00 of subtotal of 01/01 and 4.2+4.3 tax
						(object)array('shopID'=>142,'date'=>'01/02/2013','paymentTypeName'=>'foo payment type','amount'=>(43.01+4.4)), // shop142 covers 43.01 of subtotal of 01/02 and 4.4 tax
						(object)array('shopID'=>142,'date'=>'01/03/2013','paymentTypeName'=>'foo payment type','amount'=>(44.01-5.2)), // shop142 covers 43.01 of subtotal of 01/03 and 5.2 discount
						(object)array('shopID'=>143,'date'=>'01/02/2013','paymentTypeName'=>'foo payment type','amount'=>(45.01-6.2+4.5)), // shop143 covers 45.01 of subtotal of 01/01 and 6.2 discounts and 4.5 tax
						(object)array('shopID'=>143,'date'=>'01/03/2013','paymentTypeName'=>'bat payment type','amount'=>(-10)), // shop143 10 refund
					)	
				)
			);
		}
		
		$mock_sync = $this->getMock(
			"Sync_MerchantOStoQuickBooks",
			array(
				'_getIntuitAnywhereBill',
				'_getIntuitAnywhereBillLine',
				'_getIntuitAnywhereJournalEntryLine',
				'_getIntuitAnywhereJournalEntry',
				'_getIntuitAnywherePayment',
				'_getIntuitAnywherePaymentMethod',
				'_getIntuitAnywhereCustomer',
				'_getIntuitAnywhereClass',
				'_getIntuitAnywhereAccount',
				'_getIntuitAnywhereVendor',
			),
			array(
				$mock_mos_accounting,
				$mock_mos_shop,
				$mock_ianywhere
			)
		);
		
		$mock_sync->expects($this->any())
			->method('_getIntuitAnywhereBill')
			->will($this->returnValue($this->_getMockIntuitAnywhereModel('IntuitAnywhere_Bill',60,$mock_ianywhere)));
		
		$mock_sync->expects($this->any())
			->method('_getIntuitAnywhereBillLine')
			->will($this->returnValue($this->_getMockIntuitAnywhereModel('IntuitAnywhere_BillLine',70,$mock_ianywhere)));
		
		$mock_sync->expects($this->any())
			->method('_getIntuitAnywhereJournalEntry')
			->will($this->returnValue($this->_getMockIntuitAnywhereModel('IntuitAnywhere_JournalEntry',80,$mock_ianywhere)));
		
		$mock_sync->expects($this->any())
			->method('_getIntuitAnywhereJournalEntryLine')
			->will($this->returnValue($this->_getMockIntuitAnywhereModel('IntuitAnywhere_JournalEntryLine',90,$mock_ianywhere)));
		
		$mock_sync->expects($this->any())
			->method('_getIntuitAnywherePayment')
			->will($this->returnValue($this->_getMockIntuitAnywhereModel('IntuitAnywhere_Payment',100,$mock_ianywhere)));
		
		$mock_sync->expects($this->any())
			->method('_getIntuitAnywherePaymentMethod')
			->will($this->returnValue($this->_getMockIntuitAnywhereModel('IntuitAnywhere_PaymentMethod',200,$mock_ianywhere)));
		
		$mock_sync->expects($this->any())
			->method('_getIntuitAnywhereCustomer')
			->will($this->returnValue($this->_getMockIntuitAnywhereModel('IntuitAnywhere_Customer',300,$mock_ianywhere)));
		
		$mock_sync->expects($this->any())
			->method('_getIntuitAnywhereClass')
			->will($this->returnValue($this->_getMockIntuitAnywhereModel('IntuitAnywhere_Class',400,$mock_ianywhere)));
		
		$mock_sync->expects($this->any())
			->method('_getIntuitAnywhereAccount')
			->will($this->returnValue($this->_getMockIntuitAnywhereModel('IntuitAnywhere_Account',500,$mock_ianywhere)));
		
		$mock_sync->expects($this->any())
			->method('_getIntuitAnywhereVendor')
			->will($this->returnValue($this->_getMockIntuitAnywhereModel('IntuitAnywhere_Vendor',600,$mock_ianywhere)));
		
		$mock_sync->setAccountMapping(array(
			'sales'=>1,
			'discounts'=>2,
			'accounts_receivable'=>3,
			'credit_accounts'=>4,
			'gift_cards'=>5,
			'cogs'=>6,
			'inventory'=>7,
			'orders_shipping'=>8,
			'orders_other'=>9,
		));
		
		$this->_mock_sync = $mock_sync;
		$this->_mock_sync->mock_ianywhere = $mock_ianywhere;
		$this->_mock_sync->mock_mos_accounting = $mock_mos_accounting;
		$this->_mock_sync->mock_mos_shop = $mock_mos_shop;
	}
	
	protected function _getMockIntuitAnywhereModel($classname,$idstart,$mock_ianywhere)
	{
		$mock_i = $this->getMock('IntuitAnywhere_Vendor',array(),array($mock_ianywhere));
		$mock_i->Id = $idstart+20;
		$mock_i->expects($this->any())
			->method('listAll')
			->will($this->onConsecutiveCalls(
				array(),
				array((object)array('Id'=>$idstart+21)),
				array((object)array('Id'=>$idstart+22)),
				array((object)array('Id'=>$idstart+23)),
				array((object)array('Id'=>$idstart+24)),
				array((object)array('Id'=>$idstart+25))
			));
		$mock_i->expects($this->any())
			->method('getType')
			->will($this->returnValue($classname));
		
		return $mock_i;
	}
	
	public function tearDown()
	{
		unset($this->_mock_sync);
	}
	
	public function testConstruct()
	{
		$this->assertTrue(is_object($this->_mock_sync));
	}
	
	public function testSyncNothing()
	{
		$this->_mock_sync->setSyncShop(142);
		$this->_mock_sync->setSyncShop(143);
		
		$this->_mock_sync->addTaxAccount('foo sales tax',10);
		$this->_mock_sync->addTaxAccount('bar sales tax',11);
		
		$this->_mock_sync->setNoSales();
		$this->_mock_sync->setNoCOGS();
		$this->_mock_sync->setNoOrders();
		
		$sales_sync_res = $this->_mock_sync->syncSales(new DateTime(),new DateTime());
		$orders_sync_res = $this->_mock_sync->syncOrders(new DateTime(),new DateTime());
		$objects_synced = $this->_mock_sync->getObjectsWritten();
		
		$this->assertEquals(array(),$sales_sync_res);
		$this->assertEquals(array(),$orders_sync_res);
		$this->assertEquals(array(),$objects_synced);
	}
	
	public function testSyncNoSalesBecauseOfShops()
	{
		//$this->_mock_sync->setSyncShop(142);
		//$this->_mock_sync->setSyncShop(143);
		
		$this->_mock_sync->addTaxAccount('foo sales tax',10);
		$this->_mock_sync->addTaxAccount('bar sales tax',11);
		
		//$this->_mock_sync->setNoSales();
		$this->_mock_sync->setNoCOGS();
		$this->_mock_sync->setNoOrders();
		
		$sales_sync_res = $this->_mock_sync->syncSales(new DateTime(),new DateTime());
		$orders_sync_res = $this->_mock_sync->syncOrders(new DateTime(),new DateTime());
		$objects_synced = $this->_mock_sync->getObjectsWritten();
		
		//$this->assertEquals(array(),$sales_sync_res);
		$this->assertEquals(array(),$orders_sync_res);
		$this->assertEquals(array(),$objects_synced);
		
		$this->assertEquals(date_format(new DateTime(),"m/d/Y"),$sales_sync_res[0]['date']);
		$this->assertEquals('No sales/COGS to sync.',$sales_sync_res[0]['msg']);
		$this->assertEquals('1',$sales_sync_res[0]['success']);
		$this->assertEquals('',$sales_sync_res[0]['alert']);
		$this->assertEquals('sales',$sales_sync_res[0]['type']);
	}
	
	public function testSyncNoSalesBecauseOfData()
	{
		$this->_setupMock(false);
		
		$this->_mock_sync->setSyncShop(142);
		$this->_mock_sync->setSyncShop(143);
		
		$this->_mock_sync->addTaxAccount('foo sales tax',10);
		$this->_mock_sync->addTaxAccount('bar sales tax',11);
		
		//$this->_mock_sync->setNoSales();
		$this->_mock_sync->setNoCOGS();
		$this->_mock_sync->setNoOrders();
		
		$sales_sync_res = $this->_mock_sync->syncSales(new DateTime(),new DateTime());
		$orders_sync_res = $this->_mock_sync->syncOrders(new DateTime(),new DateTime());
		$objects_synced = $this->_mock_sync->getObjectsWritten();
		
		//$this->assertEquals(array(),$sales_sync_res);
		$this->assertEquals(array(),$orders_sync_res);
		$this->assertEquals(array(),$objects_synced);
		
		$this->assertEquals(date_format(new DateTime(),"m/d/Y"),$sales_sync_res[0]['date']);
		$this->assertEquals('No sales/COGS to sync.',$sales_sync_res[0]['msg']);
		$this->assertEquals('1',$sales_sync_res[0]['success']);
		$this->assertEquals('',$sales_sync_res[0]['alert']);
		$this->assertEquals('sales',$sales_sync_res[0]['type']);
	}
	
	public function testSyncSales()
	{
		$this->_mock_sync->setSyncShop(142);
		$this->_mock_sync->setSyncShop(143);
		
		$this->_mock_sync->addTaxAccount('foo sales tax',10);
		$this->_mock_sync->addTaxAccount('bar sales tax',11);
		
		$this->_mock_sync->setNoCOGS();
		$this->_mock_sync->setNoOrders();
		
		$sales_sync_res = $this->_mock_sync->syncSales(new DateTime(),new DateTime());
		$orders_sync_res = $this->_mock_sync->syncOrders(new DateTime(),new DateTime());
		$objects_synced = $this->_mock_sync->getObjectsWritten();
		
		$this->assertEquals(array(),$orders_sync_res);
		//$this->assertEquals(array(),$sales_sync_res);
		//$this->assertEquals(array(),$objects_synced);
		
		$this->assertEquals('01/01/2013',$sales_sync_res[0]['date']);
		$this->assertEquals('fooshop: Sent $42.01 Sales, $4.2 Discounts, $8.5 Tax, $46.31 Payments.',$sales_sync_res[0]['msg']);
		$this->assertEquals('1',$sales_sync_res[0]['success']);
		$this->assertEquals('',$sales_sync_res[0]['alert']);
		$this->assertEquals('sales',$sales_sync_res[0]['type']);
		
		$this->assertEquals('01/02/2013',$sales_sync_res[1]['date']);
		$this->assertEquals('fooshop: Sent $43.01 Sales, $0 Discounts, $4.4 Tax, $47.41 Payments.',$sales_sync_res[1]['msg']);
		$this->assertEquals('1',$sales_sync_res[1]['success']);
		$this->assertEquals('',$sales_sync_res[1]['alert']);
		$this->assertEquals('sales',$sales_sync_res[1]['type']);
		
		$this->assertEquals('01/03/2013',$sales_sync_res[2]['date']);
		$this->assertEquals('fooshop: Sent $44.01 Sales, $5.2 Discounts, $0 Tax, $38.81 Payments.',$sales_sync_res[2]['msg']);
		$this->assertEquals('1',$sales_sync_res[2]['success']);
		$this->assertEquals('',$sales_sync_res[2]['alert']);
		$this->assertEquals('sales',$sales_sync_res[2]['type']);
		
		$this->assertEquals('01/02/2013',$sales_sync_res[3]['date']);
		$this->assertEquals('barshop: Sent $45.01 Sales, $6.2 Discounts, $4.5 Tax, $43.31 Payments.',$sales_sync_res[3]['msg']);
		$this->assertEquals('1',$sales_sync_res[3]['success']);
		$this->assertEquals('',$sales_sync_res[3]['alert']);
		$this->assertEquals('sales',$sales_sync_res[3]['type']);
		
		$this->assertEquals('01/03/2013',$sales_sync_res[4]['date']);
		$this->assertEquals('barshop: Sent $-10 Sales, $0 Discounts, $0 Tax, $-10 Payments.',$sales_sync_res[4]['msg']);
		$this->assertEquals('1',$sales_sync_res[4]['success']);
		$this->assertEquals('',$sales_sync_res[4]['alert']);
		$this->assertEquals('sales',$sales_sync_res[4]['type']);
		
		$jounalentries = 0;
		$payments = 0;
		$payment_methods = 0;
		foreach ($objects_synced as $obj_sync)
		{
			switch ($obj_sync['type'])
			{
				case 'IntuitAnywhere_JournalEntry':
					$jounalentries++;
					break;
				case 'IntuitAnywhere_Payment':
					$payments++;
					break;
				case 'IntuitAnywhere_PaymentMethod':
					$payment_methods++;
					break;
			}
		}
		
		$this->assertEquals(5,$jounalentries);
		$this->assertEquals(5,$payments);
		$this->assertEquals(1,$payment_methods);
	}
	
	public function testSyncCOGS()
	{
		$this->_mock_sync->setSyncShop(142);
		$this->_mock_sync->setSyncShop(143);
		
		$this->_mock_sync->addTaxAccount('foo sales tax',10);
		$this->_mock_sync->addTaxAccount('bar sales tax',11);
		
		$this->_mock_sync->setNoSales();
		$this->_mock_sync->setNoOrders();
		
		$sales_sync_res = $this->_mock_sync->syncSales(new DateTime(),new DateTime());
		$orders_sync_res = $this->_mock_sync->syncOrders(new DateTime(),new DateTime());
		$objects_synced = $this->_mock_sync->getObjectsWritten();
		
		$this->assertEquals(array(),$orders_sync_res);
		//$this->assertEquals(array(),$sales_sync_res);
		//$this->assertEquals(array(),$objects_synced);
		
		$this->assertEquals('01/01/2013',$sales_sync_res[0]['date']);
		$this->assertEquals('fooshop: Sent $5.2 in Cost of Goods Sold.',$sales_sync_res[0]['msg']);
		$this->assertEquals('1',$sales_sync_res[0]['success']);
		$this->assertEquals('',$sales_sync_res[0]['alert']);
		$this->assertEquals('cogs',$sales_sync_res[0]['type']);
		
		$this->assertEquals('01/02/2013',$sales_sync_res[1]['date']);
		$this->assertEquals('fooshop: Sent $5.3 in Cost of Goods Sold.',$sales_sync_res[1]['msg']);
		$this->assertEquals('1',$sales_sync_res[1]['success']);
		$this->assertEquals('',$sales_sync_res[1]['alert']);
		$this->assertEquals('cogs',$sales_sync_res[1]['type']);
		
		$this->assertEquals('01/03/2013',$sales_sync_res[2]['date']);
		$this->assertEquals('fooshop: Sent $5.4 in Cost of Goods Sold.',$sales_sync_res[2]['msg']);
		$this->assertEquals('1',$sales_sync_res[2]['success']);
		$this->assertEquals('',$sales_sync_res[2]['alert']);
		$this->assertEquals('cogs',$sales_sync_res[2]['type']);
		
		$this->assertEquals('01/02/2013',$sales_sync_res[3]['date']);
		$this->assertEquals('barshop: Sent $5.5 in Cost of Goods Sold.',$sales_sync_res[3]['msg']);
		$this->assertEquals('1',$sales_sync_res[3]['success']);
		$this->assertEquals('',$sales_sync_res[3]['alert']);
		$this->assertEquals('cogs',$sales_sync_res[3]['type']);
		
		$this->assertEquals('01/03/2013',$sales_sync_res[4]['date']);
		$this->assertEquals('barshop: Sent $-5 in Cost of Goods Sold.',$sales_sync_res[4]['msg']);
		$this->assertEquals('1',$sales_sync_res[4]['success']);
		$this->assertEquals('',$sales_sync_res[4]['alert']);
		$this->assertEquals('cogs',$sales_sync_res[4]['type']);
		
		$jounalentries = 0;
		$payments = 0;
		$payment_methods = 0;
		foreach ($objects_synced as $obj_sync)
		{
			switch ($obj_sync['type'])
			{
				case 'IntuitAnywhere_JournalEntry':
					$jounalentries++;
					break;
				case 'IntuitAnywhere_Payment':
					$payments++;
					break;
				case 'IntuitAnywhere_PaymentMethod':
					$payment_methods++;
					break;
			}
		}
		
		$this->assertEquals(5,$jounalentries);
		$this->assertEquals(0,$payments);
		$this->assertEquals(0,$payment_methods);
	}
	
	public function testSyncOrders()
	{
		$this->_mock_sync->setSyncShop(142);
		$this->_mock_sync->setSyncShop(143);
		
		$this->_mock_sync->addTaxAccount('foo sales tax',10);
		$this->_mock_sync->addTaxAccount('bar sales tax',11);
		
		$this->_mock_sync->setNoSales();
		$this->_mock_sync->setNoCOGS();
		
		$sales_sync_res = $this->_mock_sync->syncSales(new DateTime(),new DateTime());
		$orders_sync_res = $this->_mock_sync->syncOrders(new DateTime(),new DateTime());
		$objects_synced = $this->_mock_sync->getObjectsWritten();
		
		//$this->assertEquals(array(),$orders_sync_res);
		$this->assertEquals(array(),$sales_sync_res);
		//$this->assertEquals(array(),$objects_synced);
		
		$this->assertEquals('01/03/2013',$orders_sync_res[0]['date']);
		$this->assertEquals('fooshop: Order #342 synced.',$orders_sync_res[0]['msg']);
		$this->assertEquals('1',$orders_sync_res[0]['success']);
		$this->assertEquals('',$orders_sync_res[0]['alert']);
		$this->assertEquals('orders',$orders_sync_res[0]['type']);
		
		$this->assertEquals('01/03/2013',$orders_sync_res[1]['date']);
		$this->assertEquals('fooshop: Order #343 synced.',$orders_sync_res[1]['msg']);
		$this->assertEquals('1',$orders_sync_res[1]['success']);
		$this->assertEquals('',$orders_sync_res[1]['alert']);
		$this->assertEquals('orders',$orders_sync_res[1]['type']);
		
		$this->assertEquals('01/03/2013',$orders_sync_res[2]['date']);
		$this->assertEquals('barshop: Order #344 synced.',$orders_sync_res[2]['msg']);
		$this->assertEquals('1',$orders_sync_res[2]['success']);
		$this->assertEquals('',$orders_sync_res[2]['alert']);
		$this->assertEquals('orders',$orders_sync_res[2]['type']);
		
		$classes = 0;
		$vendors = 0;
		$bills = 0;
		foreach ($objects_synced as $obj_sync)
		{
			switch ($obj_sync['type'])
			{
				case 'IntuitAnywhere_Class':
					$classes++;
					break;
				case 'IntuitAnywhere_Vendor':
					$vendors++;
					break;
				case 'IntuitAnywhere_Bill':
					$bills++;
					break;
			}
		}
		
		$this->assertEquals(1,$classes);
		$this->assertEquals(1,$vendors);
		$this->assertEquals(3,$bills);
	}
	
	
	public function testSyncNoOrdersBecauseOfShops()
	{
		//$this->_mock_sync->setSyncShop(142);
		//$this->_mock_sync->setSyncShop(143);
		
		$this->_mock_sync->addTaxAccount('foo sales tax',10);
		$this->_mock_sync->addTaxAccount('bar sales tax',11);
		
		$this->_mock_sync->setNoSales();
		$this->_mock_sync->setNoCOGS();
		//$this->_mock_sync->setNoOrders();
		
		$sales_sync_res = $this->_mock_sync->syncSales(new DateTime(),new DateTime());
		$orders_sync_res = $this->_mock_sync->syncOrders(new DateTime(),new DateTime());
		$objects_synced = $this->_mock_sync->getObjectsWritten();
		
		$this->assertEquals(array(),$sales_sync_res);
		//$this->assertEquals(array(),$orders_sync_res);
		$this->assertEquals(array(),$objects_synced);
		
		$this->assertEquals(date_format(new DateTime(),"m/d/Y"),$orders_sync_res[0]['date']);
		$this->assertEquals('No orders to sync.',$orders_sync_res[0]['msg']);
		$this->assertEquals('1',$orders_sync_res[0]['success']);
		$this->assertEquals('',$orders_sync_res[0]['alert']);
		$this->assertEquals('orders',$orders_sync_res[0]['type']);
	}
	
	public function testSyncNoOrdersBecauseOfData()
	{
		$this->_setupMock(false);
		
		$this->_mock_sync->setSyncShop(142);
		$this->_mock_sync->setSyncShop(143);
		
		$this->_mock_sync->addTaxAccount('foo sales tax',10);
		$this->_mock_sync->addTaxAccount('bar sales tax',11);
		
		$this->_mock_sync->setNoSales();
		$this->_mock_sync->setNoCOGS();
		//$this->_mock_sync->setNoOrders();
		
		$sales_sync_res = $this->_mock_sync->syncSales(new DateTime(),new DateTime());
		$orders_sync_res = $this->_mock_sync->syncOrders(new DateTime(),new DateTime());
		$objects_synced = $this->_mock_sync->getObjectsWritten();
		
		$this->assertEquals(array(),$sales_sync_res);
		//$this->assertEquals(array(),$orders_sync_res);
		$this->assertEquals(array(),$objects_synced);
		
		$this->assertEquals(date_format(new DateTime(),"m/d/Y"),$orders_sync_res[0]['date']);
		$this->assertEquals('No orders to sync.',$orders_sync_res[0]['msg']);
		$this->assertEquals('1',$orders_sync_res[0]['success']);
		$this->assertEquals('',$orders_sync_res[0]['alert']);
		$this->assertEquals('orders',$orders_sync_res[0]['type']);
	}
}
