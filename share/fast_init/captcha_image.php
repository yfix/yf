<?php

return function() {
	main()->NO_GRAPHICS = true;
	_class('captcha')->show_image();
	return true; // Means success
};
