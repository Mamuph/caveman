<?php


/**
 * Default controller entry-point
 */
class Controller_Main extends Controller
{

    const MAMUPH_RELEASE = 'https://github.com/Mamuph/base/archive/master.zip';


    /**
     * @var \League\CLImate\CLImate
     */
    protected $term;

    /**
     * @var \League\CLImate\CLImate
     */
    protected $progress = null;

    /**
     * @var Config
     */
    protected $external_conf = null;


    /**
     * Controller_Main constructor.
     */
    public function __construct()
    {
        $this->term = new League\CLImate\CLImate();
    }


    /**
     * Entry point
     */
    public function action_main()
    {

        $controller = 'action_' . strtolower(Params::get('command'));
        $controller = str_replace('-', '_', $controller);

        if (!Params::get('command') || $controller == __FUNCTION__)
            $this->action_help();
        else
        {
            if (method_exists($this, $controller))
            {

                if ($controller != 'action_new')
                {
                    $this->read_manifest();
                    $this->open_target_conf();
                }

                $this->{$controller}();

                $this->term->br()->out('Operation completed!');
            }
            else
                $this->action_help();
        }

        return Apprunner::terminate(Apprunner::EXIT_SUCCESS);

    }


    /**
     * Display help message
     */
    protected function action_help()
    {
        $help = file_get_contents('App/View/help_main.txt');
        $help = str_replace('#{{__EXECUTABLE__}}', basename(Phar::running()), $help);

        $this->term->out($help);
    }


    /**
     * Start a new project
     *
     * @return int
     */
    protected function action_new()
    {

        $dest = Params::get('source') ? Params::get('source') : '.';

        if ($dest[0] != DS)
            $dest = getcwd() . DS . $dest;

        $dest = realpath($dest);

        if (!is_writable($dest))
            $this->exit_error('Unable to write in path!');

        $fsize = get_headers(self::MAMUPH_RELEASE, true);
        $fsize = isset($fsize['Content-Length']) ? $fsize['Content-Length'] : false;


        // Download file
        if (!$fpsrc = fopen(self::MAMUPH_RELEASE, 'r'))
            $this->exit_error('Unable to read ' . self::MAMUPH_RELEASE);

        $this->term->br()->out('<blue>Downloading:</blue> ' . self::MAMUPH_RELEASE);

        $fptmp = tmpfile();

        while(!feof($fpsrc))
        {
            fwrite($fptmp, fread($fpsrc, 65536));

            if ($fsize)
                $this->show_progressbar(array('total' => $fsize, 'current' => fstat($fptmp)['size']));
        }

        fclose($fpsrc);


        // Decompress file

        $this->term->br()->out('<blue>Uncompressing...</blue>');

        $zip = new ZipArchive();
        $zippath = stream_get_meta_data($fptmp)['uri'];

        if ($zip->open($zippath) === true)
        {

            $total_files = $zip->numFiles;

            for ($i = 0; $i < $total_files; $i++)
            {
                $entry = $zip->getNameIndex($i);

                if ($entry === 'base-master/')
                    continue;

                $name = str_replace('base-master/', '/', $entry);
                $dir = $dest . dirname($name);

                if (!file_exists($dir))
                    mkdir($dir);

                @copy('zip://' . $zippath . '#' . $entry, $dest . $name);

                $this->show_progressbar(['total' => $total_files, 'current' => $i + 1]);
            }

            $zip->close();

        }
        else
            $this->exit_error('Unable to decompress source file');

        fclose($fptmp);


        $this->term->br()->out(\Juanparati\Emoji\Emoji::char('thumbs up') . '  <green>New project deployed</green>');

        return Apprunner::EXIT_SUCCESS;

    }


