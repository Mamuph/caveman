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
     * @param array     $options
     * @return $this
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

        try
        {
            $phar = new Phar($file, $filesystem_mask, APPID . '.phar');
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
        $phar->buildFromDirectory($this->source_dir);

        $default_stub = $phar->createDefaultStub('index.php');
        $default_stub = '<?php define("APPID", "' . APPID . '")?>' . $default_stub;

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

        if ($this->options['executable'] === true)
            chmod($file, 0775);

        return true;

    }

}