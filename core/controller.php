<?php

/**
 * Abstract Controller Base library
 *
 */

namespace Core;

abstract class Controller {
    protected static $before_filters = array ();
    protected static $after_filters = array ();
    protected static $layout = array(); 
    
    protected static $exception_callbacks = array(
        'Exception' => 'exception_handler'
    );

    protected $session = array ();
    protected $flash = array ();
    protected $get = array ();
    protected $params = array ();
    protected $post = array ();
    protected $files = array ();
    protected $server = array ();
    protected $view = array ();

    private $status = 200;
    protected $x_requested = false;
    private $content_type = 'text/html';
    protected $is_running = false;

    private $render_action = true;

    private final function __construct () {}

    public final function __run ($params)
    {
        $this->params = $params;
        $this->view = \View::getInstance();

        if (isset($this->params['format']))
        {
            \View::setOption('format', $this->params['format']);
        }

        try 
        {
            $this->__parseGlobals();
            $this->__runAction();
            $this->__sendHeaders();    
            $this->__renderView();
        }
        catch(\Exception $e)
        {
            $exception_callback_method = null;
            foreach(static::$exception_callbacks as $exception_name => $exception_method)
            {
                if(\is_a($e, $exception_name))
                {
                    $exception_callback_method = $exception_method;
                }
            }
            if(!$exception_callback_method)
                throw $e;
            if (method_exists($this, $exception_callback_method))
            {
                $this->$exception_callback_method($e);
                $this->__sendHeaders();
                $this->__renderView();
            }
            else
            {
                throw new \ControllerException('exceptions.controller.no_exception_callback', array('method' => $exception_callback_method, 'exception_class' => \get_class($e)));
            }
        }
    }
    protected function exception_handler($e){
        \Core\DebugConsole::log("Exception: ".\get_class($e)."\nMessage: {$e->getMessage()}\nStack Trace: {$e->getTraceAsString()}");
        if(isset($this->params['format']) && $this->params['format'] == 'json')
            $this->render(array('inline' => \json_encode(array('success' => false, 'msg' => $e->getMessage()))));
        else
            throw $e;
    }


    private final function __runAction()
    {
        if (method_exists($this, $this->params['action']) || (isset($this->params['format']) && method_exists($this, $this->params['action'].'_'.$this->params['format'])))
        {
            $this->_trigger ();


            $this->__runFilters ($this->params['action'], 'before');

            if (isset($this->params['format']))
            {
                $this->content_type = \Mime\from_extension($this->params['format']); 
            }

            if($this->render_action)
            {
                if (isset($this->params['format']) && method_exists($this, $this->params['action'].'_'.$this->params['format']))
                {
                    $this->{$this->params['action'].'_'.$this->params['format']}();
                }
                else
                {
                    $this->{$this->params['action']}();
                }
            }

            $this->__runFilters ($this->params['action'], 'after');
            $this->_output ();
        }
        else
        {
            if (\Core\Config::controller()->debug)
            {
                throw new \ControllerException('exceptions.controller.no_action', array (
                    'action' => $this->params['action'],
                    'available_actions' => join(', ', $this->__getAvailableActions()),
                ));
            }
            else
            {
                throw new \ControllersException('exceptions.controller.page_not_found');
            }
        }

    }

    private final function __sendHeaders()
    {         
        \Header\status($this->status);
        \Header\content_type($this->content_type);
    }

    private final function __renderView()
    {
        $variables = array(
            'session' => $this->session,
            'flash' => $this->flash,
            'get' => $this->get,
            'params' => $this->params,
            'post' => $this->post,
            'files' => $this->files,
            'server' => $this->server,    
        );

        foreach ($this->view as $key => $value)
        {
            $variables[$key] = $value;
        }

        $this->__setViewLayout();

        echo \View::generate($variables);
    }

    private function __setViewLayout()
    {
        $current_layout = false;

        if (\View::getOption('layout') == '')
        {
            if (is_array(static::$layout))
            {
                foreach (static::$layout as $layout_name => $methods)
                {
                    if(in_array($this->params['action'], $methods))
                    {
                        $current_layout = $layout_name; 
                        break;
                    }
                }
            }
            else
            {
                $current_layout = static::$layout;
            }

            \View::setOption('layout', $current_layout);
        }
    }

