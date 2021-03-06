<?php
require dirname(__DIR__)."/autoload.php";
require dirname(__DIR__)."/lime.php";

//load the class
$X = new \Pharsec\Parser();

class ParserTest extends lime_test {
  public function basicTest(){
    $fa = \Pharsec\Failed();
    $this->is((string)$fa->parse("abc"), 'Nothing', 'The failed parser always returns Nothing');
    $p = \Pharsec\Value(1);
    $res = $p->parse("abc");
    $r = $res->get();
    $this->is($r->fst, "abc", "The value parser does not consume the input");
    $this->is($r->snd, 1,     "The value parser adds a value");
  }

  public function characterTest() {
    $c = \Pharsec\Character();
    $res = $c->parse("abc")->get();
    $this->is($res->fst, "bc", "Character parser consumes one character");
    $this->is($res->snd, "a",  "Character parser returns one character");
    $this->is((string)$c->parse(""), "Nothing", "Character parser returns Nothing on empty string");
  }

  public function choiceTest() {
    $p = \Pharsec\C(\Pharsec\Character(), \Pharsec\Value(1));
    $res1 = $p->parse("abc")->get();
    $this->is($res1->snd, "a", "Choice parser works for first parser");
    $res2 = $p->parse("")->get();
    $this->is($res2->snd, 1, "Choice parser works for second parser");
  }

  public function bindTest() {
    $p1 = \Pharsec\__t(\Pharsec\Character());
    $p2 = $p1->bind(function($c){
      if(ctype_upper($c)){
        return \Pharsec\Value(1);
      }else{
        return \Pharsec\Failed();
      }
    });
    $p3 = $p2();
    $this->is((string)$p3->parse(""), "Nothing", "bindtest correctly ignores empty string");
    $this->is((string)$p3->parse("abc"), "Nothing", "fails if first char is lowercase");
    $res = $p3->parse("Abc");
    $this->is($p3->parse("Abc")->get()->snd, 1, "gives 1 if first char is uppercase");
  }

  public function ignoreTest() {
    $p = \Pharsec\Ignore(\Pharsec\Character(), \Pharsec\Character());
    $r = $p->parse("abc");
    $res1 = $r->get();
    $this->is($res1->snd, "b", "Ignores the first parser's result");
  }

  public function mapTest() {
    $p = \Pharsec\__t(\Pharsec\Character());
    $p2 = $p->map(function($x){return strtoupper($x);});
    $res = $p2()->parse("abc")->get();
    $this->is($res->fst, "bc", "map does not affect the remaining string");
    $this->is($res->snd, "A", "map affects the result");
    $this->is((string)$p2()->parse(""), "Nothing", "map does not run on Nothing");
  }

  public function sequenceTest() {
    $p = \Pharsec\Sequence(array(\Pharsec\Character(), \Pharsec\Value("x"), \Pharsec\Character()));
    $this->is((string)$p->parse(""), "Nothing", "A sequence ignores the empty string");
    $this->is((string)$p->parse("a"), "Nothing", "A sequence ignores a string too small");
    $res1 = $p->parse("ab")->get();
    $this->is($res1->fst, "", "this sequence eats two characters");
    $this->is($res1->snd, array("a", "x", "b"), "this sequence eats two characters and intersperses a 'x'");
    $res2 = $p->parse("abc")->get();
    $this->is($res2->fst, "c", "this sequence eats two characters, no more");
    $this->is($res2->snd, array("a", "x", "b"), "this sequence eats two characters and intersperses a 'x'");
  }

  public function manyTest() {
    $p = \Pharsec\manys(\Pharsec\Character());
    $this->is((string)$p->parse(""), "Nothing", "many1 returns Nothing on empty string");
    $res = $p->parse("abc")->get();
    $this->is($res->fst, "", "many1 eats all the characters");
    $this->is($res->snd, "abc", "many1 returns all the characters");
  }

  public function listTest() {
    $p = \Pharsec\lists(\Pharsec\Character());
    $res = $p->parse("")->get();
    $this->is($res->fst, "", "list returns a empty Just on empty strings");
    $this->is($res->snd, "", "list returns an empty Just on empty strings");
    $res = $p->parse("abc")->get();
    $this->is($res->fst, "", "list abc eats all the characters");
    $this->is($res->snd, "abc", "list abc returns all the characters");
    $res = $p->parse("100")->get();
    $this->is($res->fst, "", "list 100 eats all the numbers");
    $this->is($res->snd, "100", "list 100 returns all the numbers");
    $res = $p->parse("101")->get();
    $this->is($res->fst, "", "list 101 eats all the numbers");
    $this->is($res->snd, "101", "list 101 returns all the numbers");
    $res = $p->parse("1a")->get();
    $this->is($res->fst, "", "list1 eats all the numbers");
    $this->is($res->snd, "1a", "list11 returns all the numbers");
  }

  public function satisfyTest() {
    $p = \Pharsec\Satisfy(ctype_upper);
    $this->is((string)$p->parse(""), "Nothing", "Satisfy returns Nothing on empty String");
    $this->is((string)$p->parse("abc"), "Nothing", "Satisfy returns nothing if the condition is not met");
    $res = $p->parse("Abc")->get();
    $this->is($res->fst, "bc", "If the condition is satisfied, store the rest of the input");
    $this->is($res->snd, "A", "If the condition is satisfied, return the matching character");
  }

  public function isTest() {
    $p = \Pharsec\is("a");
    $this->is((string)$p->parse(""), "Nothing", "is returns Nothing on empty String");
    $this->is((string)$p->parse("xbc"), "Nothing", "is returns nothing if the condition is not met");
    $res = $p->parse("abc")->get();
    $this->is($res->fst, "bc", "If the condition is satisfied, store the rest of the input");
    $this->is($res->snd, "a", "If the condition is satisfied, return the matching character");
  }

  public function satisfiersTest() {
    $u = \Pharsec\upper();
    $this->is((string)$u->parse(""), "Nothing", "still Nothing for the empty string");
    $this->is((string)$u->parse("a"), "Nothing", "Nothing if not uppercase");
    $res = $u->parse("A")->get();
    $this->is($res->snd, "A", "correctly parsed uppercase letter");

    $d = \Pharsec\lists(\Pharsec\digit());
    $res2 = $d->parse("")->get();
    $this->is($res2->fst, "", "digit does not parse empty strings");
    $this->is($res2->snd, "", "digit does not parse empty strings");
    $res3 = $d->parse("79abc")->get();
    $this->is($res3->fst, "abc", "list of digits stopped at the first letter");
    $this->is($res3->snd, "79", "list of digits selected the digit chain");

    $s = \Pharsec\spaces();
    $res4 = $s->parse(" \t \n \r a\n")->get();
    $this->is($res4->fst, "a\n", "spaces stop at the first non space character");
    $this->is($res4->snd, " \t \n \r ", "spaces matches space, tabs and carriage returns");

    $eol = \Pharsec\eol();
    $res5 = $eol->parse("\n")->get();
    $this->is($res5->snd, "\n", "eol parses \\n");
    $res6 = $eol->parse("\r\n")->get();
    $this->is($res6->snd, "\r\n", "eol parses \\r\\n");
  }
}

$test = new ParserTest();
$test->basicTest();
$test->characterTest();
$test->choiceTest();
$test->bindTest();
$test->ignoreTest();
$test->mapTest();
$test->sequenceTest();
$test->manyTest();
$test->listTest();
$test->satisfyTest();
$test->isTest();
$test->satisfiersTest();
