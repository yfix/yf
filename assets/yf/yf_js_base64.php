<?php

// based on: http://phpjs.org/functions/base64_encode/
return [
    'versions' => ['master' => [
        'js' => <<<'END'
	function yf_base64_encode_safe(data) {
		var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
		var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
			ac = 0,
			enc = "",
			tmp_arr = [];

		if (!data) {
			return data;
		}
		do { // pack three octets into four hexets
			o1 = data.charCodeAt(i++);
			o2 = data.charCodeAt(i++);
			o3 = data.charCodeAt(i++);

			bits = o1 << 16 | o2 << 8 | o3;

			h1 = bits >> 18 & 0x3f;
			h2 = bits >> 12 & 0x3f;
			h3 = bits >> 6 & 0x3f;
			h4 = bits & 0x3f;

			// use hexets to index into b64, and append result to encoded string
			tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
		} while (i < data.length);

		enc = tmp_arr.join("");
		var r = data.length % 3;
		var out = (r ? enc.slice(0, r - 3) : enc) + "===".slice(r || 3);

		// for safe uri encode: we use "*" sign instead of "/" and "%20" instead of "+"
		return out.replace("+","-").replace("/","*");
	}
END
    ]],
];
