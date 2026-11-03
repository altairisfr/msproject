<?php
/* LabQC Pro 1.1.1: optional inspection metadata, independent of analytical PASS/FAIL. */

/**
 * Create the additive inspection table on first use. Existing LabQC test and result
 * tables are not changed. Module init contains the same CREATE TABLE declaration.
 * This allows updating an active installation without module reactivation.
 */
function labqcproInspectionEnsureSchema($db)
{
    static $ready = false;
    if ($ready) return true;
    $table = MAIN_DB_PREFIX.'labqcpro_test_inspection';
    $result = $db->query("SHOW TABLES LIKE '".$db->escape($table)."'");
    if (!$result) return false;
    if ($db->num_rows($result) === 0) {
        $sql = "CREATE TABLE IF NOT EXISTS ".$table." (
            rowid integer AUTO_INCREMENT PRIMARY KEY,
            entity integer NOT NULL DEFAULT 1,
            fk_test integer NOT NULL,
            batch_quantity bigint unsigned DEFAULT NULL,
            quantity_inspected bigint unsigned DEFAULT NULL,
            aql_level varchar(20) DEFAULT NULL,
            third_party_reference varchar(255) DEFAULT NULL,
            inspection_type varchar(32) DEFAULT NULL,
            datec datetime NOT NULL,
            tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            fk_user_modif integer NOT NULL,
            UNIQUE KEY uk_labqcpro_inspection_test (entity, fk_test),
            KEY idx_labqcpro_inspection_test (fk_test)
        ) ENGINE=innodb";
        if (!$db->query($sql)) return false;
    }
    $ready = true;
    return true;
}

function labqcproInspectionDefaults()
{
    return array(
        'batch_quantity' => '',
        'quantity_inspected' => '',
        'aql_level' => '',
        'third_party_reference' => '',
        'inspection_type' => ''
    );
}

function labqcproInspectionRead($db, $entity, $testId)
{
    $values = labqcproInspectionDefaults();
    $sql = 'SELECT batch_quantity, quantity_inspected, aql_level, third_party_reference, inspection_type';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'labqcpro_test_inspection';
    $sql .= ' WHERE entity='.(int) $entity.' AND fk_test='.(int) $testId.' LIMIT 1';
    $res = $db->query($sql);
    if ($res && $db->num_rows($res)) {
        $row = $db->fetch_object($res);
        foreach ($values as $key => $unused) {
            $values[$key] = $row->$key === null ? '' : (string) $row->$key;
        }
    }
    return $values;
}

/** Parse and validate user supplied (manual) metadata. No AQL calculation is performed. */
function labqcproInspectionValidate($raw, &$errorKey)
{
    $values = labqcproInspectionDefaults();
    $errorKey = '';
    foreach (array('batch_quantity', 'quantity_inspected') as $key) {
        $v = trim((string) (isset($raw[$key]) ? $raw[$key] : ''));
        if ($v !== '' && (!preg_match('/^[0-9]{1,15}$/D', $v) || (float) $v <= 0)) {
            $errorKey = 'LabQCInspectionPositiveInteger';
            return false;
        }
        $values[$key] = $v === '' ? '' : (string) (int) $v;
    }
    if ($values['batch_quantity'] !== '' && $values['quantity_inspected'] !== ''
        && (int) $values['quantity_inspected'] > (int) $values['batch_quantity']) {
        $errorKey = 'LabQCInspectionExceedsBatch';
        return false;
    }
    $aql = str_replace(',', '.', trim((string) (isset($raw['aql_level']) ? $raw['aql_level'] : '')));
    if ($aql !== '' && (!preg_match('/^[0-9]{1,3}(\.[0-9]{1,3})?$/D', $aql) || (float) $aql <= 0 || (float) $aql > 100)) {
        $errorKey = 'LabQCInspectionInvalidAql';
        return false;
    }
    $values['aql_level'] = $aql;
    $reference = trim((string) (isset($raw['third_party_reference']) ? $raw['third_party_reference'] : ''));
    if (strlen($reference) > 255) {
        $errorKey = 'LabQCInspectionReferenceTooLong';
        return false;
    }
    $values['third_party_reference'] = $reference;
    $type = trim((string) (isset($raw['inspection_type']) ? $raw['inspection_type'] : ''));
    if (!in_array($type, array('', 'incoming', 'preshipment', 'other'), true)) {
        $errorKey = 'LabQCInspectionInvalidType';
        return false;
    }
    $values['inspection_type'] = $type;
    return $values;
}

function labqcproInspectionPersist($db, $entity, $testId, $userId, $values)
{
    $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'labqcpro_test_inspection';
    $sql .= ' (entity, fk_test, batch_quantity, quantity_inspected, aql_level, third_party_reference, inspection_type, datec, fk_user_modif) VALUES (';
    $sql .= (int) $entity.','.(int) $testId.',';
    foreach (array('batch_quantity', 'quantity_inspected') as $key) {
        $sql .= $values[$key] === '' ? 'NULL,' : ((int) $values[$key]).',';
    }
    foreach (array('aql_level', 'third_party_reference', 'inspection_type') as $key) {
        $sql .= $values[$key] === '' ? 'NULL,' : "'".$db->escape($values[$key])."',";
    }
    $sql .= "'".$db->idate(dol_now())."',".(int) $userId.')';
    $sql .= ' ON DUPLICATE KEY UPDATE batch_quantity=VALUES(batch_quantity),';
    $sql .= ' quantity_inspected=VALUES(quantity_inspected), aql_level=VALUES(aql_level),';
    $sql .= ' third_party_reference=VALUES(third_party_reference), inspection_type=VALUES(inspection_type),';
    $sql .= ' fk_user_modif=VALUES(fk_user_modif)';
    return (bool) $db->query($sql);
}

function labqcproInspectionTypeLabel($type, $langs)
{
    if ($type === 'incoming') return $langs->trans('LabQCIncomingInspection');
    if ($type === 'preshipment') return $langs->trans('LabQCPreShipmentInspection');
    if ($type === 'other') return $langs->trans('LabQCOtherInspection');
    return '';
}
