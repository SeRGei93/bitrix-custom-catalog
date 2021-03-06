<?php


namespace Oip\GuestUser\Repository\ServerRepository\Exception;

use Exception;

class AddingNewGuest extends Exception
{
    public function __construct()
    {
        $message = "An error occurred while adding a new user";
        parent::__construct($message, $code = 0, $previous = null);
    }
}