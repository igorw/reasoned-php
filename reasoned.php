<?php

namespace igorw\reasoned;

//                    Quick miniKanren-like code
//
// written at the meeting of a Functional Programming Group
// (Toukyou/Shibuya, Apr 29, 2006), as a quick illustration of logic
// programming.  The code is really quite trivial and unsophisticated:
// it was written without any preparation whatsoever. The present file
// adds comments and makes minor adjustments.
//
// $Id: sokuza-kanren.scm,v 1.1 2006/05/10 23:12:41 oleg Exp oleg $


// Point 1: `functions' that can have more (or less) than one result
//
// As known from logic, a binary relation xRy (where x \in X, y \in Y)
// can be represented by a _function_  X -> PowerSet{Y}. As usual in
// computer science, we interpret the set PowerSet{Y} as a multi-set
// (realized as a regular scheme list). Compare with SQL, which likewise
// uses multisets and sequences were sets are properly called for.
// Also compare with Wadler's `representing failure as a list of successes.'
//
// Thus, we represent a 'relation' (aka `non-deterministic function')
// as a regular scheme function that returns a list of possible results.
// Here, we use a regular list rather than a lazy list, just to be quick.

// First, we define two primitive non-deterministic functions;
// one of them yields no result whatsoever for any argument; the other
// merely returns its argument as the sole result.

function fail($x) {
    return [];
}

function succeed($x) {
    return [$x];
}

const fail = 'igorw\reasoned\fail';
const succeed = 'igorw\reasoned\succeed';

// We build more complex non-deterministic functions by combining
// the existing ones with the help of the following two combinators.

function apply($fn, $args) {
    return call_user_func_array($fn, $args);
}

function call(/* $fn, $args... */) {
    $args = func_get_args();
    $fn = array_shift($args);
    return call_user_func_array($fn, $args);
}

function append(/** $args */) {
    return apply('array_merge', func_get_args());
}


// (disj f1 f2) returns all the results of f1 and all the results of f2.
// (disj f1 f2) returns no results only if neither f1 nor f2 returned
// any. In that sense, it is analogous to the logical disjunction.
function disj($f1, $f2) {
    return function ($x) use ($f1, $f2) {
        return append($f1($x), $f2($x));
    };
}

// (conj f1 f2) looks like a `functional composition' of f2 and f1.
// Only (f1 x) may return several results, so we have to apply f2 to
// each of them.
// Obviously (conj fail f) and (conj f fail) are both equivalent to fail:
// they return no results, ever. It that sense, conj is analogous to the
// logical conjunction.
function conj($f1, $f2) {
    return function ($x) use ($f1, $f2) {
        return apply(__NAMESPACE__.'\append', array_map($f2, $f1($x)));
    };
}


// Point 2: (Prolog-like) Logic variables
//
// One may think of regular variables as `certain knowledge': they give
// names to definite values.  A logic variable then stands for
// `improvable ignorance'.  An unbound logic variable represents no
// knowledge at all; in other words, it represents the result of a
// measurement _before_ we have done the measurement. A logic variable
// may be associated with a definite value, like 10. That means
// definite knowledge.  A logic variable may be associated with a
// semi-definite value, like (list X) where X is an unbound
// variable. We know something about the original variable: it is
// associated with the list of one element.  We can't say though what
// that element is. A logic variable can be associated with another,
// unbound logic variable. In that case, we still don't know what
// precisely the original variable stands for. However, we can say that it
// represents the same thing as the other variable. So, our
// uncertainty is reduced.

// We chose to represent logic variables as vectors:

class LVar {
    public $name;

    function __construct($name) {
        $this->name = $name;
    }
}

function lvar($name) {
    return new LVar($name);
}

function is_lvar($x) {
    return is_object($x) && $x instanceof LVar;
}

// We implement associations of logic variables and their values
// (aka, _substitutions_) as associative lists of (variable . value)
// pairs.
// One may say that a substitution represents our current knowledge
// of the world.

class Pair {
    public $first;
    public $second;

    function __construct($first, $second) {
        $this->first = $first;
        $this->second = $second;
    }

