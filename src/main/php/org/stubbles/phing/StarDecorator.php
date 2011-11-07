<?php
/**
 * This type represents a list of decorators for star files.
 * 
 * @author      Frank Kleine
 * @package     star
 * @subpackage  phing
 */
require_once 'phing/types/DataType.php';
/**
 * This type represents a list of decorators for star files.
 * 
 * @package     star
 * @subpackage  phing
 */
class StarDecorator extends DataType
{
    /**
     * id of the file to decorate
     *
     * @var  string
     */
    protected $starId;
    /**
     * directory where decorator class file exists
     *
     * @var  string
     */
    protected $dir;
    /**
     * name of the decorator class
     *
     * @var  string
     */
    protected $class;

    /**
     * sets the id
     *
     * @param  string  $starId
     */
    public function setStarId($starId)
    {
        $this->starId = $starId;
    }

    /**
     * returns the id
     *
     * @return  string
     */
    public function getStarId()
    {
        return $this->starId;
    }

    /**
     * sets the file
     *
     * @param  string  $dir
     */
    public function setDir($dir)
    {
        $this->dir = $dir;
    }

    /**
     * returns the file
     *
     * @return  string
     */
    public function getFile()
    {
        return $this->dir . DIRECTORY_SEPARATOR . $this->class . '.php';
    }

    /**
     * sets the class of the decorator to use
     *
     * @param  string  $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * returns the class of the decorator to use
     *
     * @return  string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Ensures that there are no circular references and that the reference is 
     * of the correct type.
     *
     * @return  StarDecorator
     */
    public function getRef(Project $p)
    {
        if (false == $this->checked) {
            $stk = array();
            array_push($stk, $this);
            $this->dieOnCircularReference($stk, $p);
        }
        
        $o = $this->ref->getReferencedObject($p);
        if (($o instanceof self) === false) {
            throw new BuildException($this->ref->getRefId() . ' doesn\'t denote a StarDecorator');
        }
        
        return $o;
    }
}
?>