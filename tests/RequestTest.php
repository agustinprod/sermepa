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
		$productionURL = "https://sis.redsys.es/sis/realizarPago";

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

		$invalidOrderNumber = '1636T0000344123123';
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
		$validSignature = "4RvVYv/GaPyQKnCPwVaj5j27ZgY5o5soFr/v6wUtoMA=";

		$request = new Request(5556123123, 'Mk9m98IfEblmPfrpsawt7BmxObt98Jev', false, 1, 'MassiveDynamics');
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
		$request = new Request(5556123123, 'Mk9m98IfEblmPfrpsawt7BmxObt98Jev', false, 1, 'MassiveDynamics');

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
		$request = new Request(5556123123, 'Mk9m98IfEblmPfrpsawt7BmxObt98Jev', false, 1, 'MassiveDynamics');

		$request->setAmount(45.54);
		$request->setOrder('201409killgt');
		$request->setTransactionType(0);
		$request->setCallbackURL('http://agustin.pro/callback'); //dont try, doesn't exists
		$request->setPayer('Agustín');
		$request->setProductDescription('Long Sword');

		$renderedHTML = $request->render();


		$realHTML = '
		<form action="http://sis-d.redsys.es/sis/realizarPago" method="post" id="sermepaForm" name="sermepaForm" >
			<input type="hidden" name="Ds_SignatureVersion" value="HMAC_SHA256_V1"/>
			<input type="hidden" name="Ds_MerchantParameters" value="eyJEc19NZXJjaGFudF9BbW91bnQiOjQ1NTQsIkRzX01lcmNoYW50X0N1cnJlbmN5IjoiOTc4IiwiRHNfTWVyY2hhbnRfT3JkZXIiOiIyMDE0MDlraWxsZ3QiLCJEc19NZXJjaGFudF9NZXJjaGFudERhdGEiOm51bGwsIkRzX01lcmNoYW50X01lcmNoYW50Q29kZSI6NTU1NjEyMzEyMywiRHNfTWVyY2hhbnRfVGVybWluYWwiOjEsIkRzX01lcmNoYW50X1RyYW5zYWN0aW9uVHlwZSI6MCwiRHNfTWVyY2hhbnRfVGl0dWxhciI6IkFndXN0XHUwMGVkbiIsIkRzX01lcmNoYW50X01lcmNoYW50TmFtZSI6Ik1hc3NpdmVEeW5hbWljcyIsIkRzX01lcmNoYW50X01lcmNoYW50VVJMIjoiaHR0cDpcL1wvYWd1c3Rpbi5wcm9cL2NhbGxiYWNrIiwiRHNfTWVyY2hhbnRfUHJvZHVjdERlc2NyaXB0aW9uIjoiTG9uZyBTd29yZCIsIkRzX01lcmNoYW50X0NvbnN1bWVyTGFuZ3VhZ2UiOiIwMDEiLCJEc19NZXJjaGFudF9VcmxPSyI6bnVsbCwiRHNfTWVyY2hhbnRfVXJsS08iOm51bGwsIkRzX01lcmNoYW50X1BheU1ldGhvZHMiOiJUIn0="/>
			<input type="hidden" name="Ds_Signature" value="4RvVYv/GaPyQKnCPwVaj5j27ZgY5o5soFr/v6wUtoMA="/>
		<script>document.forms["sermepaForm"].submit();</script></form>';

		$this->assertEquals($realHTML, $renderedHTML);
	}


	public function testCheckAutenticity()
	{
		$notifData = [
			'Ds_SignatureVersion' => 'HMAC_SHA256_V1',
			'Ds_Signature' => 'E5cQa4JGo3nHOmRdDzpph4Ar_3RewlLOcUnnJMCdYFM=',
			'Ds_MerchantParameters' => base64_encode(json_encode([
				"Ds_TransactionType" => "0",
				"Ds_Card_Country" => "724",
				"Ds_Date" => "29/09/2014",
				"Ds_SecurePayment" => "1",
				"Ds_Order" => "201409killgt",
				"Ds_Hour" => "23:14",
				"Ds_Response" => "0000",
				"Ds_AuthorisationCode" => "160099",
				"Ds_Currency" => "978",
				"Ds_ConsumerLanguage" => "1",
				"Ds_MerchantCode" => "5556123123",
				"Ds_Amount" => "4554",
				"Ds_Terminal" => "001"
			]))
		];

		$request = new Request(5556123123, 'Mk9m98IfEblmPfrpsawt7BmxObt98Jev', false, 1, 'MassiveDynamics');

		$request->setAmount(45.54);
		$request->setOrder('201409killgt');
		$request->setCallbackURL('http://agustin.pro/callback'); //dont try, doesn't exists
		$request->setPayer('Agustín');
		$request->setProductDescription('Long Sword');
		$request->signature();

		$this->assertTrue(!!$request->checkCallback($notifData));
	}

	public function testJson()
	{
		$postData = [
			"Ds_TransactionType" => "0",
			"Ds_Card_Country" => "724",
			"Ds_Date" => "29/09/2014",
			"Ds_SecurePayment" => "1",
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

		$request = new Request(5556123123, 'Mk9m98IfEblmPfrpsawt7BmxObt98Jev', false, 1, 'MassiveDynamics');

		$json = $request->json();
	}
}
