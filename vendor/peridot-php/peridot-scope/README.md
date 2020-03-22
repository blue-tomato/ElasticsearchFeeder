Peridot Scope
=============

[![Build Status](https://travis-ci.org/peridot-php/peridot-scope.png)](https://travis-ci.org/peridot-php/peridot-scope) [![HHVM Status](http://hhvm.h4cc.de/badge/peridot-php/peridot-scope.svg)](http://hhvm.h4cc.de/package/peridot-php/peridot-scope)

Peridot [Scope](https://github.com/peridot-php/peridot/wiki/Scopes).

Scopes allow safe binding of state for closures and offers a mechanism
for mixing state and behavior in via [child scopes](https://github.com/peridot-php/peridot/wiki/Scopes#extending-functionality-with-scopes).

Extracted from the [Peridot](http://peridot-php.github.io/) testing framework.

##Usage

We recommend installing this package via composer:

```
$ composer require peridot-php/peridot-scope:~1.0
```

###Creating a Scope

```php
$scope = new Scope();
$scope->name = "Brian";

$fnWithName = function() {
    print $this->name;
};

$fnWithName = $scope->peridotBindTo($fnWithName);

$fnWithName(); //prints "Brian"
```

###Using the ScopeTrait

If an existing class can benefit from a Scope, you can use the `ScopeTrait`

```php
class Test
{
    use ScopeTrait;
    
    protected $definition;
    
    public function __construct(callable $definition)
    {
        $this->definition = $definition; 
    }
    
    /**
     * Return the definition bound to a scope
     */
    public function getDefinition()
    {
        $scope = $this->getScope();
        return $scope->peridotBindTo($this->definition);
    }
}
```

##Mixins

You can mix behavior in via [child scopes](https://github.com/peridot-php/peridot/wiki/Scopes#extending-functionality-with-scopes).
