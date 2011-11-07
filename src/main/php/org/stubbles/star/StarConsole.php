<?php
/**
 * Class for creating star archives via console.
 *
 * @package  star
 * @version  $Id$
 */
/**
 * used to read command line arguments
 *
 * @see  http://pear.php.net/package/Console_Getargs/
 */
require_once 'Console/Getargs.php';
/**
 * Class for creating star archives via console.
 *
 * @package  star
 */
if (class_exists('StarConsole') === false) {
    class StarConsole
    {
        /**
         * configuration for Console_Getargs
         *
         * @var  array
         */
        private static $config = array('target'     => array('short'   => 't',
                                                             'min'     => 0,
                                                             'max'     => 1,
                                                             'desc'    => 'Name of the archive to create.',
                                                             'default' => ''
                                                       ),
                                       'ini'        => array('short'   => 'i',
                                                             'min'     => 0,
                                                             'max'     => 1,
                                                             'desc'    => 'Name of the ini file to use.',
                                                             'default' => ''
                                                       ),
                                       'removePath' => array('short'   => 'r',
                                                             'min'     => 0,
                                                             'max'     => 1,
                                                             'desc'    => 'Path to remove from logfiles.',
                                                             'default' => ''
                                                       ),
                                       'star'       => array('short'   => 's',
                                                             'min'     => 0,
                                                             'max'     => 1,
                                                             'desc'    => 'Star version to use: 1 or 2.',
                                                             'default' => ''
                                                       ),
                                       'verbose'    => array('short'   => 'v',
                                                             'min'     => 0,
                                                             'max'     => 0,
                                                             'desc'    => 'Be verbose.'
                                                       )
                                  );
        /**
         * access to arguments
         *
         * @var  Console_Getargs
         */
        private $args;
        /**
         * ini configuration file to use
         *
         * @var  string
         */
        private $iniFile;
        /**
         * target to compile to
         *
         * @var  string
         */
        private $target;
        /**
         * star version to use
         *
         * @var  int
         */
        private $starVersion = 2;
        
        /**
         * constructor
         *
         * @throws  Exception
         */
        private function __construct()
        {
            $args = Console_Getargs::factory(self::$config);
    
            if (PEAR::isError($args)) {
                throw new StarException($args->getMessage(), $args->getCode());
            }
            
            $this->args = $args;
        }
        
        /**
         * print out a help screen
         */
        private static function help(StarException $sce)
        {
            $header = "star stubbles archive creator 1.0\n".
                      'Usage: ' . basename($_SERVER['SCRIPT_NAME']) . " [options]\n\n";
            if ($sce->getCode() === CONSOLE_GETARGS_ERROR_USER) {
                echo Console_Getargs::getHelp(self::$config, $header, $sce->getMessage())."\n";
            } else if ($sce->getCode() === CONSOLE_GETARGS_HELP) {
                echo Console_Getargs::getHelp(self::$config, $header)."\n";
            }
        }
        
        /**
         * the main method
         */
        public static function main()
        {
            $startTime = microtime(true);
            try {
                $starConsole = new self();
            } catch (StarException $sce) {
                self::help($sce);
                exit(1);
            }
            
            try {
                $starConsole->readArgs();
            } catch (StarException $sce) {
                self::help($sce);
                exit(1);
            }
            
            $starConsole->process();
            $endTime = microtime(true);
            echo 'Done in ' . ($endTime - $startTime) . " seconds\n";
        }
        
        /**
         * read arguments from command line
         */
        private function readArgs()
        {
            if ($this->args->isDefined('ini') == false) {
                if (file_exists('./compile.ini') == false) {
                    throw new StarException('Can not compile: no ini set and no default compile.ini found.', CONSOLE_GETARGS_ERROR_USER);
                }
                
                $this->iniFile = './compile.ini';
            } else {
                if (file_exists($this->args->getValue('ini')) == false) {
                    throw new StarException('Can not compile: ' . $this->args->getValue('ini') . ' not found.', CONSOLE_GETARGS_ERROR_USER);
                }
                
                $this->iniFile = $this->args->getValue('ini');
            }
            
            if ($this->args->isDefined('target') == true) {
                $this->target = $this->args->getValue('target');
            }
            
            if ($this->args->isDefined('star') == true) {
                $this->starVersion = $this->args->getValue('star');
            }
        }
        
        /**
         * do the real action: execute phing for each day and shop
         */
        private function process()
        {
            $this->verbose('Reading ini file ' . $this->iniFile . "\n");
            $conf = parse_ini_file($this->iniFile, true);
            if (isset($conf['MAIN']) == false || (isset($conf['MAIN']['target']) == false && null == $this->target)) {
                echo 'No target found in ' . $this->iniFile . ". Please enter a target via option -t.\n";
                exit(1);
            }
            if (isset($conf['INCLUDES']) == false) {
                echo 'No includes found in ' . $this->iniFile . ".\n";
                exit(1);
            }
            
            $target = ((null == $this->target) ? ($conf['MAIN']['target']) : ($this->target));
            $this->verbose('Writing star data to ' . $target . "\n");
            $starArchive = new StarArchive(new StarCreator($target), $this->starVersion);
            $removePath  = null;
            if ($this->args->isDefined('removePath') == true) {
                $removePath = $this->args->getValue('removePath');
            }
            foreach ($conf['INCLUDES'] as $id => $fileName) {
                $this->verbose('Include ' . $fileName . ' with id ' . $id . "\n");
                $starArchive->add(new StarFile($fileName, $removePath), $id);
            }
            
            if (isset($conf['PREFACE']) == true && is_array($conf['PREFACE']) == true) {
                $prefaceContents = '';
                foreach ($conf['PREFACE'] as $preface) {
                    if (file_exists($preface) == false) {
                        echo 'Preface file ' . $preface . " does not exist.\n";
                    }
                    
                    $this->verbose('Preface ' . $preface . "\n");
                    $prefaceContents .= file_get_contents($preface);
                }
                
                $starArchive->setPreface($prefaceContents);
            }
            
            if (isset($conf['META-INF']) == true && is_array($conf['META-INF']) == true) {
                foreach ($conf['META-INF'] as $name => $value) {
                    $starArchive->addMetaData($name, $value);
                }
            }
            
            $this->verbose("Creating star\n");
            $starArchive->create();
        }
        
        /**
         * display message to default output
         *
         * @param unknown_type $message
         */
        private function verbose($message)
        {
            if ($this->args->isDefined('verbose') == true) {
                echo $message;
            }
        }
    }
}
?>