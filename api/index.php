<?php

// Kickstart the framework
$f3=require('../libs/fatfree/lib/base.php');


$f3->route("GET /hola/test/@iduser", function ($f3){
	echo $f3->get("PARAMS.iduser");
	echo "ruta 24";
});

$f3->route("GET /", function ($f3){
	
	// MySql settings
	/*$f3->set('DB', new DB\SQL(
			'mysql:host=localhost;port=3306;dbname=simplerisk',
			'simplerisk',
			'simplerisk'
			));*/
	
	$db		= new DB\SQL(
			'mysql:host=localhost;port=3306;dbname=simplerisk',
			'simplerisk',
			'simplerisk'
			);
	
	$table = new DB\SQL\Mapper($db, 'assets');
	$table->load(array('id=?', '1'));
	$result = $table->created;
	
	echo $result;
	
});



	
	
$f3->run();