    function as_array() {
        return [$this->first, $this->second];
    }
}

function pair($first, $second) {
    return new Pair($first, $second);
}

function cons($x, $l) {
    array_unshift($l, $x);
    return $l;
}

function first($l) {
    return array_shift($l);
}

function rest($l) {
    array_shift($l);
    return $l;
}

function empty_subst() {
    return [];
}

function ext_s($var, $value, $s) {
    return cons(pair($var->name, $value), $s);
}

// Find the value associated with var in substitution s.
// Return var itself if it is unbound.
// In miniKanren, this function is called 'walk'

function assq($x, $l) {
    foreach ($l as $pair) {
        list($key, $value) = $pair->as_array();
        if ($x == $key) {
            return $pair;
        }
    }

    return false;
}

function lookup($var, $s) {
    if (!is_lvar($var)) {
        return $var;
    }

    $pair = assq($var->name, $s);
    if ($pair) {
        return lookup($pair->second, $s);
    }

    return $var;
}

// There are actually two ways of implementing substitutions as
// associative list.
// If the variable x is associated with y and y is associated with 1,
// we could represent this knowledge as
// ((x . 1) (y . 1))
// It is easy to lookup the value associated with the variable then,
// via a simple assq. OTH, if we have the substitution ((x . y))
// and we wish to add the association of y to 1, we have
// to make rearrangements so to produce ((x . 1) (y . 1)).
// OTH, we can just record the associations as we learn them, without
// modifying the previous ones. If originally we knew ((x . y))
// and later we learned that y is associated with 1, we can simply
// prepend the latter association, obtaining ((y . 1) (x . y)).
// So, adding new knowledge becomes fast. The lookup procedure becomes
// more complex though, as we have to chase the chains of variables.
// To obtain the value associated with x in the latter substitution, we
// first lookup x, obtain y (another logic variable), then lookup y
// finally obtaining 1.
// We prefer the latter, incremental way of representing knowledge:
// it is easier to backtrack if we later find out our
// knowledge leads to a contradiction.


// Unification is the process of improving knowledge: or, the process
// of measurement. That measurement may uncover a contradiction though
// (things are not what we thought them to be). To be precise, the
// unification is the statement that two terms are the same. For
// example, unification of 1 and 1 is successful -- 1 is indeed the
// same as 1. That doesn't add however to our knowledge of the world. If
// the logic variable X is associated with 1 in the current
// substitution, the unification of X with 2 yields a contradiction
// (the new measurement is not consistent with the previous
// measurements/hypotheses).  Unification of an unbound logic variable
// X and 1 improves our knowledge: the `measurement' found that X is
// actually 1.  We record that fact in the new substitution.


// return the new substitution, or #f on contradiction.

function unify($t1, $t2, $s) {
    $t1 = lookup($t1, $s);
    $t2 = lookup($t2, $s);

    if (!is_object($t1) && !is_object($t2) && $t1 === $t2) {
        return $s;
    }

    if (is_lvar($t1)) {
        return ext_s($t1, $t2, $s);
    }

    if (is_lvar($t2)) {
        return ext_s($t2, $t1, $s);
    }

    if (is_array($t1) && is_array($t2)) {
        $s = unify(first($t1), first($t2), $s);
        return $s !== false ? unify(rest($t1), rest($t2), $s) : false;
    }

    if ($t1 === $t2) {
        return $s;
    }

    return false;
}


// Part 3: Logic system
//
// Now we can combine non-deterministic functions (Part 1) and
// the representation of knowledge (Part 2) into a logic system.
// We introduce a 'goal' -- a non-deterministic function that takes
// a substitution and produces 0, 1 or more other substitutions (new
// knowledge). In case the goal produces 0 substitutions, we say that the
// goal failed. We will call any result produced by the goal an 'outcome'.

// The functions 'succeed' and 'fail' defined earlier are obviously
// goals.  The latter is the failing goal. OTH, 'succeed' is the
// trivial successful goal, a tautology that doesn't improve our
// knowledge of the world. We can now add another primitive goal, the
// result of a `measurement'.  The quantum-mechanical connotations of
// `the measurement' must be obvious by now.

