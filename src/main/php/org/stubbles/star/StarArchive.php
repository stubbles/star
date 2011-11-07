<?php
/**
 * Class for creating star archives.
 *
 * @package  star
 * @version  $Id$
 */
/**
 * Class for creating star archives.
 *
 * This class contains code from skeleton/lang/archive/Archive.class.php
 * of the XP-framework, written by Timm Friebe and Alex Kiesel.
 *
 * @package  star
 */
if (class_exists('StarArchive') === false) {
    class StarArchive
    {
        /**
         * the file to write star data into
         *
         * @var  StarCreator
         */
        protected $writer;
        /**
         * preface for star archive
         *
         * @var  string
         */
        protected $preface;
        /**
         * switch whether to prepend the StarStreamWrapper class in preface or not
         *
         * @var  bool
         */
        protected $prependStreamWrapper = true;
        /**
         * the index of files to put into the star
         *
         * @var  array
         */
        protected $index                = array();
        /**
         * metadata of star archive: could be version, packages, etc.
         *
         * @var  array<string,string>
         */
        protected $metadata             = array();
        /**
         * star archive version to create
         *
         * @var  int
         */
        protected $version                  = 2;
        
        /**
         * constructor
         *
         * @param  StarCreator  $writer  writer to use
         */
        public function __construct(StarCreator $writer, $version = 2)
        {
            $this->writer = $writer;
            $this->writer->setExtension('star');
            $this->version = $version;
        }
        
        /**
         * add a file to the star archive
         *
         * @param  StarFile  $file  the file to add
         * @param  string    $id    id for the file
         */
        public function add(StarFile $file, $id)
        {
            $data             = $file->getContents();
            $this->index[$id] = array('basename' => $file->getBaseName(),
                                      'path'     => $file->getPathWithBaseRemoved(),
                                      'datasize' => strlen($data),
                                      'offset'   => -1,
                                      'payload'  => $data
                                );
        }
        
        /**
         * adds meta data to the star archive
         *
         * @param  string  $name
         * @param  string  $value
         */
        public function addMetaData($name, $value)
        {
            $this->metadata[$name] = $value;
        }
        
        /**
         * set the preface
         *
         * @param  string  $preface
         * @param  bool    $prependStreamWrapper  optional
         */
        public function setPreface($preface, $prependStreamWrapper = true)
        {
            $this->preface = $preface;
            if (strlen($this->preface) > 0 && $this->writer->getExtension() != 'php') {
                $this->writer->setExtension('php');
            } elseif (strlen($this->preface) == 0 && $this->writer->getExtension() != 'star') {
                $this->writer->setExtension('star');
            }
            
            $this->prependStreamWrapper = $prependStreamWrapper;
        }
        
        /**
         * creates the star archive
         *
         * @param   bool  $selfRunning  optional  set to true if file should be self-running
         * @throws  StarException
         */
        public function create()
        {
            $this->writer->open();
            if (strlen($this->preface) > 0) {
                $preFace = '';
                if (true === $this->prependStreamWrapper) {
                    $dirname = str_replace('star://', '', dirname(__FILE__));
                    if (file_exists($dirname . '/starReader.php') === true) {
                        $preFace .= file_get_contents($dirname . '/starReader.php');
                    } else {
                        $preFace .= file_get_contents($dirname . '/StarStreamWrapper.php');
                        $preFace .= file_get_contents($dirname . '/StarException.php');
                        $preFace .= file_get_contents($dirname . '/StarClassRegistry.php');
                        $preFace .= "<?php StarStreamWrapper::register(); ?>";
                    }
                }
                $preFace .= trim($this->preface) . "<?php __halt_compiler();";
                $this->writer->write($preFace);
                $offset = strlen($preFace);
            } else {
                $offset = 0;
            }
            
            $ids = array_keys($this->index);
            $this->writer->write(pack('a4c1a8a14a229',
                                      'star',
                                      $this->version,
                                      (string) count($ids),
                                      date('YmdHis'),
                                      "\0"
                                 )
            );
            
            // write index
            foreach ($ids as $id) {
                $this->writer->write($this->getHeader($id, $offset));
                $offset += $this->index[$id]['datasize'];
            }
            
            // write index
            foreach ($ids as $id) {
                $this->writer->write($this->index[$id]['payload']);
            }
            
            if (count($this->metadata) > 0) {
                $this->writer->write("\n");
                foreach ($this->metadata as $name => $value) {
                    $this->writer->write($name . ' => ' . $value . "\n");
                }
            }
            
            $this->writer->close();
        }
    
        /**
         * returns the header of entries depending on requested version
         *
         * @param   string  $id      id of entry to create header data for
         * @param   int     $offset  offset where entry is located in file
         * @return  string
         */
        protected function getHeader($id, $offset)
        {
            switch ($this->version) {
                case 1:
                    $method = 'getHeaderForVersion1';
                    break;
                
                case 2:
                default:
                    $method = 'getHeaderForVersion2';
            }
            
            return $this->$method($id, $offset);
        }
    
        /**
         * returns the header of entries for star files version 1
         *
         * @param   string  $id      id of entry to create header data for
         * @param   int     $offset  offset where entry is located in file
         * @return  string
         */
        protected function getHeaderForVersion1($id, $offset)
        {
            return pack('a80a72a80a8a8a8', $id,
                                           $this->index[$id]['basename'],
                                           $this->index[$id]['path'],
                                           (string) $this->index[$id]['datasize'],
                                           (string) $offset,
                                           "\0"
                   );
        }
    
        /**
         * returns the header of entries for star files version 2
         *
         * @param   string  $id      id of entry to create header data for
         * @param   int     $offset  offset where entry is located in file
         * @return  string
         */
        protected function getHeaderForVersion2($id, $offset)
        {
            return pack('a232a8a8a8', $id,
                                      (string) $this->index[$id]['datasize'],
                                      (string) $offset,
                                      "\0"
                   );
        }
    }
}
?>