<?php

/**
* YF Session driver abstract
*/
abstract class yf_session_driver {
	abstract protected function open($path, $name);
	abstract protected function close();
	abstract protected function read($ses_id);
	abstract protected function write($ses_id, $data);
	abstract protected function destroy($ses_id);
	abstract protected function gc($life_time);
}
