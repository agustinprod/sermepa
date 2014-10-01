Sermepa [![Build Status](https://travis-ci.org/killgt/sermepa.svg)](https://travis-ci.org/killgt/sermepa)
=======

PHP Sermepa payments utility


## Usage

```php
use Killgt\Sermepa\Request;

$request = new Killgt\Sermepa\Request($fuc, $key, $useProductionEnviroment, $terminal, $businessName);

$request->setAmount(45.54);
$request->setOrder('2014abcd1234');
$request->setTransactionType(0);
$request->setCallbackURL('http://example.com/callback');
$request->setSuccessURL('http://example.com/ok');
$request->setErrorURL('http://example.com/error');
$request->setPayer('Peter Bishop');
$request->setProductDescription('The machine
');

echo $request->render(); //outputs the form and auto-submit
```

### Checking the callback
```php
$request = new Killgt\Sermepa\Request;
$request->setKey($key);

if ($request->checkCallback($_POST)) {
	// Get $_POST['Ds_Order'] and update your order
} else {
	// Something went wrong
}
```