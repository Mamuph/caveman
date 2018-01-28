<?php

/**
 * New action controller
 */
class Controller_New extends Controller_Main
{

    /**
     * New controller entry-point
     */
    public function actionMain()
    {

        if (!Params::get('source'))
        {
            $this->actionHelp('new');
            return Apprunner::EXIT_SUCCESS;
        }


        return parent::actionMain();
    }


    /**
     * Deploy a new project
     *
     * @return int
     */
    protected function actionNew()
    {

        // Read destination directory
        // --------------------------
        $dest = Params::get('source') ? Params::get('source') : '.';

        if ($dest[0] != DS)
            $dest = getcwd() . DS . $dest;


        if (is_file($dest))
            $this->exitError('Wrong path');


        if (is_dir($dest))
        {

            // Check if directory is empty
            if ((new \FilesystemIterator($dest))->valid())
            {
                $this->term->br()->out('<red>Directory already exists and it is not empty</red>');

                $input = $this->term->confirm('<yellow>Continue?</yellow>');

                if (!$input->confirmed())
                    $this->exitError('Aborting...');
            }

        }
        else
        {
            if (!@mkdir($dest, 0774))
                $this->exitError("Unable to create directory $dest");
        }


        if (!is_writable($dest))
            $this->exitError("Unable to write in $dest");

        $this->term->br()->out('<blue>Deploying new project into: </blue>' . $dest);


        // Retrieve release binary URL
        // ---------------------------
        if (!Params::get('release'))
            Params::set('release', 'latest');

        $package = new Model_ReleasePackage(Params::get('release'));
        $package_url = $package->getBinaryURL();

        if (!$package_url)
            $this->exitError('Release package is not available (Wrong version?)');


        // Get release binary size
        // -----------------------
        $fsize = get_headers($package_url, true);
        $fsize = isset($fsize['Content-Length']) ? $fsize['Content-Length'] : false;


        // Download file
        // -------------
        if (!$fpsrc = fopen($package_url, 'r'))
            $this->exitError('Unable to download ' . $package_url);

        $this->term->br()->out('<blue>Downloading:</blue> ' . $package_url);

        $fptmp = tmpfile();

        while(!feof($fpsrc))
        {
            fwrite($fptmp, fread($fpsrc, 65536));

            if ($fsize)
                $this->showProgressbar(array('total' => $fsize, 'current' => fstat($fptmp)['size']));
        }

        fclose($fpsrc);


        // Decompress file
        // ---------------
        $this->term->br()->out('<blue>Decompressing...</blue>');

        $zip = new ZipArchive();
        $zippath = stream_get_meta_data($fptmp)['uri'];

        if ($zip->open($zippath) === true)
        {

            $total_files = $zip->numFiles;

            for ($i = 0; $i < $total_files; $i++)
            {
                $entry = $zip->getNameIndex($i);

                if ($entry === 'mamuph_base/')
                    continue;

                $name = str_replace('mamuph_base/', '/', $entry);
                $dir = $dest . dirname($name);

                if (!file_exists($dir))
                    mkdir($dir);

                @copy('zip://' . $zippath . '#' . $entry, $dest . $name);

                $this->showProgressbar(['total' => $total_files, 'current' => $i + 1]);
            }

            $zip->close();

        }
        else
            $this->exitError('Unable to decompress source file');

        fclose($fptmp);


        // Set project name
        // ----------------
        $manifest_path = $dest . DS . 'igloo.json';

        if (!File::exists($manifest_path, File::SCOPE_EXTERNAL))
            $this->exitError('Unable to find igloo.json');

        $manifest = json_decode(file_get_contents($manifest_path), true);
        $project_name = Params::get('name');

        if (!$project_name)
        {
            do
            {
                $input = $this->term->br()->input('<yellow>Project name?</yellow>');
                $project_name = $input->prompt();

                if (empty(trim($project_name)))
                    $project_name = false;

            } while (!$project_name);

        }

        $manifest['name'] = $project_name;

        file_put_contents($manifest_path, json_encode($manifest, JSON_PRETTY_PRINT));


        // End action
        // ----------
        $this->term->br()->out("👍  <green>New project</green> $project_name <green>deployed</green>");

        return Apprunner::EXIT_SUCCESS;

    }


}