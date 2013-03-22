<?php

echo "test depricated";
exit;

require_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");

require_once("MerchantOS/Accounting.class.php");

$mos_accounting = new MerchantOS_Accounting($merchantos_sess_access->api_key,$merchantos_sess_access->api_account);

$start_date = new DateTime("2012-01-01");
$start_date = $start_date->format('c');

$end_date = new DateTime();
$end_date = $end_date->format('c');

$sales_days = array();

echo "<html><body>";

$sales_by_tax_class = $mos_accounting->getTaxClassSalesByDay($start_date,$end_date);

echo "<table>";
echo "<tr>";
	echo "<th>date</th>";
	echo "<th>shopID</th>";
	echo "<th>classID</th>";
	echo "<th>taxClassName</th>";
	echo "<th>subtotal</th>";
	echo "<th>fifoCost</th>";
	echo "<th>avgCost</th>";
echo "</tr>";
foreach ($sales_by_tax_class as $sales_day_class)
{
	$shopID = (string)$sales_day_class->shopID;
	$date = (string)$sales_day_class->date;
	$tax_class = (string)$sales_day_class->taxClassName;
	$sales_days[$shopID][$date]['sales'][$tax_class] = (string)$sales_day_class->subtotal;
	$sales_days[$shopID][$date]['fifo_cogs'][$tax_class] = (string)$sales_day_class->fifoCost;
	$sales_days[$shopID][$date]['avg_cogs'][$tax_class] = (string)$sales_day_class->avgCost;
	
	echo "<tr>";
		echo "<td>" . (string)$sales_day_class->date . "</td>";
		echo "<td>" . (string)$sales_day_class->shopID . "</td>";
		echo "<td>" . (string)$sales_day_class->taxClassID . "</td>";
		echo "<td>" . (string)$sales_day_class->taxClassName . "</td>";
		echo "<td>" . (string)$sales_day_class->subtotal . "</td>";
		echo "<td>" . (string)$sales_day_class->fifoCost . "</td>";
		echo "<td>" . (string)$sales_day_class->avgCost . "</td>";
	echo "</tr>";
}
echo "</table>";

$discounts = $mos_accounting->getDiscountsByDay($start_date,$end_date);

echo "<table>";
echo "<tr>";
	echo "<th>date</th>";
	echo "<th>shopID</th>";
	echo "<th>discount</th>";
echo "</tr>";
foreach ($discounts as $discount_day)
{
	$shopID = (string)$discount_day->shopID;
	$date = (string)$discount_day->date;
	$sales_days[$shopID][$date]['discounts'] = (string)$discount_day->discount;
	
	echo "<tr>";
		echo "<td>" . (string)$discount_day->date . "</td>";
		echo "<td>" . (string)$discount_day->shopID . "</td>";
		echo "<td>" . (string)$discount_day->discount . "</td>";
	echo "</tr>";
}
echo "</table>";

$taxes_by_day = $mos_accounting->getTaxesByDay($start_date,$end_date);

echo "<table>";
echo "<tr>";
	echo "<th>date</th>";
	echo "<th>shopID</th>";
	echo "<th>taxCategoryID</th>";
	echo "<th>taxCategoryName</th>";
	echo "<th>tax</th>";
echo "</tr>";
foreach ($taxes_by_day as $tax_day)
{
	$shopID = (string)$tax_day->shopID;
	$date = (string)$tax_day->date;
	$tax_vendor = (string)$tax_day->taxCategoryName;
	$sales_days[$shopID][$date]['taxes'][$tax_vendor] = (string)$tax_day->tax;
	
	echo "<tr>";
		echo "<td>" . (string)$tax_day->date . "</td>";
		echo "<td>" . (string)$tax_day->shopID . "</td>";
		echo "<td>" . (string)$tax_day->taxCategoryID . "</td>";
		echo "<td>" . (string)$tax_day->taxCategoryName . "</td>";
		echo "<td>" . (string)$tax_day->tax . "</td>";
	echo "</tr>";
}
echo "</table>";

$payments = $mos_accounting->getPaymentsByDay($start_date,$end_date);

echo "<table>";
echo "<tr>";
	echo "<th>date</th>";
	echo "<th>shopID</th>";
	echo "<th>amount</th>";
	echo "<th>paymentTypeName</th>";
	echo "<th>paymentTypeID</th>";