function eq($t1, $t2) {
    return function ($s) use ($t1, $t2) {
        $res = unify($t1, $t2, $s);
        if ($res !== false) {
            return succeed($res);
        }

        return fail($s);
    };
}


// We also need a way to 'run' a goal,
// to see what knowledge we can obtain starting from sheer ignorance
function run($g) {
    return $g(empty_subst());
}


// We can build more complex goals using lambda-abstractions and previously
// defined combinators, conj and disj.
// For example, we can define the function `choice' such that
// (choice t1 a-list) is a goal that succeeds if t1 is an element of a-list.

function choice($var, $list) {
    if ([] === $list) {
        return fail;
    }

    return disj(
        eq($var, first($list)),
        choice($var, rest($list)));
}

// The name `choice' should evoke The Axiom of Choice...

// Now we can write a very primitive program: find an element that is
// common in two lists:

function common_el($l1, $l2) {
    $vx = lvar('x');

    return conj(
        choice($vx, $l1),
        choice($vx, $l2));
}

__halt_compiler();


// Let us do something a bit more complex

(define (conso a b l) (== (cons a b) l))

// (conso a b l) is a goal that succeeds if in the current state
// of the world, (cons a b) is the same as l.
// That may, at first, sound like the meaning of cons. However, the
// declarative formulation is more powerful, because a, b, or l might
// be logic variables.
//
// By running the goal which includes logic variables we are
// essentially asking the question what the state of the world should
// be so that (cons a b) could be the same as l.

