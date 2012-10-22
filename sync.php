<?php

require_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.php");

$setup_sess_access = new SessionAccess("setup");
$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");
$merchantos_sess_access = new SessionAccess("merchantos");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

require_once("MerchantOS/Option.class.php");
require_once("MerchantOS/Accounting.class.php");

try
{
	$ianywhere = new IntuitAnywhere($qb_sess_access);	
	$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = not interactive, fail if OAuth needs authorization
	
	$mos_option = new MerchantOS_Option($merchantos_sess_access->api_key,$merchantos_sess_access->api_account);
	
	$options = $mos_option->listAll();
	$average_costing = true;
	if (isset($options['cost_method']) && $options['cost_method']!="average")
	{
		$average_costing = false;
	}
	
	$send_sales = $setup_sess_access->send_sales;
	if ($send_sales == "on" || $send_sales == "On" || $send_sales->send_sales)
	{
		$send_sales = true;
	}
	else
	{
		$send_sales = false;
	}
	
	$send_inventory = $setup_sess_access->send_inventory;
	if ($send_inventory == "on" || $send_inventory == "On" || $send_inventory->send_sales)
	{
		$send_inventory = true;
	}
	else
	{
		$send_inventory = false;
	}
	
	$send_orders = $setup_sess_access->send_orders;
	if ($send_orders == "on" || $send_orders == "On" || $send_orders->send_sales)
	{
		$send_orders = true;
	}
	else
	{
		$send_orders = false;
	}
	
	$start_date = new DateTime($setup_sess_access->start_date);
	$start_date = $start_date->format('c');
	
	// data delay is an offset from todays date that DateTime knows how to translate
	$end_date = new DateTime($setup_sess_access->data_delay);
	$end_date = $end_date->format('c');
	
	$mos_accounting = new MerchantOS_Accounting($merchantos_sess_access->api_key,$merchantos_sess_access->api_account);
	
	$sales_days = array();
	
	if ($send_sales || $send_inventory)
	{
		$sales_by_tax_class = $mos_accounting->getTaxClassSalesByDay($start_date,$end_date);
		
		foreach ($sales_by_tax_class as $sales_day_class)
		{
			$shopID = (string)$sales_day_class->shopID;
			$date = (string)$sales_day_class->date;
			$tax_class = (string)$sales_day_class->taxClassName;
			$sales_days[$shopID][$date]['sales'][$tax_class] = (string)$sales_day_class->subtotal;
			$sales_days[$shopID][$date]['fifo_cogs'][$tax_class] = (string)$sales_day_class->fifoCost;
			$sales_days[$shopID][$date]['avg_cogs'][$tax_class] = (string)$sales_day_class->avgCost;
		}
	}
	
	if ($send_sales)
	{
		$discounts = $mos_accounting->getDiscountsByDay($start_date,$end_date);
		
		foreach ($discounts as $discount_day)
		{
			$shopID = (string)$discount_day->shopID;
			$date = (string)$discount_day->date;
			$sales_days[$shopID][$date]['discounts'] = (string)$discount_day->discount;
		}
		
		$taxes_by_day = $mos_accounting->getTaxesByDay($start_date,$end_date);
		
		foreach ($taxes_by_day as $tax_day)
		{
			$shopID = (string)$tax_day->shopID;
			$date = (string)$tax_day->date;
			$tax_vendor = (string)$tax_day->taxCategoryName;
			$sales_days[$shopID][$date]['taxes'][$tax_vendor] = (string)$tax_day->tax;
		}
		
		$payments = $mos_accounting->getPaymentsByDay($start_date,$end_date);
		
		foreach ($payments as $payment_day_type)
		{
			$shopID = (string)$payment_day_type->shopID;
			$date = (string)$payment_day_type->date;
			$payment_type = (string)$payment_day_type->paymentTypeName;
			$sales_days[$shopID][$date]['payments'][$payment_type] = (string)$payment_day_type->amount;
		}
	}
	
	$unbalanced_days = array();
	foreach ($sales_days as $shopID=>$sales_day_shop)
	{
		foreach ($sales_day_shop as $date=>$sales_data)
		{
			$balance = 0;
			
			require_once("IntuitAnywhere/JournalEntry.class.php");
			
			// Sales, Discounts, Tax, Payments
			if ($send_sales)
			{
				$sales_line = new IntuitAnywhere_JournalEntryLine($ianywhere);
				$sales_line->AccountId = $setup_sess_access->sales;
				$sales_line->Desc = "MerchantOS sales $date";
				$sales_line->Amount = 0;
				foreach ($sales_data['sales'] as $tax_class=>$sales_subtotal)
				{
					$sales_line->Amount += (float)$sales_subtotal;
					$balance += (float)$sales_subtotal;
				}
				if ($sales_line->Amount>0)
				{
					$sales_line->PostingType = "Credit";
				}
				else
				{
					$sales_line->PostingType = "Debit";
				}
				
				$discounts_line = new IntuitAnywhere_JournalEntryLine($ianywhere);
				$discounts_line->AccountId = $setup_sess_access->discounts;
				$discounts_line->Desc = "MerchantOS discounts $date";
				$discounts_line->Amount = (float)$sales_data['discounts'];
				$balance -= (float)$sales_data['discounts'];
				if ($sales_line->Amount>0)
				{
					$discounts_line->PostingType = "Debit";
				}
				else
				{
					$discounts_line->PostingType = "Credit";
				}
				
				/**
				 * @todo Need let them pick a tax vendor for each sales tax
				 */
				$tax_line = new IntuitAnywhere_JournalEntryLine($ianywhere);
				$tax_line->AccountId = $setup_sess_access->tax;
				$tax_line->Desc = "MerchantOS sales tax $date";
				$tax_line->Amount = 0;
				foreach ($sales_data['taxes'] as $tax_vendor=>$tax_subtotal)
				{
					$tax_line->Amount += (float)$tax_subtotal;
					$balance += (float)$tax_subtotal;
				}
				if ($tax_line->Amount>0)
				{
					$tax_line->PostingType = "Credit";
				}
				else
				{
					$tax_line->PostingType = "Debit";
				}
				
				$payment_line = new IntuitAnywhere_JournalEntryLine($ianywhere);
				$payment_line->AccountId = $setup_sess_access->payments;
				$payment_line->Desc = "MerchantOS payments $date";
				$payment_line->Amount = 0;
				foreach ($sales_data['payments'] as $payment_type=>$payment_subtotal)
				{
					$payment_line->Amount += (float)$payment_subtotal;
					$balance -= (float)$payment_subtotal;
				}
				if ($sales_line->Amount>0)
				{
					$payment_line->PostingType = "Debit";
				}
				else
				{
					$payment_line->PostingType = "Credit";
				}
				
				if (round($balance) != 0)
				{
					$unbalanced_days[] = array($shopID,$date,$sales_data,$balance);
					continue; // don't sync unbalanced
				}
				
				$lines = array();
				if ($sales_line->Amount>0)
				{
					$lines[] = $sales_line;
				}
				if ($discounts_line->Amount>0)
				{
					$lines[] = $discounts_line;
				}
				if ($tax_line->Amount>0)
				{
					$lines[] = $tax_line;
				}
				if ($payment_line->Amount>0)
				{
					$lines[] = $payment_line;
				}
				if (count($lines)>0)
				{
					$journalentry = new IntuitAnywhere_JournalEntry($ianywhere);
					$journalentry->Lines = $lines;
					$journalentry->HeaderTxnDate = new DateTime($date);
					$journalentry->HeaderNote = "Retail Sales from MerchantOS $date";
					$journalentry->save();
				}
			}
			
			// Inventory, COGS
			if ($send_inventory)
			{
				$inventory_line = new IntuitAnywhere_JournalEntryLine($ianywhere);
				$inventory_line->AccountId = $setup_sess_access->inventory;
				$inventory_line->Desc = "MerchantOS Inventory Sold $date";
				$inventory_line->Amount = 0;
				
				$cogs_line = new IntuitAnywhere_JournalEntryLine($ianywhere);
				$cogs_line->AccountId = $setup_sess_access->cogs;
				$cogs_line->Desc = "MerchantOS COGS $date";
				$cogs_line->Amount = 0;
				if ($average_costing)
				{
					foreach ($sales_data['avg_cogs'] as $tax_class=>$avg_cogs_subtotal)
					{
						$inventory_line->Amount += (float)$avg_cogs_subtotal;
						$cogs_line->Amount += (float)$avg_cogs_subtotal;
					}
				}
				else
				{
					foreach ($sales_data['fifo_cogs'] as $tax_class=>$fifo_cogs_subtotal)
					{
						$inventory_line->Amount += (float)$fifo_cogs_subtotal;
						$cogs_line->Amount += (float)$fifo_cogs_subtotal;
					}
				}
				if ($inventory_line->Amount>0)
				{
					$inventory_line->PostingType = "Credit";
					$cogs_line->PostingType = "Debit";
				}
				else
				{
					$inventory_line->PostingType = "Debit";
					$cogs_line->PostingType = "Credit";
				}
				
				$lines = array();
				if ($inventory_line->Amount>0)
				{
					$lines[] = $inventory_line;
					$lines[] = $cogs_line;
				}
				
				$journalentry = new IntuitAnywhere_JournalEntry($ianywhere);
				$journalentry->Lines = $lines;
				$journalentry->HeaderTxnDate = new DateTime($date);
				$journalentry->HeaderNote = "COGS and Inventory from MerchantOS $date";
				$journalentry->save();
			}
		}
	}
	
	/*
	echo "<h1>Unbalanced Days</h1><ul>";
	foreach ($unbalanced_days as $unbal_day)
	{
		echo "<li>";
		var_dump($unbal_day);
		echo "</li>";
	}
	echo "</ul>";
	*/
	
	$orders_by_taxclass = $mos_accounting->getOrdersByTaxClass($start_date,$end_date);
	
	$orders = array();
	
	foreach ($orders_by_taxclass as $order_taxclass)
	{
		$orderID = (string)$order_taxclass->orderID;
		$tax_class = (string)$order_taxclass->taxClassName;
		$cost = (string)$order_taxclass->cost;
		$vendor = (string)$order_taxclass->vendorName;
		$shipCost = (string)$order_taxclass->totalShipCost;
		$otherCost = (string)$order_taxclass->totalOtherCost;
		
		$orders[$orderID]['vendor'] = $vendor;
		$orders[$orderID]['shipCost'] = $shipCost;
		$orders[$orderID]['otherCost'] = $otherCost;
		$orders[$orderID]['lines'][$tax_class] = $cost;
	}
	
	echo "<h1>Purchase Orders</h2>";
	foreach ($orders as $orderID=>$order)
	{
		echo "<h3>Order #$orderID</h3><ul>";
		echo "<li>Vendor: " . $order['vendor'] . "</li>";
		echo "<li>shipCost: " . $order['shipCost'] . "</li>";
		echo "<li>otherCost: " . $order['otherCost'] . "</li>";
		echo "<li>lines:<ul>";
		foreach ($order['lines'] as $tax_class=>$subtotal)
		{
			echo "<li>$tax_class: $subtotal</li>";
		}
		echo "</ul></li>";
		echo "</ul>";
		
		break;
	}
	
	echo "</body></html>";
}	
catch(Exception $e)
{
	echo "Exception: " . $e->getMessage();
	var_dump($e);
}
