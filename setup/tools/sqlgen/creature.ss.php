<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');

if (!CLI)
    die('not in cli mode');


CLISetup::registerSetup("sql", new class extends SetupScript
{
    protected $info = array(
        'creature' => [[], CLISetup::ARGV_PARAM, 'Compiles data for type: NPC from dbc and world db.']
    );

    protected $dbcSourceFiles  = ['creaturedisplayinfo', 'creaturedisplayinfoextra'];
    protected $worldDependency = ['creature_template', 'creature_template_locale', 'creature_template_resistance', 'creature_template_spell', 'creature_classlevelstats', 'instance_encounters'];
    public function generate(array $ids = []) : bool
    {
        $baseQuery =
           'SELECT    ct.entry,
                      IF(ie.creditEntry IS NULL, 0, ?d) AS cuFlags,
                      difficulty_entry_1, difficulty_entry_2, difficulty_entry_3,
                      KillCredit1, KillCredit2,
                      IFNULL(ctm1.CreatureDisplayID, 0), IFNULL(ctm2.CreatureDisplayID, 0), IFNULL(ctm3.CreatureDisplayID, 0), IFNULL(ctm4.CreatureDisplayID, 0),
                      "" AS textureString,
                      0 AS modelId,
                      0 AS humanoid,                        -- uses creaturedisplayinfoextra
                      "" AS iconString,
                      ct.name, IFNULL(ctl2.`Name`, "")  AS n2, IFNULL(ctl3.`Name`, "")  AS n3, IFNULL(ctl4.`Name`, "")  AS n4, IFNULL(ctl6.`Name`, "")  AS n6, IFNULL(ctl8.`Name`, "")  AS n8,
                      subname, IFNULL(ctl2.`Title`, "") AS t2, IFNULL(ctl3.`Title`, "") AS t3, IFNULL(ctl4.`Title`, "") AS t4, IFNULL(ctl6.`Title`, "") AS t6, IFNULL(ctl8.`Title`, "") AS t8,
                      minLevel, maxLevel,
                      exp,
                      faction,
                      npcflag,
                      IF(`rank` > 4, 0, `rank`),
                      dmgSchool,
                      DamageModifier,
                      BaseAttackTime,
                      RangeAttackTime,
                      BaseVariance,
                      RangeVariance,
                      unit_class,
                      unit_flags, unit_flags2, dynamicflags,
                      family,
                      trainer_type,
                      trainer_spell,
                      trainer_class,
                      trainer_race,
                      -- IFNULL(t.Type, 0),
                      -- IFNULL(t.Requirement, 0),
                      (CASE ct.exp WHEN 0 THEN min.damage_base WHEN 1 THEN min.damage_exp1 ELSE min.damage_exp2 END) AS dmgMin,
                      (CASE ct.exp WHEN 0 THEN max.damage_base WHEN 1 THEN max.damage_exp1 ELSE max.damage_exp2 END) AS dmgMax,
                      min.attackpower AS mleAtkPwrMin,
                      max.attackpower AS mleAtkPwrMax,
                      min.rangedattackpower AS rmgAtkPwrMin,
                      max.rangedattackpower AS rmgAtkPwrMax,
                      `type`, -- ct.type,
                      type_flags,
                      lootid, pickpocketloot, skinloot,
                      IFNULL(cts0.Spell, 0), IFNULL(cts1.Spell, 0), IFNULL(cts2.Spell, 0), IFNULL(cts3.Spell, 0), IFNULL(cts4.Spell, 0), IFNULL(cts5.Spell, 0), IFNULL(cts6.Spell, 0), IFNULL(cts7.Spell, 0),
                      PetSpellDataId,
                      VehicleId,
                      mingold, maxgold,
                      AIName,
                      (CASE ct.exp WHEN 0 THEN min.basehp0 WHEN 1 THEN min.basehp1 ELSE min.basehp2 END) * ct.HealthModifier AS healthMin,
                      (CASE ct.exp WHEN 0 THEN max.basehp0 WHEN 1 THEN max.basehp1 ELSE max.basehp2 END) * ct.HealthModifier AS healthMax,
                      min.basemana  * ct.ManaModifier AS manaMin,
                      max.basemana  * ct.ManaModifier AS manaMax,
                      min.basearmor * ct.ArmorModifier AS armorMin,
                      max.basearmor * ct.ArmorModifier AS armorMax,
                      IFNULL(ctr1.Resistance, 0), IFNULL(ctr2.Resistance, 0), IFNULL(ctr3.Resistance, 0), IFNULL(ctr4.Resistance, 0), IFNULL(ctr5.Resistance, 0), IFNULL(ctr6.Resistance, 0),
                      RacialLeader,
                      mechanic_immune_mask,
                      flags_extra,
                      ScriptName
            FROM      creature_template ct
            JOIN      creature_classlevelstats min ON ct.unit_class = min.class AND ct.minlevel = min.level
            JOIN      creature_classlevelstats max ON ct.unit_class = max.class AND ct.maxlevel = max.level
            -- LEFT JOIN creature_default_trainer cdt ON cdt.CreatureId = ct.entry
            -- LEFT JOIN trainer t ON t.Id = cdt.TrainerId
            LEFT JOIN creature_template_locale ctl2 ON ct.entry = ctl2.entry AND ctl2.`locale` = "frFR"
            LEFT JOIN creature_template_locale ctl3 ON ct.entry = ctl3.entry AND ctl3.`locale` = "deDE"
            LEFT JOIN creature_template_locale ctl4 ON ct.entry = ctl4.entry AND ctl4.`locale` = "zhCN"
            LEFT JOIN creature_template_locale ctl6 ON ct.entry = ctl6.entry AND ctl6.`locale` = "esES"
            LEFT JOIN creature_template_locale ctl8 ON ct.entry = ctl8.entry AND ctl8.`locale` = "ruRU"
            LEFT JOIN (SELECT creditEntry FROM instance_encounters WHERE creditType = 0 GROUP BY creditEntry) ie ON ie.creditEntry = ct.entry
            LEFT JOIN creature_template_spell cts0 ON ct.entry = cts0.CreatureID AND cts0.Index = 0
            LEFT JOIN creature_template_spell cts1 ON ct.entry = cts1.CreatureID AND cts1.Index = 1
            LEFT JOIN creature_template_spell cts2 ON ct.entry = cts2.CreatureID AND cts2.Index = 2
            LEFT JOIN creature_template_spell cts3 ON ct.entry = cts3.CreatureID AND cts3.Index = 3
            LEFT JOIN creature_template_spell cts4 ON ct.entry = cts4.CreatureID AND cts4.Index = 4
            LEFT JOIN creature_template_spell cts5 ON ct.entry = cts5.CreatureID AND cts5.Index = 5
            LEFT JOIN creature_template_spell cts6 ON ct.entry = cts6.CreatureID AND cts6.Index = 6
            LEFT JOIN creature_template_spell cts7 ON ct.entry = cts7.CreatureID AND cts7.Index = 7
            LEFT JOIN creature_template_resistance ctr1 ON ct.entry = ctr1.CreatureID AND ctr1.School = 1
            LEFT JOIN creature_template_resistance ctr2 ON ct.entry = ctr2.CreatureID AND ctr2.School = 2
            LEFT JOIN creature_template_resistance ctr3 ON ct.entry = ctr3.CreatureID AND ctr3.School = 3
            LEFT JOIN creature_template_resistance ctr4 ON ct.entry = ctr4.CreatureID AND ctr4.School = 4
            LEFT JOIN creature_template_resistance ctr5 ON ct.entry = ctr5.CreatureID AND ctr5.School = 5
            LEFT JOIN creature_template_resistance ctr6 ON ct.entry = ctr6.CreatureID AND ctr6.School = 6
            -- AC
            LEFT JOIN creature_template_model ctm1 ON ct.entry = ctm1.CreatureID AND ctm1.Idx = 0
            LEFT JOIN creature_template_model ctm2 ON ct.entry = ctm2.CreatureID AND ctm2.Idx = 1
            LEFT JOIN creature_template_model ctm3 ON ct.entry = ctm3.CreatureID AND ctm3.Idx = 2
            LEFT JOIN creature_template_model ctm4 ON ct.entry = ctm4.CreatureID AND ctm4.Idx = 3
           { WHERE     ct.entry IN (?a) }
            LIMIT     ?d, ?d';

        $i = 0;
        DB::Aowow()->query('TRUNCATE ?_creature');
        while ($npcs = DB::World()->select($baseQuery, NPC_CU_INSTANCE_BOSS, $ids ?: DBSIMPLE_SKIP, CLISetup::SQL_BATCH * $i, CLISetup::SQL_BATCH))
        {
            CLI::write(' * batch #' . ++$i . ' (' . count($npcs) . ')', CLI::LOG_BLANK, true, true);

            foreach ($npcs as $npc)
                DB::Aowow()->query('INSERT INTO ?_creature VALUES (?a)', array_values($npc));
        }

        // apply "textureString", "modelId" and "iconSring"
        DB::Aowow()->query(
           'UPDATE    ?_creature c
            JOIN      dbc_creaturedisplayinfo cdi ON c.displayId1 = cdi.id
            LEFT JOIN dbc_creaturedisplayinfoextra cdie ON cdi.extraInfoId = cdie.id
            SET       c.textureString = IFNULL(cdie.textureString, cdi.skin1),
                      c.modelId = cdi.modelId,
                      c.iconString = cdi.iconString,
                      c.humanoid = IF(cdie.id IS NULL, 0, 1)'
        );

        // apply cuFlag: difficultyDummy
        DB::Aowow()->query(
           'UPDATE ?_creature a
            JOIN   (SELECT b.difficultyEntry1 AS dummy FROM ?_creature b UNION
                    SELECT c.difficultyEntry2 AS dummy FROM ?_creature c UNION
                    SELECT d.difficultyEntry3 AS dummy FROM ?_creature d) j
            SET    a.cuFlags = a.cuFlags | ?d
            WHERE  a.id = j.dummy',
            NPC_CU_DIFFICULTY_DUMMY | CUSTOM_EXCLUDE_FOR_LISTVIEW
        );

        // apply cuFlag: excludeFromListview [for trigger-creatures]
        DB::Aowow()->query('UPDATE ?_creature SET cuFlags = cuFlags | ?d WHERE flagsExtra & ?d', CUSTOM_EXCLUDE_FOR_LISTVIEW, 0x80);

        // apply cuFlag: exCludeFromListview [for nameparts indicating internal usage]
        DB::Aowow()->query('UPDATE ?_creature SET cuFlags = cuFlags | ?d WHERE name_loc0 LIKE "%[%" OR name_loc0 LIKE "%(%" OR name_loc0 LIKE "%visual%" OR name_loc0 LIKE "%trigger%" OR name_loc0 LIKE "%credit%" OR name_loc0 LIKE "%marker%"', CUSTOM_EXCLUDE_FOR_LISTVIEW);

        $this->reapplyCCFlags('creature', Type::NPC);

        return true;
    }
});

?>
