<?php

function _fast_captcha_image () {
	main()->NO_GRAPHICS = true;
	_class('captcha')->show_image();
	return true; // Means success
}
