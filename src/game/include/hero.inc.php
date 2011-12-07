<?php
/*
 * hero.inc.php - basic hero system
 * Copyright (c) 2003  OGP Team
 * Copyright (c) 2011 Georg Pitterle
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

function getHeroByPlayer($playerID) {
  global $db;

  // set database query with playerID
  $sql = $db->prepare("SELECT *
                       FROM ". HERO_TABLE ." 
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID);
  if (!$sql->execute()) return null;

  $hero = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  if (empty($hero)) {
    return null;
  }

  $hero['id']       = $GLOBALS['heroTypesList'][$hero['heroTypeID']]['id'];
  $hero['typeName'] = $GLOBALS['heroTypesList'][$hero['heroTypeID']]['name'];
  $hero['path']     = _('hero_imperator.gif');
  $hero['location'] = _('tot');

  if($hero['id']=='Defender') {
    $hero['path'] = _('hero_defender.gif');
  }
  if($hero['id']=='Constructor') {
    $hero['path'] = _('hero_constructor.gif');
  }

  $hero['lvlUp'] = eval("return " . hero_parseFormulas($GLOBALS['heroTypesList'][$hero['heroTypeID']]['lvlUp_formula']) . ";");
  $hero['expLeft'] = $hero['lvlUp'] - $hero['exp'];

  if ($hero['healPoints'] == 0 || $hero['isAlive'] == false) {
    $hero['location'] = _('tot');
    $hero['path'] = _('hero_death.gif');
  } else if($hero['isMoving']) {
    $hero['location'] = _('in Bewegung');
  } else {
    $cave = getCaveByID($hero['caveID']);
    $hero['location'] = $cave['name'] . " in (" . $cave['xCoord'] . "|" . $cave['yCoord'] .")";
  }

  return $hero;
}

function hero_parseFormulas ($formula) {
  $formula = str_replace(
    array(
      '{forceLvl}',
      '{lvl}', 
      '{exp}',
      '{regHpLvl}',
      '{healPoints}', 
      '{maxHealPoints}',
      '{tpFree}', 
      '{maxHpLvl}'
    ), 
    array(
      '$hero[\'forceLvl\']',
      '$hero[\'lvl\']',
      '$hero[\'exp\']',
      '$hero[\'regHpLvl\']',
      '$hero[\'healPoints\']',
      '$hero[\'maxHealPoints\']',
      '$hero[\'tpFree\']',
      '$hero[\'maxHpLvl\']'
    ), $formula);

  return $formula;
}

function checkEventHeroExists($playerID) {
  global $db;

  // set database query with playerID
  $sql = $db->prepare("SELECT *
                       FROM ". EVENT_HERO_TABLE ." 
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID);

  // no event
  if ($sql->rowCountSelect() == 0) {
    return false;
  }

  return true;
}

function getHeroQueue($playerID) {
  global $db;

  // set database query with playerID
  $sql = $db->prepare("SELECT *
                       FROM ". EVENT_HERO_TABLE ." 
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID);
  if (!$sql->execute()) return NULL;

  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  
  return (empty($ret)) ? null : $ret;
}


function skillForce($playerID, $hero) {
  global $db;

  $hero['forceLvl'] = $hero['forceLvl']++;
  $force = eval("return " . hero_parseFormulas($GLOBALS['heroTypesList'][$hero['heroTypeID']]['force_formula']) . ";");

  // update effects
  $fields = array();
  foreach ($GLOBALS['effectTypeList'] as $effect) {
    if (array_key_exists($effect->dbFieldName, $GLOBALS['heroTypesList'][$hero['heroTypeID']]['effects'])) {
      array_push($fields, $effect->dbFieldName . " = ".$GLOBALS['heroTypesList'][$hero['heroTypeID']]['effects'][$effect->dbFieldName]['absolute'].
      "+" . $effect->dbFieldName."*(1+".$GLOBALS['heroTypesList'][$hero['heroTypeID']]['effects'][$effect->dbFieldName]['relative'].")");
    }
  }

  // set database query with playerID
  $sql = $db->prepare("UPDATE ". HERO_TABLE ."
                       SET forceLvl = forceLvl + 1,
                       tpFree = tpFree - 1, 
                       `force` = :force,
                       ".implode(", ", $fields)."
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('force', $force, PDO::PARAM_STR);
  if (!$sql->execute()) {
    return false;
  }

  // update cave-effect if constructor
  if ($hero['id'] == 'Constructor') {
    $sql = $db->prepare("UPDATE " . CAVE_TABLE . "
                         SET " . implode(", ", $fields). "
                         WHERE caveID = :caveID");
    $sql->bindValue('caveID', $hero['caveID'], PDO::PARAM_INT);
    if (!$sql->execute()) {
      return false;
    }
  }

  return true;
}

function skillMaxHp($playerID, $hero) {
  global $db;
  
  $hero['maxHpLvl'] = $hero['maxHpLvl']++;
  $maxHP = eval("return " . hero_parseFormulas($GLOBALS['heroTypesList'][$hero['heroTypeID']]['maxHP_formula']) . ";");
  // set database query with playerID
  $sql = $db->prepare("UPDATE ". HERO_TABLE ."
                     SET maxHpLvl = maxHpLvl + 1,
                       tpFree = tpFree - 1,
                       maxHealPoints = :maxHP
                     WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('maxHP', $maxHP, PDO::PARAM_INT);
  return $sql->execute();
}

function skillRegHp($playerID, $hero) {
  global $db;
  
  $hero['regHpLvl'] = ++$hero['regHpLvl'];
  $regHP = eval("return " . hero_parseFormulas($GLOBALS['heroTypesList'][$hero['heroTypeID']]['regHP_formula']) . ";");

  // set database query with playerID
  $sql = $db->prepare("UPDATE ". HERO_TABLE ."
                       SET regHpLvl = regHpLvl + 1,
                         tpFree = tpFree - 1,
                         regHP = :regHP
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('regHP', $regHP, PDO::PARAM_INT);
  
  return $sql->execute();

}
function getRitual($hero) {
  global $db;
  
  $ritualCost = $GLOBALS['heroTypesList'][$hero['heroTypeID']]['ritual']['ritualCost'];
  foreach ($ritualCost as $costID => $cost) {
    $ritualCost[$costID] = eval("return " . hero_parseFormulas($cost) . ";");
  }
  
  $duration = array('duration' => $GLOBALS['heroTypesList'][$hero['heroTypeID']]['ritual']['duration']);
  return array_merge($ritualCost, $duration);
}

function createRitual($caveID, $playerID, $ritual, $hero, &$ownCaves) {
  global $db;

  $cave = getCaveSecure($caveID, $playerID);
  $duration = $ritual['duration'];
  unset($ritual['duration']);
  // get ritual costs
  $costs = array();
  $temp = array_merge($GLOBALS['resourceTypeList'], $GLOBALS['buildingTypeList'], $GLOBALS['unitTypeList'], $GLOBALS['scienceTypeList'], $GLOBALS['defenseSystemTypeList']);
  foreach($temp as $val) {
    if (array_key_exists($val->dbFieldName, $ritual)) {
      if ($ritual[$val->dbFieldName]['value']) {
        $costs[$val->dbFieldName] = $ritual[$val->dbFieldName]['value'];
      }
    }
  }

  $set     = array();
  $setBack = array();
  $where   = array("WHERE caveID = '{$caveID}'");

  // get all the costs
  foreach ($costs as $key => $value) {
    array_push($set,     "{$key} = {$key} - ({$value})");
    array_push($setBack, "{$key} = {$key} + ({$value})");
    array_push($where,   "{$key} >= ({$value})");
  }

  $where = implode(" AND ", $where);

  // generate SQL
  if (sizeof($set)) {
    $set     = implode(", ", $set);
    
    if (!$db->exec("UPDATE ". CAVE_TABLE ." SET {$set} {$where}")) {
      return -3;
    }

    $setBack = implode(", ", $setBack);
    $setBack = "UPDATE ". CAVE_TABLE ." SET $setBack WHERE caveID = '{$caveID}'";
  } else {
    return -7;
  }

  $now = time();
  $sql = $db->prepare("INSERT INTO ". EVENT_HERO_TABLE ."
                         (caveID, playerID, heroID, start, end, blocked) 
                       VALUES
                         (:caveID, :playerID, :heroID, :start, :end, :blocked)");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('heroID', $hero['heroID'], PDO::PARAM_INT);
  $sql->bindValue('start', time_toDatetime($now), PDO::PARAM_STR);
  $sql->bindValue('end', time_toDatetime($now + $duration), PDO::PARAM_STR);
  $sql->bindValue('blocked', 0, PDO::PARAM_INT);
  if ($sql->execute()) {
    $sql->closeCursor();

    $sql =  $db->prepare("UPDATE " . HERO_TABLE . "
                          SET isAlive = -1,
                            caveID = :caveID
                          WHERE heroID = :heroID");
    $sql->bindValue('heroID', $hero['heroID'], PDO::PARAM_INT);
    $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
    if (!$sql->execute()) {
      $db->query($setBack);
      return -7;
    }

    // update cave
    $ownCaves[$caveID] = getCaveSecure($caveID, $_SESSION['player']->playerID);

    return 2;
  }
}

function createNewHero($heroTypeID, $playerID, $caveID) {
  global $db;

  $hero = getHeroByPlayer($playerID);

  if($hero == null) {
    $player = getPlayerByID($playerID);

    $sql = $db->prepare("INSERT INTO ". HERO_TABLE ."
                          (caveID, playerID, heroTypeID, name, exp, healPoints, maxHealPoints, isAlive) 
                        VALUES
                          (:caveID, :playerID, :heroTypeID, :name, :exp,  :healPoints, :maxHealPoints, :isAlive)");
    $sql->bindValue('caveID', 0, PDO::PARAM_INT);
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    $sql->bindValue('heroTypeID', $heroTypeID, PDO::PARAM_INT);
    $sql->bindValue('name', $player['name'], PDO::PARAM_INT);
    $sql->bindValue('exp', 0, PDO::PARAM_INT);
    $sql->bindValue('healPoints', 0, PDO::PARAM_INT);
    $sql->bindValue('maxHealPoints', eval("return " . hero_parseFormulas($GLOBALS['heroTypesList'][$heroTypeID]['maxHP_formula']) . ";"), PDO::PARAM_INT);
    $sql->bindValue('isAlive', 0, PDO::PARAM_INT);
    if (!$sql->execute()) {
      $sql->closeCursor();
      return -6;
    }

    return 3;
  }

  return -6;
}

function hero_removeHeroFromCave ($heroID) {
  global $db;

  $sql = $db->prepare("UPDATE " . CAVE_TABLE ."
                       SET hero = 0
                       WHERE hero = :heroID");
  $sql->bindValue('heroID', $heroID, PDO::PARAM_INT);
  if (!$sql->execute()) {
    return false;
  }

  $sql = $db->prepare("UPDATE " . HERO_TABLE . "
                       SET isMoving = 1 
                       WHERE heroID = :heroID");
  $sql->bindValue('heroID', $heroID, PDO::PARAM_INT);
  if (!$sql->execute()) {
    return false;
  }

  return true;
}

function hero_usePotion ($potionID, $value) {
  global $db;

  $playerID = $_SESSION['player']->playerID;

  $playerData = getPlayerByID($playerID);
  $hero = getHeroByPlayer($playerID);

  $potion = $GLOBALS['potionTypeList'][$potionID];

  if (!$potion) {
    return -8;
  }

  if ($potion->needed_level > $hero['lvl']) {
    return -10;
  }

  if ($playerData[$potion->dbFieldName] < $value) {
    return -9; 
  }

  // remove potions
  $sql = $db->prepare("UPDATE " . PLAYER_TABLE ."
                       SET " . $potion->dbFieldName . " = " . $potion->dbFieldName . " - :value
                       WHERE playerID = :playerID");
  $sql->bindValue('value', $value, PDO::PARAM_INT);
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);

  $sql_setback = $db->prepare("UPDATE " . PLAYER_TABLE ."
                               SET " . $potion->dbFieldName . " = " . $potion->dbFieldName . " + :value
                               WHERE playerID = :playerID");
  $sql_setback->bindValue('value', $value, PDO::PARAM_INT);
  $sql_setback->bindValue('playerID', $playerID, PDO::PARAM_INT);

  if (!$sql->execute()) {
    return -8;
  }

  // apply potion effects
  $newHealPoints = $hero['healPoints'];
  for ($i = 0; $i< $value; $i ++) {
    $newHealPoints += floor($hero['maxHealPoints'] * $potion->hp_prozentual_increase/100) + $potion->hp_increase;
  }
  if ($hero['maxHealPoints'] < $newHealPoints) {
    $newHealPoints = $hero['maxHealPoints'];
  }

  if ($potion->tp_setBack == false) {
    $sql = $db->prepare("UPDATE " .HERO_TABLE ."
                         SET healPoints = :newHealPoints
                         WHERE playerID = :playerID");
    $sql->bindValue('newHealPoints', $newHealPoints, PDO::PARAM_INT);
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    if (!$sql->execute()) {
      $sql_setback->execute();
      return -8;
    }
  } else {
    
    //remove hero effects from cave
    if (!hero_removeHeroEffectsFromCave($playerID)) {
      return -8;
    }
    
    //remove hero effect from hero
    if (!hero_clearHeroEffects($playerID)) {
      return -8;
    }
    
    $tpFree = $hero['maxHpLvl'] + $hero['forceLvl'] + $hero['regHpLvl'] + $hero['tpFree'];
    $healPoints = eval("return " . hero_parseFormulas($heroTypesList[$hero['heroTypeID']]['maxHP_formula']) . ";");
    $sql = $db->prepare("UPDATE " . HERO_TABLE ." SET 
                           maxHpLvl = 0, maxHealPoints = :maxHealPoints,
                           healPoints = :healpoints,
                           forceLvl = 0, `force` = 0,
                           regHpLvl = 0, regHP = 0,
                           tpFree = :tpFree, 
                           heroTypeID = 1000
                         WHERE playerID = :playerID");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    $sql->bindValue('tpFree', $tpFree, PDO::PARAM_INT);
    $sql->bindValue('maxHealPoints', $healPoints, PDO::PARAM_INT);
    $sql->bindValue('healpoints', floor($healPoints / 2), PDO::PARAM_INT);
    if (!$sql->execute()) {
      $sql_setback->execute();
      return -8;
    }
    
    

    return 6;
  }

  return 5;
}

function hero_levelUp($hero) {
  global $db;

  if ($hero['exp'] < $hero['lvlUp']) {
    return -12;
  }

  $sql = $db->prepare("UPDATE " . HERO_TABLE ."
                       SET lvl = lvl +1,
                         tpFree = tpFree +1 
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $hero['playerID'], PDO::PARAM_INT);

  if (!$sql->execute()) {
    return -12;
  }

  return 7;
}

function hero_immolateResources($resourceID, $value, $caveID, &$ownCaves) {
  global $db;

  if ($resourceID < 0 || $value == 0) {
    return array('messageID' => -13, 'value' => 0);
  }

  if (array_key_exists($resourceID, $GLOBALS['resourceTypeList'])) {
    $resource = $GLOBALS['resourceTypeList'][$resourceID];
    $playerID = $_SESSION['player']->playerID;

    // not enough resources in cave
    if ($ownCaves[$caveID][$resource->dbFieldName] < $value) {
      return array('messageID' => -14, 'value'=>0);
    }

    // take resource from cave
    $sql = $db->prepare("UPDATE " . CAVE_TABLE . "
                         SET ". $resource->dbFieldName . " = " . $resource->dbFieldName . " - :value
                         WHERE caveID = :caveID");
    $sql->bindValue('value', $value, PDO::PARAM_INT);
    $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return array('messageID' => -15, 'value' => 0);
    }

    // add experience points
    $sql = $db->prepare("UPDATE " . HERO_TABLE . "
                         SET exp = exp + :expValue
                         WHERE playerID = :playerID");
    $sql->bindValue('expValue', $value*$resource->takeoverValue, PDO::PARAM_INT);
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      // return resource to cave
      $sql_setback = $db->prepare("UPDATE " . CAVE_TABLE . "
                                   SET " . $resource->dbFieldName . " = " . $resource->dbFieldName . " + :value
                                   WHERE caveID = :caveID");
      $sql_setback->bindValue('value', $value, PDO::PARAM_INT);
      $sql_setback->bindValue('caveID', $caveID, PDO::PARAM_INT);
      $sql_setback->execute();

      return array('messageID' => -16, 'value' => 0);
    }

    $ownCaves = getCaves($playerID);
    return array('messageID' => 8, 'value' => $value * $resource->takeoverValue);
  } else {
    return array('messageID'=> -13, 'value' => 0);
  }
}

function hero_cancelOrder () {
  global $db;

  $sql = $db->prepare("DELETE FROM " . EVENT_HERO_TABLE . "
                       WHERE playerID = :playerID ");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

  if (!$sql->execute() || $sql->rowCount() == 0) {
    return -4;
  }

  $sql = $db->prepare("UPDATE " . HERO_TABLE ."
                       SET isAlive = 0,
                        caveID = 0,
                        healPoints = 0,
                        isMoving = 0 
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

  if (!$sql->execute() || $sql->rowCount() == 0) {
    return -4;
  }

  return 9;
}

function hero_killHero ($playerID) {
  global $db;

  $hero = getHeroByPlayer($playerID);

  // reset hero
  $sql = $db->prepare("UPDATE " . HERO_TABLE. "
                       SET  isAlive = 0, 
                         caveID = 0, 
                         healPoints = 0, 
                         isMoving = 0 
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->execute();

  // cancel all events
  $sql = $db->prepare("DELETE FROM ". EVENT_HERO_TABLE ." WHERE heroID = :heroID");
  $sql->bindValue('heroID', $hero['heroID'], PDO::PARAM_INT);
  
  $return = $sql->execute();

  // remove effects from cave
  $return = hero_removeHeroEffectsFromCave($playerID) && $return;
  
  return $return;
}

function hero_removeHeroEffectsFromCave($playerID) {
  global $db;

  $hero = getHeroByPlayer($playerID);

  // if hero is dead, effects should not exist
  //"<1" because while reincarnating status is "-1"
  if ($hero['isAlive'] < 1) {
    return true;
  }

  // remove effects only for constructor type
  if ($hero['id'] != 'Constructor') {
    return true;
  }

  // removing hero effects from cave is only needed, if resource factors are involved
  $effectArray = array();
  foreach($GLOBALS['effectTypeList'] as $effect) {
    if (array_key_exists($effect->dbFieldName, $GLOBALS['heroTypesList'][$hero['heroTypeID']]['effects'])) {
      array_push($effectArray, $effect->dbFieldName . " = " . $effect->dbFieldName . " - " . $hero[$effect->dbFieldName]);
    }
  }

  $sql = $db->prepare("UPDATE " . CAVE_TABLE . "
                       SET " . implode(", ", $effectArray) ."
                       WHERE caveID = :caveID");
  $sql->bindValue('caveID', $hero['caveID'], PDO::PARAM_INT);
  if (!$sql->execute()) {
    return false;
  }

  return true;
}

function hero_changeType($typeID) {
  global $db;
  
  $hero = getHeroByPlayer($_SESSION['player']->playerID);
  if ($hero['heroTypeID'] != 1000) {
    return -19;
  }
  
  $sql = $db->prepare("UPDATE " . HERO_TABLE . "
                       SET heroTypeID = :typeID
                       WHERE playerID = :playerID");
  $sql->bindValue('typeID', $typeID, PDO::PARAM_INT);
  $sql->bindValue('playerID', $_SESSION['player']->playerID);
  
  if (!$sql->execute()) {
    return -19;
  }
  
  return 10;
}

function hero_clearHeroEffects($playerID) {
  global $db, $effectTypeList;
  
  $hero = getHeroByPlayer($playerID);
  
  $fields = array();
  foreach ($effectTypeList as $effect) {
    array_push( $fields, $effect->dbFieldName . " = 0");
  }
  
  $sql = $db->prepare("UPDATE " . HERO_TABLE . " SET " . implode(", ", $fields) . " WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  
  return $sql->execute();
  
}

?>