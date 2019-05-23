## Requirements

The following versions of PHP are supported.

* PHP 7.1+

## Installation

To install, use composer:

```
php composer.phar require pe/component-smtp
```

## Minimal usage

```php
<?php

namespace PE\Component\SMTP;

$host     = 'example.com';// Your server host
$port     = 587;          // Your server port
$security = true;         // Enable/disable server ssl security
$validate = false;        // Enable/disable certificate verification

// Create client
$client = new Client(new Connection($host, $port, $security, $validate));

// Send message minimal flow
$client->connect();
$client->HELO();
$client->MAIL('sender@domain.com');
$client->RCPT('recipient@domain.xyz');

$message = "
From: <sender@domain.com>\n
To: <recipient@domain.xyz>\n
Subject: HELLO\n
Content-Type: text/plain\n
\n
HELLO
";

$client->DATA($message);
$client->QUIT();
```

## Bundled E-SMTP extensions

#### StartTLS

```php
<?php
namespace PE\Component\SMTP;

// For enable module you need to disable connection security and use one of options
$client = new Client(new Connection('example.com', 587, false));

// Option 1: upgrade connection if TLS supported (default)
$client->attachModule(new Module\ModuleStartTLS());

// Option 2: force TLS, if not supported - exception will be thrown
$client->attachModule(new Module\ModuleStartTLS(true));
```

#### Authentication

```php
<?php
namespace PE\Component\SMTP;

$client = new Client(new Connection());

// For enable module first you need to configure authenticators
$module = new Module\ModuleAuthenticator([
    new Authenticator\AuthenticatorPlain(),
    new Authenticator\AuthenticatorLogin(),
    new Authenticator\AuthenticatorCramMD5(),
]);

// Then you must set your credentials
$module->setUsername('username');
$module->setPassword('password');

// And then you can attach module
$client->attachModule($module);
```

#### Logger

```php
<?php
namespace PE\Component\SMTP;

$client = new Client(new Connection());

// Option 1: use STDOUT handler
$client->attachModule(
    new Module\ModuleLogger(new LogHandler\LogHandlerSTDOUT())
);

// Option 2: use PSR handler
/* @var $psrLogger \Psr\Log\LoggerInterface */
$client->attachModule(
    new Module\ModuleLogger(new LogHandler\LogHandlerPSR($psrLogger))
);
```

#### Delivery Status Notifications

Allow to configure delivery status notification reports

Warning: SMTP servers may ignore some options

```php
<?php
namespace PE\Component\SMTP;

use PE\Component\SMTP\Module\ModuleDSN;

$module = new ModuleDSN();

// For enable you can use any combination of constants below
$module->setNotify([
    ModuleDSN::NOTIFY_SUCCESS,
    ModuleDSN::NOTIFY_DELAY,
    ModuleDSN::NOTIFY_FAILURE,
]);

// For disable you can pass only or just empty array
$module->setNotify([ModuleDSN::NOTIFY_NEVER]);

// For return only headers in delivery report
$module->setReturn(ModuleDSN::RETURN_HEADERS);

// For return full body in delivery report
$module->setReturn(ModuleDSN::RETURN_FULL);

$client = new Client(new Connection());
$client->attachModule($module);
```

#### Pipelining support

If remote server supports pipelining - you commands sends as groups and reduce read response count

```php
<?php
namespace PE\Component\SMTP;

// For enable module just attach it
$client = new Client(new Connection());
$client->attachModule(new Module\ModulePipelining());
```

#### SMTP UTF8 support

For use this module all message addresses must be encoded in UTF8

```php
<?php
namespace PE\Component\SMTP;

// For enable module just attach it
$client = new Client(new Connection());
$client->attachModule(new Module\ModuleSMTPUTF8());
```

#### 8bit MIME support

For use this module message body must be encoded in 8bit mode

```php
<?php
namespace PE\Component\SMTP;

// For enable module just attach it
$client = new Client(new Connection());
$client->attachModule(new Module\Module8BitMIME());
```
