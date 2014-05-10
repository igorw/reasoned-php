# reasoned-php

A [miniKanren](http://minikanren.org/) in PHP, based on the microKanren paper.

## Examples

### basic usage

    var_dump(run_star(function ($q) {
        return conde([
            [eq($q, 'a')],
            [eq($q, 'b')],
            [eq($q, 'c')],
        ]);
    }));

    // => ['a', 'b', 'c']

### membero

    function conso($a, $d, $l) {
        return eq(pair($a, $d), $l);
    }

    function membero($x, $l) {
        return conde([
            [fresh(function ($d) use ($x, $l) {
                return conso($x, $d, $l);
            })],
            [fresh(function ($a, $d) use ($x, $l) {
                return all([
                    conso($a, $d, $l),
                    membero($x, $d),
                ]);
            })],
        ]);
    }

    var_dump(run_star(function ($q) {
        return all([
            membero($q, [1, 2, 3]),
            membero($q, [2, 3, 4]),
        ]);
    }));

    // => [2, 3]

## See also

* [The Reasoned Schemer](http://mitpress.mit.edu/books/reasoned-schemer)
* [miniKanren](http://minikanren.org/)
* [microKanren](http://webyrd.net/scheme-2013/papers/HemannMuKanren2013.pdf)
