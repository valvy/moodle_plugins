<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

// Debugging
$CFG->debug = (E_ALL | E_STRICT);
$CFG->debugdisplay = true;

ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr'); // Log naar container stderr

// Database instellingen
$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'mariadb';
$CFG->dbname    = 'bitnami_moodle';
$CFG->dbuser    = 'bn_moodle';
$CFG->dbpass    = '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => 3306,
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_unicode_ci',
);

// WWW root automatisch bepalen (werkt voor dev / compose setup)
if (empty($_SERVER['HTTP_HOST'])) {
  $_SERVER['HTTP_HOST'] = '127.0.0.1:8080';
}
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
  $CFG->wwwroot = 'https://' . $_SERVER['HTTP_HOST'];
} else {
  $CFG->wwwroot = 'http://' . $_SERVER['HTTP_HOST'];
}

// Pad naar moodledata
$CFG->dataroot  = '/bitnami/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 02775;

require_once(__DIR__ . '/lib/setup.php');

// Geen sluitende PHP-tag om whitespace errors te voorkomen

