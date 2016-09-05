<?php
namespace Kahlan;

use Exception;
use Kahlan\Plugin\Stub\Fct;
use Kahlan\Plugin\Stub;
use Kahlan\Plugin\Monkey;

class Allow
{
    /**
     * A fully-namespaced function name.
     *
     * @var string|object
     */
    protected $_actual = null;

    /**
     * The stub.
     *
     * @var string|object
     */
    protected $_stub = null;

    /**
     * The method instance.
     *
     * @var object
     */
    protected $_method = null;

    /**
     * Boolean indicating if actual is a class or not.
     *
     * @var boolean
     */
    protected $_isClass = false;

    /**
     * Constructor
     *
     * @param string|object $actual   A fully-namespaced class name or an object instance.
     * @param string        $expected The expected method method name to be called.
     */
    public function __construct($actual)
    {
        if (is_string($actual)) {
            $actual = ltrim($actual, '\\');
        }

        if (!is_string($actual) || class_exists($actual)) {
            $this->_isClass = true;
            $this->_stub = Stub::on($actual);
        }
        $this->_actual = $actual;
    }

    /**
     * Stub a chain of methods.
     *
     * @param  string $expected the method to be stubbed or a chain of methods.
     * @return        self.
     */
    public function toReceive($expected)
    {
        if (!$this->_isClass) {
            throw new Exception("Method stubbing are only available on instances ot classes.");
        }
        return $this->_method = $this->_stub->method($expected);
    }

    /**
     * Stub function.
     *
     * @return        self.
     */
    public function toBeCalled()
    {
        if ($this->_isClass) {
            throw new Exception("Function stubbing are only available for functions.");
        }
        return new Fct(['name' => $this->_actual]);
    }

    /**
     * Sets the stub logic.
     *
     * @param mixed $substitute The logic.
     */
    public function toBe($substitute)
    {
        Monkey::patch($this->_actual, $substitute);
    }

    /**
     * Sets the stub logic.
     *
     * @param mixed $substitute The logic.
     */
    public function toWork()
    {
        if ($this->_isClass) {
            Monkey::patch($this->_actual, Stub::classname());
        } else {
            Monkey::patch($this->_actual, function(){});
        }
    }

    /**
     * Set. return values.
     *
     * @param mixed ... <0,n> Return value(s).
     */
    public function andReturn()
    {
        throw new Exception("You must to use `toReceive()/toBeCalled()` before defining a return value.");
    }
}
