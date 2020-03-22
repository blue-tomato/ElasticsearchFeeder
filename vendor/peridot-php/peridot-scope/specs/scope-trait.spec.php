<?php
use Peridot\Scope\Scope;
use Peridot\Scope\ScopeTrait;

describe('ScopeTrait', function() {

    beforeEach(function() {
        $this->scoped = new TestHasScope();
    });

    describe('->getScope()', function() {
        context('when scope is not set', function() {
            it('should return a scope', function() {
                $scope = $this->scoped->getScope();
                assert($scope instanceof Scope, "scope should have been returned");
            });

            it('should set scope before returning it', function() {
                $scope = $this->scoped->getScope();
                $again = $this->scoped->getScope();
                assert($scope === $again, "should return set scope");
            });
        });
    });

    describe('->setScope()', function() {
        it('should set scope', function() {
            $scope = new Scope();
            $this->scoped->setScope($scope);
            $again = $this->scoped->getScope();
            assert($scope === $again, "should have set scope");
        });
    });

});

class TestHasScope {
    use ScopeTrait;
}
