<?php

namespace igorw\reasoned;

// microKanren implementation

class Variable {
    public $name;
    function __construct($name) {
        $this->name = $name;
    }
    function is_equal(Variable $var) {
        return $this->name === $var->name;
    }
}

function variable($name) {
    return new Variable($name);
}

function is_variable($x) {
    return $x instanceof Variable;
}

class Substitution {
    public $values;
    function __construct(array $values = []) {
        $this->values = $values;
    }
    function walk($u) {
        if (is_variable($u) && null !== $value = $this->find($u)) {
            return $this->walk($value);
        }
        return $u;
    }
    function find(Variable $var) {
        foreach ($this->values as list($x, $value)) {
            if ($var->is_equal($x)) {
                return $value;
            }
        }
        return null;
    }
    function extend(Variable $x, $value) {
        return new Substitution(array_merge(
            [[$x, $value]],
            $this->values
        ));
    }
    function length() {
        return count($this->values);
    }
    function reify($v) {
        $v = $this->walk($v);
        if (is_variable($v)) {
            $n = reify_name($this->length());
            return $this->extend($v, $n);
        }
        if (is_unifiable_array($v)) {
            return $this->reify(first($v))
                        ->reify(rest($v));
        }
        if (is_pair($v)) {
            return $this->reify($v->first)
                        ->reify($v->rest);
        }
        return $this;
    }
}

class State {
    public $subst;
    public $count;
    function __construct(Substitution $subst = null, $count = 0) {
        $this->subst = $subst ?: new Substitution();
        $this->count = $count;
    }
    function next() {
        return new State($this->subst, $this->count + 1);
    }
    function reify() {
        $v = walk_star(variable(0), $this->subst);
        return walk_star($v, (new Substitution())->reify($v));
    }
}

function eq($u, $v) {
    return function (State $state) use ($u, $v) {
        $subst = unify($u, $v, $state->subst);
        if ($subst) {
            return unit(new State($subst, $state->count));
        }
        return mzero();
    };
}

function unit(State $state) {
    return new PairStream($state, mzero());
}

function mzero() {
    return new EmptyStream();
}

function is_unifiable_array($value) {
    return is_array($value) && count($value) > 0;
}

class Pair {
    public $first;
    public $rest;
    function __construct($first, $rest) {
        $this->first = $first;
        $this->rest = $rest;
    }
}

function pair($first, $rest) {
    return new Pair($first, $rest);
}

function is_pair($x) {
    return $x instanceof Pair;
}

function unify($u, $v, Substitution $subst) {
    $u = $subst->walk($u);
    $v = $subst->walk($v);

    if (is_variable($u) && is_variable($v) && $u->is_equal($v)) {
        return $subst;
    }
    if (is_variable($u)) {
        return $subst->extend($u, $v);
    }
    if (is_variable($v)) {
        return $subst->extend($v, $u);
    }
    if (is_unifiable_array($u) && is_unifiable_array($v)) {
        $subst = unify(first($u), first($v), $subst);
        return $subst ? unify(rest($u), rest($v), $subst) : null;
    }

    if (is_pair($u) && is_unifiable_array($v)) {
        $subst = unify($u->first, first($v), $subst);
        return $subst ? unify($u->rest, rest($v), $subst) : null;
    }
    if (is_unifiable_array($u) && is_pair($v)) {
        return unify($v, $u, $subst);
    }
    if (is_pair($u) && is_pair($v)) {
        $subst = unify($u->first, $v->first, $subst);
        return $subst ? unify($u->rest, $v->rest, $subst) : null;
    }

    if ($u === $v) {
        return $subst;
    }
    return null;
}

// $f takes a fresh variable and returns a goal
function call_fresh(callable $f) {
    return function (State $state) use ($f) {
        $goal = $f(variable($state->count));
        return $goal($state->next());
    };
}

// same as call_fresh, but without fresh var
function delay(callable $f) {
    return function (State $state) use ($f) {
        $goal = $f();
        return $goal($state->next());
    };
}

function disj(callable $goal1, callable $goal2) {
    return function (State $state) use ($goal1, $goal2) {
        return $goal1($state)->mplus($goal2($state));
    };
}

function conj(callable $goal1, callable $goal2) {
    return function (State $state) use ($goal1, $goal2) {
        return $goal1($state)->bind($goal2);
    };
}

function cons($value, array $list) {
    array_unshift($list, $value);
    return $list;
}

function first(array $list) {
    return array_shift($list);
}

function rest(array $list) {
    array_shift($list);
    return $list;
}

interface Stream extends \IteratorAggregate {
    function mplus(Stream $stream2);
    function bind(callable $goal);
}

class EmptyStream implements Stream {
    function mplus(Stream $stream2) {
        return $stream2;
    }
    function bind(callable $goal) {
        return mzero();
    }
    function getIterator() {
        return new \EmptyIterator();
    }
}

class CallableStream implements Stream {
    public $f;
    function __construct(callable $f) {
        $this->f = $f;
    }
    function mplus(Stream $stream2) {
        return new CallableStream(function () use ($stream2) {
            return $stream2->mplus($this->resolve());
        });
    }
    function bind(callable $goal) {
        return new CallableStream(function () use ($goal) {
            return $this->resolve()->bind($goal);
        });
    }
    function getIterator() {
        return $this->resolve()->getIterator();
    }
    function resolve() {
        return call_user_func($this->f);
    }
}

