Sermepa [![Build Status](https://travis-ci.org/killgt/sermepa.svg)](https://travis-ci.org/killgt/sermepa)
=======

PHP Sermepa payments utility


## Usage

```php
use Killgt\Sermepa\Request;

$fuc = "999008881";
$key = "Mk9m98IfEblmPfrpsawt7BmxObt98Jev";
$useProductionEnviroment = false;
$terminal = "871";
$businessName = "Massive Dynamic";

$request = new Killgt\Sermepa\Request($fuc, $key, $useProductionEnviroment, $terminal, $businessName);

$request->setAmount(45.54);
$request->setOrder('2014abcd1234');
$request->setTransactionType(0);
$request->setCallbackURL('http://example.com/callback');
$request->setSuccessURL('http://example.com/ok');
$request->setErrorURL('http://example.com/error');
$request->setPayer('Peter Bishop');
$request->setProductDescription('The machine');

echo $request->render(); //outputs the form and auto-submit
```

### Checking the callback
```php
$request = new Killgt\Sermepa\Request;
$request->setKey($key);

if ($response = $request->checkCallback($_POST)) {
	// Get $response['Ds_Order'] and update your order
} else {
	// Something went wrong
}
```
