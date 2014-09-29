<?php namespace Killgt\Sermepa;

use Killgt\Sermepa\Exceptions\LanguageNotFoundException;
use Killgt\Sermepa\Exceptions\CurrencyNotFoundException;
use Killgt\Sermepa\Exceptions\InvalidOrderNumberException;
use Killgt\Sermepa\Exceptions\InvalidPaymentMethodException;
use Killgt\Sermepa\Exceptions\IncompleteRequestException;
use Killgt\Sermepa\Exceptions\CallbackErrorException;

class Request {

	const METHOD_CARD = 'card';
	const METHOD_TRANSFER = 'transfer';
	const METHOD_SUBSCRIPTION = 'subscription';

	protected $productionEnviroment = false;
	protected $amount;
	protected $currency = '978'; // Eur
	protected $currencyCode = 'EUR';
	protected $order;
	protected $merchantData;
	protected $productDescription;
	protected $payer;
	protected $fuc;
	protected $terminal;
	protected $transactionType = 0;
	protected $callbackURL;
	protected $key;
	protected $successURL;
	protected $errorURL;
	protected $businessName;
	protected $language = '001';
	protected $languageCode = 'es';
	protected $method = 'T';

	private $productionURL = 'https://sis.sermepa.es/sis/realizarPago';
	private $testURL = 'https://sis-t.redsys.es:25443/sis/realizarPago';


	public function __construct($fuc = null, $key = null, $production = false, $terminal = 1, $businessName = null)
	{
		$this->setFuc($fuc);
		$this->setKey($key);
		$this->setTerminal($terminal);
		$this->setBusinessName($businessName);

		if ($production) {
			$this->setProductionEnviroment();
		}
	}

	/**
	 * Generates the signature for this request
	 * @return string
	 */
	public function signature()
	{
		if ( ! $this->validateRequest() ) {
			throw new IncompleteRequestException;
		}

		$signatureData = $this->amount . $this->order . $this->fuc . $this->currency;
		$signatureData .= $this->transactionType . $this->callbackURL . $this->key;

		return strtoupper(sha1($signatureData));
	}

	/**
	 * Checks all obligatory fields are fulfilled
	 * @return string
	 */
	public function validateRequest()
	{
		if ( ! $this->amount )
			return false;

		if ( ! $this->order )
			return false;

		if ( ! $this->fuc )
			return false;

		if ( ! $this->callbackURL )
			return false;

		if ( ! $this->key )
			return false;

		if ( ! $this->productDescription )
			return false;

		if ( ! $this->payer )
			return false;

		return true;
	}

	/**
	 * Check if the POST data received its a valid
	 * @return boolean
	 */
	public function checkCallback($postData)
	{
			$Ds_Response     = $postData['Ds_Response'];
			$Ds_Amount       = $postData['Ds_Amount'];
			$Ds_Order        = $postData['Ds_Order'];
			$Ds_MerchantCode = $postData['Ds_MerchantCode'];
			$Ds_Currency     = $postData['Ds_Currency'];
			$Ds_Date         = $postData['Ds_Date'];
			$signature       = $postData['Ds_Signature'];

			$newSignature = strtoupper(sha1($Ds_Amount . $Ds_Order . $Ds_MerchantCode . $Ds_Currency . $Ds_Response . $this->getKey()));

			if ($signature != $newSignature) {
				throw new CallbackErrorException("Cannot check the authenticate the request, check all the fields are filled");
			}

			if ( (int)$Ds_Response >= 100) {
				throw new CallbackErrorException("Invalid DS_Response returned", $DS_Response);
			}

		    return true;
	}

