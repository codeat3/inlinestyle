<?php
namespace InlineStyle\Css;

/**
 * OrderedStyleSheet
 * @author Christiaan Baartse <anotherhero@gmail.com>
 */
final class OrderedStyleSheet
{
    /** @var Rule[] */
    private $rules;

    /**
     * @param Rule[] $rules
     */
    function __construct(array $rules)
    {
        usort($rules, function(Rule $a, Rule $b) {
            return $b->isMoreSpecificThan($a) ? -1 : 1;
        });
        $this->rules = $rules;
    }

    public static function fromString($string)
    {
        $string = Comment::stripFromString($string);
        return new OrderedStyleSheet(Rule::fromString($string));
    }

    public function __toString()
    {
        $string = '';
        foreach ($this->rules as $rule) {
            $string .= $rule . "\n";
        }
        return $string;

    }

    public function merge(OrderedStyleSheet $other)
    {
        return new OrderedStyleSheet(
            array_merge($this->rules, $other->rules)
        );
    }
}