<?php

/*
 * Constantes
 */

define('SERVER_BDD', 'localhost');
define('USER_BDD', 'root');
define('MDP_BDD', '');
define('BDD', 'twitr2');
define('INC_DIR',APP_PATH.DIRECTORY_SEPARATOR.'includes');
define('IMG_LINK','img');
define('IMG_DIR',APP_PATH.DIRECTORY_SEPARATOR.IMG_LINK);
$error = '';
$tab_favoris = array();

$favoris = 0;

$tabAction = array(
    'fv'=>FILTER_VALIDATE_INT,
    'nfv'=>FILTER_VALIDATE_INT,
    'rt'=> FILTER_VALIDATE_INT,
    'nrt'=> FILTER_VALIDATE_INT,
    'origin'=>FILTER_VALIDATE_INT
    );
$Twit = '';
$progression = 15;

$page = 'default';


$prev = 15;
$next = 0;
$pas = 0;
