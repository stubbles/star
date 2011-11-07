<?php
/**
 * Task to create star files.
 *
 * @author      Frank Kleine <mikey@stubbles.net>
 * @package     star
 * @subpackage  phing
 */
/**
 * Uses the Phing Task
 */
require_once 'phing/Task.php';
/**
 * Uses star writer classes
 */
require_once 'star/StarArchive.php';
/**
 * Uses star writer classes
 */
require_once 'star/StarFile.php';
/**
 * Uses star writer classes
 */
require_once 'star/StarCreator.php';
/**
 * Uses star decorator
 */
require_once dirname(__FILE__) . '/StarDecorator.php';
/**
 * Task to create star files.
 *
 * @package     star
 * @subpackage  phing
 */
class StarWriterTask extends Task
{
    /**
     * the title meta information
     *
     * @var  string
     */
    protected $title              = '';
    /**
     * version to create
     *
     * @var  string
     */
    protected $version            = '';
    /**
     * the package meta information
     *
     * @var  string
     */
    protected $package            = '';
    /**
     * the author meta information
     *
     * @var  string
     */
    protected $author             = '';
    /**
     * the copyright meta information
     *
     * @var  string
     */
    protected $copyright          = '';
    /**
     * the copyright meta information
     *
     * @var  string
     */
    protected $copyrightStartYear = 2007;
    /**
     * the preface to add to the star file
     *
     * @var  string
     */
    protected $preface            = null;
    /**
     * the version of the starfile to build
     *
     * @var  int
     */
    protected $starVersion        = 2;
    /**
     * the path to put the star archive into
     *
     * @var  string
     */
    protected $buildPath;
    /**
     * the base source path
     *
     * @var  string
     */
    protected $baseSrcPath;
    /**
     * the source files
     *
     * @var  FileSet
     */
    protected $filesets           = array();
    /**
     * list of decorators for single files
     *
     * @var  array<string,StarDecorator>
     */
    protected $decorators         = array();

    /**
     * sets the title
     *
     * @param  string  $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * sets the version number
     *
     * @param  string  $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * sets the package
     *
     * @param  string  $package
     */
    public function setPackage($package)
    {
        $this->package = $package;
    }

    /**
     * sets the author
     *
     * @param  string  $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * sets the copyright
     *
     * @param  string  $copyright
     */
    public function setCopyright($copyright)
    {
        $this->copyright = $copyright;
    }

    /**
     * sets the copyright start year
     *
     * @param  string  $copyrightStartYear
     */
    public function setCopyrightStartYear($copyrightStartYear)
    {
        $this->copyrightStartYear = $copyrightStartYear;
    }

    /**
     * sets the preface
     *
     * @param  string  $preface
     */
    public function setPreface($preface)
    {
        $this->preface = $preface;
    }

    /**
     * sets the star version number
     *
     * @param  string  $starVersion
     */
    public function setStarVersion($starVersion)
    {
        $this->starVersion = $starVersion;
    }

    /**
     * the the path to put the star archive into
     *
     * @param  string  $buildPath
     */
    public function setBuildPath($buildPath)
    {
        $this->buildPath = $buildPath;
    }

    /**
     * sets the base source path
     *
     * @param  string  $baseSrcPath
     */
    public function setBaseSrcPath($baseSrcPath)
    {
        $this->baseSrcPath = realpath($baseSrcPath);
    }

    /**
     * adds a decorator
     *
     * @param  StarDecorator  $decorator
     */
    public function addStarDecorator(StarDecorator $decorator)
    {
        $this->decorators[] = $decorator;
    }

    /**
     *  Nested creator, adds a set of files (nested fileset attribute).
     */
    public function createFileSet()
    {
        $num = array_push($this->filesets, new FileSet());
        return $this->filesets[$num - 1];
    }

    /**
     * The init method: Do init steps.
     */
    public function init()
    {
        // nothing to do here
    }

    /**
     * The main entry point method.
     */
    public function main()
    {
        // need to rewrap the decorator array because due to some unknown
        // reasons the properties of the decorator are not set when it is
        // added to the task

        // works now with refactored buildfile
        
        $decorators = array();
        foreach ($this->decorators as $decorator) {
            $decorators[$decorator->getStarId()] = $decorator;
        }
        
        
        $starArchive = new StarArchive(new StarCreator($this->buildPath), $this->starVersion);
        foreach($this->filesets as $fs) {
            try {
                $files    = $fs->getDirectoryScanner($this->project)->getIncludedFiles();
                $realPath = str_replace($this->baseSrcPath, '', realpath($fs->getDir($this->project)));
                if (DIRECTORY_SEPARATOR === $realPath{0}) {
                    $realPath = substr($realPath, 1);
                }
                
                foreach ($files as $file) {
                    if (strlen($realPath) > 0) {
                        $fullFileName = $this->baseSrcPath . DIRECTORY_SEPARATOR . $realPath . DIRECTORY_SEPARATOR . $file;
                        $file         = $realPath . DIRECTORY_SEPARATOR . $file;
                    } else {
                        $fullFileName = $this->baseSrcPath . DIRECTORY_SEPARATOR . $file;
                    }
                    
                    if (substr($file, 0, 3) == 'php') {
                        $srcDir = 'src' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR;
                        $pos    = strpos($fullFileName, $srcDir) + strlen($srcDir);
                        $id     = str_replace(DIRECTORY_SEPARATOR, '::', str_replace('.php', '', substr($fullFileName, $pos)));
                    } else {
                        $srcDir = 'src' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
                        $pos    = strpos($fullFileName, $srcDir) + strlen($srcDir);
                        $id     = str_replace(DIRECTORY_SEPARATOR, '/', substr($fullFileName, $pos));
                    }

                    $this->log('Adding ' . $fullFileName . ' as ' . $id);
                    if (isset($decorators[$id]) === false) {
                        $starFile = new StarFile($fullFileName, $this->baseSrcPath);
                    } else {
                        require_once $decorators[$id]->getFile();
                        $class = $decorators[$id]->getClass();
                        $starFile = new $class(new StarFile($fullFileName, $this->baseSrcPath));
                        $this->log('Decorating ' . $id . ' with ' . $class);
                    }

                    $starArchive->add($starFile, $id);
                }
            } catch (BuildException $be) {
                    // directory doesn't exist or is not readable
                    if ($this->failonerror) {
                        throw $be;
                    } else {
                        $this->log($be->getMessage(), $this->quiet ? PROJECT_MSG_VERBOSE : PROJECT_MSG_WARN);
                    }
                }
        }

        if (null != $this->preface) {
            $starArchive->setPreface($this->preface);
        }

        $starArchive->addMetaData('title', $this->title);
        $starArchive->addMetaData('package', $this->package);
        $starArchive->addMetaData('version', $this->version);
        $starArchive->addMetaData('author', $this->author);
        if (date('Y') == $this->copyrightStartYear) {
            $starArchive->addMetaData('copyright', '(c) ' . $this->copyrightStartYear . ' ' . $this->copyright);
        } else {
            $starArchive->addMetaData('copyright', '(c) ' . $this->copyrightStartYear . '-' . date('Y') . ' ' . $this->copyright);
        }
        
        $starArchive->create();
    }

}
?>