echo "</tr>";
foreach ($payments as $payment_day_type)
{
	$shopID = (string)$payment_day_type->shopID;
	$date = (string)$payment_day_type->date;
	$payment_type = (string)$payment_day_type->paymentTypeName;
	$sales_days[$shopID][$date]['payments'][$payment_type] = (string)$payment_day_type->amount;
	
	echo "<tr>";
		echo "<td>" . (string)$payment_day_type->date . "</td>";
		echo "<td>" . (string)$payment_day_type->shopID . "</td>";
		echo "<td>" . (string)$payment_day_type->amount . "</td>";
		echo "<td>" . (string)$payment_day_type->paymentTypeName . "</td>";
		echo "<td>" . (string)$payment_day_type->paymentTypeID . "</td>";
	echo "</tr>";
}
echo "</table>";

$unbalanced_days = array();
echo "<h1>Sales Day</h2>";
foreach ($sales_days as $shopID=>$sales_day_shop)
{
	echo "<h2>shopID = $shopID</h2>";
	
	foreach ($sales_day_shop as $date=>$sales_data)
	{
		$balance = 0;
		
		echo "<h3>Sales Date: $date</h3>";
		echo "Tax Class Sales<ul>";
		foreach ($sales_data['sales'] as $tax_class=>$sales_subtotal)
		{
			$balance += (float)$sales_subtotal;
			echo "<li>$tax_class $sales_subtotal</li>";
		}
		echo "</ul>";
		
		echo "Discounts<ul>";
		echo "<li>Discounts " . (string)$sales_data['discounts'] . "</li>";
		echo "</ul>";
		$balance -= (float)$sales_data['discounts'];
		
		echo "Taxes<ul>";
		foreach ($sales_data['taxes'] as $tax_vendor=>$tax_subtotal)
		{
			$balance += (float)$tax_subtotal;
			echo "<li>$tax_vendor $tax_subtotal</li>";
		}
		echo "</ul>";
		
		echo "Payments<ul>";
		foreach ($sales_data['payments'] as $payment_type=>$payment_subtotal)
		{
			$balance -= (float)$payment_subtotal;
			echo "<li>$payment_type $payment_subtotal</li>";
		}
		echo "</ul>";
		
		echo "Fifo Cogs<ul>";
		foreach ($sales_data['fifo_cogs'] as $tax_class=>$fifo_cogs_subtotal)
		{
			echo "<li>$tax_class $fifo_cogs_subtotal</li>";
		}
		echo "</ul>";
		
		echo "Avg Cogs<ul>";
		foreach ($sales_data['avg_cogs'] as $tax_class=>$avg_cogs_subtotal)
		{
			echo "<li>$tax_class $avg_cogs_subtotal</li>";
		}
		echo "</ul>";
		
		if (round($balance) != 0)
		{
			$unbalanced_days[] = array($shopID,$date,$sales_data,$balance);
		}
	}
}

echo "<h1>Unbalanced Days</h1><ul>";
foreach ($unbalanced_days as $unbal_day)
{
	echo "<li>";
	var_dump($unbal_day);
	echo "</li>";
}
echo "</ul>";

$orders_by_taxclass = $mos_accounting->getOrdersByTaxClass($start_date,$end_date);

$orders = array();

echo "<table>";
echo "<tr>";
	echo "<th>date</th>";
	echo "<th>shopID</th>";
	echo "<th>vendorID</th>";
	echo "<th>vendorName</th>";
	echo "<th>taxClassID</th>";
	echo "<th>taxClassName</th>";
	echo "<th>cost</th>";
	echo "<th>orderID</th>";
	echo "<th>totalShipCost</th>";
	echo "<th>totalOtherCost</th>";
echo "</tr>";
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
	
	echo "<tr>";
		echo "<td>" . (string)$order_taxclass->date . "</td>";
		echo "<td>" . (string)$order_taxclass->shopID . "</td>";
		echo "<td>" . (string)$order_taxclass->vendorID . "</td>";
		echo "<td>" . (string)$order_taxclass->vendorName . "</td>";
		echo "<td>" . (string)$order_taxclass->taxClassID . "</td>";
		echo "<td>" . (string)$order_taxclass->taxClassName . "</td>";
		echo "<td>" . (string)$order_taxclass->cost . "</td>";
		echo "<td>" . (string)$order_taxclass->orderID . "</td>";
		echo "<td>" . (string)$order_taxclass->totalShipCost . "</td>";
		echo "<td>" . (string)$order_taxclass->totalOtherCost . "</td>";
	echo "</tr>";
}
echo "</table>";

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
}

echo "</body></html>";
