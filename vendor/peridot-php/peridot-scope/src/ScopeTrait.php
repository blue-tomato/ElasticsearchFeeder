<?php
namespace Peridot\Scope;

trait ScopeTrait
{
    /**
     * @var Scope
     */
    protected $scope;

    /**
     * @return Scope
     */
    public function getScope()
    {
        if (is_null($this->scope)) {
            $this->scope = new Scope();
        }
        return $this->scope;
    }

    /**
     * @param Scope $scope
     * @return $this
     */
    public function setScope(Scope $scope)
    {
        $this->scope = $scope;
        return $this;
    }
} 