class PairStream implements Stream {
    public $first;
    public $rest;
    function __construct($first, Stream $rest) {
        $this->first = $first;
        $this->rest = $rest;
    }
    function mplus(Stream $stream2) {
        return new PairStream($this->first, $this->rest->mplus($stream2));
    }
    function bind(callable $goal) {
        return $goal($this->first)->mplus($this->rest->bind($goal));
    }
    function getIterator() {
        yield $this->first;
        foreach ($this->rest as $x) {
            yield $x;
        }
    }
}

// recovering miniKanren's control operators

function zzz(callable $goal) {
    return function (State $state) use ($goal) {
        return new CallableStream(function () use ($goal, $state) {
            return $goal($state);
        });
    };
}

function conj_plus(array $goals) {
    if (count($goals) === 0) {
        throw new \InvalidArgumentException('Must supply at least one goal');
    }
    if (count($goals) === 1) {
        return zzz(first($goals));
    }
    return conj(zzz(first($goals)), conj_plus(rest($goals)));
}

function disj_plus(array $goals) {
    if (count($goals) === 0) {
        throw new \InvalidArgumentException('Must supply at least one goal');
    }
    if (count($goals) === 1) {
        return zzz(first($goals));
    }
    return disj(zzz(first($goals)), disj_plus(rest($goals)));
}

function conde(array $lines) {
    return disj_plus(array_map('igorw\reasoned\conj_plus', $lines));
}

// based heavily on mudge/php-microkanren
function fresh(callable $f) {
    $argCount = (new \ReflectionFunction($f))->getNumberOfParameters();
    if ($argCount === 0) {
        return delay($f);
    }
    return call_fresh(function ($x) use ($f, $argCount) {
        return collect_args($f, $argCount, [$x]);
    });
}

function collect_args(callable $f, $argCount, $args) {
    if (count($args) === $argCount) {
        return call_user_func_array($f, $args);
    }

    return call_fresh(function ($x) use ($f, $argCount, $args) {
        return collect_args($f, $argCount, array_merge($args, [$x]));
    });
}

// from streams to lists
// @todo use iter?

function take($n, $stream) {
    foreach ($stream as $x) {
        if ($n-- === 0) {
            break;
        }
        yield $x;
    }
}

function map(callable $f, $stream) {
    foreach ($stream as $x) {
        yield $f($x);
    }
}

function to_array($stream) {
    $array = [];
    foreach ($stream as $x) {
        $array[] = $x;
    }
    return $array;
}

// recovering reification

function reify($states) {
    return map(function (State $state) { return $state->reify(); }, $states);
}

function reify_name($n) {
    return "_.$n";
}

function walk_star($v, Substitution $subst) {
    $v = $subst->walk($v);
    if (is_variable($v)) {
        return $v;
    }
    if (is_unifiable_array($v)) {
        return cons(walk_star(first($v), $subst), walk_star(rest($v), $subst));
    }
    // @todo return pair and stringify pairs later
    if (is_pair($v)) {
        $first = walk_star($v->first, $subst);
        $rest = walk_star($v->rest, $subst);
        if (is_array($rest)) {
            return cons($first, $rest);
        }
        return [$first, '.', $rest];
    }
    return $v;
}

// recovering the scheme interface

function call_goal(callable $goal) {
    return $goal(new State());
}

function run($n, callable $goal) {
    return to_array(take($n, reify(call_goal(fresh($goal)))));
}

function run_star(callable $goal) {
    return to_array(reify(call_goal(fresh($goal))));
}

function all(array $goals) {
    return conj_plus($goals);
}

// unicode madness

function ≡($u, $v) {
    return eq($u, $v);
}

function ⋀(array $goals) {
    return conj_plus($goals);
}

function ⋁(array $goals) {
    return disj_plus($goals);
}

function run٭(callable $goal) {
    return run_star($goal);
}

function condᵉ(array $lines) {
    return conde($lines);
}

// user level plumbing

function conso($a, $d, $l) {
    return eq(pair($a, $d), $l);
}

function firsto($l, $a) {
    return fresh(function ($d) use ($l, $a) {
        return conso($a, $d, $l);
    });
}

function resto($l, $d) {
    return fresh(function ($a) use ($l, $d) {
        return conso($a, $d, $l);
    });
}

function appendo($l, $s, $out) {
    return conde([
        [eq($l, []), eq($s, $out)],
        [fresh(function ($a, $d, $res) use ($l, $s, $out) {
            return all([
                conso($a, $d, $l),
                conso($a, $res, $out),
                appendo($d, $s, $res),
            ]);
        })],
    ]);
}

// user level unicode madness

function consᵒ($a, $d, $l) {
    return conso($a, $d, $l);
}

function firstᵒ($l, $a) {
    return firsto($l, $a);
}

function restᵒ($l, $d) {
    return resto($l, $d);
}

function appendᵒ($l, $s, $out) {
    return appendo($l, $s, $out);
}

// @todo occurs check
// @todo the fun never ends: anyo, nevero, alwayso
// @todo a bit too much: oleg numbers
