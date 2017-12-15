<?php
/**
 *  MIT License
 *
 * Copyright (c) 2017 Pentagonal Development
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Apatis\CallbackResolver;

/**
 * Class CallbackResolver
 * @package Apatis\CallbackResolver
 */
class CallbackResolver implements CallbackResolverInterface
{
    /**
     * @var mixed $binding Value for binding into callable
     *                     if callable is \Closure
     */
    protected $binding = false;

    /**
     * @type string regex
     * Regex for callable as "class:method" or "class->method"
     */
    const REGEX_CALLABLE_CLASS = <<<'REGEXP'
`^(
        [\\\]?                    # start with backslash
        [a-zA-Z_]                 # 2nd char with letter or underscore
        (?:
            (?:
                \\\?              # maybe start backslash name space separator
                (?:
                    [a-zA-Z0-9_]+ # valid class identifier
                )
            )*
            [a-zA-Z0-9_]+         # end with [a-zA-Z0-9_]
        )?
    )
    ([:]{1,2}|->)                      # method operator
    ([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*) # method
$`x
REGEXP;

    /**
     * CallbackResolver constructor.
     *
     * @param mixed|null|false $binding false to disable binding
     */
    public function __construct($binding = false)
    {
        $this->binding = $binding;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($toResolve) : callable
    {
        $resolved = $toResolve;
        if (is_string($toResolve)) {
            $class  = $toResolve;
            $method = '__invoke';
            // check for callable as "class:method", "class::method" or "class->method"
            if (preg_match(static::REGEX_CALLABLE_CLASS, $toResolve, $matches)) {
                $class     = $matches[1];
                $operator  = $matches[2];
                $method    = $matches[3];
                if (!class_exists($class)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Class %s does not exist',
                            $class
                        )
                    );
                }

                // resolve is method contains double colon (static method)
                if ($operator === '::') {
                    try {
                        $reflection = new \ReflectionMethod($class, $method);
                    } catch (\Throwable $e) {
                        throw new \RuntimeException(
                            sprintf(
                                'Object class %s does not have method %s',
                                $class,
                                $method
                            )
                        );
                    }

                    if ($reflection->isStatic()) {
                        return $resolved;
                    }
                }
            }

            $binding = $this->getBinding();
            $resolved = [new $class($binding), $method];
        } elseif (!is_callable($resolved)) {
            throw new \RuntimeException(
                sprintf(
                    '%s is not resolvable',
                    is_array($toResolve) || is_object($toResolve)
                        ? json_encode($toResolve)
                        : $toResolve
                )
            );
        } elseif ($resolved instanceof \Closure) {
            $binding = $this->getBinding();
            if ($binding === null || is_object($binding)) {
                // bind closure if null | object
                $resolved = $resolved->bindTo($binding);
            }
        }

        return $resolved;
    }

    /**
     * {@inheritdoc}
     */
    public function getBinding()
    {
        return $this->binding;
    }

    /**
     * {@inheritdoc}
     */
    public function setBinding($binding)
    {
        $this->binding = $binding;
    }
}
