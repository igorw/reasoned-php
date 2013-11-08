# reasoned-php

A miniKanren in PHP.

As all other non-scheme implementations, based on sokuza-kanren by Oleg.


## Examples

### Choice

    use igorw\reasoned as r;

    $results = run(choice(r\lvar('x'), [1, 2, 3]));
    // [[pair('x', 1)],
    //  [pair('x', 2)],
    //  [pair('x', 3)]]

### Common elements

    use igorw\reasoned as r;

    $results = run(common_el([1, 2, 3], [3, 4, 5]));
    // [[pair('x', 3)]]
