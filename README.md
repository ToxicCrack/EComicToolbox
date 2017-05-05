# eComic and eManga Toolbox
A toolbox for creating .cbz files. Handling book chapters and volumes.
<br /><br />
INFO: Only tested on Windows 10. But should be work on Linux too.
<br /><br />
With this script you can easily create Comic/Manga .cbz files and volumes from chapters downloaded from the web (i.e. mangafox).
<br/>
## Features
- Mass extract .cbz into folders.
- Mass compress contents of folders in separate .cbz.
- Mass rename of pages (i.e. 1.jpg -> P001.jpg) in unlimited folders.
- Mass creation of book volumes from chapter-folders (chapter1/, chapter2/,.. -> Volume 1)

## Prerequisites (Windows)
- Download PHP http://www.php.net and unzip it (in an easy-to-reach folder, like c:\php)
- Download and install 7-Zip http://www.7-zip.org/ (Default: c:\Program Files\7-Zip\ otherwise adjust the path in the script)
- Copy eComicToolbox.php to the directory with your .cbz chapters oder folders.
- Your chapter-directories or chapter-cbz's should be named as "0001 Comicname" or "1 Comicname" etc. First part should be a number.

# Examples
Open cmd.exe, navigate to your directory in which your files are (.cbz's or directories of the book chapters).
Your chapter-directories or chapter-cbz's should be named as "0001 - Comicname". The important part is the numbering at the first place. It can be "1 - Comicname" but not "Comicname - 1". In that case, please use a mass-renamer tool.

## Mass extract .cbz to folders
<code>c:\php\php.exe eComicToolbox.php unzipIntoFolders</code>
<br />
<br />
Assuming you have .cbz's "001 MyComic.cbz" to "056 MyComic.cbz" in the directory with the script. It will create directories "001 MyComic" to "056 MyComic".

## Mass compress folders to .cbz
<code>c:\php\php.exe eComicToolbox.php zipFolders</code>
<br />
<br />
Assuming you have folders "001 MyComic" to "056 MyComic" in the directory with the script. It will create "001 MyComic.cbz" to "056 MyComic.cbz" inside a new directory (./new by default).

## Mass rename pages (add paddings)
<code>c:\php\php.exe eComicToolbox.php addPaddings</code>
<br />
<br />
Assuming you have extracted your .cbz files and have now chapter-folders which including the images from the respective chapter.<br />
This method reads all folders and adds an padding to the filename (filnames must be numeric!). I.e.: 2.jpg -> P002.jpg. The files getting replaced!

## Creating volumes from chapters
<code>c:\php\php.exe eComicToolbox.php makeVolumes -comicName=MyComic</code>
<br />
<br />
Assuming you have extracted your .cbz files and have now chapter-folders which including the images from the respective chapter.<br />
This method reads the volumelist.txt to determine which chapters are in one volume. <br /><br />
(The content of volumelist.txt is simply copy&pasted from http://mangafox.me/ . Choose an comic/manga and scroll down. There is a most of the time a volume overview. Collapse all expanded tabs and copy the text.)<br /><br />
The chapters and volumes in the volumelist doesn't have to be zero-padded. It's fine how it is in my example.<br />
So, it puts the contents of the chapter-directories in a new volume directory (all this under ./volumes) and renames the pages accordingly.
<br />After that, it creates .cbz's of your volumes prefixed which whatever you typed at -comicName (default: Comic) -> Comic 001.cbz

# Developers
Feel free to modify the script to your needs. The most important settings are public class properties.
