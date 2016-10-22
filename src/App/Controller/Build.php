<?php

/**
 * Build action controller
 */
class Controller_Build extends Controller_Main
{

    /**
     * Build controller entry point
     *
     * @return int|void
     */
    public function action_main()
    {

        if (!Params::get('source'))
        {
            $this->action_help('build');
            return Apprunner::EXIT_SUCCESS;
        }

        $this->read_manifest();
        $this->open_target_conf();

        return parent::action_main();
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

}