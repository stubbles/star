<?php
/**
 * Class for very simple file handling.
 *
 * @package  star
 * @version  $Id$
 */
/**
 * Class for very simple file handling.
 *
 * @package  star
 */
if (class_exists('StarFile') === false) {
    class StarFile
    {
        /**
         * name of the file
         *
         * @var  string
         */
        protected $name;
        /**
         * the path that gets removed if file is added to a star archive
         *
         * @var  string
         * @see  getPathWithBaseRemoved()
         */
        protected $removePath = null;
        
        /**
         * constructor
         *
         * @param  string  $name        name of the file
         * @param  string  $removePath  optional  part of the directory name that should be removed
         */
        public function __construct($name, $removePath = null)
        {
            $this->name       = $name;
            if (null !== $removePath) {
                $this->removePath = realpath($removePath) . DIRECTORY_SEPARATOR;
            }
        }
        
        /**
         * returns the name of the file
         *
         * @return  string
         */
        public function getName()
        {
            return $this->name;
        }
        
        /**
         * returns the basename of the file
         *
         * @return  string
         */
        public function getBaseName()
        {
            return basename($this->name);
        }
        
        /**
         * get the extension of the file
         *
         * @return  string
         */
        public function getExtension()
        {
            $pathinfo = pathinfo($this->name);
            if (isset($pathinfo['extension']) == true) {
                return $pathinfo['extension'];
            }
            
            return null;
        }
        
        /**
         * set the extension
         *
         * @param  string  $extension
         */
        public function setExtension($extension)
        {
            $pathinfo   = pathinfo($this->name);
            $pathinfo['basename'] = substr($pathinfo['basename'], 0, ((strlen($pathinfo['extension']) + 1) * -1));
            $this->name = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['basename'] . '.' . $extension;
        }
        
        /**
         * returns the path of the file
         *
         * @return  string
         */
        public function getPath()
        {
            return dirname($this->name);
        }
        
        /**
         * returns the path of the file but with the base removed
         *
         * @var  string
         */
        public function getPathWithBaseRemoved()
        {
            if (null !== $this->removePath) {
                return str_replace(DIRECTORY_SEPARATOR, '/', str_replace($this->removePath, '', $this->getPath()));
            }
            
            return $this->getPath();
        }
    
        /**
         * returns the contents of the file
         *
         * @return  string
         */
        public function getContents()
        {
            if (file_exists($this->name) == true) {
                return file_get_contents($this->name);
            }
            
            return '';
        }
    }
}
?>