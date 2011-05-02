<?

require('database.php');

var_dump(pg_unescape_bytea(pg_unescape_bytea(pg_escape_bytea('äöü')))); // ???