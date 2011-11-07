<?php
/**
 * Tests for org::stubbles::star::StarFile.
 *
 * @author      Frank Kleine <mikey@stubbles.net>
 * @package     star
 * @subpackage  test
 */
require_once MAIN_SRC_PATH . '/org/stubbles/star/StarFile.php';
/**
 * Tests for norg::stubbles::star::StarFile.
 *
 * @package     star
 * @subpackage  test
 */
class StarFileTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * instance to be used for tests
     *
     * @var  StarFile
     */
    protected $starFile;
    /**
     * the filename
     *
     * @var  string
     */
    protected $filename;
    /**
     * the directory name
     * 
     * @var  string
     */
    protected $dirname;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->dirname  = dirname(__FILE__);
        $this->filename = $this->dirname . DIRECTORY_SEPARATOR . 'foo' . DIRECTORY_SEPARATOR . 'bar.baz';
        $this->starFile = new StarFile($this->filename, $this->dirname);
    }

    /**
     * assure that values are returned as expected
     *
     * @test
     */
    public function values()
    {
        $this->assertEquals($this->filename, $this->starFile->getName());
        $this->assertEquals('bar.baz', $this->starFile->getBaseName());
        $this->assertEquals('baz', $this->starFile->getExtension());
        $this->assertEquals($this->dirname . DIRECTORY_SEPARATOR . 'foo', $this->starFile->getPath());
        $this->assertEquals('foo', $this->starFile->getPathWithBaseRemoved());
    }

    /**
     * assure that the setExtension() method works correct
     *
     * @test
     */
    public function setExtension()
    {
        $this->starFile->setExtension('baz');
        $this->assertEquals('baz', $this->starFile->getExtension());
        $this->assertEquals($this->filename, $this->starFile->getName());
        $this->assertEquals('foo', $this->starFile->getPathWithBaseRemoved());
        
        $this->starFile->setExtension('foo');
        $this->assertEquals('foo', $this->starFile->getExtension());
        $this->assertEquals(substr($this->filename, 0, -3) . 'foo', $this->starFile->getName());
        $this->assertEquals('foo', $this->starFile->getPathWithBaseRemoved());
        
        $this->starFile->setExtension('bar');
        $this->assertEquals('bar', $this->starFile->getExtension());
        $this->assertEquals(substr($this->filename, 0, -3) . 'bar', $this->starFile->getName());
        $this->assertEquals('foo', $this->starFile->getPathWithBaseRemoved());
        
        $this->starFile->setExtension('baz');
        $this->assertEquals('baz', $this->starFile->getExtension());
        $this->assertEquals($this->filename, $this->starFile->getName());
        $this->assertEquals('foo', $this->starFile->getPathWithBaseRemoved());
    }
}
?>