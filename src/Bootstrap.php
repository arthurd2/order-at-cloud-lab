<?php

require_once "libs/order-at-cloud/src/Bootstrap.php";

$root = getcwd();
$folders = ['src/basics', 'src/interfaces','src/helpers','src/model', 'src/rules', 'src/qualifiers', 'src/costs'];
foreach ($folders as $folder) 
	foreach (glob("$root/$folder/*.php") as $filename) 
		require_once "$filename";

require_once "tests/dummyClasses.php";
Counter::$start = time()-1;


