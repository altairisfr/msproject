<?php
/*
 * LabQC Pro for Dolibarr
 * Copyright (C) 2026 PanciucSoft
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modLabQCPro extends DolibarrModules
{
    public function __construct($db)
    {
        global $conf;

        $this->db = $db;
        $this->numero = 207603;
        $this->rights_class = 'labqcpro';
        $this->family = 'products';
        $this->module_position = '90';

        $this->name = 'LabQCPro';
        $this->description = 'ModuleLabQCProDesc';
        $this->descriptionlong = 'ModuleLabQCProDescLong';

        $this->editor_name = 'PanciucSoft';
        $this->editor_url = 'https://panciucsoft.com';

        $this->version = '1.1.1';
        $this->const_name = 'MAIN_MODULE_LABQCPRO';
        $this->picto = 'product';

        $this->module_parts = array();
        $this->dirs = array();
        $this->config_page_url = array();
        $this->hidden = false;

        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array('labqcpro@labqcpro');

        $this->phpmin = array(7, 4);
        $this->need_dolibarr_version = array(18, 0);

        $this->const = array();

        // Permissions provided by LabQC Pro.
        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = 20760301;
        $this->rights[$r][1] = 'LabQCRightRead';
        $this->rights[$r][4] = 'qc';
        $this->rights[$r][5] = 'read';
        $r++;

        $this->rights[$r][0] = 20760302;
        $this->rights[$r][1] = 'LabQCRightSpecifications';
        $this->rights[$r][4] = 'specification';
        $this->rights[$r][5] = 'write';
        $r++;

        $this->rights[$r][0] = 20760303;
        $this->rights[$r][1] = 'LabQCRightCreateTests';
        $this->rights[$r][4] = 'test';
        $this->rights[$r][5] = 'create';
        $r++;

        $this->rights[$r][0] = 20760304;
        $this->rights[$r][1] = 'LabQCRightResults';
        $this->rights[$r][4] = 'result';
        $this->rights[$r][5] = 'write';
        $r++;

        $this->rights[$r][0] = 20760305;
        $this->rights[$r][1] = 'LabQCRightFinalize';
        $this->rights[$r][4] = 'approval';
        $this->rights[$r][5] = 'finalize';
        $r++;

        $this->rights[$r][0] = 20760306;
        $this->rights[$r][1] = 'LabQCRightDocuments';
        $this->rights[$r][4] = 'document';
        $this->rights[$r][5] = 'generate';
        $r++;

        $this->rights[$r][0] = 20760307;
        $this->rights[$r][1] = 'LabQCRightAudit';
        $this->rights[$r][4] = 'audit';
        $this->rights[$r][5] = 'read';
        $r++;

        $this->menu = array();

        $this->menu[] = array(
            'fk_menu' => '',
            'type' => 'top',
            'titre' => 'LabQCMenu',
            'mainmenu' => 'labqcpro',
            'leftmenu' => '',
            'url' => '/labqcpro/index.php?mainmenu=labqcpro&leftmenu=labqcpro_dashboard',
            'langs' => 'labqcpro@labqcpro',
            'position' => 95,
            'enabled' => 'isModEnabled("labqcpro")',
            'perms' => '$user->admin || $user->hasRight("labqcpro", "qc", "read")',
            'target' => '',
            'user' => 0
        );

        $this->menu[] = array(
            'fk_menu' => 'fk_mainmenu=labqcpro',
            'type' => 'left',
            'titre' => 'LabQCDashboard',
            'mainmenu' => 'labqcpro',
            'leftmenu' => 'labqcpro_dashboard',
            'url' => '/labqcpro/index.php?mainmenu=labqcpro&leftmenu=labqcpro_dashboard',
            'langs' => 'labqcpro@labqcpro',
            'position' => 100,
            'enabled' => 'isModEnabled("labqcpro")',
            'perms' => '$user->admin || $user->hasRight("labqcpro", "qc", "read")',
            'target' => '',
            'user' => 0
        );

        $this->menu[] = array(
            'fk_menu' => 'fk_mainmenu=labqcpro',
            'type' => 'left',
            'titre' => 'LabQCSpecifications',
            'mainmenu' => 'labqcpro',
            'leftmenu' => 'labqcpro_specifications',
            'url' => '/labqcpro/specifications.php?mainmenu=labqcpro&leftmenu=labqcpro_specifications',
            'langs' => 'labqcpro@labqcpro',
            'position' => 110,
            'enabled' => 'isModEnabled("labqcpro")',
            'perms' => '$user->admin || $user->hasRight("labqcpro", "qc", "read")',
            'target' => '',
            'user' => 0
        );

        $this->menu[] = array(
            'fk_menu' => 'fk_mainmenu=labqcpro',
            'type' => 'left',
            'titre' => 'LabQCTests',
            'mainmenu' => 'labqcpro',
            'leftmenu' => 'labqcpro_tests',
            'url' => '/labqcpro/tests.php?mainmenu=labqcpro&leftmenu=labqcpro_tests',
            'langs' => 'labqcpro@labqcpro',
            'position' => 120,
            'enabled' => 'isModEnabled("labqcpro")',
            'perms' => '$user->admin || $user->hasRight("labqcpro", "qc", "read")',
            'target' => '',
            'user' => 0
        );

        $this->menu[] = array(
            'fk_menu' => 'fk_mainmenu=labqcpro',
            'type' => 'left',
            'titre' => 'LabQCCertificatesOOS',
            'mainmenu' => 'labqcpro',
            'leftmenu' => 'labqcpro_certificates',
            'url' => '/labqcpro/coa.php?mainmenu=labqcpro&leftmenu=labqcpro_certificates',
            'langs' => 'labqcpro@labqcpro',
            'position' => 130,
            'enabled' => 'isModEnabled("labqcpro")',
            'perms' => '$user->admin || $user->hasRight("labqcpro", "qc", "read")',
            'target' => '',
            'user' => 0
        );

        $this->menu[] = array(
            'fk_menu' => 'fk_mainmenu=labqcpro',
            'type' => 'left',
            'titre' => 'LabQCAuditTrail',
            'mainmenu' => 'labqcpro',
            'leftmenu' => 'labqcpro_audit',
            'url' => '/labqcpro/audit.php?mainmenu=labqcpro&leftmenu=labqcpro_audit',
            'langs' => 'labqcpro@labqcpro',
            'position' => 140,
            'enabled' => 'isModEnabled("labqcpro")',
            'perms' => '$user->admin || ($user->hasRight("labqcpro", "qc", "read") && $user->hasRight("labqcpro", "audit", "read"))',
            'target' => '',
            'user' => 0
        );
    }

    public function init($options = '')
    {
        $prefix = MAIN_DB_PREFIX;
        $sql = array();

        $sql[] = "CREATE TABLE IF NOT EXISTS ".$prefix."labqcpro_specification (
            rowid integer AUTO_INCREMENT PRIMARY KEY,
            entity integer NOT NULL DEFAULT 1,
            fk_product integer NOT NULL,
            ref varchar(128) NOT NULL,
            label varchar(255) NOT NULL,
            version varchar(50) DEFAULT '1.0',
            status smallint NOT NULL DEFAULT 0,
            description text,
            datec datetime NOT NULL,
            tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            fk_user_creat integer NOT NULL,
            fk_user_modif integer,
            UNIQUE KEY uk_labqcpro_specification_ref (entity, ref),
            KEY idx_labqcpro_specification_product (fk_product),
            KEY idx_labqcpro_specification_status (status)
        ) ENGINE=innodb";

        $sql[] = "CREATE TABLE IF NOT EXISTS ".$prefix."labqcpro_specification_line (
            rowid integer AUTO_INCREMENT PRIMARY KEY,
            fk_specification integer NOT NULL,
            position integer NOT NULL DEFAULT 0,
            parameter varchar(255) NOT NULL,
            method varchar(255),
            criteria_type varchar(20) NOT NULL DEFAULT 'range',
            min_value double,
            max_value double,
            target_text varchar(255),
            unit varchar(64),
            mandatory smallint NOT NULL DEFAULT 1,
            datec datetime NOT NULL,
            tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            fk_user_creat integer NOT NULL,
            fk_user_modif integer,
            KEY idx_labqcpro_specification_line_spec (fk_specification),
            KEY idx_labqcpro_specification_line_position (position)
        ) ENGINE=innodb";

        $sql[] = "CREATE TABLE IF NOT EXISTS ".$prefix."labqcpro_test (
            rowid integer AUTO_INCREMENT PRIMARY KEY,
            entity integer NOT NULL DEFAULT 1,
            ref varchar(128) NOT NULL,
            fk_product integer NOT NULL,
            fk_specification integer NOT NULL,
            batch_number varchar(128) NOT NULL,
            date_test date,
            status smallint NOT NULL DEFAULT 0,
            notes text,
            datec datetime NOT NULL,
            tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            fk_user_creat integer NOT NULL,
            fk_user_modif integer,
            UNIQUE KEY uk_labqcpro_test_ref (entity, ref),
            KEY idx_labqcpro_test_product (fk_product),
            KEY idx_labqcpro_test_spec (fk_specification),
            KEY idx_labqcpro_test_batch (batch_number),
            KEY idx_labqcpro_test_status (status)
        ) ENGINE=innodb";

        $sql[] = "CREATE TABLE IF NOT EXISTS ".$prefix."labqcpro_test_inspection (
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

        $sql[] = "CREATE TABLE IF NOT EXISTS ".$prefix."labqcpro_test_result (
            rowid integer AUTO_INCREMENT PRIMARY KEY,
            fk_test integer NOT NULL,
            fk_specification_line integer,
            position integer NOT NULL DEFAULT 0,
            parameter varchar(255) NOT NULL,
            method varchar(255),
            criteria_type varchar(20) NOT NULL,
            min_value double,
            max_value double,
            target_text varchar(255),
            unit varchar(64),
            mandatory smallint NOT NULL DEFAULT 1,
            result_value double,
            result_text varchar(255),
            verdict smallint NOT NULL DEFAULT 0,
            comment text,
            datec datetime NOT NULL,
            tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            fk_user_creat integer NOT NULL,
            fk_user_modif integer,
            KEY idx_labqcpro_test_result_test (fk_test),
            KEY idx_labqcpro_test_result_spec_line (fk_specification_line),
            KEY idx_labqcpro_test_result_verdict (verdict)
        ) ENGINE=innodb";

        $sql[] = "CREATE TABLE IF NOT EXISTS ".$prefix."labqcpro_test_approval (
            rowid integer AUTO_INCREMENT PRIMARY KEY,
            entity integer NOT NULL DEFAULT 1,
            fk_test integer NOT NULL,
            decision smallint NOT NULL,
            fk_user_approval integer NOT NULL,
            approver_name varchar(255) NOT NULL,
            approver_position varchar(255),
            approval_date datetime NOT NULL,
            note text,
            datec datetime NOT NULL,
            tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_labqcpro_test_approval (entity, fk_test),
            KEY idx_labqcpro_test_approval_test (fk_test),
            KEY idx_labqcpro_test_approval_user (fk_user_approval)
        ) ENGINE=innodb";

        $sql[] = "CREATE TABLE IF NOT EXISTS ".$prefix."labqcpro_audit (
            rowid integer AUTO_INCREMENT PRIMARY KEY,
            entity integer NOT NULL DEFAULT 1,
            fk_test integer NOT NULL,
            action varchar(64) NOT NULL,
            object_type varchar(64) NOT NULL DEFAULT 'test',
            fk_object integer NOT NULL DEFAULT 0,
            old_value text,
            new_value text,
            note text,
            datec datetime NOT NULL,
            fk_user integer NOT NULL,
            KEY idx_labqcpro_audit_test (fk_test),
            KEY idx_labqcpro_audit_action (action),
            KEY idx_labqcpro_audit_datec (datec),
            KEY idx_labqcpro_audit_user (fk_user)
        ) ENGINE=innodb";

        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
