<?php

//header("Content-encoding: utf8");

//include "validation.php";
//include "unicode.php";
//include "specials.php";
//include "bad.php";
include "ascii.php";

$examples["MY_TEST"] = file_get_contents("broken_utf8.txt");

echo $examples["MY_TEST"];
echo "<br /><hr />\n\n";

//echo (int)utf8_is_valid($examples["MY_TEST"]);
//echo "<br /><hr />\n\n";
//echo utf8_strip_specials($examples["MY_TEST"]);
//echo "<br /><hr />\n\n";
//echo utf8_bad_strip($examples["MY_TEST"]);
//echo "<br /><hr />\n\n";
//echo utf8_strip_ascii_ctrl($examples["MY_TEST"]);
//echo "<br /><hr />\n\n";
echo utf8_accents_to_ascii($examples["MY_TEST"]);
echo "<br /><hr />\n\n";
echo utf8_strip_non_ascii(utf8_accents_to_ascii($examples["MY_TEST"]));
echo "<br /><hr />\n\n";
echo utf8_strip_ascii_ctrl(utf8_strip_non_ascii(utf8_accents_to_ascii($examples["MY_TEST"])));
echo "<br /><hr />\n\n";

//echo "<br />\n\n";
//echo $examples["MY_TEST"];

/*
echo "<pre>\n";
echo "++Invalid UTF-8 in pattern\n";
foreach ( $examples as $name => $str ) {
    echo "$name\n";
    @preg_match("/".$str."/u",'Testing');
}

echo "++ preg_match() examples\n";
foreach ( $examples as $name => $str ) {
    
    @preg_match("/\xf8\xa1\xa1\xa1\xa1/u", $str, $ar);
    echo "$name: ";

    if ( count($ar) == 0 ) {
        echo "Matched nothing!\n";
    } else {
        echo "Matched {$ar[0]}\n";
    }
    
}

echo "++ preg_match_all() examples\n";
foreach ( $examples as $name => $str ) {
    preg_match_all('/./u', $str, $ar);
    echo "$name: ";
    
    $num_utf8_chars = count($ar[0]);
    if ( $num_utf8_chars == 0 ) {
        echo "Matched nothing!\n";
    } else {
        echo "Matched $num_utf8_chars character\n";
    }
    
}
*/
