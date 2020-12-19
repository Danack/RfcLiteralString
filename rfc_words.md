# PHP RFC: safe literals

## Introduction

It should be possible to use an API safely by default.

Many APIs are difficult to use them safely as it is trivially easy to include an SQL injection attack in the code.

```
$sql = 'SELECT * FROM foo WHERE id = ' . $_GET['id'];

$db = new DB();
$db->query($sql);
```

For SQL, it is possible to restrict yourself to always using parameterized queries, and so guaranteed to be safe from typical SQL injection attacks.

However, other APIs are on transport mechanisms that do not support parameterized queries. 

For example, any data source that is accessed over HTTP/S will be serving their data over a URI that is generated as a string.

```
function getDataFromWeb(int $user_id, string $search) {
    $url = "http://example.com/api?userid=" . $user_id . " search=" . $search

    return file_get_contents($url);
}

// This is fine
getDataFromWeb($session->getUserID(), 'latest_news');

// This could be an injection attack, 
getDataFromWeb($session->getUserID(), $_GET['search']);

``` 

Trying to educate developers that they 'need to be careful' and that they need to remember to use special handling for variables is inevitably going to lead to people not being aware that they need to do that (or just forgetting) which results in security vulnerabilities in code they write. 

Adding a way for function to check if the strings they were given were embedded as source code would allow them to determine if they are being used in a safe way or not.

## Proposal 

This RFC proposes adding an is_literal() function to check if a variable represents a value written into the source code or not.

### Add is_literal function

Add an //is_literal()// function to check if a variable represents a value written into the source code. The function returns true if the variable is a literal from the source, and false if is not.

Strings that can be concatenated in place at compile time are treated as single literals.  

```<?php

var_dump(is_literal("foo"));
// true - this is directly a string literal 

$value = is_literal("foo");
var_dump($value);
// true - variable is directly created from a string literal 

var_dump(4); // true
var_dump(0.3); // true
var_dump(is_literal("foo" . "bar"));
// true - compiler can concatenate two strings into one. 


var_dump(is_literal($_GET['search']));
// false - variable comes from user input

var_dump(is_literal(rand(0, 10));
// false - variable is generated

$bar = 'bar';
var_dump(is_literal("foo" . $bar));
// false - strings are not compiled in place at run time.

function foo($bar) {
    return is_literal($bar);
}

var_dump(foo("hello"));
// true - literal-ness not affected by being passed as parameter.

```

### Example of using is_literal

```php
function getDataFromWeb(string $limit) {
    if (is_literal($limit) !== true) {
        throw new \ExceptionBetweenKeyboardAndChair("injection detected");
    }

    $url = "http://example.com/data/search?limit=" . $limit;

    return file_get_contents($url);
}
```

However, sometimes you do need to allow variables to be included in queries, rather than just hard-coded values. This can be done safely by supporting 'query builder' type objects to the API:  

```php
function getDataFromWeb(string|SearchBuilder $limit) {
    if (is_string($limit) && is_literal($limit) !== true) {
        throw new \ExceptionBetweenKeyboardAndChair("injection detected");
    }

    $url = "http://example.com/data/search?limit=" . $limit;

    return file_get_contents($url);
}

class SearchBuilder {

    private int $limit;

    public function __construct($value)
    {
        $this->limit = intval($value);
    }
    
    public function getSearchLimit(): int
    {
        return $this->limit;
    }
}

// This is fine
getDataFromWeb(20);

// This is also fine
getDataFromWeb(new SearchBuilder($_GET['limit']));

// This fails with exception
getDataFromWeb($_GET['limit']);

```

If the api was called with search set to 'latest_news=foo&userid=123' this is an injection attack that would be blocked by this pattern of coding.


## Notes

The aim of this RFC is not to make it impossible to write code that contains data injection attacks, instead the aim is to make it easy to avoid doing that accidentally.

### No concatenating dynamic strings

The position of this RFC is that dynamic concatenated literal strings are an edge-case. Although it would be possible to preserve the literal-ness of runtime concatenated strings, it would be a small performance overhead. It would also make it more difficult to reason about this feature. 

Trying to determine if the is_literal flag should be passed through functions like implode, str_repeat, substr etc is difficult. Having a security feature be difficult to reason about, gives a much higher chance of making a mistake.

For any use-case where dynamic strings are required, it would be better to build those strings with an appropriate query builder,

## Questions

???

## Backward Incompatible Changes 

Adding is_literal() has no known BC breaks, except for code-bases that already contain a userland function with that name

## Proposed PHP Version(s) 

PHP 8.1

## RFC Impact 

### To SAPIs 

Not sure

### To Existing Extensions 

Not sure

### To Opcache 

Not sure

## Open Issues 


## Unaffected PHP Functionality 

None known.

## Future Scope 


### Add support for a QueryBuilder to Mysqli/PDO

It is out of scope for this RFC but a future piece of work we might choose to do is to add support for query builders to MySQLi and PDO. e.g. change the definition of Mysqli::query from:

   public mysqli::query( string $query [, int $resultmode = MYSQLI_STORE_RESULT ] ) : mixed

to: 

   public mysqli::query( string|MysqliQueryBuilder $query [, int $resultmode = MYSQLI_STORE_RESULT ] ) : mixed

and then internally check whether the $query was either a literal string, or a built query. 


## Proposed Voting Choices 

Accept the RFC. Yes/No 

## Patches and Tests 

None yet.

## Implementation 

N/A

## References 







