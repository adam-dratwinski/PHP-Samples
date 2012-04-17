<?php

    /**
     * Sample Session Handler which stores sessions in the database, using our ActiveRecords like ORM library
     */

    namespace MP\Session;
    use MP\Config;
    use App\Models\Session;
    
    class Base {
        private static $instance;
        private $session_data = NULL;
        private $session_id;
        private $session_object_id = null;
        private $sid;
        
        public function __construct ()
        {
            $session_config   = Config\Base::getInstance()->session;
            $config_config    = Config\Base::getInstance()->config;
            $this->sid        = $session_config->sid;
            $this->lifetime   = $session_config->lifetime;
            $this->timeout    = $session_config->timeout;
            $this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
            
            \ini_set('session.gc_probability', 100);
            \ini_set('session.gc_divisor', 100);
            \ini_set('session.name',$this->sid);
            \session_set_save_handler (
                array ( & $this, '_open'),
                array ( & $this, '_close'),
                array ( & $this, '_read'),
                array ( & $this, '_write'),
                array ( & $this, '_destroy'),
                array ( & $this, '_gc')
            );
                        
            \session_start();

        }

        public function _open ($session_savepath, $session_name)
        {
            if (isset($_COOKIE[$this->sid]))
            {
                $this->session_id = $_COOKIE[$this->sid];
            }
            
            return TRUE;
        }

        public function _close ()
        {
            $this->saveSessionData();
            session_write_close();
            $this->_gc($this->lifetime);
            return TRUE;
        }

        public function _read ($session_id)
        {
            $this->loadSessionData($session_id);
            return '';
        }

        public function _write ($session_id, $sess_data)
        {
            $this->saveSessionData($session_id);
            return TRUE;
        }

        public function _destroy ($session_id)
        {
            $this->destroy();

            return TRUE;
        }

        public function _gc ($lifetime)
        {
            $this->clearGarbage();

            return TRUE;
        }
        
        private function generateCookie()
        {
            $session_config         = Config\Base::getInstance()->session;
            $config_config          = Config\Base::getInstance()->config;

            $cookie_id = md5(microtime().mt_rand(RANDOM_TOKEN));

            //jezeli wlaczone sesje globalne i adres zawiera poprawna nazwe domeny (dwuczlonowa)
            //inaczej jest bug php i sesje nie sa zapisywane
            if ($session_config->global && explode('.', $config_config->base_url) >= 2)
            {
                preg_match('/^(.*?)(:\d+)?$/', $config_config->base_url, $matches);
                setcookie($this->sid, $cookie_id, time()+$this->lifetime, '/', '.'.$matches[1]);
            }
            else
            {
                setcookie($this->sid, $cookie_id, time()+$this->lifetime, '/');
            }

            return $cookie_id;
        }

        public function loadSessionData ()
        {
            $current_session = Session
                ::session_id_is_eq($this->session_id)
                ->created_at_is_gte(\date ('Y-m-d H:i:s', date('U') - $this->lifetime))
                ->updated_at_is_gte(\date ('Y-m-d H:i:s', date('U') - $this->timeout))
            ->first();

            if ( ! $current_session || ! $this->session_id)
            {
                $this->session_id = $this->generateCookie();

                $current_session = new Session(array(
                    'session_id'        => $this->session_id,
                    'created_at'        => \date ('Y-m-d H:i:s'),
                    'updated_at'        => \date ('Y-m-d H:i:s'),
                    'data'              => \serialize(array()),
                    'user_agent'        => $this->user_agent,
                ));
                
                $current_session->save();
            }

            $this->session_data = unserialize($current_session->data);
            $this->session_object_id = $current_session->id;
            
            $this->setToDeleteFlashData();
        }

        public function getSessionObjectId(){
            return $this->session_object_id;
        }

        public function saveSessionData ()
        {
            $this->deleteFlashData();

            $current_session = Session::session_id_is_eq($this->session_id)->first();
            
            if ($current_session)
            {
                $current_session->data = serialize($this->session_data);
                $current_session->updated_at = \date ('Y-m-d H:i:s');
                $current_session->save();
            }
        }

        public function clearGarbage()
        {
            foreach (array_merge(
                Session::created_at_is_lt(\date ('Y-m-d H:i:s', date('U') - $this->lifetime))->all(),
                Session::updated_at_is_lt(\date ('Y-m-d H:i:s', date('U') - $this->timeout))->all())
                as $old_session) {
                $old_session->destroy();
            }
        }

        public function destroy ()
        {
            foreach (Session::session_id_is_eq($this->session_id)->all() as $session)
            {
                $session->destroy();
            }
        }

        private function deleteFlashData ()
        {
            $new_data = array();

            foreach ($this->session_data as $key => $data)
            {
                if ($data['to_delete'] == FALSE)
                {
                    $new_data[$key] = array (
                        'type'      => $data['type'],
                        'to_delete' => FALSE,
                        'value'     => $data['value'],
                    );
                }
            }

            $this->session_data = $new_data;
        }

        private function setToDeleteFlashData ()
        {
            $new_data = array();
            if (count($this->session_data))
            {
                foreach ($this->session_data as $key => $data)
                {
                    $to_delete = FALSE;

                    if ($data['type'] == 'flash')
                    {
                        $to_delete = TRUE;
                    }

                    $new_data[$key] = array (
                        'type'      => $data['type'],
                        'to_delete' => $to_delete,
                        'value'     => $data['value'],
                    );
                }
            }

            $this->session_data = $new_data;
        }

        public function set ($key, $value, $is_flash = FALSE)
        {
            if ($is_flash === TRUE)
            {
                $type   = 'flash';
            }
            else
            {
                $type   = 'normal';
            }

            $this->session_data[$key] = array (
                'type'      => $type,
                'to_delete' => FALSE,
                'value'     => $value,
            );
        }

        public function get ($key)
        {
            return isset($this->session_data[$key]['value']) ? $this->session_data[$key]['value'] : FALSE;
        }

        public function getAll ()
        {
            $return_array = array ();

            foreach ($this->session_data as $key => $data)
            {
                $return_array[$key] = $data['value'];
            }

            return $return_array;
        }

        public function keepFlash ($key)
        {
            $this->set($key, $this->get($key), TRUE);
        }

        public function keepFlashAll ()
        {
            $new_data = array();

            foreach ($this->session_data as $key => $data)
            {
                $new_data[$key] = array (
                    'type'      => $data['type'],
                    'to_delete' => FALSE,
                    'value'     => $data['value'],
                );
            }

            $this->session_data = $new_data;
        }

        public function delete ($key)
        {
            unset($this->session_data[$key]);
        }


        static public function getInstance()
        {
            if( ! self::$instance)
            {
                self::$instance = new Base();
            }

            return self::$instance;
        }
    }
