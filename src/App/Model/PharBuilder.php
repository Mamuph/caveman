<?php


class Model_PharBuilder
{

    const PHP_PHARCMD_BANG = "#!/usr/bin/env php \n";



    /**
     * Source directory
     *
     * @var string
     */
    protected $source_dir;


    /**
     * Phar options
     *
     * @var array
     */
    protected $options = [
        'signature'         => false,
        'private_key'       => false,
        'password_key'      => false,
        'executable'        => false,
        'compress'          => false,
    ];


    /**
     * Model_PharBuilder constructor.
     *
     * @param string    $source_dir
     * @throws Exception
     */
    public function __construct($source_dir)
    {
        $this->source_dir = $source_dir;

        if (!Phar::canWrite())
            throw new Exception('Unable to write phar file');

    }


    /**
     * Factory method
     *
     * @param string    $source_dir
     * @return static
     */
    public static function factory($source_dir)
    {
        return new static($source_dir);
    }


    /**
     * Set many options
     *
     * @param array $options
     * @return $this
     * @throws Exception
     */
    public function sets(array $options)
    {
        foreach ($options as $k => $value)
            $this->set($k, $value);

        return $this;
    }


    /**
     * Set a phar option
     *
     * @param string    $option
     * @param string    $value
     * @return $this
     * @throws Exception
     *
     */
    public function set($option, $value)
    {
        if (!isset($this->options[$option]))
            throw new Exception("Option not available");

        $this->options[$option] = $value;

        return $this;
    }


    /**
     * Build the PHAR
     *
     * @param $file
     * @return bool|string
     */
    public function build($file)
    {

        if (File::exists($file, File::SCOPE_EXTERNAL))
            unlink($file);

        $filesystem_mask = FilesystemIterator::UNIX_PATHS | FilesystemIterator::SKIP_DOTS;

        // The header is taken before instantiate the Phar object.
        // It is due because as soon that Phar is instantiated the context scope changes and it will not allow
        // to load internal files.
        $default_stub = $this->getPharHeader();

        // Add .phar extension to filename
        $phar_filename = strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'phar' ? $file : $file . '.phar';

        try
        {
            $phar = new Phar($phar_filename, $filesystem_mask, APPID . '.phar');
        }
        catch (UnexpectedValueException $e) {
            return $e->getMessage();
        }
        catch (BadMethodCallException $e)
        {
            return $e->getMessage();
        }

        // Set signature
        if ($this->options['signature'])
            $phar->setSignatureAlgorithm($this->options['signature'], $this->options['private_key'] ? $this->options['private_key'] : null);

        // Add files
        try
        {
            $phar->buildFromDirectory($this->source_dir);
        }
        catch (PharException $e)
        {
            return $e->getMessage();
        }

        /*
        $default_stub = $phar->createDefaultStub('index.php');
        $default_stub = '<?php define("APPID", "' . APPID . '")?>' . $default_stub;
        */

        // Compress
        if ($this->options['compress'])
            $phar->compressFiles($this->options['compress']);


        // Provide environment when executable
        if ($this->options['executable'] === true)
        {
            $default_stub = static::PHP_PHARCMD_BANG . $default_stub;
            $phar->setStub($default_stub);
        }
        else
            $phar->setStub($default_stub);


        // Write changes on disk
        $phar->stopBuffering();

        // Rename file
        if ($phar_filename !== $file)
            rename($phar_filename, $file);

        if ($this->options['executable'] === true)
            chmod($file, 0775);

        return true;

    }


    /**
     * Copy and process PHAR stub.
     *
     * @return string
     */
    protected function getPharHeader()
    {

        // Read default header file
        $header = file_get_contents(RESOURCESPATH . 'pharheader.php.stub');

        // Replace AppID symbol
        $header = str_replace('#{{APPID}}', APPID, $header);

        // Replace required extensions symbols
        $req = empty($GLOBALS['igloo']->dependencies->extensions) ? [] : $GLOBALS['igloo']->dependencies->extensions;
        $header = str_replace('#{{REQ_EXTENSIONS}}', var_export($req, true), $header);

        // Replace required functions symbols
        $req = empty($GLOBALS['igloo']->dependencies->functions) ? [] : $GLOBALS['igloo']->dependencies->functions;
        $header = str_replace('#{{REQ_FUNCTIONS}}', var_export($req, true), $header);

        // Replace minimum reserved memory symbols
        $req = empty($GLOBALS['igloo']->dependencies->memory) ? 0 : Mem::convert($GLOBALS['igloo']->dependencies->memory);
        $header = str_replace('#{{REQ_MEMORY_B}}', $req, $header);

        return $header;
    }

}