	/**
	 * Sets the language for the form
	 * @param string $code ISO 639-1 (Two characters code) [es|en|ca|fr|de|nl|it|sv|pt|val|pl|gl|eu]
	 */
	public function setLanguage($code)
	{
		switch ($code) {
			case 'es':
				$this->language = '001';
			break;
			case 'en':
				$this->language = "002";
			break;
			case 'ca':
				$this->language = "003";
			break;
			case 'fr':
				$this->language = "004";
			break;
			case 'de':
				$this->language = "005";
			break;
			case 'nl':
				$this->language = "006";
			break;
			case 'it':
				$this->language = "007";
			break;
			case 'sv':
				$this->language = "008";
			break;
			case 'pt':
				$this->language = "009";
			break;
			case 'val':// Valencian has not a iso 639 code
				$this->language = "010";
			break;
			case 'pl':
				$this->language = "011";
			break;
			case 'gl':
				$this->language = "012";
			break;
			case 'eu':
				$this->language = "013";
			break;
			default:
				throw new LanguageNotFoundException;
			break;
		}

		$this->languageCode = $code;
	}

	/**
	 * Returns the HTML form
	 * @param  boolean $autoSubmit
	 * @return string
	 */
	public function render($autoSubmit = true)
	{
		$html = "
		<form action=\"{$this->getURL()}\" method=\"post\" id=\"sermepaForm\" name=\"sermepaForm\" >
			<input type=\"hidden\" name=\"Ds_Merchant_Amount\" value=\"{$this->getAmount()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_Currency\" value=\"{$this->getCurrency()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_Order\" value=\"{$this->getOrder()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_MerchantData\" value=\"{$this->getMerchantData()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_MerchantCode\" value=\"{$this->getFuc()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_Terminal\" value=\"{$this->getTerminal()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_TransactionType\" value=\"{$this->getTransactionType()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_Titular\" value=\"{$this->getPayer()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_MerchantName\" value=\"{$this->getBusinessName()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_MerchantURL\" value=\"{$this->getCallbackURL()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_ProductDescription\" value=\"{$this->getProductDescription()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_ConsumerLanguage\" value=\"{$this->getLanguage()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_UrlOK\" value=\"{$this->getSuccessURL()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_UrlKO\" value=\"{$this->getErrorURL()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_PayMethods\" value=\"{$this->getMethod()}\" />
			<input type=\"hidden\" name=\"Ds_Merchant_MerchantSignature\" value=\"{$this->signature()}\" />
		";

		if ($autoSubmit) {
			$html .= "<script>document.forms[\"sermepaForm\"].submit();</script>";
		}

		$html .= "</form>";

		return $html;
	}

	/**
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * @return string
	 */
	public function getLanguageCode()
	{
		return $this->languageCode;
	}

	/**
	 * Sets the enviroment to production
	 */
	public function setProductionEnviroment()
	{
		$this->productionEnviroment = true;
	}

	/**
	 * Sets the test enviroment
	 */
	public function setTestEnviroment()
	{
		$this->productionEnviroment = false;
	}

	/**
	 * Check if we are working on the production enviroment
	 * @return boolean
	 */
	public function isProductionEnviroment()
	{
		return $this->productionEnviroment;
	}

	/**
	 * Returns the form URL depending on the enviroment
	 * @return string
	 */
	public function getURL()
	{
		return $this->isProductionEnviroment() ? $this->productionURL : $this->testURL;
	}

	/**
	 * Sets the currency by using currecy official code
	 * @param string $code [EUR|USD|GGP|JPY]
	 */
	public function setCurrency($currencyCode)
	{
		$currencyCode = strtoupper($currencyCode);

		switch($currencyCode) {
			case 'EUR':
				$this->currency = '978';
			break;
			case 'USD':
				$this->currency = '840';
			break;
			case 'GGP':
				$this->currency = '826';
			break;
			case 'JPY':
				$this->currency = '392';
			break;
			default:
				throw new CurrencyNotFoundException;
			break;
		}

		$this->currencyCode = $currencyCode;
	}

