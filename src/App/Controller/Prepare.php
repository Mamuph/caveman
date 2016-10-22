<?php

/**
 * Prepare action controller
 */
class Controller_Prepare extends Controller_Main
{

    /**
     * Exclusion list
     *
     * @var array
     */
    protected $exclusion_list = [
        '/\/\.DS_Store$/',
        '/\/\.git$/',
        '/\/\.idea$/'
    ];


    /**
     * List of directories that should be remain empty with only a .gitkeep file
     *
     * @var array
     */
    protected $keep_empty_dir = [
        '/\/build$/'
    ];


    /**
     * Prepare controller entry point
     *
     * @return int|void
     */
    public function action_main()
    {

        if (!Params::get('source'))
        {
            $this->action_help('prepare');
            return Apprunner::EXIT_SUCCESS;
        }

        return parent::action_main();
    }


    /**
     * Perform the prepare command
     */
    public function action_prepare()
    {

        // Read source directory
        // ---------------------
        $src = Params::get('source') ? Params::get('source') : '.';

        if ($src[0] != DS)
            $src = getcwd() . DS . $src;


        if (!is_dir($src))
            $this->exit_error('Wrong path, missing project directory');


        // Read destination directory
        // --------------------------
        $dest = Params::get('destination') ? Params::get('destination') : '.';

        if ($dest[0] != DS)
            $dest = getcwd() . DS . $dest;

        if (is_dir($dest))
        {

            // Check if directory is empty
            if ((new \FilesystemIterator($dest))->valid())
            {
                $this->term->br()->out('<red>Directory already exists and it is not empty</red>');

                $input = $this->term->confirm('<yellow>Continue?</yellow>');

                if (!$input->confirmed())
                    $this->exit_error('Aborting...');
            }

        }
        else
        {
            if (!@mkdir($dest, 0774))
                $this->exit_error("Unable to create directory $dest");
        }


        if (!is_writable($dest))
            $this->exit_error("Unable to write in $dest");

        $this->term->br()->out('<blue>Preparing release into: </blue>' . $dest);


        // Copy project files
        // ------------------
        $this->term->br()->out("<blue>Copying release files...</blue>");

        if (!File::xcopy($src . DS . '*', $dest, 0755))
            $this->exit_error("Unable to copy release files from $src to temporal directory: $dest");


        // Exclude files
        // -------------
        $p_files = File::ls($dest, true, File::EXCLUDE_BLOCK | File::EXCLUDE_LINKS | File::LIST_RECURSIVE);


        foreach ($p_files as $file)
        {

            $excluded = Arr::preg_match($this->exclusion_list, $file);

            if (is_file($file) && $excluded === 1)
            {
                unlink($file);
                $this->term->br()->out('<blue>Excluded:</blue> ' . $file);
            }
            else if (is_dir($file))
            {
                if ($excluded === 1)
                {
                    File::deltree($file);
                    $this->term->br()->out('<blue>Excluded:</blue> ' . $file);
                }
                else
                {
                    if (Arr::preg_match($this->keep_empty_dir, $file) === 1)
                    {
                        File::deltree($file);
                        mkdir($file, 0775);
                        touch($file . DS . '.gitkeep');
                        $this->term->br()->out('<blue>Cleaned:</blue> ' . $file);
                    }

                }
            }

        }


        $this->term->br()->out('<green>Release is ready at:</green> ' . $dest);


        return Apprunner::EXIT_SUCCESS;

    }


}