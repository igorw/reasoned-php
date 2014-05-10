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
    function lookup(Variable $var) {
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
}

function walk($u, Substitution $subst) {
    if (is_variable($u) && $value = $subst->lookup($u)) {
        return walk($value, $subst);
    }
    return $u;
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
    return [$state, mzero()];
}

function mzero() {
    return [];
}

function unify($u, $v, Substitution $subst) {
    $u = walk($u, $subst);
    $v = walk($v, $subst);

    if (is_variable($u) && is_variable($v) && $u->is_equal($v)) {
        return $subst;
    }
    if (is_variable($u)) {
        return $subst->extend($u, $v);
    }
    if (is_variable($v)) {
        return $subst->extend($v, $u);
    }
    if (is_array($u) && is_array($v)) {
        $subst = unify(first($u), first($v), $subst);
        return $subst ? unify(rest($u), rest($v), $subst) : null;
    }
    if ($u === $v) {
        return $subst;
    }
    return null;
}

function call_fresh(callable $f) {
    return function (State $state) use ($f) {
        $res = $f(variable($state->count));
        return $res($state->next());
    };
}

function disj(callable $goal1, callable $goal2) {
    return function (State $state) use ($goal1, $goal2) {
        return mplus($goal1($state), $goal2($state));
    };
}

function conj(callable $goal1, callable $goal2) {
    return function (State $state) use ($goal1, $goal2) {
        return bind($goal1($state), $goal2);
    };
}

function cons($value, array $stream) {
    array_unshift($stream, $value);
    return $stream;
}

function first(array $stream) {
    return array_shift($stream);
}

function rest(array $stream) {
    array_shift($stream);
    return $stream;
}

function mplus($stream1, $stream2) {
    if ($stream1 === []) {
        return $stream2;
    }
    if (is_callable($stream1)) {
        return function () use ($stream1, $stream2) {
            return mplus($stream2, $stream1());
        };
    }
    return cons(first($stream1), mplus(rest($stream1), $stream2));
}

function bind($stream, callable $goal) {
    if ($stream === []) {
        return mzero();
    }
    if (is_callable($stream)) {
        return function () use ($stream, $goal) {
            return bind($stream(), $goal);
        };
    }
    return mplus($goal(first($stream)), bind(rest($stream), $goal));
}

// recovering miniKanren's control operators

function zzz(callable $goal) {
    return function (State $state) use ($goal) {
        return function () use ($goal, $state) {
            return $goal($state);
        };
    };
}

function conj_plus(/** callable $goals... */) {
    $goals = func_get_args();
    if (count($goals) === 0) {
        throw new \InvalidArgumentException('Must supply at least one goal');
    }
    if (count($goals) === 1) {
        return zzz(first($goals));
    }
    return conj(zzz(first($goals)), call_user_func_array('igorw\reasoned\conj_plus', rest($goals)));
}

function disj_plus(/** callable $goals... */) {
    $goals = func_get_args();
    if (count($goals) === 0) {
        throw new \InvalidArgumentException('Must supply at least one goal');
    }
    if (count($goals) === 1) {
        return zzz(first($goals));
    }
    return disj(zzz(first($goals)), call_user_func_array('igorw\reasoned\disj_plus', rest($goals)));
}

function conde(/** array $lines... */) {
    $lines = func_get_args();
    $conjs = array_map(function ($line) {
        return call_user_func_array('igorw\reasoned\conj_plus', $line);
    }, $lines);
    return call_user_func_array('igorw\reasoned\disj_plus', $conjs);
}

function fresh(callable $f) {
    $argCount = (new \ReflectionFunction($f))->getNumberOfParameters();
    if ($argCount === 0) {
        return $f();
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

function pull($stream) {
    if (is_callable($stream)) {
        return pull($stream());
    }
    return $stream;
}

function take_all($stream) {
    $stream = pull($stream);
    if ($stream === []) {
        return [];
    }
    return cons(first($stream), take_all(rest($stream)));
}

function take($n, $stream) {
    if ($n === 0) {
        return [];
    }
    $stream = pull($stream);
    if ($stream === []) {
        return [];
    }
    return cons(first($stream), take($n - 1, rest($stream)));
}

// recovering reification

function reify(array $states) {
    return array_map('igorw\reasoned\reify_first', $states);
}

function reify_first(State $state) {
    $v = walk_star(variable(0), $state->subst);
    return walk_star($v, reify_subst($v, new Substitution()));
}

function reify_subst($v, Substitution $subst) {
    $v = walk($v, $subst);
    if (is_variable($v)) {
        $n = reify_name($subst->length());
        return $subst->extend($v, $n);
    }
    if (is_array($v)) {
        return reify_subst(rest($v), reify_subst(first($v), $subst));
    }
    return $subst;
}

function reify_name($n) {
    return "_.$n";
}

function walk_star($v, Substitution $subst) {
    $v = walk($v, $subst);
    if (is_variable($v)) {
        return $v;
    }
    if (is_array($v)) {
        return cons(walk_star(first($v), $subst), walk_star(rest($v), $subst));
    }
    return $v;
}

// recovering the scheme interface

function call_goal($goal) {
    return $goal(new State());
}

function run($n, $goal) {
    return reify(take($n, call_goal(fresh($goal))));
}

function run_star($goal) {
    var_dump(take_all(call_goal(fresh($goal))));exit;
    var_dump(reify(array_filter(take_all(call_goal(fresh($goal))))));exit;
    return reify(take_all(call_goal(fresh($goal))));
}

var_dump(run_star(function ($x) {
    return conj(
        eq($x, 'a'),
        eq($x, 'b')
    );
}));

var_dump(run_star(function ($x) {
    return disj(
        eq($x, 'a'),
        eq($x, 'b')
    );
}));
