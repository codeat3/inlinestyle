<?php
namespace InlineStyle\Html;

use InlineStyle\Css\Declarations;
use InlineStyle\Css\OrderedStyleSheet;
use InlineStyle\Css\Rule;
use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\CssSelector\Exception\ParseException;

/**
 * ApplyStyleSheet
 * @author Christiaan Baartse <anotherhero@gmail.com>
 */
final class ApplyStyleSheets implements Transform
{
    /**
     * @var OrderedStyleSheet
     */
    private $styleSheets;

    public function __construct($styleSheets)
    {
        $this->styleSheets = array();
        foreach ($styleSheets as $styleSheet) {
            $this->addStyleSheet($styleSheet);
        }

    }

    private function addStyleSheet(OrderedStyleSheet $styleSheet)
    {
        $this->styleSheets[] = $styleSheet;
    }

    /**
     * @param string $html
     * @return string New HTML for the document
     */
    public function transformDocument($html)
    {
        $dom = new \DOMDocument();
        $dom->formatOutput = true;

        // strip illegal XML UTF-8 chars
        // remove all control characters except CR, LF and tab
        $html = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $html); // 00-09, 11-31, 127
        $dom->loadHTML($html);

        $this->saveOriginalStyles($dom);

        $this->applyAllStyleSheets($dom);

        $this->restoreOriginalStyles($dom);

        return $dom->saveHTML();
    }

    /**
     * @param \DOMDocument $dom
     */
    private function saveOriginalStyles(\DOMDocument $dom)
    {
        foreach ($this->find($dom, '[style]') as $node) {
            $node->setAttribute(
                'inlinestyle-original-style',
                $node->getAttribute('style')
            );
        }
    }

    /**
     * @param \DOMDocument $dom
     */
    private function restoreOriginalStyles(\DOMDocument $dom)
    {
        foreach ($this->find($dom, '[inlinestyle-original-style]') as $node) {
            $current = $this->getDeclarationsFromAttribute($node, 'style');
            $original = $this->getDeclarationsFromAttribute(
                $node,
                'inlinestyle-original-style'
            );

            $current = $original->merge($current);

            $node->setAttribute("style", (string) $current);
            $node->removeAttribute('inlinestyle-original-style');
        }
    }

    /**
     * @param \DOMDocument $dom
     */
    private function applyAllStyleSheets(\DOMDocument $dom)
    {
        foreach ($this->styleSheets as $styleSheet) {
            $this->applyStyleSheet($styleSheet, $dom);
        }
    }

    /**
     * @param OrderedStyleSheet $styleSheet
     * @param \DOMDocument $document
     */
    private function applyStyleSheet(OrderedStyleSheet $styleSheet, \DOMDocument $document)
    {
        foreach ($styleSheet->getRules() as $rule) {
            $this->applyRule($rule, $document);
        }
    }

    /**
     * @param Rule $rule
     * @param \DOMDocument $document
     */
    private function applyRule(Rule $rule, \DOMDocument $document)
    {
        $nodes = $this->find($document, $rule->getSelector());
        foreach ($nodes as $node) {
            $current = $this->getDeclarationsFromAttribute($node, 'style');

            $node->setAttribute(
                'style',
                $current->merge($rule->getDeclarations())
            );
        }
    }

    /**
     * @param \DOMDocument $document
     * @param string $sel Css Selector
     * @return array|\DOMElement[]|\DOMNodeList
     */
    private function find(\DOMDocument $document, $sel)
    {
        try {
            $xpathQuery = CssSelector::toXPath($sel);
            $xpath = new \DOMXPath($document);
            return $xpath->query($xpathQuery);
        }
        catch(ParseException $e) {
            // ignore css rule parse exceptions
        }

        return array();
    }

    private function getDeclarationsFromAttribute(\DOMElement $node, $attr)
    {
        return $node->hasAttribute($attr) ?
            Declarations::fromString($node->getAttribute($attr)) :
            Declarations::fromString('');
    }
}