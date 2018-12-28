<?php

class test_mod
{
    public function show()
    {
        foreach (range(1, 10) as $i) {
            $data[$i] = [
                'id' => $i,
                'name' => 'name_' . $i,
            ];
        }
        return tpl()->parse_string('{foreach("data")} <li>{if("#.id" mod 4)}_MOD_{/if} {#.name}</li> {/foreach}', ['data' => $data]);
        //	<li>{if("#.id" mod 4)}_MOD_{/if} {if("#.id" mod 3 or "#.id" mod 5)}!!!{/if} {#.name}</li>
    }
}
