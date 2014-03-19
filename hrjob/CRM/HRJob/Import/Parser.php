<?php
/*
 +--------------------------------------------------------------------+
 | CiviHR version 1.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */


abstract class CRM_HRJob_Import_Parser extends CRM_Import_Parser {

  protected $_fileName;

  /**#@+
   * @access protected
   * @var integer
   */

  /**
   * imported file size
   */
  protected $_fileSize;

  /**
   * separator being used
   */
  protected $_separator;

  /**
   * total number of lines in file
   */
  protected $_lineCount;

  /**
   * whether the file has a column header or not
   *
   * @var boolean
   */
  protected $_haveColumnHeader;

  protected $_entityFields;


  function run($fileName,
    $separator = ',',
    &$mapper,
    $skipColumnHeader = FALSE,
    $mode = self::MODE_PREVIEW,
    $onDuplicate = self::DUPLICATE_SKIP
  ) {
    if (!is_array($fileName)) {
      CRM_Core_Error::fatal();
    }
    $fileName = $fileName['name'];

    $this->init();

    $this->_haveColumnHeader = $skipColumnHeader;

    $this->_separator = $separator;

    $fd = fopen($fileName, "r");
    if (!$fd) {
      return FALSE;
    }

    $this->_lineCount = $this->_warningCount = 0;
    $this->_invalidRowCount = $this->_validCount = 0;
    $this->_totalCount = $this->_conflictCount = 0;

    $this->_errors = array();
    $this->_warnings = array();
    $this->_conflicts = array();

    $this->_fileSize = number_format(filesize($fileName) / 1024.0, 2);

    if ($mode == self::MODE_MAPFIELD) {
      $this->_rows = array();
    }
    else {
      $this->_activeFieldCount = count($this->_activeFields);
    }

    while (!feof($fd)) {
      $this->_lineCount++;

      $values = fgetcsv($fd, 8192, $separator);
      if (!$values) {
        continue;
      }

      self::encloseScrub($values);

      // skip column header if we're not in mapfield mode
      if ($mode != self::MODE_MAPFIELD && $skipColumnHeader) {
        $skipColumnHeader = FALSE;
        continue;
      }

      /* trim whitespace around the values */

      $empty = TRUE;
      foreach ($values as $k => $v) {
        $values[$k] = trim($v, " \t\r\n");
      }

      if (CRM_Utils_System::isNull($values)) {
        continue;
      }

      $this->_totalCount++;

      if ($mode == self::MODE_MAPFIELD) {
        $returnCode = $this->mapField($values);
      }
      elseif ($mode == self::MODE_PREVIEW) {
        $returnCode = $this->preview($values);
      }
      elseif ($mode == self::MODE_SUMMARY) {
        $returnCode = $this->summary($values);
      }
      elseif ($mode == self::MODE_IMPORT) {
        $returnCode = $this->import($onDuplicate, $values);
      }
      else {
        $returnCode = self::ERROR;
      }

      // note that a line could be valid but still produce a warning
      if ($returnCode & self::VALID) {
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode & self::WARNING) {
        $this->_warningCount++;
        if ($this->_warningCount < $this->_maxWarningCount) {
          $this->_warningCount[] = $line;
        }
      }

      if ($returnCode & self::ERROR) {
        $this->_invalidRowCount++;
        if ($this->_invalidRowCount < $this->_maxErrorCount) {
          $recordNumber = $this->_lineCount;
          if ($this->_haveColumnHeader) {
            $recordNumber--;
          }
          array_unshift($values, $recordNumber);
          $this->_errors[] = $values;
        }
      }

      if ($returnCode & self::CONFLICT) {
        $this->_conflictCount++;
        $recordNumber = $this->_lineCount;
        if ($this->_haveColumnHeader) {
          $recordNumber--;
        }
        array_unshift($values, $recordNumber);
        $this->_conflicts[] = $values;
      }

      if ($returnCode & self::DUPLICATE) {
        if ($returnCode & self::MULTIPLE_DUPE) {
          /* TODO: multi-dupes should be counted apart from singles
                     * on non-skip action */
        }
        $this->_duplicateCount++;
        $recordNumber = $this->_lineCount;
        if ($this->_haveColumnHeader) {
          $recordNumber--;
        }
        array_unshift($values, $recordNumber);
        $this->_duplicates[] = $values;
        if ($onDuplicate != self::DUPLICATE_SKIP) {
          $this->_validCount++;
        }
      }

      // we give the derived class a way of aborting the process
      // note that the return code could be multiple code or'ed together
      if ($returnCode & self::STOP) {
        break;
      }

      // if we are done processing the maxNumber of lines, break
      if ($this->_maxLinesToProcess > 0 && $this->_validCount >= $this->_maxLinesToProcess) {
        break;
      }
    }

    fclose($fd);


    if ($mode == self::MODE_PREVIEW || $mode == self::MODE_IMPORT) {
      $customHeaders = $mapper;

      $customfields = CRM_Core_BAO_CustomField::getFields('Activity');
      foreach ($customHeaders as $key => $value) {
        if ($id = CRM_Core_BAO_CustomField::getKeyID($value)) {
          $customHeaders[$key] = $customfields[$id][0];
        }
      }
      if ($this->_invalidRowCount) {
        // removed view url for invlaid contacts
        $headers = array_merge(array(ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );
        $this->_errorFileName = self::errorFileName(self::ERROR);
        self::exportCSV($this->_errorFileName, $headers, $this->_errors);
      }
      if ($this->_conflictCount) {
        $headers = array_merge(array(ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );
        $this->_conflictFileName = self::errorFileName(self::CONFLICT);
        self::exportCSV($this->_conflictFileName, $headers, $this->_conflicts);
      }
      if ($this->_duplicateCount) {
        $headers = array_merge(array(ts('Line Number'),
            ts('View Activity History URL'),
          ),
          $customHeaders
        );

        $this->_duplicateFileName = self::errorFileName(self::DUPLICATE);
        self::exportCSV($this->_duplicateFileName, $headers, $this->_duplicates);
      }
    }
    return $this->fini();
  }

  /**
   * Given a list of the importable field keys that the user has selected
   * set the active fields array to this list
   *
   * @param array mapped array of values
   *
   * @return void
   * @access public
   */
  function setActiveFields($fieldKeys) {
    $this->_activeFieldCount = count($fieldKeys);
    foreach ($fieldKeys as $key) {
      if (empty($this->_fields[$key]) || $key == "do_not_import") {
        $this->_activeFields[] = new CRM_HRJob_Import_Field('', ts('- do not import -'));
      }
      else {
        $this->_activeFields[] = clone($this->_fields[$key]);
      }
    }
  }

  function addField($name, $title, $type = CRM_Utils_Type::T_INT, $headerPattern = '//', $dataPattern = '//') {
    if (empty($name) || $name == "do_not_import") {
      $this->_fields['doNotImport'] = new CRM_HRJob_Import_Field($name, $title, $type, $headerPattern, $dataPattern);
    }
    else {
      foreach($this->_entity as $entity) {
        $entityName = "CRM_HRJob_BAO_{$entity}";
        $tempField = $entityName::importableFields($entity, NULL);
        if (array_key_exists("$name", $tempField)) {
          $this->_fields[$name] = new CRM_HRJob_Import_Field($name, $title, $type, $headerPattern, $dataPattern);
          $this->_activeEntityFields[$entity][$name] = $this->_fields[$name];
        }
      }
    }
  }

  /**
   * Store parser values
   *
   * @param CRM_Core_Session $store
   *
   * @return void
   * @access public
   */
  function set($store, $mode = self::MODE_SUMMARY) {
    $store->set('fileSize', $this->_fileSize);
    $store->set('lineCount', $this->_lineCount);
    $store->set('seperator', $this->_separator);
    $store->set('fields', $this->getSelectValues());
    $store->set('fieldTypes', $this->getSelectTypes());

    $store->set('headerPatterns', $this->getHeaderPatterns());
    $store->set('dataPatterns', $this->getDataPatterns());
    $store->set('columnCount', $this->_activeFieldCount);
    $store->set('_entity', $this->_entity);
    $store->set('totalRowCount', $this->_totalCount);
    $store->set('validRowCount', $this->_validCount);
    $store->set('invalidRowCount', $this->_invalidRowCount);
    $store->set('conflictRowCount', $this->_conflictCount);

    if ($this->_invalidRowCount) {
      $store->set('errorsFileName', $this->_errorFileName);
    }
    if ($this->_conflictCount) {
      $store->set('conflictsFileName', $this->_conflictFileName);
    }
    if (isset($this->_rows) && !empty($this->_rows)) {
      $store->set('dataValues', $this->_rows);
    }

    if ($mode == self::MODE_IMPORT) {
      $store->set('duplicateRowCount', $this->_duplicateCount);
      if ($this->_duplicateCount) {
        $store->set('duplicatesFileName', $this->_duplicateFileName);
      }
    }
  }

  function isErrorInCoreData($params, &$errorMessage) {
    $fields = $this->_allFields;

    foreach ($params as $key => $value)  {
      if ($key == 'contact_id' && (empty($value) || !is_numeric($value))) {
        self::addToErrorMsg(ts('Please enter valid Contact ID'), $errorMessage);
      }
      if ($value) {
        if (array_key_exists($key, $fields)) {
          if (array_key_exists('enumValues', $fields[$key])) {
            $enumValue = $fields[$key]['enumValues'];
            $enumArray = explode(', ', $enumValue);
            if (!self::in_value(trim($value), $enumArray)) {
              self::addToErrorMsg($fields[$key]['title'], $errorMessage);
            }
          }
          if (array_key_exists('pseudoconstant', $fields[$key])) {
            $options = CRM_Core_OptionGroup::values($fields[$key]['pseudoconstant']['optionGroupName'], FALSE, FALSE, FALSE, NULL, 'name');
            if (!array_key_exists(strtolower(trim($value)), array_change_key_case($options))) {
              self::addToErrorMsg($fields[$key]['title'], $errorMessage);
            }
          }
          switch ($fields[$key]['type']) {
          case CRM_Utils_Type::T_BOOLEAN :
            if (CRM_Utils_String::strtoboolstr($value) === FALSE) {
              self::addToErrorMsg($fields[$key]['title'], $errorMessage);
            }
            break;
          case CRM_Utils_Type::T_INT :
          case CRM_Utils_Type::T_MONEY :
          case CRM_Utils_Type::T_FLOAT :
            if (!is_numeric($value)) {
              self::addToErrorMsg(ts("%1 is not numeric", $fields[$key]['title']), $errorMessage);
            }
            break;
          }
          switch ($key) {
          case 'contact_id':
            //Contact ID
            if ($key == 'contact_id' && (empty($value) || !is_numeric($value))) {
              self::addToErrorMsg(ts('Please enter Contact ID'), $errorMessage);
            }

          case 'hrjob_manager_contact_id':
            //manager
            $params = array(
              'contact_id' => $value,
            );
            $result = civicrm_api3('contact', 'get', $params);
            if ($result['count'] <=0 || ($result['values'][$result['id']]['contact_type'] != "Individual")) {
              self::addToErrorMsg($fields[$key]['title'], $errorMessage);
            }
            break;

          case 'hrjob_health_provider':
            //health provider org
            $params = array(
              'contact_id' => $value,
              'contact_type' => 'Organization',
              'contact_sub_type' => 'health_insurance_provider',
            );

          case 'hrjob_health_provider_life_insurance':
            //life provider org
            $params = array(
              'contact_id' => $value,
              'contact_type' => 'Organization',
              'contact_sub_type' => 'life_insurance_provider',
            );

          case 'hrjob_funding_org_id':
            //funding organization
            $params = array(
              'contact_id' => $value,
              'contact_type' => 'Organization',
            );
            $result = civicrm_api3('contact', 'get', $params);
            if ($result['count'] <=0) {
              self::addToErrorMsg($fields[$key]['title'], $errorMessage);
            }
            break;
          }
        }
      }
    }
  }

  /**
   * function to ckeck a value present or not in a array
   *
   * @return ture if value present in array or retun false
   *
   * @access public
   */
  function in_value($value, $valueArray) {
    foreach ($valueArray as $key => $v) {
      //fix for CRM-1514
      if (strtolower(trim($v, ".")) == strtolower(trim($value, "."))) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * function to build error-message containing error-fields
   *
   * @param String   $errorName      A string containing error-field name.
   * @param String   $errorMessage   A string containing all the error-fields, where the new errorName is concatenated.
   *
   * @static
   * @access public
   */
  function addToErrorMsg($errorName, &$errorMessage) {
    if ($errorMessage) {
      $errorMessage .= "; $errorName";
    }
    else {
      $errorMessage = $errorName;
    }
  }

  /**
   * Export data to a CSV file
   *
   * @param string $filename
   * @param array $header
   * @param data $data
   *
   * @return void
   * @access public
   */
  function exportCSV($fileName, $header, $data) {
    $output = array();
    $fd = fopen($fileName, 'w');

    foreach ($header as $key => $value) {
      $header[$key] = "\"$value\"";
    }
    $config = CRM_Core_Config::singleton();
    $output[] = implode($config->fieldSeparator, $header);

    foreach ($data as $datum) {
      foreach ($datum as $key => $value) {
        if (is_array($value)) {
          foreach ($value[0] as $k1 => $v1) {
            if ($k1 == 'location_type_id') {
              continue;
            }
            $datum[$k1] = $v1;
          }
        }
        else {
          $datum[$key] = "\"$value\"";
        }
      }
      $output[] = implode($config->fieldSeparator, $datum);
    }
    fwrite($fd, implode("\n", $output));
    fclose($fd);
  }
}