	/**
	 * @return string
	 */
	public function getCurrency()
	{
		return $this->currency;
	}

	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return $this->currencyCode;
	}


	/**
	 * Sets the currency by using currecy official code
	 * @param string $code [card|transfer|subscription]
	 */
	public function setPaymentMethod($method)
	{
		$method = strtolower($method);

		switch($method) {
			case 'card':
				$this->method = 'T';
			break;
			case 'transfer':
				$this->method = 'R';
			break;
			case 'subscription':
				$this->method = 'D';
			break;
			default:
				throw new InvalidPaymentMethodException;
			break;
		}

	}

	/**
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}


	/**
	 * Sets the order number. Must start with 4 numeric characters.
	 * A common order number could be generated like this: date("His").str_pad($order->id, 6, "0", STR_PAD_LEFT);
	 * @param string $orderNumber
	 */
	public function setOrder($orderNumber)
	{
		if ( ! preg_match('/([0-9]{4})([a-zA-Z0-9]{8})/', $orderNumber) )
			throw new InvalidOrderNumberException;

		$this->order = $orderNumber;
	}

	/**
	 * @return string
	 */
	public function getOrder()
	{
		return $this->order;
	}

	/**
	 * @param string $data
	 */
	public function setMerchantData($data)
	{
		$this->merchantData = $data;
	}

	/**
	 * @return string
	 */
	public function getMerchantData()
	{
		return $this->merchantData;
	}

	/**
	 * @param string $description
	 */
	public function setProductDescription($description)
	{
		$this->productDescription = $description;
	}

	/**
	 * @return string
	 */
	public function getProductDescription()
	{
		return $this->productDescription;
	}

	/**
	 * @param string $payerName
	 */
	public function setPayer($payerName)
	{
		$this->payer = $payerName;
	}

	/**
	 * @return string
	 */
	public function getPayer()
	{
		return $this->payer;
	}

	/**
	 * @param string $fucNumber
	 */
	public function setFuc($fucNumber)
	{
		$this->fuc = $fucNumber;
	}

	/**
	 * @return string
	 */
	public function getFuc()// lol
	{
		return $this->fuc;
	}

	/**
	 * The amount in the currency
	 * @param float $amount
	 */
	public function setAmount($amount)
	{
		$this->amount = intval($amount * 100);
	}

	/**
	 * Gets the original amount
	 * @return float
	 */
	public function getAmount()
	{
		return $this->amount;
	}

	/**
	 * @param string $terminalNumber
	 */
	public function setTerminal($terminalNumber)
	{
		$this->terminal = $terminalNumber;
	}

	/**
	 * @return string
	 */
	public function getTerminal()
	{
		return $this->terminal;
	}

	/**
	 * @param string $transactionType
	 */
	public function setTransactionType($transactionType)
	{
		$this->transactionType = $transactionType;
	}

	/**
	 * @return string
	 */
	public function getTransactionType()
	{
		return $this->transactionType;
	}

	/**
	 * This URL will be called by the service to
	 * confirm the payment. If you are developing on a
	 * non visible to the internet machine, you should
	 * consider using NGROK.
	 * @param string $callbackURL
	 */
	public function setCallbackURL($callbackURL)
	{
		$this->callbackURL = $callbackURL;
	}

	/**
	 * @return string
	 */
	public function getCallbackURL()
	{
		return $this->callbackURL;
	}

	/**
	 * @param string $key
	 */
	public function setKey($key)
	{
		$this->key = $key;
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @param string $successURL
	 */
	public function setSuccessURL($successURL)
	{
		$this->successURL = $successURL;
	}

	/**
	 * @return string
	 */
	public function getSuccessURL()
	{
		return $this->successURL;
	}

	/**
	 * @param string $errorURL
	 */
	public function setErrorURL($errorURL)
	{
		$this->errorURL = $errorURL;
	}

	/**
	 * @return string
	 */
	public function getErrorURL()
	{
		return $this->errorURL;
	}

	/**
	 * @param string $businessName
	 */
	public function setBusinessName($businessName)
	{
		$this->businessName = $businessName;
	}

	/**
	 * @return string
	 */
	public function getBusinessName()
	{
		return $this->businessName;
	}
}