    private function __getAvailableActions ()
    {
        $reflection = new \ReflectionClass(get_class($this));

        return array_map(function ($method) {
            return $method->getName();
        }, array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), function($method) {
            return ! preg_match('/^\_/', $method->getName());
        }));
    }

    private function __parseGlobals ()
    {
        $_SESSION = isset($_SESSION) ? $_SESSION : array ();
        $this->session = \SessionHandler::createAccessor(false);
        $this->flash = \SessionHandler::createAccessor(true);
        $globals_array = array (
            'session'   => array (
                'variable' => & $_SESSION, 
                'action' => false,
            ),
            'post'      => array (
                'variable' => & $_POST,
                'action' => true,
            ),
            'get'       => array (
                'variable'  => & $_GET,
                'action'    => function ($data) {
                    foreach ($data as $key => $val)
                    {
                        if(preg_match('/^\__/', $key))
                        {
                            unset ($data[$key]);
                        }
                    }
                    return $data;
                },
            ),
            'cookie'       => array (
                'variable'  => & $_COOKIE,
                'action'    => false,
            ),
            'request'       => array (
                'variable'  => & $_REQUEST,
                'action'    => false,
            ),
            'server'       => array (
                'variable'  => & $_SERVER,
                'action'    => true,
            ),
            'files'       => array (
                'variable'  => & $_FILES,
                'action'    => true,
            ),
            'env'         => array (
                'variable'  => & $_ENV,
                'action'    => true,
            )
        );
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
            $this->x_requested = true;
        }
        foreach ($globals_array as $global_key => $global)
        {
            if ($global['action'] === TRUE || is_a($global['action'], 'Closure'))
            {
                foreach ($global['variable'] as $key => $value)
                {
                    if ($global['action'] === TRUE)
                    {
                        $this->{$global_key}[$key] = $value;
                    }
                    else
                    {
                        $this->{$global_key} = $global['action']($global['variable']);
                    }
                }
            }
        }

        unset($_SESSION, $_COOKIE, $_POST, $_GET, $FILES, $_REQUEST, $_FILES, $_ENV);
    }

    public static function __dispatcher ($params)
    {
        $controller_class_name = \join('\\', \array_map(function($element){
            return \String\camelize($element);
        }, \explode('\\', $params['controller']))).'Controller';
        $controller_class = new $controller_class_name();

        if (! is_a($controller_class, 'Core\Controller')) {
            if (\Core\Config::controller()->debug) {
                throw new \ControllerException('exceptions.controller.no_type', array ('class_name' => $controller_class_name));
            } else {
                throw new \ControllerException('exceptions.controller.page_not_found');
            }
        }

        $controller_class->__run($params);
    }

    private final function __runFilters ($action, $when = 'before')
    {
        foreach (static::${$when.'_filters'} as $filter_action => $params)
        {
            if (isset($params['only']) && in_array($action, $params['only']))
            {
                if($this->$filter_action() === false) return;
            }
            else if (isset($params['except']) && ! in_array($action, $params['except']))
            {
                if($this->$filter_action() === false) return;
            }
            else if(!$params)
            {
                if($this->$filter_action() === false) return;
            }
        }
    }

    protected function render ($action_or_options, $options = array())
    {
        if (is_array($action_or_options))
        {
            $options = $action_or_options;
        }
        else
        {
            $options['action'] = $action_or_options;
        }

        if (isset($options['action'])) 
        {
            \View::setOption('action', $options['action']);
            $this->render_action = false;
        }
        else if (isset($options['nothing']))
        {
            \View::setOption('inline', false);
            \View::setOption('layout', false);
            \View::setOption('template', false);
            $this->render_action = false;
        }  
        else if (isset($options['template']))
        {
            \View::setOption('template', $options['template']);
            $this->render_action = false;
        }
        else if (isset($options['inline']))
        {
            \View::setOption('layout', false);
            \View::setOption('template', false);
            \View::setOption('inline', $options['inline']); 
            $this->render_action = false;
        }
        if (isset($options['format']))
        {
            \View::setOption('format', $options['format']);
        }
        else if (isset($options['json']))
        {
            \View::setOption('layout', false);
            \View::setOption('format', 'json');    
        }
        else if (isset($options['xml']))
        {
            \View::setOption('layout', false);
            \View::setOption('format', 'xml');
        }
        else if (isset($options['js']))
        {
            \View::setOption('layout', false);
            \View::setOption('format', 'js');
        }
        if (isset($options['content_type']))
        {
            $this->content_type = $options['content_type'];
        }
        if (isset($options['layout']))
        {
            \View::setOption('layout', $options['layout']);
        }
        if (isset($options['status']))
        {
            $this->status = $options['status'];
        }
    }

    protected function redirect ($url)
    {
        \Header\location($url);
    }
}
