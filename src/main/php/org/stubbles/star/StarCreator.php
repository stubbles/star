<?php
/**
 * Class to write data into files.
 *
 * @package  star
 * @version  $Id$
 */
/**
 * Class to write data into files.
 *
 * @package  star
 */
if (class_exists('StarCreator') === false) {
    class StarCreator extends StarFile
    {
        /**
         * pointer to file to write
         *
         * @var  resource
         */
        protected $fp;
        
        /**
         * open the file
         * 
         * Warning: Opening existing files will truncate them!
         * 
         * @throws  StarException
         */
        public function open()
        {
            $fp = fopen($this->name, 'wb+');
            if (false === $fp) {
                throw new StarException('Could not open file ' . $this->name);
            }
            
            $this->fp = $fp;
        }
        
        /**
         * write data to star file
         *
         * @param   string  $data  data to write
         * @return  int     amount of written bytes
         * @throws  StarException
         */
        public function write($data)
        {
            if (false === ($result = fwrite($this->fp, $data))) {
                throw new StarException('Cannot write ' . strlen($data) . ' bytes to ' . $this->name);
            }
            
            return $result;
        }
        
        /**
         * close the file
         *
         * @return  bool
         * @throws  StarException
         */
        public function close()
        {
            if (false === fclose($this->fp)) {
                throw new StarException('Cannot close file ' . $this->name);
            }
          
            $this->fp = null;
            return true;
        }
    }
}
?>