    /**
     * Perform the build command
     */
    protected function action_build()
    {


        // Check dependencies
        // ------------------
        if (!Phar::canWrite())
            $this->exit_error('phar.readonly was set to 1, please modify your php.ini');


        $compression_method = false;

        if (Params::get('compress'))
        {

            switch (Params::get('compress'))
            {

                case 'bz2':
                    $compression_method = Phar::BZ2;

                    if (!Phar::canCompress($compression_method))
                        $this->exit_error('BZ2 compression method not available. Install the BZIP2 extension');

                case 'gz':
                default:

                    $compression_method = Phar::GZ;

                    if (!Phar::canCompress($compression_method))
                        $this->exit_error('GZ compression method not available. Install the ZLIB extension');

            }
        }


        // Improve phar security
        // ---------------------
        ini_set('phar.require_hash', true);


        // Compute buildpath
        // -----------------
        if (empty($GLOBALS['manifest']->paths->build))
            $buildpath =  getcwd() . DS;
        else
            $buildpath = dirname($GLOBALS['manifest']->_manifest_path) . DS . $GLOBALS['manifest']->paths->build;

        $buildpath = realpath($buildpath) . DS;


        // Create temp directory
        // ---------------------
        $this->term->br()->out('<blue>Creating build base...</blue>');

        $tmpdir = $buildpath . 'temp_' . uniqid() . DS;

        if (!@mkdir($tmpdir))
            $this->exit_error('Unable to create temp directory: ' . $tmpdir);


        // Read version
        // ------------
        $external_version = (array) $external_version = $this->external_conf->load('version');;


        // Copy src files
        // --------------
        $this->term->br()->out("<blue>Copying base files...</blue>");
        if (!File::xcopy($GLOBALS['manifest']->_srcpath . '*', $tmpdir, 0755, File::EXCLUDE_HIDDEN))
            $this->exit_error("Unable to copy project files from {$GLOBALS['manifest']->_srcpath} to temporal directory: $tmpdir");


        // Set version to build file
        // -------------------------
        $GLOBALS['manifest']->build->name = Replacer::replace_from_array($external_version, $GLOBALS['manifest']->build->name, 'VERSION');


        // Set phar file path
        // ------------------
        $pharfile = $buildpath . $GLOBALS['manifest']->build->name;


        // Read and process project files
        // ------------------------------
        $this->term->br()->out("<blue>Replacing symbols and striping files...</blue>");
        $p_files = File::ls($tmpdir, true, File::EXCLUDE_BLOCK | File::EXCLUDE_DIRECTORIES | File::EXCLUDE_LINKS | File::LIST_RECURSIVE);

        foreach ($p_files as $file)
        {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $file_mime = finfo_file($finfo, $file);

            @list($file_type, $file_format) = explode('/', $file_mime);

            // Replace only text files
            if ($file_type == 'text' || $file_format == 'php')
            {

                if ($file_format == 'php' || $file_format == 'x-php')
                    $file_content = php_strip_whitespace($file);
                else
                    $file_content = file_get_contents($file);

                $file_content = Replacer::replace_from_array($external_version, $file_content, 'VERSION');

                file_put_contents($file, $file_content);
            }

        }



        // Modify versions
        // ---------------
        if (Params::get('inc-major'))
            $this->action_inc_major();

        if (Params::get('inc-minor'))
            $this->action_inc_minor();

        $this->action_inc_build();



        // Build phar
        // ----------
        $this->term->br()->out("<blue>Building PHAR...</blue>");
        $phar = new Model_PharBuilder($tmpdir);

        // Prepare signature
        // -----------------
        if (!empty(Params::get('private-key')))
        {
            $signature = new Model_SignatureManager(Params::get('private-key'));
            $key_password = '';

            if ($signature->is_protected())
            {
                $input_password = $this->term->br()->password(Juanparati\Emoji\Emoji::char('lock') . '  <yellow>Enter the private key password:</yellow>');
                $key_password = $input_password->prompt();
            }

            $this->term->br()->out('<blue>Exporting signature...</blue>');

            $signature->export($key_password);
            $phar->set('private_key', $signature->get());

            Params::set('signature-type', 'openssl');
        }


        // Set signature type
        $signature_type = Params::get('signature-type');

        if (is_string($signature_type))
            $signature_type = strtolower($signature_type);


        switch ($signature_type)
        {

            case 'openssl':
                $phar->set('signature', Phar::OPENSSL);
                break;

            case 'md5':
                $phar->set('signature', Phar::MD5);
                break;

            case 'sha1':
                $phar->set('signature', Phar::SHA1);
                break;

            case 'sha256':
                $phar->set('signature', Phar::SHA256);
                break;

            case 'sha512':
                $phar->set('signature', Phar::SHA512);
                break;
        }


        if (Params::get('executable'))
            $phar->set('executable', true);

        $phar->set('compress', $compression_method);

        if (($build_status = $phar->build($pharfile)) !== true)
            $this->exit_error($build_status);

        $this->term->br()->out(Juanparati\Emoji\Emoji::char('beer mug') . "  <green>Build performed, enjoy of your new PHAR at:</green> $pharfile");


        if (Params::get('remove-tmp') && File::deltree(substr($tmpdir, 0, -1)))
            $this->term->br()->out("Removed temporal folder: <yellow>$tmpdir</yellow>");


    }


