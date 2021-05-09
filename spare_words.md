
The position of this RFC is that dynamic concatenated literal strings are an edge-case. Although it would be possible to preserve the literal-ness of runtime concatenated strings, it would be a small performance overhead. It would also make it more difficult to reason about this feature.


It would also be annoying for userland code:

e.g.

```
function foo ($value) {
    if (is_literal($value) !== true) {
      throw new \Exception("bad input");
    }
    ...
}

$entries = [
    0 => 'one',
    1 => 'two',
    2 => 'three',
    ...
    ...
    ...,
    97 => 'ninety_seven',
    98 => 'ninety_eight'
    99 => 'ninety_nine_' .  $_GET['surprise']
];

foo('bar_' . $entries[rand(0, 100)]);
```

That code would work 99 times out of a hundred. 


Design Goals
Local Reasoning: Preconditions established by surrounding code
Scalable & Mandatory Expert Review
● Avoid need for expert reasoning about preconditions all throughout application code
● Confine security-relevant program slice to expert-owned/reviewed source












Imagine we have a function that checks for safety:

```
function foo(array $params)
{
    foreach ($params as $param) {
        // TODO - rename SearchBuilder
        if (is_literal($param) !== true && !($param instanceof SearchBuilder)) {
            throw new \Exception("this is not safe."); 
        }
    }
    ...
}

```

And then we have some code that does stuff:
```
$sortOrder = 'ASC';

// 20 lines of code, or multiple function calls

$params[] = 'order=' . $sortOrder;
 
// 500 lines of code, or multiple function calls

foo($params[]);
```

This code works, but then a few months later someone in the team changes it to be:

```
$sortOrder = $_GET['order'];

// 20 lines of code, or multiple function calls

$params[] = 'order=' . $sortOrder;
 
// 500 lines of code, or multiple function calls

foo($params[]);

```

That code would correctly fail, but it would be a nightmare trying to track back where the error lies in the program.

Although forcing developers to use specific functions to explicitly preserve the literal flag has a small overhead, it makes it easier to maintain large applications.

```
$sortOrder = $_GET['order'];

// 20 lines of code, or multiple function calls

$params[] = literal_combine('order=', $sortOrder);
// ERROR occurs here, closer to where $sortOrder is coming from.
```