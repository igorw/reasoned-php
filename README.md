# reasoned-php

A [miniKanren](http://minikanren.org/) in PHP.

## Examples

### eq

    var_dump(run_star(function ($q) {
        return eq($q, 'corn');
    }));

    // => ['corn']

### conde

    var_dump(run_star(function ($q) {
        return conde([
            [eq($q, 'tea')],
            [eq($q, 'cup')],
        ]);
    }));

    // => ['tea', 'cup']

### firsto

    var_dump(run_star(function ($q) {
        return firsto(['a', 'c', 'o', 'r', 'n'], $q);
    }));

    // => ['a']

### resto

    var_dump(run_star(function ($q) {
        return resto(['a', 'c', 'o', 'r', 'n'], $q);
    }));

    // => [['c', 'o', 'r', 'n']]

### all

    var_dump(run_star(function ($q) {
        return all([
            firsto(['a', 'l'], $q),
            firsto(['a', 'x'], $q),
            firsto(['a', 'z'], $q),
        ]);
    }));

    // => ['a']

### fresh

    var_dump(run_star(function ($q) {
        return fresh(function ($x) use ($q) {
            return all([
                eq(['d', 'a', $x, 'c'], $q),
                conso($x, ['a', $x, 'c'], $q),
            ]);
        });
    }));

    // => ['d', 'a', 'd', 'c']

### membero

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

### run

    var_dump(run(3, function ($q) {
        return membero('tofu', $q);
    }));

    // => [['tofu', '.', '_.0']
    //     ['_.0', 'tofu', '.', '_.1']
    //     ['_.0', '_.1', 'tofu', '.', '_.2']]

### appendo

    var_dump(run_star(function ($q) {
        return appendo([1, 2, 3], [4, 5, 6], $q);
    }));

    // => [[1, 2, 3, 4, 5, 6]]

### neq (disequality)

    var_dump(run_star(function ($q) {
        return all([
            membero($q, [1, 2, 3]),
            neq($q, 2),
        ]);
    }));

    // => [1, 3]

### rembero

    function rembero($x, $l, $out) {
        return conde([
            [eq([], $l), eq([], $out)],
            [fresh(function ($a, $d) use ($x, $l, $out) {
                return all([
                    eq(pair($a, $d), $l),
                    eq($a, $x),
                    eq($d, $out),
                ]);
             })],
            [fresh(function ($a, $d, $res) use ($x, $l, $out) {
                return all([
                    eq(pair($a, $d), $l),
                    neq($a, $x),
                    eq(pair($a, $res), $out),
                    rembero($x, $d, $res),
                ]);
             })],
        ]);
    }

    var_dump(run_star(function ($q) {
        return rembero('b', ['a', 'b', 'c', 'b', 'd'], $q);
    }));

    // => [['a', 'c', 'b', 'd']]

## See also

* [The Reasoned Schemer](http://mitpress.mit.edu/books/reasoned-schemer)
* [miniKanren](http://minikanren.org/)
* [miniKanren (Byrd's Dissertation)](https://scholarworks.iu.edu/dspace/bitstream/handle/2022/8777/Byrd_indiana_0093A_10344.pdf)
* [microKanren](http://webyrd.net/scheme-2013/papers/HemannMuKanren2013.pdf)
* [rKanren](http://webyrd.net/scheme-2013/papers/Swords2013.pdf)
