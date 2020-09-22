# PHP RFC: safe literals


## Introduction

It should be possible to use an API safely by default.

The current MySQLi and PDO apis make it difficult to use them safely as it is trivially easy to include an SQL injection attack in the code.

```
$sql = 'SELECT * FROM foo WHERE id = ' . $_GET['id'];

$db = new DB();
$db->query($sql);
```

Trying to educate developers that they 'need to be careful' and that they need to remember to use special handling for variables is inevitably going to lead to people not being aware that they need to do that (or just forgetting) which results in security vulnerabilities in code they write. 

Adding a way for function to check if the strings they were given were embedded as source code would allow them to determine if they are being used in a safe way or not.


```
class DB {
    public function query(string $sql) {
        if (is_literal($sql) !== true) {
            throw new \SecurityException("Query is dependent on variable data.");
        }
        // query is fine, run it.
    } 
}

```


## Proposal 

This RFC proposes:

* adding an is_literal() function to check if a variable represents a value written into the source code or not.
* adding support for query builders to MySQLi, PDO, and odbc.

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
// false - strings cannot be compiled in place at compile time.

function foo($bar) {
    return is_literal($bar);
}

var_dump(foo("hello"));
// true - literal-ness not affected by being passed as parameter.

```


### Add support for MysqliQueryBuilder to Mysqli

As some queries will still need to have some parts that come from data, users need a way to be able to generate these queries in ways that can be safe.

#### MysqliQueryBuilder interface
Add an interface to core:

```
interface MysqliQueryBuilder {
    public function getQuery(): string;
}
```

#### Support passing MysqliQueryBuilder to Mysqli::query

change the definition of Mysqli::query from:

   public mysqli::query( string $query [, int $resultmode = MYSQLI_STORE_RESULT ] ) : mixed

to: 

   public mysqli::query( string|MysqliQueryBuilder $query [, int $resultmode = MYSQLI_STORE_RESULT ] ) : mixed

### Ini setting

The `mysqli.literal_query_check` would be added. It would support the following values:

* off - no literal check is done.
* deprecate - if a non-literal string is used for the query, a deprecation warning is generated.
* error - if a non-literal string is used for the query, an exception is thrown.

The default value for the ini setting would be as follows for the versions of PHP:

* 8.1 = off by default
* 8.x = deprecate
* 9.0 = error 

#### Alter MySQLi::query

Alter the MySQLi query method to make it support either being passed literal string queries, or objects that implement the MysqliQueryBuilder interface.


```
    class Mysqli {

        // ...

        public function query( string|MysqliQueryBuilder $query [, int $resultmode = MYSQLI_STORE_RESULT ] ) : mixed
    
            if ($query instance of MysqliQueryBuilder) {
                $query = $query->getQuery()
            }
            // must be string
            else if (is_literal($query) !== true) {
                $literal_query_check = ini_get('mysqli.literal_query_check');
                
                if ($literal_query_check === 'error') {
                    throw new \MysqliSecurityException("Only literal strings accepted.");
                }
                else if ($literal_query_check === 'deprecate') {
                   trigger_error("non-literal string detected", E_DEPRECATED ) :
               }
            }

            // exexcute $query
        }
    }
```

## Justification

### Makes it possible to default to safe access for parameterizable apis

Hopefully the above example and existing problems with accidentally using user controlled data in SQL queries are well known and don't need further explanation. But in addition to those....

### Promotes safer access to non-parameterizable apis

For SQL, it is possible to restrict yourself to always using parameterized queries, and so guaranteed to be safe from typical SQL injection attacks.

However, other APIs are on transport mechanisms that do not support parameterized queries. 

For example, any data source that is accessed over HTTP/S will be serving their data over a URI that is generated as a string. This proposal would make it easier to access those resources safely.


Example of current situation:
```
function getDataFromWeb(string $limit) {
    $url = "http://example.com/data/search?limit=" . $limit

    return file_get_contents($url);
}

// This is fine
getDataFromWeb(20);

// This could be an injection attack, 
getDataFromWeb($_GET['limit']);

``` 

Example using new is_literal check.
```
function getDataFromWeb(string|SearchBuilder $limit) {
    if (is_string($limit) && is_literal($limit) !== true) {
        throw new \ExceptionBetweenKeyboardAndChair("injection detected");
    }

    $url = "http://example.com/data/search?limit=" . $limit

    return file_get_contents($url);
}

class SearchBuilder {

    private int $limit;

    public static function createFromLimit($value) {
        $this->limit = intval($value)
    }

    public function getSearchLimit(): int
    {
        return $this->limit;
    }
}

// This is fine
getDataFromWeb(20);

// This is also fine
getDataFromWeb(SearchBuilder::createFromInt($_GET['limit']));

// This fails with exception
getDataFromWeb($_GET['limit']);

```


## Notes

### Always possible to work around safety

No matter what the ini setting mysqli.literal_query_check was set to, it would always be possible to pass in user controlled data: 

```
class UnsafeMysqliQueryBuilder implements MysqliQueryBuilder
{
    private string $query;

    public function __construct(string $query)
    {
        $this->query = $query;
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}

$sql = new UnsafeMysqliQueryBuilder($_GET['user_sql']);

$mysqli->query($sql);
```

The aim of this RFC is not to make it impossible to write code that contains SQL injection.

Instead the aim is to make it easy to avoid doing that accidentally.

### No concatenating dynamic strings

The position of this RFC is that dynamic concatenated literal strings are an edge-case. Although it would be possible to preserve the literal-ness of runtime concatenated strings, it would be a small performance overhead. It would also make it more difficult to reason about this feature. 

Trying to determine if the is_literal flag should be passed through functions like implode, str_repeat, substr etc is difficult. Having a security feature be difficult to reason about, gives a much higher chance of making a mistake. 

## Questions

### Should PHP ship with MysqliQueryBuilder implementations?

API design is hard. If we tried to implement this ourselves, we'd probably get it wrong.

It might be okay to leave implementations of MysqliQueryBuilder to be done in userland, unless we could make known good implementations.

### Should the ini setting mysqli.literal_query_check eventually be removed?

?

## Backward Incompatible Changes 

Adding is_literal() has no known BC breaks.

Making MySQLi reject non-literal strings would eventually break a lot of code that is open to SQL injection attacks.

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

Update $pdo->query() and odbc_exec() to use query builders.

## Proposed Voting Choices 

Accept the RFC. Yes/No 

## Patches and Tests 

None yet.

## Implementation 

N/A

## References 







