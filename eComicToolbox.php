<?php
/*
MIT License

Copyright (c) 2017 Daniel Lichtblau

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/
/**
* Toolbox for .cbz eComics
* @author Daniel Lichtblau
* @link http://www.lichtblau-it.de
**/
define('DS', DIRECTORY_SEPARATOR);

class EComicToolbox {
	//General
	public $path = '.'; //operating path (default '.')
	public $blacklist = array(); //Exclude dirs
	public $comicName = "Comic";
	
	//Zipping
	public $zipIntoSubdir = 'new'; //New .cbz's getting created there
	public $zipCmd = '"c:\Program Files\7-Zip\7z.exe"';
	
	//Padding
	public $numberPadding = 3; //How many digits the filename should have (default 3 -> 001.jpg, 014.jpg etc)
	public $paddingPrefix = "P"; //Pages getting this prefix (default "P" -> P001.jpg)
	
	//Volumes
	public $volumeListFormat = "Volume %volume Chapter %chapter_start \- %chapter_end"; //Represents the format of one line in volumelist.txt (Default: "Volume %volume Chapter %chapter_start \- %chapter_end")
	public $volumesSubdir = 'volumes'; //Volumes getting created there
	
	//Positions for preg_match
	public $volumeIndex = 1; //Position of the number of the volume in $volumeListFormat
	public $chapterStartIndex = 2; //Position of the number of the chapter start in $volumeListFormat
	public $chapterEndIndex = 3; //Position of the number of the chapter end in $volumeListFormat
	
	//private
	private $volumeListRegex;
	
	public function __construct($argv) {
		$this->blacklist = array('.', '..', $this->zipIntoSubdir, $this->volumesSubdir);
		$this->volumeListRegex = str_replace(array("%volume", "%chapter_start", "%chapter_end"), array('([0-9]*)', '([0-9]*)', '([0-9]*)'), $this->volumeListFormat);
		
		$argvValues = $this->extractArgv($argv);
		if(isset($argvValues['comicname'])) {
			$this->comicName = $argvValues['comicname'];
		}
	}
	