(cout "conso-1" nl
  (run (conso 1 '(2 3) vx))
  nl)
// => (((#(x) 1 2 3))) === (((#(x) . (1 2 3))))

(cout "conso-2" nl
  (run (conso vx vy (list 1 2 3)))
  nl)
// => (((#(y) 2 3) (#(x) . 1)))
// That looks now like 'cons' in reverse. The answer means that
// if we replace vx with 1 and vy with (2 3), then (cons vx vy)
// will be the same as '(1 2 3)

// Terminology: (conso vx vy '(1 2 3)) is a goal (or, to be more precise,
// an expression that evaluates to a goal). By itself, 'conso'
// is a parameterized goal (or, abstraction over a goal):
// conso === (lambda (x y z) (conso x y z))
// We will call such an abstraction 'relation'.

// Let us attempt a more complex relation: appendo
// That is, (appendo l1 l2 l3) holds if the list l3 is the
// concatenation of lists l1 and l2.
// The first attempt:

(define (apppendo l1 l2 l3)
  (disj
    (conj (== l1 '()) (== l2 l3))    ; [] ++ l == l
    (let ((h (var 'h)) (t (var 't))  ; (h:t) ++ l == h : (t ++ l)
      (l3p (var 'l3p)))
      (conj
    (conso h t l1)
    (conj
      (conso h l3p l3)
      (apppendo t l2 l3p))))))

// If we run the following, we get into the infinite loop.
// (cout "t1"
//   (run (apppendo '(1) '(2) vq))
//   nl)

// It is instructive to analyze why. The reason is that
// (apppendo t l2 l3p) is a function application in Scheme,
// and so the (call-by-value) evaluator tries to find its value first,
// before invoking (conso h t l1). But evaluating (apppendo t l2 l3p)
// will again require the evaluation of (apppendo t1 l21 l3p1), etc.
// So, we have to introduce eta-expansion. Now, the recursive
// call to apppendo gets evaluated only when conj applies
// (lambda (s) ((apppendo t l2 l3p) s)) to each result of (conso h l3p l3).
// If the latter yields '() (no results), then appendo will not be
// invoked. Compare that with the situation above, where appendo would
// have been invoked anyway.

(define (apppendo l1 l2 l3)
  (disj                              ; In Haskell notation:
    (conj (== l1 '()) (== l2 l3))    ; [] ++ l == l
    (let ((h (var 'h)) (t (var 't))  ; (h:t) ++ l == h : (t ++ l)
      (l3p (var 'l3p)))
      (conj
    (conso h t l1)
    (lambda (s)
    ((conj
      (conso h l3p l3)
      (lambda (s)
       ((apppendo t l2 l3p) s))) s))))))

(cout "t1" nl
  (run (apppendo '(1) '(2) vq))
  nl)
// => (((#(l3p) 2) (#(q) #(h) . #(l3p)) (#(t)) (#(h) . 1)))

// That all appears to work, but the result is kind of ugly;
// and all the eta-expansion spoils the code.

// To hide the eta-expansion (that is, (lambda (s) ...) forms),
// we have to introduce a bit of syntactic sugar:

(define-syntax conj*
  (syntax-rules ()
    ((conj*) succeed)
    ((conj* g) g)
    ((conj* g gs ...)
      (conj g (lambda (s) ((conj* gs ...) s))))))

// Incidentally, for disj* we can use a regular function
// (because we represent all the values yielded by a non-deterministic
// function as a regular list rather than a lazy list). All branches
// of disj will be evaluated anyway, in our present model.
(define (disj* . gs)
  (if (null? gs) fail
    (disj (car gs) (apply disj* (cdr gs)))))

// And so we can re-define appendo as follows. It does look
// quite declarative, as the statement of two equations that
// define what list concatenation is.

(define (apppendo l1 l2 l3)
  (disj                              ; In Haskell notation:
    (conj* (== l1 '()) (== l2 l3))   ; [] ++ l == l
    (let ((h (var 'h)) (t (var 't))  ; (h:t) ++ l == h : (t ++ l)
      (l3p (var 'l3p)))
      (conj*
    (conso h t l1)
    (conso h l3p l3)
    (apppendo t l2 l3p)))))


// We also would like to make the result yielded by run more
// pleasant to look at.
// First of all, let us assume that the variable vq (if bound),
// holds the answer to our inquiry. Thus, our new run will try to
// find the value associated with vq in the final substitution.
// However, the found value may itself contain logic variables.
// We would like to replace them, too, with their associated values,
// if any, so the returned value will be more informative.

// We define a more diligent version of lookup, which replaces
// variables with their values even if those variables occur deep
// inside a term.

(define (lookup* var s)
  (let ((v (lookup var s)))
    (cond
      ((var? v) v)          ; if lookup returned var, it is unbound
      ((pair? v)
    (cons (lookup* (car v) s)
          (lookup* (cdr v) s)))
      (else v))))

// We can now redefine run as

(define (run g)
  (map (lambda (s) (lookup* vq s)) (g empty-subst)))

// and we can re-run the test

(cout "t1" nl
  (run (apppendo '(1) '(2) vq))
  nl)
// => ((1 2))

(cout "t2" nl
  (run (apppendo '(1) '(2) '(1)))
  nl)
// => ()
// That is, concatenation of '(1) and '(2) is not the same as '(1)

(cout "t3" nl
  (run (apppendo '(1 2 3) vq '(1 2 3 4 5)))
  nl)
// => ((4 5))


(cout "t4" nl
  (run (apppendo vq '(4 5) '(1 2 3 4 5)))
  nl)
// => ((1 2 3))

(cout "t5" nl
  (run (apppendo vq vx '(1 2 3 4 5)))
  nl)
// => (() (1) (1 2) (1 2 3) (1 2 3 4) (1 2 3 4 5))
// All prefixes of '(1 2 3 4 5)


(cout "t6" nl
  (run (apppendo vx vq '(1 2 3 4 5)))
  nl)
// => ((1 2 3 4 5) (2 3 4 5) (3 4 5) (4 5) (5) ())
// All suffixes of '(1 2 3 4 5)


(cout "t7" nl
  (run (let ((x (var 'x)) (y (var 'y)))
     (conj* (apppendo x y '(1 2 3 4 5))
            (== vq (list x y)))))
  nl)
// => ((() (1 2 3 4 5)) ((1) (2 3 4 5)) ((1 2) (3 4 5))
//     ((1 2 3) (4 5)) ((1 2 3 4) (5)) ((1 2 3 4 5) ()))
// All the ways to split (1 2 3 4 5) into two complementary parts


// For more detail, please see `The Reasoned Schemer'