    /**
     * Increment major version
     */
    public function action_inc_major()
    {
        $number = $this->modify_version('major');
        $this->term->br()->out(Juanparati\Emoji\Emoji::char('up arrow') . "  <blue>Increasing major version to</blue> $number");

        return Apprunner::EXIT_SUCCESS;
    }


    /**
     * Decrement major version
     */
    public function action_dec_major()
    {
        $number = $this->modify_version('major', -1);
        $this->term->br()->out(Juanparati\Emoji\Emoji::char('down arrow') . "  <blue>Decreased major version to</blue> $number");

        return Apprunner::EXIT_SUCCESS;
    }


    /**
     * Increment minor version
     */
    public function action_inc_minor()
    {
        $number = $this->modify_version('minor');
        $this->term->br()->out(Juanparati\Emoji\Emoji::char('up arrow') . "  <blue>Increased minor version to</blue> $number");

        return Apprunner::EXIT_SUCCESS;
    }


    /**
     * Decrement minor version
     */
    public function action_dec_minor()
    {
        $number = $this->modify_version('minor', -1);
        $this->term->br()->out(Juanparati\Emoji\Emoji::char('down arrow') . "  <blue>Decreased minor version to</blue> $number");

        return Apprunner::EXIT_SUCCESS;
    }


    /**
     * Increment build
     */
    public function action_inc_build()
    {
        $number = $this->modify_version('build');
        $this->term->br()->out(Juanparati\Emoji\Emoji::char('up arrow') . "  <blue>Increased build version to</blue> $number");

        return Apprunner::EXIT_SUCCESS;
    }


    /**
     * Decrement build
     */
    public function action_dec_build()
    {
        $number = $this->modify_version('build', -1);
        $this->term->br()->out(Juanparati\Emoji\Emoji::char('down arrow') . "  <blue>Decreased build version to</blue> $number");

        return Apprunner::EXIT_SUCCESS;
    }


    protected function modify_version($key, $inc = 1)
    {
        $external_version = $this->external_conf->load('version');
        $external_version->{$key} = $external_version->{$key} + $inc;
        $external_version->set($key, $external_version->{$key});

        return $external_version->{$key};
    }


    /**
     * Read the manifest file, compute the source directory,
     * dump the configuration and return the manifest path
     *
     * @return string
     */
    protected function read_manifest()
    {

        $this->term->br()->out('<blue>Reading manifest...</blue>');

        if (!Params::get('source'))
            Params::set('source', '.');

        $manifest_path = Params::get('source') . DS . 'manifest.json';

        if (!File::exists($manifest_path, File::SCOPE_EXTERNAL))
            $this->exit_error('Manifest file is not available!');

        $GLOBALS['manifest'] = json_decode(file_get_contents($manifest_path));

        if ($GLOBALS['manifest'] === null)
            $this->exit_error('Unable to read the manifest file');

        if (empty($GLOBALS['manifest']->paths) || empty($GLOBALS['manifest']->paths->src))
            $this->exit_error('manifest.js: Wrong format');

        if (empty($GLOBALS['manifest']->build->name))
            $this->exit_error('manifest.js: Wrong build name');

        // Save manifest path in globals
        $GLOBALS['manifest']->_manifest_path = $manifest_path;

        // Save the srcpath in globals
        $srcpath = dirname($manifest_path) . DS . $GLOBALS['manifest']->paths->src;
        $GLOBALS['manifest']->_srcpath = realpath($srcpath) . DS;

        return $manifest_path;

    }


    /**
     * Open the target configuration
     */
    protected function open_target_conf()
    {

        $this->term->br()->out("<blue>Opening target configuration...</blue>");
        $external_conf_path  = $GLOBALS['manifest']->_srcpath . 'Config' . DS;

        $this->external_conf = new Config();
        $this->external_conf
            ->attach(new Config_FileReader($external_conf_path))       // Reader
            ->attach(new Config_FileWriter($external_conf_path));      // Writer

    }



    /**
     * Exit the program and return failure status code
     *
     * @param $message
     */
    protected function exit_error($message)
    {
        $this->term->error($message);
        Apprunner::terminate(Apprunner::EXIT_FAILURE);
    }


    /**
     * Render a progress bar
     *
     * @param $progress_info
     */
    public function show_progressbar($progress_info)
    {

        if ($this->progress === null)
            $this->progress = $this->term->progress()->total($progress_info['total']);

        $this->progress->current($progress_info['current']);

        if ($progress_info['current'] >= $progress_info['total'])
            $this->progress = null;

    }


}