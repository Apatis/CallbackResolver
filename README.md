# Callback Resolver

Resolve callable value

```php
<?php
use \Apatis\CallbackResolver\CallbackResolver;

// use null to bind closure to null
$bind = null;
$resolveStaticMethod = true;
$resolver = new CallbackResolver($bind, $resolveStaticMethod);

$callable_1 = $resolver->resolve('Class:method');
// Class:method is equal with Class->method
// Method operator use 
$callable_2 = $resolver->resolve('Class->method');

$callable_closure = $resolver->resolve(function() {
   /**
    * @var null $this
    * even callable inside of Object
    * When binding set to null it will bind into null
    */
});
// set Bind to \stdClass
$resolver->setBinding(new stdClass());
$callable_closure_std_class = $resolver->resolve(function() {
   /**
    * @var \stdClass $this
    * after binding set into \stdClass
    * the variable $this will be accessible info \stdClass 
    */
});
```

## OPERATOR

```text
 - Use operator single `:` or `->` to determine that class need to be new instance
 - Use double `:` (eg : 'class::method') to determine that method is static 
        or will be convert like a (standard) `->` operator if method is non static
        and set as resolve static method
```
