<?php

namespace igorw\reasoned;

// miniKanren implementation, loosely based on microKanren

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
        if (occurs_check($x, $value, $this)) {
            // @todo return unextended subst? throw exception?
            return null;
        }
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
        return $this;
    }
    function prefix(Substitution $s) {
        $prefix = [];
        $values = $this->values;
        while ($values != $s->values) {
            $prefix[] = first($values);
            $values = rest($values);
        }
        return new Substitution($prefix);
    }
}

// the road not taken: occurs-check

function occurs_check($x, $v, Substitution $subst) {
    $v = $subst->walk($v);
    if (is_variable($v)) {
        return $v->is_equal($x);
    }
    if (is_unifiable_array($v)) {
        return occurs_check($x, first($v), $subst) || occurs_check($x, rest($v), $subst);
    }
    return false;
}

// disequality, from byrd's dissertation

class ConstraintStore {
    public $constraints;
    function __construct(array $constraints = []) {
        $this->constraints = $constraints;
    }
    function first() {
        return first($this->constraints);
    }
    function extend(Substitution $constraint) {
        return new ConstraintStore(array_merge(
            [$constraint],
            $this->constraints
        ));
    }
    function verify(Substitution $subst) {
        $verified = [];
        foreach ($this->constraints as $c) {
            $subst2 = unify_star($c, $subst);
            if ($subst2) {
                if ($subst == $subst2) {
                    return null;
                }
                $c = $subst2->prefix($subst);
                $verified[] = $c;
            }
        }
        return new ConstraintStore($verified);
    }
    // r = reified name substitution
    function purify(Substitution $r) {
        return new ConstraintStore(array_filter($this->constraints, function ($c) use ($r) {
            return !any_var($c->values, $r);
        }));
    }
    function to_array() {
        return array_map(function (Substitution $c) {
            return $c->values;
        }, $this->constraints);
    }
}

function any_var($v, Substitution $r) {
    if (is_variable($v)) {
        return is_variable($r->walk($v));
    }
    if (is_unifiable_array($v)) {
        // @todo use foreach?
        return any_var(first($v), $r) || any_var(rest($v), $r);
    }
    return false;
}

function is_subsumed(Substitution $c, ConstraintStore $cs) {
    foreach ($cs->constraints as $constraint) {
        if (unify_star($constraint, $c) == $c) {
            return true;
        }
    }
    return false;
}

function remove_subsumed(ConstraintStore $cs, ConstraintStore $cs2) {
    if ([] === $cs->constraints) {
        return $cs2;
    }
    $cs_rest = new ConstraintStore(rest($cs->constraints));
    if (is_subsumed($cs->first(), $cs2) || is_subsumed($cs->first(), $cs_rest)) {
        return remove_subsumed($cs_rest, $cs2);
    }
    return remove_subsumed($cs_rest, $cs2->extend($cs->first()));
}

class State {
    public $subst;
    public $count;
    public $cs;
    function __construct(Substitution $subst = null, $count = 0, ConstraintStore $cs = null) {
        $this->subst = $subst ?: new Substitution();
        $this->count = $count;
        $this->cs = $cs ?: new ConstraintStore();
    }
    function next() {
        return new State($this->subst, $this->count + 1, $this->cs);
    }
    function reify() {
        $v = walk_star(variable(0), $this->subst);
        $cs = walk_star($this->cs, $this->subst);

        $r = (new Substitution())->reify($v);
        $v = walk_star($v, $r);

        $cs = $cs->purify($r);
        $cs = remove_subsumed($cs, new ConstraintStore());
        $cs = walk_star($cs, $r);

        $cs = $cs->to_array();
        if ([] === $cs) {
            return $v;
        }
        return [$v, ':-', array_merge(['!='], $cs)];
    }
}

function eq($u, $v) {
    return function (State $state) use ($u, $v) {
        $subst = unify($u, $v, $state->subst);
        if (!$subst) {
            return mzero();
        }
        if ($state->subst == $subst) {
            return unit($state);
        }
        $cs = $state->cs->verify($subst);
        if ($cs) {
            return unit(new State($subst, $state->count, $cs));
        }
        return mzero();
    };
}

function neq($u, $v) {
    return function (State $state) use ($u, $v) {
        $subst = unify($u, $v, $state->subst);
        if (!$subst) {
            return unit($state);
        }
        if ($state->subst == $subst) {
            return mzero();
        }
        $c = $subst->prefix($state->subst);
        return unit(new State($state->subst, $state->count, $state->cs->extend($c)));
    };
}

function unit(State $state) {
    return new PairStream($state, mzero());
}

function mzero() {
    return new EmptyStream();
}

function is_unifiable_array($value) {
    if ($value instanceof Substitution) {
        $value = $value->values;
    }
    if ($value instanceof ConstraintStore) {
        $value = $value->constraints;
    }
    return is_pair($value) || is_array($value) && count($value) > 0;
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
    if ($u === $v) {
        return $subst;
    }
    return null;
}

function unify_star(Substitution $ps, Substitution $subst) {
    foreach ($ps->values as list($u, $v)) {
        $subst = unify($u, $v, $subst);
        if (!$subst) {
            return null; // false instead of null?
        }
    }
    return $subst;
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

// @todo: do not rely on cons/first/rest for subst/cs during reification?
function cons($value, $list) {
    if ($list instanceof Substitution) {
        return new Substitution(cons($value, $list->values));
    }
    if ($list instanceof ConstraintStore) {
        return new ConstraintStore(cons($value, $list->constraints));
    }
    array_unshift($list, $value);
    return $list;
}

function first($list) {
    if ($list instanceof Substitution) {
        return first($list->values);
    }
    if ($list instanceof ConstraintStore) {
        return first($list->constraints);
    }
    if (is_pair($list)) {
        return $list->first;
    }
    return array_shift($list);
}

function rest($list) {
    if ($list instanceof Substitution) {
        return new Substitution(rest($list->values));
    }
    if ($list instanceof ConstraintStore) {
        return new ConstraintStore(rest($list->constraints));
    }
    if (is_pair($list)) {
        return $list->rest;
    }
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
    if (is_pair($v)) {
        $first = walk_star(first($v), $subst);
        $rest = walk_star(rest($v), $subst);
        if (is_array($rest)) {
            return cons($first, $rest);
        }
        return [$first, '.', $rest];
    }
    if (is_unifiable_array($v)) {
        return cons(walk_star(first($v), $subst), walk_star(rest($v), $subst));
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

function ≢($u, $v) {
    return neq($u, $v);
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

// debugging goals (inspired by core.logic)

function log($msg) {
    return function (State $state) use ($msg) {
        echo "$msg\n";
        return unit($state);
    };
}

function trace_subst() {
    return function (State $state) {
        var_dump($state->subst);
        return unit($state);
    };
}

function trace_lvars(array $vars) {
    return function (State $state) use ($vars) {
        foreach ($vars as $var) {
            $v = walk_star($var, $state->subst);
            $reified = walk_star($v, (new Substitution())->reify($v));

            if (is_variable($var) && is_string($reified)) {
                echo "variable({$var->name}) = $reified\n";
            } else if (is_variable($var)) {
                echo "variable({$var->name}) =\n";
                var_dump($reified);
            } else {
                var_dump($reified);
            }
        }
        return unit($state);
    };
}

// @todo unifying with null
// @todo the fun never ends: anyo, nevero, alwayso
