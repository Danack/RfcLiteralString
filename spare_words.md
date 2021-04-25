
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