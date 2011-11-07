<?php
/**
 * Class registry for mapping of classes to star files.
 *
 * @package  star
 * @version  $Id$
 */
/**
 * Class registry for mapping of classes to star files.
 *
 * @package  star
 */
if (class_exists('StarClassRegistry') === false) {
    class StarClassRegistry
    {
        /**
         * switch whether init has been done or not
         *
         * @var  bool
         */
        protected static $initDone  = false;
        /**
         * path to star files
         *
         * @var  string
         */
        protected static $libPathes = array();
        /**
         * list of classes and the file where they are in
         *
         * @var  array<string,string>
         */
        protected static $classes   = array();
        /**
         * list of files and the classes they contain
         *
         * @var  array<string,array<string>>
         */
        protected static $files     = array();
    
        /**
         * set the path to the star files
         *
         * @param  string  $libPath    path to lib files
         * @param  bool    $recursive  optional  recurse into sub directories of lib path
         */
        public static function addLibPath($libPath, $recursive = true)
        {
            self::$libPathes[$libPath] = $recursive;
            self::$initDone            = false;
        }
    
        /**
         * returns the file where the given classes is stored in
         *
         * @param   string  $fqClassName  the full qualified class name
         * @return  string
         */
        public static function getFileForClass($fqClassName)
        {
            if (false === self::$initDone) {
                self::init();
            }
            
            if (isset(self::$classes[$fqClassName]) === true) {
                return self::$classes[$fqClassName];
            }
    
            return null;
        }
    
        /**
         * returns the uri for the given class
         *
         * @param   string  $fqClassName  the full qualified class name
         * @return  string
         */
        public static function getUriForClass($fqClassName)
        {
            if (false === self::$initDone) {
                self::init();
            }
            
            if (isset(self::$classes[$fqClassName]) === true) {
                return 'star://' . self::$classes[$fqClassName] . '?' . $fqClassName;
            }
    
            return null;
        }
    
        /**
         * returns all uris for a given resource
         *
         * @param   string  $fileName  file name of resource
         * @return  array
         */
        public static function getUrisForResource($resource)
        {
            if (false === self::$initDone) {
                self::init();
            }
            
            $uris = array();
            foreach (self::$files as $file => $contents) {
                foreach ($contents as $content) {
                    if ($content === $resource) {
                        $uris[] = 'star://' . $file . '?' . $resource;
                        continue 2;
                    }
                }
            }
    
            return $uris;
        }
    
        /**
         * returns a list of all classes within given file
         *
         * @param   string  $file  name of file
         * @return  array
         */
        public static function getClassNamesFromFile($file)
        {
            if (false === self::$initDone) {
                self::init();
            }
            
            if (isset(self::$files[$file]) === true) {
                return self::$files[$file];
            }
    
            return array();
        }
    
        /**
         * returns a list of all classes
         *
         * @return  string
         */
        public static function getClasses()
        {
            return array_keys(self::$classes);
        }
    
        /**
         * initialize the class registry
         */
        protected static function init()
        {
            if (true === self::$initDone) {
                return;
            }
    
            if (count(self::$libPathes) == 0) {
                self::$libPathes[dirname(__FILE__)] = true;
            }
    
            foreach (self::$libPathes as $libPath => $recursive) {
                if (file_exists($libPath . '/.cache') === true) {
                    $cache = unserialize(file_get_contents($libPath . '/.cache'));
                    self::$files    = array_merge(self::$files, $cache['files']);
                    self::$classes  = array_merge(self::$classes, $cache['classes']);
                    self::$initDone = true;
                    continue;
                }
    
                if (true === $recursive) {
                    $dirIt = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($libPath));
                } else {
                    $dirIt = new DirectoryIterator($libPath);
                }
    
                $cache['files']   = array();
                $cache['classes'] = array();
                foreach ($dirIt as $file) {
                    if ($file->isFile() === false || substr($file->getPathname(), -14) === 'starReader.php' || (substr($file->getPathname(), -5) !== '.star' && substr($file->getPathname(), -4) !== '.php')) {
                        continue;
                    }
    
                    $archiveData = StarStreamWrapper::acquire($file->getPathname());
                    if (empty($archiveData) == true) {
                        continue;
                    }
    
                    $classes = array_keys($archiveData['index']);
                    self::$files[$file->getPathname()]    = $classes;
                    $cache['files'][$file->getPathname()] = $classes;
    
                    foreach (array_keys($archiveData['index']) as $fqClassName) {
                        self::$classes[$fqClassName]    = $file->getPathname();
                        $cache['classes'][$fqClassName] = $file->getPathname();
                    }
                }
    
                $cacheFile = $libPath . '/.cache';
                if (is_writable($libPath) === false && is_writable($cacheFile) === false) {
                    throw new StarException("Unable to write starRegistry cache file to {$cacheFile}.");
                }
                
                file_put_contents($cacheFile, serialize($cache));
                self::$initDone = true;
            }
        }
    }
}
?>