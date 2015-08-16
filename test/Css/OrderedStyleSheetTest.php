<?php
namespace InlineStyle\Css;

class OrderedStyleSheetTest extends \PHPUnit_Framework_TestCase
{
    public function test_parse_simple_stylesheet()
    {
        $string = <<<CSS
a[href], #id, #id.class, .class { font-family: "Arvo", Courier, monospace;
/* set color */
color: #003399;}
CSS;

        $stylesheet = OrderedStyleSheet::fromString($string);

        $this->assertEquals(
            <<<CSS
a[href]{font-family:"Arvo", Courier, monospace;color:#003399}
.class{font-family:"Arvo", Courier, monospace;color:#003399}
#id{font-family:"Arvo", Courier, monospace;color:#003399}
#id.class{font-family:"Arvo", Courier, monospace;color:#003399}
CSS
            ,
            (string) $stylesheet

        );
    }

    public function test_merge_two_small_stylesheets()
    {
        $a = OrderedStyleSheet::fromString("a {color: red;}");
        $b = OrderedStyleSheet::fromString("a {color: blue}");

        $this->assertEquals(
            'a{color:red}
a{color:blue}',
            (string) $a->merge($b)
        );
    }

    public function test_empty_string()
    {
        $stylesheet = OrderedStyleSheet::fromString('');

        $this->assertEquals('', (string) $stylesheet);
    }

    public function test_multiple_rules()
    {
        $stylesheet = OrderedStyleSheet::fromString('    h1{
        color:yellow
    }
    p {
        color:yellow !important;
    }
    p {
        color:blue
    }');

        $this->assertEquals('h1{color:yellow}
p{color:yellow !important}
p{color:blue}', (string) $stylesheet);
    }

    public function test_does_not_include_keyframes()
    {
        $stylesheet = OrderedStyleSheet::fromString('    h1{
        color:yellow
    }
    @-webkit-keyframes test {
  from {
    background-position: 40px 0;
  }
  to {
    background-position: 0 0;
  }
}');

        $this->assertEquals('h1{color:yellow}', (string) $stylesheet);
    }

    public function test_multiple_rules_are_ordered_on_specificity()
    {
        $stylesheet = OrderedStyleSheet::fromString(
            'p {
    color: red;
}

p.p2 {
    color: green;
}

.p2 {
    color: blue;
}

'
        );

        $this->assertEquals('p{color:red}
.p2{color:blue}
p.p2{color:green}', (string) $stylesheet);
    }
}
