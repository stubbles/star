<?php
/**
 * Class for reading data from star archives via stream wrapper.
 *
 * @package  star
 * @version  $Id$
 */
/**
 * Class for reading data from star archives via stream wrapper.
 *
 * This class contains code from lang.base.php of the XP-framework,
 * written by Timm Friebe and Alex Kiesel.
 *
 * @package  star
 * @see      http://php.net/stream_wrapper_register
 */
class StarStreamWrapper
{
    /**
     * switch whether class has already been registered as stream wrapper or not
     *
     * @var  bool
     */
    private static $registered = false;
    /**
     * current position in star archive
     *
     * @var  int
     */
    protected $position;
    /**
     * current star archive data
     *
     * @var  array
     */
    protected $archive;
    /**
     * id of the file entry to retrieve
     *
     * @var  string
     */
    protected $id;

    /**
     * registers the class as stream wrapper for the star protocol
     * 
     * @throws  StarException
     */
    public static function register()
    {
        if (true == self::$registered) {
            return;
        }
        
        if (stream_wrapper_register('star', __CLASS__) == false) {
            throw new StarException('A handler has already been registered for the star protocol.');
        }
        
        self::$registered = true;
    }

    /**
     * returns index of requested archive
     *
     * @param   string  $archive  archive to retrieve index for
     * @return  array
     */
    public static function acquire($archive)
    {
        static $archives = array();
        $archive = str_replace('\\', '/', $archive);
        if (isset($archives[$archive]) == true) {
            return $archives[$archive];
        }
        
        $archives[$archive] = array();
        if (file_exists($archive) == false) {
            return array();
        }
            
        $current           =& $archives[$archive];
        $current['handle'] = fopen($archive, 'rb');
        if (str_replace('\\', '/', __FILE__) == $archive && defined('__COMPILER_HALT_OFFSET__') == true) {
            fseek($current['handle'], __COMPILER_HALT_OFFSET__);
        } else {
            fseek($current['handle'], 0);
        }
        
        $header = unpack('a4id/c1version/a8indexsize/a14buildtime/a*reserved', fread($current['handle'], 0x0100));
        if (false === $header) {
            // invalid star file
            return array();
        }
        
        $current['index']  = array();
        $current['header'] = $header;
        if (str_replace('\\', '/', __FILE__) == $archive && defined('__COMPILER_HALT_OFFSET__') == true) {
            $current['header']['totalSize'] = __COMPILER_HALT_OFFSET__ + 0x0100;
        } else {
            $current['header']['totalSize'] = 0x0100;
        }
        
        if (1 === $header['version']) {
            $key = 'a80id/a72filename/a80path/a8size/a8offset/a*reserved';
        } else {
            $key = 'a232id/a8size/a8offset/a*reserved';
        }

        for ($i = 0; $i < $header['indexsize']; $i++) {
            $entry  = unpack($key, fread($current['handle'], 0x0100));
            $current['index'][$entry['id']]  = array('size' => (int) $entry['size'], 'offset' => (int) $entry['offset']);
            $current['header']['totalSize'] += 0x0100 + ((int) $entry['size']);
        }
        
        return $archives[$archive];
    }

    /**
     * returns the metadata of an archive
     *
     * @param   string  $archive  archive to retrieve metadata for
     * @return  array
     */
    public static function getMetaData($archive)
    {
        $current  = self::acquire($archive);
        if (isset($current['index']) == false) {
            throw new StarException('Star file ' . $archive . ' does not exist or is not a valid star file.');
        }
        
        $metaData = array();
        fseek($current['handle'], $current['header']['totalSize']);
        while (feof($current['handle']) == false) {
            $line = trim(fgets($current['handle'], 4096));
            if (empty($line) == true) {
                continue;
            }
            
            $lineData = explode(' => ', $line);
            $metaData[$lineData[0]] = $lineData[1];
        }
        
        return $metaData;
    }

    /**
     * open the stream
     *
     * @param   string  $path         the path to open
     * @param   string  $mode         mode for opening
     * @param   string  $options      options for opening
     * @param   string  $opened_path  full path that was actually opened
     * @return  bool
     */
    public function stream_open($path, $mode, $options, $opened_path)
    {
        $this->parsePath($path);
        $current = self::acquire($this->archive);
        if (isset($current['index'][$this->id]) == false) {
            $this->parsePath(urldecode($path));
            $current = self::acquire($this->archive);
            if (isset($current['index'][$this->id]) == false) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * read the stream up to $count bytes
     *
     * @param   int     $count  amount of bytes to read
     * @return  string
     */
    public function stream_read($count)
    {
        $current = self::acquire($this->archive);
        if (isset($current['index'][$this->id]) == false) {
            return false;
        }
        
        if ($current['index'][$this->id]['size'] == $this->position || 0 == $count) {
            return false;
        }

        fseek($current['handle'], 0x0100 + sizeof($current['index']) * 0x0100 + $current['index'][$this->id]['offset'] + $this->position, SEEK_SET);
        $bytes = fread($current['handle'], min($current['index'][$this->id]['size'] - $this->position, $count));
        $this->position += strlen($bytes);
        return $bytes;
    }

    /**
     * checks whether stream is at end of file
     *
     * @return  bool
     */
    public function stream_eof()
    {
        $current= self::acquire($this->archive);
        return $this->position >= $current['index'][$this->id]['size'];
    }

    /**
     * returns status of stream
     *
     * @return  array
     */
    public function stream_stat()
    {
        $current  = self::acquire($this->archive);
        $fileStat = array('dev'     => 0,
                          'ino'     => 0,
                          'mode'    => 010000 | 0777,
                          'nlink'   => 0,
                          'uid'     => 0,
                          'gid'     => 0,
                          'rdev'    => 0,
                          'size'    => $current['index'][$this->id]['size'],
                          'atime'   => time(),
                          'mtime'   => time(),
                          'ctime'   => time(),
                          'blksize' => -1,
                          'blocks'  => -1
                    );

        return array_merge(array_values($fileStat), $fileStat);
    }

    /**
     * returns status of url
     *
     * @param   string      $path  path of url to return status for
     * @return  array|bool  false if $path does not exist, else 
     */
    public function url_stat($path)
    {
        $this->parsePath($path);
        $current = self::acquire($this->archive);

        if (isset($current['index'][$this->id]) == false) {
            $this->parsePath(urldecode($path));
            $current = self::acquire($this->archive);
            if (isset($current['index'][$this->id]) == false) {
                return false;
            }
        }
        
        $fileStat = array('dev'     => 0,
                          'ino'     => 0,
                          'mode'    => 010000 | 0777,
                          'nlink'   => 0,
                          'uid'     => 0,
                          'gid'     => 0,
                          'rdev'    => 0,
                          'size'    => $current['index'][$this->id]['size'],
                          'atime'   => time(),
                          'mtime'   => time(),
                          'ctime'   => time(),
                          'blksize' => -1,
                          'blocks'  => -1
                    );

        return array_merge(array_values($fileStat), $fileStat);
    }

    /**
     * parses the path into class members
     *
     * @param  string  $path
     */
    protected function parsePath($path)
    {
        list($archive, $id) = sscanf($path, 'star://%[^?]?%[^$]');
        $this->archive      = $archive;
        $this->id           = $id;
    }
}
?>