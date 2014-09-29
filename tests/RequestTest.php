<?php

use Killgt\Sermepa\Request;

class RequestTest extends PHPUnit_Framework_TestCase {

	public function testSetValidLanguage()
	{
		$request = new Request;
		$request->setLanguage('en');

		$newCode = $request->getLanguageCode();
		$newLanguage = $request->getLanguage();

		$this->assertEquals($newCode, 'en');
		$this->assertEquals($newLanguage, '002');
	}

	public function testSetInvalidLanguage()
	{
		$this->setExpectedException("Killgt\Sermepa\Exceptions\LanguageNotFoundException");

		$request = new Request;
		$request->setLanguage('ru');
	}

	public function testSetProductionEnviroment()
	{
		$request = new Request;
		$request->setProductionEnviroment();

		$this->assertTrue($request->isProductionEnviroment());
	}

	public function testSetTestEnviroment()
	{
		$request = new Request;
		$request->setTestEnviroment();

		$this->assertFalse($request->isProductionEnviroment());
	}

	public function testGetURL()
	{
		$testURL = "https://sis-t.redsys.es:25443/sis/realizarPago";
		$productionURL = "https://sis.sermepa.es/sis/realizarPago";

		$request = new Request;
		$url = $request->getURL();

		$this->assertEquals($testURL, $url);

		$request->setProductionEnviroment();
		$url = $request->getURL();

		$this->assertEquals($productionURL, $url);
	}

	public function testSetValidCurrency()
	{
		$request = new Request;
		$request->setCurrency('USD');

		$newCode = $request->getCurrencyCode();
		$newCurrency = $request->getCurrency();

		$this->assertEquals($newCode, 'USD');
		$this->assertEquals($newCurrency, '840');
	}

	public function testSetInvalidCurrency()
	{
		$this->setExpectedException("Killgt\Sermepa\Exceptions\CurrencyNotFoundException");

		$request = new Request;
		$request->setCurrency('YEN');
	}

	public function testSetValidOrder()
	{
		$request = new Request;

		$validOrderNumber = '2014aabbcc11';
		$request->setOrder($validOrderNumber);

		$newOrder = $request->getOrder();

		$this->assertEquals($newOrder, $validOrderNumber);
	}

	public function testSetInvalidOrder()
	{
		$this->setExpectedException("Killgt\Sermepa\Exceptions\InvalidOrderNumberException");
		$request = new Request;

		$invalidOrderNumber = 'j14aabbcc11';
		$request->setOrder($invalidOrderNumber);

		$invalidOrderNumber = '2014aaaccccdddbcc11';
		$request->setOrder($invalidOrderNumber);
	}

	public function testSetAmount()
	{
		$request = new Request;

		$originalAmount = 435.42;
		$newAmount = 43542;
		$request->setAmount($originalAmount);

		$savedAmount = $request->getAmount();

		$this->assertEquals($newAmount, $savedAmount);
	}

	public function testSignature()
	{
		$validSignature = "AB44EAC503809841C9BC767C6343B135CE17EE16";

		$request = new Request(5556123123, 'abcdefg123456', false, 1, 'MassiveDynamics');
		$request->setAmount(45.54);
		$request->setOrder('201409killgt');
		$request->setTransactionType(0);
		$request->setCallbackURL('http://agustin.pro/callback'); //dont try, doesn't exists
		$request->setPayer('Agustín');
		$request->setProductDescription('Long Sword');
		$signature = $request->signature();


		$this->assertEquals($validSignature, $signature);
	}

	public function testValidate()
	{
		$request = new Request(5556123123, 'abcdefg123456', false, 1, 'MassiveDynamics');

		$this->assertFalse($request->validateRequest());

		$request->setAmount(45.54);

		$this->assertFalse($request->validateRequest());

		$request->setOrder('201409killgt');

		$this->assertFalse($request->validateRequest());

		$request->setTransactionType(0);

		$this->assertFalse($request->validateRequest());

		$request->setCallbackURL('http://agustin.pro/callback'); //dont try, doesn't exists

		$this->assertFalse($request->validateRequest());

		$request->setPayer('Agustín');

		$this->assertFalse($request->validateRequest());

		$request->setProductDescription('Long Sword');

		$this->assertTrue($request->validateRequest());
	}

	public function testRender()
	{
		$request = new Request(5556123123, 'abcdefg123456', false, 1, 'MassiveDynamics');

		$request->setAmount(45.54);
		$request->setOrder('201409killgt');
		$request->setTransactionType(0);
		$request->setCallbackURL('http://agustin.pro/callback'); //dont try, doesn't exists
		$request->setPayer('Agustín');
		$request->setProductDescription('Long Sword');

		$renderedHTML = $request->render();


		$realHTML = '
		<form action="https://sis-t.redsys.es:25443/sis/realizarPago" method="post" id="sermepaForm" name="sermepaForm" >
			<input type="hidden" name="Ds_Merchant_Amount" value="4554" />
			<input type="hidden" name="Ds_Merchant_Currency" value="978" />
			<input type="hidden" name="Ds_Merchant_Order" value="201409killgt" />
			<input type="hidden" name="Ds_Merchant_MerchantData" value="" />
			<input type="hidden" name="Ds_Merchant_MerchantCode" value="5556123123" />
			<input type="hidden" name="Ds_Merchant_Terminal" value="1" />
			<input type="hidden" name="Ds_Merchant_TransactionType" value="0" />
			<input type="hidden" name="Ds_Merchant_Titular" value="Agustín" />
			<input type="hidden" name="Ds_Merchant_MerchantName" value="MassiveDynamics" />
			<input type="hidden" name="Ds_Merchant_MerchantURL" value="http://agustin.pro/callback" />
			<input type="hidden" name="Ds_Merchant_ProductDescription" value="Long Sword" />
			<input type="hidden" name="Ds_Merchant_ConsumerLanguage" value="001" />
			<input type="hidden" name="Ds_Merchant_UrlOK" value="" />
			<input type="hidden" name="Ds_Merchant_UrlKO" value="" />
			<input type="hidden" name="Ds_Merchant_PayMethods" value="T" />
			<input type="hidden" name="Ds_Merchant_MerchantSignature" value="AB44EAC503809841C9BC767C6343B135CE17EE16" />
		<script>document.forms["sermepaForm"].submit();</script></form>';

		$this->assertEquals($realHTML, $renderedHTML);
	}


	public function testCheckAutenticity()
	{
		$postData = [
			"Ds_TransactionType" => "0",
			"Ds_Card_Country" => "724",
			"Ds_Date" => "29/09/2014",
			"Ds_SecurePayment" => "1",
			"Ds_Signature" => "F0E49C153196F98FDCF77627014078CBF2FFD506",
			"Ds_Order" => "201409killgt",
			"Ds_Hour" => "23:14",
			"Ds_Response" => "0000",
			"Ds_AuthorisationCode" => "160099",
			"Ds_Currency" => "978",
			"Ds_ConsumerLanguage" => "1",
			"Ds_MerchantCode" => "5556123123",
			"Ds_Amount" => "4554",
			"Ds_Terminal" => "001"
		];

		$request = new Request(5556123123, 'abcdefg123456', false, 1, 'MassiveDynamics');

		$request->setAmount(45.54);
		$request->setOrder('201409killgt');
		$request->setCallbackURL('http://agustin.pro/callback'); //dont try, doesn't exists
		$request->setPayer('Agustín');
		$request->setProductDescription('Long Sword');
		$request->signature();

		$this->assertTrue($request->checkCallback($postData));
	}
}