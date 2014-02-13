<?php

/**
* YF Session driver abstract
*/
abstract class yf_session_driver {
	abstract protected function _open($path, $name);
	abstract protected function _close();
	abstract protected function _read($ses_id);
	abstract protected function _write($ses_id, $data);
	abstract protected function _destroy($ses_id);
	abstract protected function _gc($life_time);
}
