#!/usr/bin/php
<?php

/*
Place this script in the PF framework folder (PF-x.y.z.rxxx/PF-phar.php) and run it
to package the framework into a single phar file.

In the "index.php" of your application, assuming you placed the packaged framework under
your application's "protected" folder, add the following line at the top:

new Phar(dirname(__FILE__).'/protected/PF-1.1.9.r3527.phar');

This makes the framework available via the phar:// stream-wrapper - when specifying the
path to the PF framework, set it as "phar://PF".
*/

// Configuration:

$dir = dirname(__FILE__);

ini_set("phar.readonly", 0); // Could be done in php.ini

#$path = $dir.'/yf';
$path = "/home/www/yf/";

$name = 'text_wiki.phar';
echo $name;
$mode = Phar::GZ;

// Error checks:

if (!class_exists('Phar')) {
  die('*** Phar extension is not installed (or not enabled)');
}

if (!Phar::canCompress($mode)) {
  die('*** Compression unsupported - please enable the zlib extension');
}

if (!is_dir($path)) {
  die('*** PF Framework not found: '.$path);
}

if (!Phar::canWrite()) {
  die('*** Phar is in read-only mode (check phar.readonly in php.ini)');
}

// Iterator:

class FrameworkIterator implements Iterator, Countable {
  
  private $index;
  private $files;
  private $baselen;
  private $size;
  private $mask;
  private $dirs;
  
  public function __construct($path, $mask = '*') {
	$this->index = 0;
	$this->files = array();
	$this->baselen = strlen($path) + 1;
	$this->size = 0;
	$this->mask = $mask;
	$this->dirs = array();
	
	$this->scan($path);
  }

  private function scan($path) {
	global $phar, $baselen, $total;
	
	foreach (glob($path.'/'.$this->mask) as $file) {
	  if (is_dir($file)) {
		$this->dirs[ $this->getRelative($file) ] = $file;
		$this->scan($file);
	  } else {
#		if (substr($file, -10) != ".class.php") {
#		if (substr($file, -4) != ".php") {
#			continue;
#		}
echo $file."\n";
		$this->size += filesize($file);
		$this->files[] = $file;
	  }
	}
  }
  
  private function getRelative($path) {
	return substr($path, $this->baselen);
  }
  
  public function rewind() {
	$this->index = 0;
  }

  public function current() {
	return $this->files[$this->index];
  }

  public function key() {
	return $this->getRelative($this->files[$this->index]);
  }

  public function next() {
	$this->index += 1;
  }

  public function valid() {
	return isset($this->files[$this->index]);
  }
  
  public function count() {
	return count($this->files);
  }
  
  public function getSize() {
	return $this->size;
  }
  
  public function getDirs() {
	return $this->dirs;
  }
}

// Build and Compress:

echo "Creating archive: $name\n\n";

if (file_exists($name))
  unlink($name);


$phar = new Phar($name, 0, 'pf');


$iter = new FrameworkIterator($path);

echo "Building: ".number_format(count($iter)).' files in '.number_format(count($iter->getDirs())).' folders ('.number_format($iter->getSize())." bytes) ...\n\n";

$phar->buildFromIterator($iter);

echo "Compressing files ...\n\n";

#$phar->compressFiles($mode);
#$phar->compress(Phar::BZ2);
$filesize = filesize($name);

echo "Output: ".number_format($filesize)." bytes (".sprintf('%0.2f', $filesize*100 / $iter->getSize())."%)\n\n";
