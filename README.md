
# Barefoot

Streamlined tools for PHP minimalists. Dangerous, but spiritually rewarding.

## Rationale

Modern PHP frameworks have abstracted away the fundamental concept of PHP; a
request becomes environment, and output becomes response. That is to say,
request information is loaded into `$_SERVER`, `$_GET`, `$_POST` etc, and
whatever our application outputs with `http_response_code`, `echo` etc. is sent
back.

Abstracting requests and responses (and everything else) into objects, like so
many frameworks do, hides this concept from the programmer, in an attempt to
reduce foot-shooting and simplify application architecture. But if you know
what you're doing, this feels like wrapping everything in cotton wool.

Barefoot is a thin layer of tools over PHP's built-ins, designed to work in
tandem with the traditional request/response concept. It provides many of the
common requirements of modern PHP applications, in a little over 100 lines of
code:

- Regex-based routing
- URL generation
- Request header helpers
- CSRF protection
- Flash messages

## Example application

Here's a small application showing off most of Barefoot's tools.

```php
require 'barefoot.php';

use function barefoot\csrf_get_token;
use function barefoot\csrf_validate_token;
use function barefoot\flash_get_message;
use function barefoot\flash_set_message;
use function barefoot\redirect_and_exit;
use function barefoot\route;
use function barefoot\url_make_from_path;

session_start();

function get_home()
{
    $action_url = url_make_from_path('/do-thing');
    $old_data = flash_get_message('home_form_data');
    $csrf_token = csrf_get_token('home_form');
    echo <<<"EOT"
<form action="${action_url}" method="post">
    <input name="data" value="${old_data}">
    <input name="csrf_token" value="${csrf_token}">
    <input type="submit">
</form>
EOT;
}

function get_page($parameter_1, $parameter_2 = 'default', $parameter_3 = 'default')
{
    echo "<p>${parameter_1}</p>";
    echo "<p>${parameter_2}</p>";
    echo "<p>${parameter_3}</p>";
}

function post_do_thing()
{
    csrf_validate_token('home_form', $_POST['csrf_token'], function () {
        throw new Exception('Invalid csrf');
    });
    if ('valid' !== $_POST['data']) {
        $old_data = flash_set_message('home_form_data', $_POST['data']);
        redirect_and_exit('/');
    }
    // Do something with the data.
    echo 'Submitted';
}

function not_found()
{
    http_response_code(404);
    echo '<h1>Page not found</h1>';
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
        route(
            [
                '/' => 'get_home',
                '/page/(\d+)/([^/]+)/(\d+)' => 'get_page',
                '/page/(\d+)/([^/]+)' => 'get_page',
                '/page/([^/]+)' => 'get_page',
            ],
            'not_found'
        );
        break;
        case 'POST':
        route(
            [
                '/do-thing' => 'post_do_thing',
            ],
            'not_found'
        );
        break;
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo "<h1>{$e->getMessage()}</h1>";
    echo "<pre>{$e->getTraceAsString()}</pre>";
    exit;
}
```

## Tools 

### Responses

`route` takes an associative array of regex => callable mappings, and a
callable for undefined routes. Regex parameters are passed into the callable.
It has no concept of request method; depending on your application you might
need multiple calls to `route`.

`redirect_and_exit` does what it says on the tin. Pass in a location string to
send a 302 redirect and stop execution.

### URLs

`url_make_from_path` will turn a string like 'path/string' into a URL like
'//www.example.com/path/string', using the `$_SERVER['HTTP_HOST']` value.  It's
opinionated about always removing trailing slashes.

### Request data

`request_get_headers` simply returns all the `$_SERVER` entries whose key
begins with `HTTP_`.

`request_get_ip_address` tries to get the client's IP address (accounting for
any proxy forwarding), and returns a default value if unavailable or invalid.

### CSRF protection

`csrf_get_token` returns the CSRF token with the specified ID. It will generate
one if it doesn't exist.

`csrf_validate_token` takes a token ID, a token string to match against, and a
callable. If the token doesn't match, the callable gets called.

`csrf_unset_token` deletes the token with the specified ID.

### Flash messages

`flash_set_message` stores a string message in the session under the specified
key.

`flash_get_message` returns the string under the specified key (or a default
value if it doesn't exist) then deletes the message from the session.

## What about DI containers, APIs, templates etc?

It's trivial to compose your application of multiple components when using
something like Barefoot. Use whatever libraries you'd normally use.

## Installation

A simple `require 'barefoot.php';` will do it. If you prefer to use Composer
you can autoload using the `files` property:

```json
{
	"autoload": {
		"files": ["barefoot.php"]
	}
}
```
