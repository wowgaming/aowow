# acore-docker credentials
echo "
<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');


\$AoWoWconf['aowow'] = array (
  'host' => '127.0.0.1:63306',
  'user' => 'root',
  'pass' => 'password',
  'db' => 'tmp_aowow',
  'prefix' => 'aowow_',
);

\$AoWoWconf['world'] = array (
  'host' => '127.0.0.1:63306',
  'user' => 'root',
  'pass' => 'password',
  'db' => 'acore_world',
  'prefix' => '',
);

\$AoWoWconf['auth'] = array (
  'host' => '127.0.0.1:63306',
  'user' => 'root',
  'pass' => 'password',
  'db' => 'acore_auth',
  'prefix' => '',
);

\$AoWoWconf['characters']['1'] = array (
  'host' => '127.0.0.1:63306',
  'user' => 'root',
  'pass' => 'password',
  'db' => 'acore_characters',
  'prefix' => '',
);

?>
" >> ../config/config.php

mysql -u root -ppassword -h 127.0.0.1 -P 63306 -e "CREATE DATABASE tmp_aowow;"
mysql -u root -ppassword -h 127.0.0.1 -P 63306 tmp_aowow < db_structure.sql
mysql -u root -ppassword -h 127.0.0.1 -P 63306 acore_world < spell_learn_spell.sql

cd ..

mkdir -p setup/mpqdata/enus/DBFilesClient/

wget https://github.com/wowgaming/client-data/releases/download/v16/data.zip > /dev/null 2>&1
unzip data.zip "dbc/*" -d ./ > /dev/null 2>&1
mv dbc/* "setup/mpqdata/enus/DBFilesClient/"

mysql -u root -ppassword -h 127.0.0.1 -P 63306 tmp_aowow -e "SET GLOBAL range_optimizer_max_mem_size=0;"
mysql -u root -ppassword -h 127.0.0.1 -P 63306 acore_world -e "SET GLOBAL range_optimizer_max_mem_size=0;"
mysql -u root -ppassword -h 127.0.0.1 -P 63306 tmp_aowow -e "UPDATE aowow_config SET value='3' WHERE \`key\`='debug';"
mysql -u root -ppassword -h 127.0.0.1 -P 63306 tmp_aowow -e "UPDATE aowow_config SET value='1' WHERE \`key\`='locales';" # EN locale

php aowow --sql

mysqldump -u root -ppassword -h 127.0.0.1 -P 63306 tmp_aowow --ignore-table=tmp_aowow.aowow_config > aowow_update.sql
mysqldump -u root -ppassword -h 127.0.0.1 -P 63306 acore_world > acore_world.sql
# zip aowow_db.sql.zip aowow_update.sql acore_world.sql
