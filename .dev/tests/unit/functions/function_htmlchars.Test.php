<?php


require_once dirname(__DIR__) . '/yf_unit_tests_setup.php';

class function_htmlchars_test extends yf\tests\wrapper
{
    public function test_htmlchars()
    {
        $this->assertEquals('test', _htmlchars('test'));
        $this->assertEquals('test' . PHP_EOL . 'test', _htmlchars('test' . PHP_EOL . 'test'));
        $this->assertEquals('{', _htmlchars('{'));
        $this->assertEquals('}', _htmlchars('}'));
        $this->assertEquals('\\\\', _htmlchars('\\\\'));
        $this->assertEquals('(', _htmlchars('('));
        $this->assertEquals(')', _htmlchars(')'));
        $this->assertEquals('?', _htmlchars('?'));
        $this->assertEquals('&apos;', _htmlchars('\''));
        $this->assertEquals('&quot;', _htmlchars('"'));
        $this->assertEquals('&lt;', _htmlchars('<'));
        $this->assertEquals('&gt;', _htmlchars('>'));
        $this->assertEquals('&lt;script&gt;', _htmlchars('<script>'));

        $this->assertEquals(['test'], _htmlchars(['test']));
        $this->assertEquals(['k1' => '&lt;', 'k2' => '&gt;'], _htmlchars(['k1' => '<', 'k2' => '>']));
        $this->assertEquals(['k1' => [['&lt;']], 'k2' => '&gt;'], _htmlchars(['k1' => [['<']], 'k2' => '>']));

        $this->assertEquals('&gt;', _htmlchars('&gt;'));
        $this->assertEquals('&#039;', _htmlchars('&#039;'));
    }
}
