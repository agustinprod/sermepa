<?php namespace Killgt\Sermepa\Exceptions;

class CallbackErrorException extends \Exception {
    private $parameters = array();

    public function __construct($message, $code, array $parameters = []) {
        parent::__construct($message, $code);
        $this->parameters = $parameters;
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}
