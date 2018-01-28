<?php


/**
 * Default controller entry-point
 */
class Controller_Main extends Controller
{


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
     * Entry point.
     */
    public function actionMain()
    {

        $controller = 'action_' . strtolower(Params::get('command'));
        $controller = Str::camel($controller);

        if (!Params::get('command') || $controller == __FUNCTION__ || $controller == 'actionHelp')
            $this->actionHelp();
        else
        {
            if (method_exists($this, $controller))
            {
                $this->{$controller}();

                $this->term->br()->out('Operation completed!');
            }
            else
            {
                $controller = ucfirst(Params::get('command'));

                if (Apprunner::findFile(APPPATH . 'Controller', $controller, 'php'))
                    Apprunner::execute($controller);
                else
                    $this->actionHelp();
            }

        }

        Apprunner::terminate(Apprunner::EXIT_SUCCESS);

    }


    /**
     * Display help view
     *
     * @param string $help_view
     */
    protected function actionHelp($help_view = 'main')
    {
        $help = file_get_contents(APPPATH . 'View/help_' . $help_view . '.txt');
        $help = str_replace('#{{__EXECUTABLE__}}', basename(Phar::running()), $help);

        $this->term->out($help);
    }


    /**
     * Increment major version
     */
    public function actionIncMajor() : int
    {
        $number = $this->modifyVersion('major');
        $this->modifyVersion('minor', 0);
        $this->term->br()->out("⬆  <blue>Increasing major version to</blue> $number");

        return Apprunner::EXIT_SUCCESS;
    }


    /**
     * Decrement major version
     */
    public function actionDecMajor() : int
    {
        $number = $this->modifyVersion('major', -1);
        $this->modifyVersion('minor', 0);
        $this->term->br()->out("⬇︎  <blue>Decreased major version to</blue> $number");

        return Apprunner::EXIT_SUCCESS;
    }


    /**
     * Increment minor version
     */
    public function actionIncMinor() : int
    {
        $number = $this->modifyVersion('minor');
        $this->term->br()->out("⬆  <blue>Increased minor version to</blue> $number");

        return Apprunner::EXIT_SUCCESS;
    }


    /**
     * Decrement minor version
     */
    public function actionDecMinor() : int
    {
        $number = $this->modifyVersion('minor', -1);
        $this->term->br()->out("⬇︎  <blue>Decreased minor version to</blue> $number");

        return Apprunner::EXIT_SUCCESS;
    }


    /**
     * Increment build
     */
    public function actionIncBuild() : int
    {
        $number = $this->modifyVersion('build');
        $this->term->br()->out("⬆︎  <blue>Increased build version to</blue> $number");

        return Apprunner::EXIT_SUCCESS;
    }


    /**
     * Decrement build.
     *
     * @return int
     * @throws Exception
     */
    public function actionDecBuild() : int
    {
        $number = $this->modifyVersion('build', -1);
        $this->term->br()->out("⬇︎  <blue>Decreased build version to</blue> $number");

        return Apprunner::EXIT_SUCCESS;
    }


    /**
     * Modify version.
     *
     * @param $key
     * @param int $inc
     * @return mixed
     * @throws Exception
     */
    protected function modifyVersion($key, $inc = 1)
    {

        if (empty($GLOBALS['igloo']))
        {
            $this->readIgloo();
            $this->openTargetConf();
        }

        $external_version = $this->external_conf->load('Version');
        $external_version->{$key} = $inc === 0 ? 0 : $external_version->{$key} + $inc;
        $external_version->set($key, $external_version->{$key});

        return $external_version->{$key};
    }


    /**
     * Read the igloo/manifest file, compute the source directory,
     * dump the configuration and return the manifest path
     *
     * @return string
     */
    protected function readIgloo()
    {

        $this->term->br()->out('<blue>Reading igloo manifest...</blue>');

        if (!Params::get('source'))
            Params::set('source', '.');

        $manifest_path = Params::get('source') . DS . 'igloo.json';

        if (!File::exists($manifest_path, File::SCOPE_EXTERNAL))
        {
            // Try to read old manifest file
            $manifest_path = Params::get('source') . DS . 'manifest.json';

            if (!File::exists($manifest_path, File::SCOPE_EXTERNAL))
                $this->exitError('igloo.json file is not available!');
        }

        $GLOBALS['igloo'] = json_decode(file_get_contents($manifest_path));

        if ($GLOBALS['igloo'] === null)
            $this->exitError('Unable to read the manifest file');

        if (empty($GLOBALS['igloo']->paths) || empty($GLOBALS['igloo']->paths->src))
            $this->exitError('igloo.json: Wrong format');

        if (empty($GLOBALS['igloo']->build->name))
            $this->exitError('igloo.json: Wrong build name');

        // Save manifest path in globals
        $GLOBALS['igloo']->_manifest_path = $manifest_path;

        // Save the srcpath in globals
        $srcpath = dirname($manifest_path) . DS . $GLOBALS['igloo']->paths->src;
        $GLOBALS['igloo']->_srcpath = realpath($srcpath) . DS;

        return $manifest_path;

    }


    /**
     * Open the target configuration
     */
    protected function openTargetConf()
    {

        $this->term->br()->out("<blue>Opening target configuration...</blue>");
        $external_conf_path  = $GLOBALS['igloo']->_srcpath . 'Config' . DS;

        $this->external_conf = new Config();
        $this->external_conf
            ->attach(new Config_File_Reader($external_conf_path))       // Reader
            ->attach(new Config_File_Writer($external_conf_path));      // Writer

    }



    /**
     * Exit the program and return failure status code
     *
     * @param $message
     */
    protected function exitError($message)
    {
        $this->term->error($message);
        Apprunner::terminate(Apprunner::EXIT_FAILURE);
    }


    /**
     * Render a progress bar
     *
     * @param $progress_info
     */
    public function showProgressbar($progress_info)
    {

        if ($this->progress === null)
            $this->progress = $this->term->progress()->total($progress_info['total']);

        $this->progress->current($progress_info['current']);

        if ($progress_info['current'] >= $progress_info['total'])
            $this->progress = null;

    }


}