	/**
	* Reads argv parameter (key=value) and puts it in an array
	**/
	private function extractArgv($argv) {
		$keyValues = array();
		foreach($argv as $i => $val) {
			if(strpos(strtolower($val), '=') !== false) {
				$val = trim($val, '-');
				$vals = explode("=", $val);
				$keyValues[trim(strtolower($vals[0]))] = trim($vals[1]);
			}
		}
		return $keyValues;
	}
	/**
	* Zips all contents from directories in current directory in separate .cbz files, named like the directory.
	**/
	public function zipFolders($path=null, $zipIntoSubdir=null) {
		if(!$path) $path = $this->path;
		if($zipIntoSubdir === null) $zipIntoSubdir = $this->zipIntoSubdir;
		
		if ($handle = opendir($path)) {
			if(!is_dir($path.DS.$zipIntoSubdir)) mkdir($path.DS.$zipIntoSubdir);
			while (false !== ($dir = readdir($handle))) {
				if (!in_array($dir, $this->blacklist) && is_dir($path.DS.$dir)) {
					$files = array_diff(scandir($path.DS.$dir), array('..', '.'));
					
					//There may be to many files to zip them in one command -> Split them up
					$cmd = $this->zipCmd.' a -y -tzip "'.$path.DS.$zipIntoSubdir.DS.$dir.'.cbz" ';
					$fileStr = "";
					$numFiles = count($files);
					foreach($files as $i => $file) {
						$fileStr .= '"'.$path.DS.$dir.DS.$file.'" ';
						if($i % 100 === 0 || $i+1 == $numFiles) {
							//Batch of 100 files at one time
							exec($cmd.$fileStr);
							$fileStr = "";
						}
					}
					//var_dump($cmd);
					echo "Zipping: ".$dir.".cbz\n";
				}
			}
			echo "DONE\n";
			closedir($handle);
		}
	}
	/**
	* Unzips .cbz from the current folder in separate directories
	**/
	public function unzipIntoFolders() {
		if ($handle = opendir($this->path)) {
			while (false !== ($file = readdir($handle))) {
				if (!in_array($file, $this->blacklist) && strpos($file, '.cbz') !== false) {
					//var_dump($file);
					$exDir = substr($file, 0, -4);
					if(!is_dir($this->path.DS.$exDir)) mkdir($this->path.DS.$exDir);
					$cmd = 'cd "'.$this->path.DS.$exDir.'" && '.$this->zipCmd.' -y x "'.$this->path.DS.'..'.DS.$file.'"';
					echo $cmd."\n";
					exec($cmd);
				}
			}
			echo "DONE\n";
			closedir($handle);
		}
	}
	/**
	* Adds number paddings (001) to files from all directories inside the current directory.
	**/
	public function addPaddings() {
		if ($handle = opendir($this->path)) {
			while (false !== ($dir = readdir($handle))) {
				if (!in_array($dir, $this->blacklist) && is_dir($this->path.DS.$dir)) {
					$files = array_diff(scandir($this->path.DS.$dir), array('..', '.'));
					sort($files, SORT_NATURAL);
					foreach($files as $file) {
						$ext = strrchr($file, '.');
						$newFilename = $this->paddingPrefix.sprintf('%0'.$this->numberPadding.'d', $file).$ext;
						echo $newFilename."\n";
						rename($this->path.DS.$dir.DS.$file, $this->path.DS.$dir.DS.$newFilename);
					}
				}
			}
			echo "DONE\n";
			closedir($handle);
		}
	}
	/**
	* Reads volumelist.txt and creates the Volumes as .cbz. Requires chapter-directories containing images.
	**/
	public function makeVolumes() {
		$fp = fopen($this->path.DS."volumelist.txt", "r");
		if($fp) {
			$chapterFolders = array();
			$lineNumber = 1;
			while(($line = fgets($fp)) !== false) {
				$data = array();
				if(preg_match('~'.$this->volumeListRegex.'~', $line, $data) == 1) {
					$volume = (int)$data[$this->volumeIndex];
					$chapterStart = (int)$data[$this->chapterStartIndex];
					$chapterEnd = (int)$data[$this->chapterEndIndex];
					
					$chapterFolders[$volume]['chapterStart'] = $chapterStart;
					$chapterFolders[$volume]['chapterEnd'] = $chapterEnd;
					
					//echo "Vol. $volume - From Chapter $chapterStart to $chapterEnd\n";
					
					if ($handle = opendir($this->path)) {
						while (false !== ($dir = readdir($handle))) {
							if (!in_array($dir, $this->blacklist) && is_dir($this->path.DS.$dir)) {
								$directoryChapter = (int)$dir;
								$matches = array();
								preg_match('~(\d+)~', $dir, $matches);
								if(isset($matches[1]) && is_numeric($matches[1])) {
									$directoryChapter = $matches[1];
								}
								if($directoryChapter >= $chapterStart && $directoryChapter <= $chapterEnd) {
									$chapterFolders[$volume]['folders'][] = $this->path.DS.$dir;
								}
							}
						}
					}
				} else {
					echo "ERROR: Couldn't match regex for volumelist.txt Line $lineNumber.\n";
				}
				$lineNumber++;
			}
			foreach($chapterFolders as $vol => $folders) {
				echo "\nVol.$vol contains ({$folders['chapterStart']} - {$folders['chapterEnd']}):\n\t";
				if(!empty($folders['folders'])) {
					sort($folders['folders'], SORT_NATURAL);
					echo implode("\n\t", $folders['folders']);
				}
			}
			if(!empty($chapterFolders)) {
				echo "\nIs that correct? (y/N) ";
				$handle = fopen ("php://stdin","r");
				$line = fgets($handle);
				if(trim($line) != 'y'){
					echo "ABORTING!\n";
					exit;
				}
				fclose($handle);
				if(!is_dir($this->path.DS.$this->volumesSubdir)) mkdir($this->path.DS.$this->volumesSubdir);
				
				foreach($chapterFolders as $vol => $folders) {
					$volumeDirName = $this->comicName.' '.sprintf('%0'.$this->numberPadding.'d', $vol);
					$volumeDir = $this->path.DS.$this->volumesSubdir.DS.$volumeDirName;
					
					$pagenumber = 1;
					if(!empty($folders['folders'])) {
						sort($folders['folders'], SORT_NATURAL);
						foreach($folders['folders'] as $chapterFolder) {
							$files = array_diff(scandir($chapterFolder), array('..', '.'));
							if(!empty($files)) {
								if(!is_dir($volumeDir)) mkdir($volumeDir);
								sort($files, SORT_NATURAL);
								foreach($files as $filename) {
									$ext = strrchr($filename, '.');
									$newFilename = $this->paddingPrefix.sprintf('%0'.$this->numberPadding.'d', $pagenumber).$ext;
									echo "Copy ".$chapterFolder.DS.$filename." -> ".$volumeDir.DS.$newFilename."\n";
									copy($chapterFolder.DS.$filename, $volumeDir.DS.$newFilename);
									$pagenumber++;
								}
							}
						}
					}
				}
				
				echo "MakeVolumes DONE\n";
				$this->zipFolders($this->path.DS.$this->volumesSubdir, '');
			}
			//var_dump($chapterFolders);
			fclose($fp);
		}
	}
}
$toolbox = new EComicToolbox($argv);

switch($argv[1]) {
	case 'zipFolders':
	case '-zipFolders':
		$toolbox->zipFolders();
	break;
	case 'unzipIntoFolders':
	case '-unzipIntoFolders':
		$toolbox->unzipIntoFolders();
	break;
	case 'addPaddings';
	case '-addPaddings';
		$toolbox->addPaddings();
	break;
	case 'makeVolumes':
	case '-makeVolumes':
		$toolbox->makeVolumes();
	break;
	case 'autovolume':
		$toolbox->unzipIntoFolders();
		$toolbox->makeVolumes();
	break;
	default:
		echo "\nUsage:\n";
		echo "GENERAL\n";
		echo "\t-comicName=Comic : Sets the name of the comic. Used for directory naming.\n\n";
		echo "METHODS:\n";
		echo "\t-zipFolders : Zips all contents from directories in current directory in separate .cbz files, named like the directory.\n";
		echo "\t-unzipIntoFolders : Unzips .cbz from the current folder in separate directories\n";
		echo "\t-addPaddings : Adds number paddings (001) to files from all directories inside the current directory.\n";
		echo "\t-makeVolumes : Reads volumelist.txt and creates the Volumes as .cbz. Requires chapter-directories containing images. (Converts filenames automatically)\n";
	break;
}
?>
