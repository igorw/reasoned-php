# reasoned-php

A [miniKanren](http://minikanren.org/) in PHP, based on the microKanren paper.

## Examples

### Choice

    use igorw\reasoned as r;

    $results = r\run(
        r\choice(r\lvar('x'), [1, 2, 3]));
    // [[pair('x', 1)],
    //  [pair('x', 2)],
    //  [pair('x', 3)]]

### Common elements

    use igorw\reasoned as r;

    $results = r\run(
        r\common_el([1, 2, 3], [3, 4, 5]));
    // [[pair('x', 3)]]

## See also

* [The Reasoned Schemer](http://mitpress.mit.edu/books/reasoned-schemer)
* [miniKanren](http://minikanren.org/)
* [microKanren](...)
