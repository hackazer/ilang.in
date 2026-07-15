<?php

declare(strict_types=1);

error_reporting(E_ALL);

$root = dirname(__DIR__);
$vendorAutoload = $root.'/vendor/autoload.php';

if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

require_once $root.'/core/Helper.class.php';
require_once $root.'/core/Request.class.php';
require_once $root.'/core/Http.class.php';
require_once $root.'/core/Gem.class.php';

$facebookOAuth = $root.'/app/helpers/FacebookOAuth.php';

if (is_file($facebookOAuth)) {
    require_once $facebookOAuth;
}
