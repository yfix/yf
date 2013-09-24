<?php

class test_mod {
	function show() {
		foreach (range(1,10) as $i) {
			$data[$i] = array(
				'id' 	=> $i,
				'name'	=> 'name_'.$i,
			);
		}
		return tpl()->parse_string('_test_', array('data' => $data), '
{foreach("data")}
	<li>{if("#.id" mod 4)}_MOD_{/if} {#.name}</li>
{/foreach}
		');
#	<li>{if("#.id" mod 4)}_MOD_{/if} {if("#.id" mod 3 or "#.id" mod 5)}!!!{/if} {#.name}</li>
	}
}
