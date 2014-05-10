# reasoned-php

A [miniKanren](http://minikanren.org/) in PHP, based on the microKanren paper.

## Examples

    var_dump(run_star(function ($q) {
        return conde([
            [eq($q, 'a')],
            [eq($q, 'b')],
            [eq($q, 'c')],
        ]);
    }));

## See also

* [The Reasoned Schemer](http://mitpress.mit.edu/books/reasoned-schemer)
* [miniKanren](http://minikanren.org/)
* [microKanren](http://webyrd.net/scheme-2013/papers/HemannMuKanren2013.pdf)
