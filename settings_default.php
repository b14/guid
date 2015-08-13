<?php

// The private hash prefix (salt)
define('GUID_PREFIX', 'enter a private prefix for the hash');

// Secret reset stats password
define('GUID_RESET_STATS_SECRET', 'shh');

// Versioning
define('VERSION_MAJOR', '1');
define('VERSION_MINOR', '0');
define('VERSION_PREFIX', 'GUID Service: ');
define('VERSION_SUFFIX', '-rc1');

// Optional memcache settings
define('MEMCACHE_STATUS', TRUE);
define('MEMCACHE_PREFIX', 'guidservice');
define('MEMCACHE_HOST', 'localhost');
define('MEMCACHE_PORT', 11211);
