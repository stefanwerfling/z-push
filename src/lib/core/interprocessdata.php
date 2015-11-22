<?php
/***********************************************
* File      :   interprocessdata.php
* Project   :   Z-Push
* Descr     :   Class takes care of interprocess
*               communicaton for different purposes
*               using a backend implementing IIpcBackend
*
* Created   :   20.10.2011
*
* Copyright 2007 - 2013 Zarafa Deutschland GmbH
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License, version 3,
* as published by the Free Software Foundation with the following additional
* term according to sec. 7:
*
* According to sec. 7 of the GNU Affero General Public License, version 3,
* the terms of the AGPL are supplemented with the following terms:
*
* "Zarafa" is a registered trademark of Zarafa B.V.
* "Z-Push" is a registered trademark of Zarafa Deutschland GmbH
* The licensing of the Program under the AGPL does not imply a trademark license.
* Therefore any rights, title and interest in our trademarks remain entirely with us.
*
* However, if you propagate an unmodified version of the Program you are
* allowed to use the term "Z-Push" to indicate that you distribute the Program.
* Furthermore you may use our trademarks where it is necessary to indicate
* the intended purpose of a product or service provided you use it in accordance
* with honest practices in industrial or commercial matters.
* If you want to propagate modified versions of the Program under the name "Z-Push",
* you may only do so if you have a written permission by Zarafa Deutschland GmbH
* (to acquire a permission please contact Zarafa at trademark@zarafa.com).
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* Consult LICENSE file for details
************************************************/

abstract class InterProcessData {
    const CLEANUPTIME = 1;

    static protected $devid;
    static protected $pid;
    static protected $user;
    static protected $start;
    protected $type;
    protected $allocate;

	/**
	 *
	 * @var IIpcBackend
	 */
	private $backend;

	/**
     * Constructor
     *
     * @access public
     */
    public function __construct() {
        if (!isset($this->type) || !isset($this->allocate))
            throw new FatalNotImplementedException(sprintf("Class InterProcessData can not be initialized. Subclass %s did not initialize type and allocable memory.", get_class($this)));

		$ipc_backend = defined('IPC_BACKEND_CLASS') ? IPC_BACKEND_CLASS : 'IpcBackendShm';

		// until z-push autoloads, manually load IpcBackend
		if (!class_exists($ipc_backend))
		{
			include_onced('lib/core/'.strtolower($ipc_backend));
		}

		try {
			$this->backend = new $ipc_backend($this->type, $this->allocate, get_class($this));
		}
		catch (Exception $e) {
			// backend could not initialise
			ZLog::Write(LOGLEVEL_ERROR, __METHOD__."() could not initialise IPC backend '$ipc_backend': ".$e->getMessage());
		}
    }

    /**
     * Initializes internal parameters
     *
     * @access public
     * @return boolean
     */
    public function InitializeParams() {
        if (!isset(self::$devid)) {
            self::$devid = Request::GetDeviceID();
            self::$pid = @getmypid();
            self::$user = Request::GetAuthUser();
            self::$start = time();
        }
        return true;
    }

    /**
     * Cleans up the shared memory block
     *
     * @access public
     * @return boolean
     */
    public function Clean() {
		return $this->backend ? $this->backend->Clean() : false;
    }

    /**
     * Indicates if the shared memory is active
     *
     * @access public
     * @return boolean
     */
    public function IsActive() {
        return $this->backend ? $this->backend->IsActive() : false;
    }

    /**
     * Blocks the class mutex
     * Method blocks until mutex is available!
     * ATTENTION: make sure that you *always* release a blocked mutex!
     *
     * @access protected
     * @return boolean
     */
    protected function blockMutex() {
        return $this->backend ? $this->backend->blockMutex() : false;
    }

    /**
     * Releases the class mutex
     * After the release other processes are able to block the mutex themselfs
     *
     * @access protected
     * @return boolean
     */
    protected function releaseMutex() {
        return $this->backend ? $this->backend->releaseMutex() : false;
    }

    /**
     * Indicates if the requested variable is available in shared memory
     *
     * @param int   $id     int indicating the variable
     *
     * @access protected
     * @return boolean
     */
    protected function hasData($id = 2) {
        return $this->backend ? $this->backend->hasData($id) : false;
    }

    /**
     * Returns the requested variable from shared memory
     *
     * @param int   $id     int indicating the variable
     *
     * @access protected
     * @return mixed
     */
    protected function getData($id = 2) {
        return $this->backend ? $this->backend->getData($id) : null;
    }

    /**
     * Writes the transmitted variable to shared memory
     * Subclasses may never use an id < 2!
     *
     * @param mixed $data   data which should be saved into shared memory
     * @param int   $id     int indicating the variable (bigger than 2!)
     *
     * @access protected
     * @return boolean
     */
    protected function setData($data, $id = 2) {
        return $this->backend ? $this->backend->setData($data, $id) : false;
    }
}
