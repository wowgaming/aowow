<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');

if (!CLI)
    die('not in cli mode');


CLISetup::registerSetup("sql", new class extends SetupScript
{
    protected $info = array(
        'itemenchantment' => [[], CLISetup::ARGV_PARAM, 'Compiles data for type: Enchantment from dbc and world db.']
    );

    protected $dbcSourceFiles  = ['spellitemenchantment'];
    protected $worldDependency = ['spell_enchant_proc_data'];

    public function generate(array $ids = []) : bool
    {
        DB::Aowow()->query('TRUNCATE ?_itemenchantment');
        DB::Aowow()->query(
           'INSERT INTO ?_itemenchantment
            SELECT      `Id`, `charges`, 0, 0, 0, `type1`, `type2`, `type3`, `amount1`, `amount2`, `amount3`, `object1`, `object2`, `object3`, `name_loc0`, `name_loc2`, `name_loc3`, `name_loc4`, `name_loc6`, `name_loc8`, `conditionId`, `skillLine`, `skillLevel`, `requiredLevel`
            FROM        dbc_spellitemenchantment'
        );

        //  $cuProcs = DB::World()->select('SELECT `EnchantID` AS ARRAY_KEY, `Chance` AS `procChance`, `ProcsPerMinute` AS `ppmRate` FROM spell_enchant_proc_data');  // TC
        $cuProcs = DB::World()->select('SELECT `entry` AS ARRAY_KEY, customChance AS procChance, PPMChance AS ppmRate FROM spell_enchant_proc_data'); // AC
        foreach ($cuProcs as $id => $vals)
            DB::Aowow()->query('UPDATE ?_itemenchantment SET ?a WHERE `id` = ?d', $vals, $id);

        // hide strange stuff
        DB::Aowow()->query('UPDATE ?_itemenchantment SET `cuFlags` = ?d WHERE `type1` = 0 AND `type2` = 0 AND `type3` = 0', CUSTOM_EXCLUDE_FOR_LISTVIEW);
        DB::Aowow()->query('UPDATE ?_itemenchantment SET `cuFlags` = ?d WHERE `name_loc0` LIKE "%test%"', CUSTOM_EXCLUDE_FOR_LISTVIEW);

        $this->reapplyCCFlags('itemenchantment', Type::ENCHANTMENT);

        return true;
    }
});

?>
