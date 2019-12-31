<?php

/**
 * @see       https://github.com/laminas/laminas-filter for the canonical source repository
 * @copyright https://github.com/laminas/laminas-filter/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-filter/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Filter;

use Laminas\Stdlib\ArrayUtils;
use Traversable;

/**
 * @category   Laminas
 * @package    Laminas_Filter
 */
class StringTrim extends AbstractFilter
{
    /**
     * @var array
     */
    protected $options = array(
        'charlist' => null,
    );

    /**
     * Sets filter options
     *
     * @param  string|array|Traversable $charlistOrOptions
     */
    public function __construct($charlistOrOptions = null)
    {
        if ($charlistOrOptions !== null) {
            if (!is_array($charlistOrOptions)
                && !$charlistOrOptions  instanceof Traversable)
            {
                $this->setCharList($charlistOrOptions);
            } else {
                $this->setOptions($charlistOrOptions);
            }
        }
    }

    /**
     * Sets the charList option
     *
     * @param  string $charList
     * @return StringTrim Provides a fluent interface
     */
    public function setCharList($charList)
    {
        if (empty($charList)) {
            $charList = null;
        }
        $this->options['charlist'] = $charList;
        return $this;
    }

    /**
     * Returns the charList option
     *
     * @return string|null
     */
    public function getCharList()
    {
        return $this->options['charlist'];
    }

    /**
     * Defined by Laminas\Filter\FilterInterface
     *
     * Returns the string $value with characters stripped from the beginning and end
     *
     * @param  string $value
     * @return string
     */
    public function filter($value)
    {
        // Do not filter non-string values
        if (!is_string($value)) {
            return $value;
        }

        if (null === $this->options['charlist']) {
            return $this->unicodeTrim((string) $value);
        }

        return $this->unicodeTrim((string) $value, $this->options['charlist']);
    }

    /**
     * Unicode aware trim method
     * Fixes a PHP problem
     *
     * @param string $value
     * @param string $charlist
     * @return string
     */
    protected function unicodeTrim($value, $charlist = '\\\\s')
    {
        $chars = preg_replace(
            array('/[\^\-\]\\\]/S', '/\\\{4}/S', '/\//'),
            array('\\\\\\0', '\\', '\/'),
            $charlist
        );

        $pattern = '/^[' . $chars . ']+|[' . $chars . ']+$/usSD';

        return preg_replace($pattern, '', $value);
    }
}
