

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
