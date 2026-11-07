<?php

namespace App\Services\Unifi;

use RuntimeException;

/** A refused or failed call to UniFi's cloud; the code is the HTTP status. */
class UnifiCloudException extends RuntimeException {}
