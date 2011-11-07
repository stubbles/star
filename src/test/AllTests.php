<?php
/**
 * Test suite for all classes of org::stubbles::star.
 *
 * @author      Frank Kleine <mikey@stubbles.net>
 * @package     stubbles
 * @subpackage  test
 */
if (defined('PHPUnit_MAIN_METHOD') === false) {
    define('PHPUnit_MAIN_METHOD', 'src_test_AllTests::main');
}

define('MAIN_SRC_PATH', realpath(dirname(__FILE__) . '/../main/php'));
define('TEST_SRC_PATH', dirname(__FILE__));
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
/**
 * Test suite for all classes of org::stubbles::star.
 *
 * @package     stubbles
 * @subpackage  test
 */
class src_test_AllTests extends PHPUnit_Framework_TestSuite
{
    /**
     * runs this test suite
     */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    /**
     * returns the test suite to be run
     *
     * @return  PHPUnit_Framework_TestSuite
     */
    public static function suite()
    {
        $suite = new self();
        $suite->addTestFile(dirname(__FILE__) . '/php/org/stubbles/star/StarFileTestCase.php');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD === 'src_test_AllTests::main') {
    src_test_AllTests::main();
}
?>