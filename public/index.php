<?php

if (!extension_loaded('redis')) {
    die('The Redis extension is not installed. Please install it to run this application.');
}
use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
