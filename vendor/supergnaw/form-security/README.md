# FormSecurity

PHP class for simplifying webpage form security. The main purpose for the creation of this class was to help mitigate
against [CSRF](https://en.wikipedia.org/wiki/Cross-site_request_forgery). The class can validate the following types of
inputs:

* boolean
* numeric
    * float
    * integer
    * hexadecimal integer
    * octal integer
* network
    * ipv4
    * ipv6
    * mac address
* timestamp
    * date
    * time
    * timestamp
* string
    * alphanumeric
    * hexadecimal
    * email
    * encoded html
    * url
    * string

## Token

### Form Tokens

FormSecurity can generate nonce tokens to be used to verify a form can only be submitted once. To use the token, simply
generate a token, add it to a hidden form input, then validate the submitted token against the one saved in the session
variable.

#### Generate the token

```php
$exampleToken = FormSecurity::token_generate('example');
```

#### Store the generated token in a form hidden input

```html

<form>
    <input type="hidden" name="token_name" value="<?php echo $exampleToken; ?>">
</form>
```

#### Validate the submitted token

```php
if (FormSecurity::token_verify("token_name")) {
    echo "security token is valid!";
} else {
    echo "security token is invalid!";
}
```

#### Clearing All Tokens

If for whatever reason you need to clear any saved tokens, use `token_clear_all()`.

```php
FormSecurity::token_clear_all();
```

## Whitelist & Blacklist

FormSecurity can apply a whitelist or blacklist to a given input and filter out unwanted or unexpected inputs.

```php
$input = [
    "var1" => 1,
    "var2" => 2,
    "var3" => 3
];

$whitelist = ["var1", "var2"];
$output = FilterSecurity::apply_whitelist($whitelist, $input);
// $output = [1, 2]

$blacklist = ["var1", "var3"];
$output = FilterSecurity::apply_blacklist($blacklist, $input);
// $output = [2]
```

## Filter

FormSecurity can filter the values of a given input (`get`, `post`, `cookie`, `server`, or `env`) and remove any values
that do not match their expected type (`bool`, `float`, `hexint`, `int`, `octint`, `ipv4`, `ipv6`, `mac`, `date`, `time`
, `timestamp`, `string`, `alnum`, `url`, `email`, or `htmlenc`).

```php
$filter = [
    "foo" => "int",
    "bar" => "string",
];

$_GET = [
    "foo" => "1",
    "bar" => "2"
]
$get = FormSecurity::filter_input(input: "get", types: $filter);
// $get = ["2"]

$_POST = [
    "foo" => 1,
    "bar" => 2
];
$post = FormSecurity::filter_input(input: "post", types: $filter);
// $post = [1]
```
### Granular Filtering

FormSecurity filters can also be used individually to validate individual inputs, where `null` will be returned on any
failure.

* `FormSecurity::filter_boolean()`
* `FormSecurity::filter_float()`
* `FormSecurity::filter_int()`
* `FormSecurity::filter_hexint()`
* `FormSecurity::filter_octint()`
* `FormSecurity::filter_ipv4()`
* `FormSecurity::filter_ipv6()`
* `FormSecurity::filter_mac()`
* `FormSecurity::filter_string()`
* `FormSecurity::filter_alnum()`
* `FormSecurity::filter_hex()`
* `FormSecurity::filter_url()`
* `FormSecurity::filter_email()`
* `FormSecurity::filter_htmlenc()`
* `FormSecurity::filter_date()`
* `FormSecurity::filter_time()`
* `FormSecurity::filter_timestamp()`

## String Cleaning

FormSecurity can clean strings

### Allow only alphabetical characters

```php
$input = "Hell0 w0rld!";
$output = FormSecurity::clean_to_alpha($input);
// $output = "Hellwrld"
```

### Allow only alphanumeric characters

```php
$input = "Hell0 w0rld!";
$output = FormSecurity::clean_to_alnum($input);
// $output = "Hell0w0rld"
```
