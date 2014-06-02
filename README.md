# reasoned-php

A [miniKanren](http://minikanren.org/) in PHP.

## Prologue

What the hell is this? It's a tiny logic programming engine!

What is logic programming, you ask? Logic programming is a largely underrated
paradigm that radically changes the way you write, think about, and run,
programs.

Imagine your program as a bunch of relations. You can relate things to each
other. Every time you would use an uni-directional assignment `=`, that
assignment now becomes a bi-directional relation `==`. It goes both ways. The
program forms a chain of relations from one or more inputs to one or more
outputs.

You can introduce logic variables that are unbound values (using `fresh`).
These variables obey the constraints imposed on them through relations. This
allows you to provide a fresh logic variable and see what it gets bound to.
That's what you generally do to get an output from a logic program.

Through the use of conjunction ("and") and disjunction ("or"), you can form
logical relations. This allows you to encode different possible flows of
execution. The higher-level method for this is `conde`, which is a disjunction
of conjunctions.

All of these logical relations form a tree. The execution of a program
corresponds to a breadth-first search through the tree that unifies the
provided arguments in accordance to the relations. This means that a program
can discover more than one solution, or no solution at all.

This radical way of thinking about and writing programs allows for something
very amazing: **You can run your programs backwards.**

What this means is that you can either give a program inputs and search for a
corresponding output, but you can also provide outputs and ask for
corresponding inputs.

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
* Clause and Effect by William F. Clocksin
