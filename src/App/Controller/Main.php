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
     * Entry point
     */
    public function action_main()
    {

        $controller = 'action_' . strtolower(Params::get('command'));
        $controller = str_replace('-', '_', $controller);

        if (!Params::get('command') || $controller == __FUNCTION__ || $controller == 'action_help')
            $this->action_help();
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

                if (Apprunner::find_file(APPPATH . 'Controller', $controller, 'php'))
                    Apprunner::execute($controller);
                else
                    $this->action_help();
            }

        }

        return Apprunner::terminate(Apprunner::EXIT_SUCCESS);

    }


    /**
     * Display help view
     *
     * @param string $help_view
     */
    protected function action_help($help_view = 'main')
    {
        $help = file_get_contents(APPPATH . 'View/help_' . $help_view . '.txt');
        $help = str_replace('#{{__EXECUTABLE__}}', basename(Phar::running()), $help);

        $this->term->out($help);
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

        if (empty($GLOBALS['manifest']))
        {
            $this->read_manifest();
            $this->open_target_conf();
        }

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