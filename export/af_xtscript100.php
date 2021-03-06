<?php

/****************************************************************************
*                                                                           *
*  Amicron-Faktura Schnittstelle fuer xtCommerce                              *
*                                                                           *
*  This program is free software; you can redistribute it and/or            *
*  modify it under the terms of the GNU General Public License              *
*  as published by the Free Software Foundation; either version 2           *
*  of the License, or any later version.                                    *
*                                                                           *
*  This program is distributed in the hope that it will be useful,          *
*  but WITHOUT ANY WARRANTY; without even the implied warranty of           *
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            *
*  GNU General Public License for more details.                             *
*                                                                           *
*  You should have received a copy of the GNU General Public License        *
*  along with this program; if not, write to the Free Software              *
*  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA                *
*  02111-1307, USA.                                                         *
*                                                                           *
*  Beschreibung : Script zum Datenaustausch Amicron-Faktura <-->            *
*                 xtCommerce-Shop                                           *
*                                                                           *
*****************************************************************************/

  /* Beispiel fuer Useragent
     if (_SERVER["HTTP_USER_AGENT"]!='Amicron-Faktura') exit;
 */

define('SET_TIME_LITMIT',0);   // use   xtc_set_time_limit(0);
define('CHARSET','utf-8');

require('../includes/application_top_export.php');

// 18.11.05 NC: wird ab 3.0.4 in Image_Manipulator getestet
define('_VALID_XTC',true);

// falls die MWST vom shop vertauscht wird, hier false setzen.
define('SWITCH_MWST',true);

include(DIR_FS_DOCUMENT_ROOT.'admin/includes/classes/'.IMAGE_MANIPULATOR);

require_once(DIR_FS_INC . 'xtc_not_null.inc.php');
require_once(DIR_FS_INC . 'xtc_redirect.inc.php');
require_once(DIR_FS_INC . 'xtc_rand.inc.php');

require_once(DIR_FS_INC . 'xtc_not_null.inc.php');



// ACHTUNG: diese Zeilen werden unter Delphi aus dem Resourcestring gesucht. Daher hier
// nichts aendern
$version_major = 2;
$version_minor = 8;

//error_reporting(E_ALL);

if ($_GET['NoHeader']!="Y")
{
  header ("Last-Modified: ". gmdate ("D, d M Y H:i:s"). " GMT");  // immer geaendert
  header ("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
  header ("Pragma: no-cache"); // HTTP/1.0
  if ($_GET['FullHeader']=="Y")
  {
    header ("Content-type: text/xml");
  }
}

class upload {
    var $file, $filename, $destination, $permissions, $extensions, $tmp_filename;

    function upload($file = '', $destination = '', $permissions = '777', $extensions = '') {

      $this->set_file($file);
      $this->set_destination($destination);
      $this->set_permissions($permissions);
      $this->set_extensions($extensions);

      if (xtc_not_null($this->file) && xtc_not_null($this->destination)) {
        if ( ($this->parse() == true) && ($this->save() == true) ) {
          return true;
        } else {
          return false;
        }
      }
    }

    function parse() {
      global $messageStack;
      if (isset($_FILES[$this->file])) {
        $file = array('name' => $_FILES[$this->file]['name'],
                      'type' => $_FILES[$this->file]['type'],
                      'size' => $_FILES[$this->file]['size'],
                      'tmp_name' => $_FILES[$this->file]['tmp_name']);
      } elseif (isset($_FILES[$this->file])) {

        $file = array('name' => $_FILES[$this->file]['name'],
                      'type' => $_FILES[$this->file]['type'],
                      'size' => $_FILES[$this->file]['size'],
                      'tmp_name' => $_FILES[$this->file]['tmp_name']);
      } else {
        $file = array('name' => $GLOBALS[$this->file . '_name'],
                      'type' => $GLOBALS[$this->file . '_type'],
                      'size' => $GLOBALS[$this->file . '_size'],
                      'tmp_name' => $GLOBALS[$this->file]);
      }

      if ( xtc_not_null($file['tmp_name']) && ($file['tmp_name'] != 'none') && is_uploaded_file($file['tmp_name']) ) {
        if (sizeof($this->extensions) > 0) {
          if (!in_array(strtolower(substr($file['name'], strrpos($file['name'], '.')+1)), $this->extensions)) {
  //          $messageStack->add_session(ERROR_FILETYPE_NOT_ALLOWED, 'error');

            return false;
          }
        }

        $this->set_file($file);
        $this->set_filename($file['name']);
        $this->set_tmp_filename($file['tmp_name']);

        return $this->check_destination();
      } else {

   //     if ($file['tmp_name']=='none') $messageStack->add_session(WARNING_NO_FILE_UPLOADED, 'warning');

        return false;
      }
    }

    function save() {
      global $messageStack;

      if (substr($this->destination, -1) != '/') $this->destination .= '/';

      // GDlib check
      if (!function_exists("imagecreatefromgif")) {

        // check if uploaded file = gif
        if ($this->destination==DIR_FS_CATALOG_ORIGINAL_IMAGES) {
            // check if merge image is defined .gif
            if (strstr(PRODUCT_IMAGE_THUMBNAIL_MERGE,'.gif') ||
                strstr(PRODUCT_IMAGE_INFO_MERGE,'.gif') ||
                strstr(PRODUCT_IMAGE_POPUP_MERGE,'.gif')) {

      //          $messageStack->add_session(ERROR_GIF_MERGE, 'error');
                return false;

            }
            // check if uploaded image = .gif
            if (strstr($this->filename,'.gif')) {
      //       $messageStack->add_session(ERROR_GIF_UPLOAD, 'error');
             return false;
            }

        }

      }

      if (move_uploaded_file($this->file['tmp_name'], $this->destination . $this->filename)) {
        chmod($this->destination . $this->filename, $this->permissions);

    //    $messageStack->add_session(SUCCESS_FILE_SAVED_SUCCESSFULLY, 'success');

        return true;
      } else {
    //    $messageStack->add_session(ERROR_FILE_NOT_SAVED, 'error');

        return false;
      }
    }

    function set_file($file) {
      $this->file = $file;
    }

    function set_destination($destination) {
      $this->destination = $destination;
    }

    function set_permissions($permissions) {
      $this->permissions = octdec($permissions);
    }

    function set_filename($filename) {
      $this->filename = $filename;
    }

    function set_tmp_filename($filename) {
      $this->tmp_filename = $filename;
    }

    function set_extensions($extensions) {
      if (xtc_not_null($extensions)) {
        if (is_array($extensions)) {
          $this->extensions = $extensions;
        } else {
          $this->extensions = array($extensions);
        }
      } else {
        $this->extensions = array();
      }
    }

    function check_destination() {
      global $messageStack;

      if (!is_writeable($this->destination)) {
        if (is_dir($this->destination)) {
    //      $messageStack->add_session(sprintf(ERROR_DESTINATION_NOT_WRITEABLE, $this->destination), 'error');
        } else {
    //      $messageStack->add_session(sprintf(ERROR_DESTINATION_DOES_NOT_EXIST, $this->destination), 'error');
        }

        return false;
      } else {
        return true;
      }
    }

  }

function clear_string($value) {
  $string=str_replace("'",'',$value);
  $string=str_replace(')','',$string);
  $string=str_replace('(','',$string);
  $array=explode(',',$string);
  return $array;
}

// 26.10.2005 Bei GD<2.0 existiert die Funktion "ImageCreateTrueColor" noch
// nicht, daher ein Workaround
if(!function_exists('ImageCreateTrueColor'))
{
  function ImageCreateTrueColor($new_x,$new_y)
  {
    return ImageCreate($new_x,$new_y);
  }
}

if(!function_exists('ImageCopyResampled'))
{
  function ImageCopyResampled($dst_im,$src_im,$dstX,$dstY,$srcX,$srcY,$dstW,$dstH,$srcW,$srcH)
  {
    return ImageCopyResized($dst_im,$src_im,$dstX,$dstY,$srcX,$srcY,$dstW,$dstH,$srcW,$srcH);
  }
}

$action = xtc_db_prepare_input(isset($_POST['action']) ?
  $_POST['action'] : $_GET['action']);

$user = xtc_db_prepare_input(isset($_POST['user']) ?
  $_POST['user'] : $_GET['user']);

$password = xtc_db_prepare_input(isset($_POST['password']) ?
  $_POST['password'] : $_GET['password']);

  // Default-Sprache
  $LangID = 1;

if ($_GET['Debug']=="Y")
{
  ShowDebug();
  exit;
}

  switch ($action)
  {
    // Versionsausgabe
    case 'read_version':
      ReadVersion();
      exit;

    case 'read_languages':
      if (CheckLogin($user,$password))
      {
        ReadLanguages();
      }
      exit;

    case 'read_categories':
      if (CheckLogin($user,$password))
      {
        ReadCategories();
      }
      exit;

    case 'write_artikel':
      if (CheckLogin($user,$password))
      {
        WriteArtikel();
      }
      exit;

    case 'write_categorie':
      if (CheckLogin($user,$password))
      {
        WriteCategorie();
      }
      exit;

    case 'read_artikel':
      if (CheckLogin($user,$password))
      {
        ReadArtikel();
      }
      exit;

    case 'get_artikel_image':
      if (CheckLogin($user,$password))
      {
        GetArtikelImage();
      }
      exit;

    case 'read_hersteller':
      if (CheckLogin($user,$password))
      {
        ReadHersteller();
      }
      exit;

    case 'write_hersteller':
      if (CheckLogin($user,$password))
      {
        WriteHersteller();
      }
      exit;

    case 'delete_artikel':
      if (CheckLogin($user,$password))
      {
        DeleteArtikel();
      }
      exit;

    case 'order_update':
      if (CheckLogin($user,$password))
      {
        OrderUpdate();
      }
      exit;

    case 'read_shopdata':
      if (CheckLogin($user,$password))
      {
        ReadShopData();
      }
      exit;

    case 'orders_export':
      if (CheckLogin($user,$password))
      {
        ReadAuftraege();
      }
      exit;

    default:
      ReadVersion();
      exit;
  } // switch

function CheckLogin($user,$password)
{
  $ok = FALSE;

  // Ist nicht md5 verschluesselt, wenn mit %% und muss nachgeholt werden
  if (substr($password,0,2)=='%%')
  {
    $password=md5(substr($password,2,40));
  }

  if ($user!='')
  {
    $customers_query=xtc_db_query("select customers_id,customers_status,customers_password" .
                             " from " . TABLE_CUSTOMERS .
                             " where customers_email_address = '$user'");

    if ($customers = xtc_db_fetch_array($customers_query))
    {
      // check if customer is Admin
      if (($customers['customers_status']=='0') && ($customers['customers_password']==$password))
      {
        $ok = TRUE;
      }
    }
  }

  if (!$ok)
  {
    // Nicht als XML ausgeben, da Textausgabe direkt als Fehler gesehen wird, waehrend ein <Status> auch fuer die
    // Versionsnummer ausgewertet wird
    echo "Anmeldung: Name/Passwort nicht korrekt!";
  }

  return $ok;
}

function ShowDebug()
{
  global $action, $version_major, $version_minor;

  echo "<DEBUG>\n";

  echo "  <GetAction>$_GET[action]</GetAction>\n";
  echo "  <PostAction>$_POST[action]</PostAction>\n";

  echo "  <GetDaten>\n";
  foreach ($_GET as $Key => $Value)
  {
    echo "    <$Key>$Value</$Key>\n";
  }
  echo "  </GetDaten>\n";

  echo "  <PostDaten>\n";
  foreach ($_POST as $Key => $Value)
  {
    echo "    <$Key>$Value</$Key>\n";
  }
  echo "  </PostDaten>\n";
  echo "</DEBUG>\n";
}

function ReadVersion()
{
  global $action, $version_major, $version_minor;

  echo '<?xml version="1.0" encoding="' . CHARSET . '"?>' . "\n" .
       "<STATUS>\n" .
       "  <STATUS_DATA>\n" .
       "    <ACTION>$action</ACTION>\n" .
       "    <SCRIPT_VERSION_MAJOR>$version_major</SCRIPT_VERSION_MAJOR>\n" .
       "    <SCRIPT_VERSION_MINOR>$version_minor</SCRIPT_VERSION_MINOR>\n" .
       "    <SCRIPT_DEFAULTCHARSET>" . htmlspecialchars(ini_get('default_charset')) . "</SCRIPT_DEFAULTCHARSET>\n" .
       "  </STATUS_DATA>\n" .
       "</STATUS>\n\n";
}

function ReadLanguages()
{
  global $action;

  echo '<?xml version="1.0" encoding="' . CHARSET . '"?>' . "\n" .
       "<LANGUAGES>\n";

  $cmd = "select languages_id,name,code from " . TABLE_LANGUAGES;
  $languages_query = xtc_db_query($cmd);
  while ($languages = xtc_db_fetch_array($languages_query))
  {
    echo "  <LANGUAGES_DATA>\n" .
         "    <ID>$languages[languages_id]</ID>\n" .
         "    <NAME>" . htmlspecialchars($languages["name"]) . "</NAME>\n" .
         "    <CODE>" . htmlspecialchars($languages["code"]) . "</CODE>\n" .
         "  </LANGUAGES_DATA>\n";
  }

  echo "</LANGUAGES>\n";
}

function ReadCategories()
{
  global $action;


  echo '<?xml version="1.0" encoding="' . CHARSET . '"?>' . "\n" .
       "<CATEGORIES>\n";

  $cmd = "select categories_id, parent_id from " . TABLE_CATEGORIES .
         " order by parent_id, categories_id";

  $cat_query = xtc_db_query($cmd);
  while ($cat = xtc_db_fetch_array($cat_query))
  {
    echo "<CATEGORIES_DATA>\n" .
         "<ID>$cat[categories_id]</ID>\n" .
         "<PARENT_ID>$cat[parent_id]</PARENT_ID>\n" .
         "<NAMES>\n";

    $cmd = "select language_id, categories_name from " . TABLE_CATEGORIES_DESCRIPTION .
         " where categories_id=" . $cat['categories_id'];
    $names_query = xtc_db_query($cmd);
    while ($names = xtc_db_fetch_array($names_query))
    {
      echo "<NAMEENTRY>\n" .
           "<LANGUAGEID>$names[language_id]</LANGUAGEID>\n" .
           "<NAME>" . htmlspecialchars($names['categories_name']) . "</NAME>\n" .
           "</NAMEENTRY>\n";
    }

    echo "</NAMES>\n" .
         "</CATEGORIES_DATA>\n";
  }

  echo "</CATEGORIES>\n";
}

function WriteArtikel()
{
  global $action, $version_major, $version_minor;

  $ExportModus = xtc_db_prepare_input($_POST['ExportModus']);
  $Artikel_ID = (integer)(xtc_db_prepare_input($_POST['Artikel_ID']));
  $Kategorie_ID = (integer)($_POST['Artikel_Kategorie_ID']);
  $Hersteller_ID = (integer)($_POST['Hersteller_ID']);

  $Artikel_Artikelnr = xtc_db_prepare_input($_POST['Artikel_Artikelnr']);
  $Artikel_Menge = xtc_db_prepare_input($_POST['Artikel_Menge']);
  $Artikel_Preis = xtc_db_prepare_input($_POST['Artikel_Preis']);
  $Artikel_Gewicht = xtc_db_prepare_input($_POST['Artikel_Gewicht']);
  $Artikel_Status = xtc_db_prepare_input($_POST['Artikel_Status']);
  $Artikel_Steuersatz = xtc_db_prepare_input($_POST['Artikel_Steuersatz']);
  $Artikel_Bilddatei = xtc_db_prepare_input($_POST['Artikel_Bilddatei']);
  $Artikel_EAN = xtc_db_prepare_input($_POST['Artikel_EAN']);
  $Artikel_Lieferstatus = xtc_db_prepare_input($_POST['Artikel_Lieferstatus']);
  $Artikel_Startseite = (integer)(xtc_db_prepare_input($_POST['Artikel_Startseite']));
  $SkipImages = (bool)(xtc_db_prepare_input($_POST['SkipImages']));

  $Artikel_Texte = array();
  $i = 1;
  while(isset($_POST["Artikel_Bezeichnung{$i}"]))
  {
    $Artikel_Texte[$i] = array(
      'B' => xtc_db_prepare_input($_POST["Artikel_Bezeichnung{$i}"]),
      'T' => xtc_db_prepare_input($_POST["Artikel_Text{$i}"]),
      'S' => xtc_db_prepare_input($_POST["Artikel_Kurztext{$i}"]),
      'L' => (integer)(xtc_db_prepare_input($_POST["Artikel_TextLanguage{$i}"])),
      'MT' => xtc_db_prepare_input($_POST["Artikel_MetaTitle{$i}"]),
      'MD' => xtc_db_prepare_input($_POST["Artikel_MetaDescription{$i}"]),
      'MK' => xtc_db_prepare_input($_POST["Artikel_MetaKeywords{$i}"]),
      'URL' => xtc_db_prepare_input($_POST["Artikel_URL{$i}"]));
    $i++;
  }

  $Artikel_Preise = array();
  $i = 1;
  while(isset($_POST["Artikelpreise_Preis{$i}"]))
  {
    $Artikel_Preise[$i] = array(
      'P' => xtc_db_prepare_input($_POST["Artikelpreise_Preis{$i}"]),
      'G' => (integer)xtc_db_prepare_input($_POST["Artikelpreise_Gruppe{$i}"]),
      'M' => (integer)xtc_db_prepare_input($_POST["Artikelpreise_Menge{$i}"]));
    $i++;
  }

  $exists = FALSE;

  // Artikel laden
  if ($Artikel_ID!=0)
  {
    $cmd = "select products_image from " . TABLE_PRODUCTS .
      " where products_id='$Artikel_ID'";

    $artikel_query = xtc_db_query($cmd);
    if ($artikel = xtc_db_fetch_array($artikel_query))
    {
      $exists = TRUE;

      $Bilddatei = $artikel['products_image'];
      // Alte Bilder entfernen
      if (!$SkipImages && $ExportModus == 'Overwrite')
      {
        if ($Bilddatei != 'no_picture.gif' && $Bilddatei != '')
        {
          $cmd = "select count(*) as total from " . TABLE_PRODUCTS .
            " where products_image = '$Bilddatei'";
          $duplicate_image_query = xtc_db_query($cmd);
          $duplicate_image = xtc_db_fetch_array($duplicate_image_query);

          if ($duplicate_image['total'] < 2)
          {
            if (file_exists(DIR_FS_CATALOG_ORIGINAL_IMAGES . $Bilddatei))
            {
              @unlink(DIR_FS_CATALOG_ORIGINAL_IMAGES . $Bilddatei);
            }
            if (file_exists(DIR_FS_CATALOG_THUMBNAIL_IMAGES . $Bilddatei))
            {
              @unlink(DIR_FS_CATALOG_THUMBNAIL_IMAGES . $Bilddatei);
            }
            if (file_exists(DIR_FS_CATALOG_INFO_IMAGES . $Bilddatei))
            {
              @unlink(DIR_FS_CATALOG_INFO_IMAGES . $Bilddatei);
            }
            if (file_exists(DIR_FS_CATALOG_POPUP_IMAGES . $Bilddatei))
            {
              @unlink(DIR_FS_CATALOG_POPUP_IMAGES . $Bilddatei);
            }
            if (file_exists(DIR_FS_CATALOG_MINI_IMAGES . $Bilddatei))
            {
              @unlink(DIR_FS_CATALOG_MINI_IMAGES . $Bilddatei);
            }
            $image_subdir = BIG_IMAGE_SUBDIR;
            if (substr($image_subdir, -1) != '/')
            {
              $image_subdir .= '/';
            }
            if (file_exists(DIR_FS_CATALOG_IMAGES . $image_subdir . $Bilddatei))
            {
              @unlink(DIR_FS_CATALOG_IMAGES . $image_subdir . $Bilddatei);
            }
          }
        }

        $cmd = "select image_name from " . TABLE_PRODUCTS_IMAGES .
          " where products_id='$Artikel_ID'";
        $images_query = xtc_db_query($cmd);
        while ($images = xtc_db_fetch_array($images_query))
        {
          $Bilddatei = $images['image_name'];
          if ($Bilddatei != 'no_picture.gif' && $Bilddatei != '')
          {
            $cmd = "select count(*) as total from " . TABLE_PRODUCTS_IMAGES .
              " where image_name = '$Bilddatei'";
            $duplicate_image_query = xtc_db_query($cmd);
            $duplicate_image = xtc_db_fetch_array($duplicate_image_query);

            if ($duplicate_image['total'] < 2)
            {
              if (file_exists(DIR_FS_CATALOG_ORIGINAL_IMAGES . $Bilddatei))
              {
                @unlink(DIR_FS_CATALOG_ORIGINAL_IMAGES . $Bilddatei);
              }
              if (file_exists(DIR_FS_CATALOG_THUMBNAIL_IMAGES . $Bilddatei))
              {
                @unlink(DIR_FS_CATALOG_THUMBNAIL_IMAGES . $Bilddatei);
              }
              if (file_exists(DIR_FS_CATALOG_INFO_IMAGES . $Bilddatei))
              {
                @unlink(DIR_FS_CATALOG_INFO_IMAGES . $Bilddatei);
              }
              if (file_exists(DIR_FS_CATALOG_POPUP_IMAGES . $Bilddatei))
              {
                @unlink(DIR_FS_CATALOG_POPUP_IMAGES . $Bilddatei);
              }
              if (file_exists(DIR_FS_CATALOG_MINI_IMAGES . $Bilddatei))
              {
                @unlink(DIR_FS_CATALOG_MINI_IMAGES . $Bilddatei);
              }
              $image_subdir = BIG_IMAGE_SUBDIR;
              if (substr($image_subdir, -1) != '/')
              {
                $image_subdir .= '/';
              }
              if (file_exists(DIR_FS_CATALOG_IMAGES . $image_subdir . $Bilddatei))
              {
                @unlink(DIR_FS_CATALOG_IMAGES . $image_subdir . $Bilddatei);
              }
            }
          }
        }

        xtc_db_query("delete from " . TABLE_PRODUCTS_IMAGES . " where products_id = '$Artikel_ID'");
      }

    }
    else
    {
      $exists = FALSE;
    }
  }

  $mode='NONE';

  // sofern es kein Datensatz gibt, oder er ueberschrieben werden kann, weitermachen
  if (!$exists || $ExportModus!='NoOverwrite')
  {

    // Array nur komplett fuellen, wenn ein Insert oder ein Komplettes Update
    // durchgefuehrt wird (und nicht nur der Preis)
    if (!$exists || $ExportModus=='Overwrite')
    {
      $sql_data_array = array(
        'products_id' => $Artikel_ID,
        'products_quantity' => $Artikel_Menge,
        'products_model' => $Artikel_Artikelnr,
        'products_price' => $Artikel_Preis,
        'products_weight' => $Artikel_Gewicht,
        'products_ean' => $Artikel_EAN,
        'products_status' => $Artikel_Status,
        'products_tax_class_id' => $Artikel_Steuersatz,
        'products_shippingtime' => $Artikel_Lieferstatus,
        'products_startpage' => $Artikel_Startseite,
        'manufacturers_id' => $Hersteller_ID);

      if (!$SkipImages)
      {
        $sql_data_array['products_image'] = $Artikel_Bilddatei;
      }

      if (isset($_POST['Artikel_VPEValue']))
      {
        $sql_data_array['products_vpe_status'] = 1;
        $sql_data_array['products_vpe_value'] = xtc_db_prepare_input($_POST['Artikel_VPEValue']);
      }

      if (isset($_POST['Artikel_Grundeinheit']) && isset($_POST['Artikel_Masseinheit']))
      {
        $vpe_name = xtc_db_prepare_input($_POST['Artikel_Grundeinheit']) . ' ' .
          xtc_db_prepare_input($_POST['Artikel_Masseinheit']);
        $vpe_id = 0;
        foreach ($Artikel_Texte as $i => $AText)
        {
          if ($AText['L'] <> 0)
          {
            $cmd = "select products_vpe_id from " . TABLE_PRODUCTS_VPE . " where products_vpe_name='" . $vpe_name . "' and " .
              "language_id='". $AText['L'] . "'";
            $vpe_query = xtc_db_query($cmd);
            if ($vpe = xtc_db_fetch_array($vpe_query))
            {
              $vpe_id = $vpe['products_vpe_id'];
            }
            else
            {
              if ($vpe_id == 0)
              {
                $cmd = "select max(products_vpe_id) vpemax from " . TABLE_PRODUCTS_VPE;
                $vpemax_query = xtc_db_query($cmd);
                $vpemax = xtc_db_fetch_array($vpemax_query);
                $vpe_id = $vpemax['vpemax'] + 1;
              }

              $vpe_data = array(
                'products_vpe_id' => $vpe_id,
                'products_vpe_name' => $vpe_name,
                'language_id' => $AText['L']);
              xtc_db_perform(TABLE_PRODUCTS_VPE, $vpe_data);
            }
          }
        }
        $sql_data_array['products_vpe'] = $vpe_id;
      }
    }
    else
    {
      if ($ExportModus=='PriceOnly')
      {
        // nur der Preis wird geaendert
        $sql_data_array = array(
          'products_price' => $Artikel_Preis);
      }
      if ($ExportModus=='QuantityOnly')
      {
        // nur die Menge wird geaendert
        $sql_data_array = array(
          'products_quantity' => $Artikel_Menge);
      }
      if ($ExportModus=='PriceAndQuantityOnly')
      {
        // nur der Preis und die Menge wird geaendert
        $sql_data_array = array(
          'products_quantity' => $Artikel_Menge,
          'products_price' => $Artikel_Preis);
      }
    }

    if (!$exists) // Neuanlage (ID wird an Amicron-Faktura zurueckgegeben !!!)
    {
      $mode='INSERTED';
      $insert_sql_data = array('products_date_added' => 'now()');
      $sql_data_array = array_merge($sql_data_array, $insert_sql_data);
      xtc_db_perform(TABLE_PRODUCTS, $sql_data_array);
      $Artikel_ID = xtc_db_insert_id();
    }
    elseif ($exists) //Update
    {
      $mode='UPDATED';
      $update_sql_data = array('products_last_modified' => 'now()');
      $sql_data_array = array_merge($sql_data_array, $update_sql_data);

      xtc_db_perform(TABLE_PRODUCTS, $sql_data_array, 'update', "products_id = '$Artikel_ID'");
    }

    // Details nur beschreiben, wenn Uebermodus oder Datensatz nicht da ist
    if (!$exists || $ExportModus=='Overwrite')
    {
      foreach ($Artikel_Texte as $i => $AText)
      {
        if ($AText['L'] <> 0)
        {
          $sql_data_array = array(
            'products_name' => $AText['B'],
            'products_description' => $AText['T'],
            'products_short_description' => $AText['S'],
            'products_meta_title' => $AText['MT'],
            'products_meta_description' => $AText['MD'],
            'products_meta_keywords' => $AText['MK'],
            'products_url' => $AText['URL']);

          // Bestehende Daten laden
          $cmd = "select products_id from " . TABLE_PRODUCTS_DESCRIPTION .
            " where products_id='$Artikel_ID' and language_id='". $AText['L'] . "'";

          $desc_query = xtc_db_query($cmd);
          if ($desc = xtc_db_fetch_array($desc_query))
          {
            xtc_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array, 'update',
              "products_id ='$Artikel_ID' and language_id = '" . $AText['L'] . "'");
          }
          else
          {
            $sql_data_array['products_id'] = $Artikel_ID;
            $sql_data_array['language_id'] = $AText['L'];

            xtc_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array);
          }
        }
      }

      // Kategorie eintragen, wenn Artikel neu und zugewiesen
      // bei einem vorhandenen Artikel kann die Kategorie nicht mehr geaendert werden, da mehrere Kategorien zulaessig,
      // und daher die alte Kategorie uebergeben werden muesste
      if (!$exists && $Kategorie_ID!=0)
      {
        // Bestehende Kategorie laden
        $cmd = "select products_id,categories_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where " .
          "products_id='$Artikel_ID' and categories_id='$Kategorie_ID'";

        // Nur eintragen, wenn diese Kategorie noch nicht zugeordnet ist
        $desc_query = xtc_db_query($cmd);
        if (!($desc = xtc_db_fetch_array($desc_query)))
        {
          $insert_sql_data= array(
            'products_id' => $Artikel_ID,
            'categories_id' => $Kategorie_ID);

          xtc_db_perform(TABLE_PRODUCTS_TO_CATEGORIES, $insert_sql_data);
        }
      }

      // Bilder laden
      if (!$SkipImages)
      {
        if (isset($_POST['Artikel_Bilddatei']))
        {
          $products_image = new upload('artikel_image');
          $products_image->set_destination(DIR_FS_CATALOG.DIR_WS_ORIGINAL_IMAGES);
          if ($products_image->parse())
          {
            $products_image->save();
          }
          $products_image_name = $products_image->filename;

          // rewrite values to use resample classes
          define('DIR_FS_CATALOG_ORIGINAL_IMAGES',DIR_FS_CATALOG.DIR_WS_ORIGINAL_IMAGES);
          define('DIR_FS_CATALOG_INFO_IMAGES',DIR_FS_CATALOG.DIR_WS_INFO_IMAGES);
          define('DIR_FS_CATALOG_POPUP_IMAGES',DIR_FS_CATALOG.DIR_WS_POPUP_IMAGES);
          define('DIR_FS_CATALOG_MINI_IMAGES',DIR_FS_CATALOG.DIR_WS_MINI_IMAGES);
          define('DIR_FS_CATALOG_THUMBNAIL_IMAGES',DIR_FS_CATALOG.DIR_WS_THUMBNAIL_IMAGES);
          define('DIR_FS_CATALOG_IMAGES',DIR_FS_CATALOG.DIR_WS_IMAGES);

          // generate resampled images
          require(DIR_FS_DOCUMENT_ROOT.'admin/includes/product_thumbnail_images.php');
          require(DIR_FS_DOCUMENT_ROOT.'admin/includes/product_info_images.php');
          require(DIR_FS_DOCUMENT_ROOT.'admin/includes/product_popup_images.php');
          require(DIR_FS_DOCUMENT_ROOT.'admin/includes/product_mini_images.php');
        }

        if (isset($_POST['Artikel_Bilddateien']))
        {
          for($i=0; $i<(integer)($_POST['Artikel_Bilddateien']); $i++)
          {
            $products_image = new upload("artikel_images$i");
            $products_image->set_destination(DIR_FS_CATALOG.DIR_WS_ORIGINAL_IMAGES);
            if ($products_image->parse())
            {
              $products_image->save();
            }
            $products_image_name = $products_image->filename;

            // $ispdf = False;
            // $pos = strripos($products_image_name, '.pdf');
            // if ($pos !== False)
            // {
            //   $ispdf = $pos == strlen($products_image_name) - 4;

            // }
            // if (!$ispdf)
            // {
              // rewrite values to use resample classes
              define('DIR_FS_CATALOG_ORIGINAL_IMAGES',DIR_FS_CATALOG.DIR_WS_ORIGINAL_IMAGES);
              define('DIR_FS_CATALOG_INFO_IMAGES',DIR_FS_CATALOG.DIR_WS_INFO_IMAGES);
              define('DIR_FS_CATALOG_POPUP_IMAGES',DIR_FS_CATALOG.DIR_WS_POPUP_IMAGES);
              define('DIR_FS_CATALOG_MINI_IMAGES',DIR_FS_CATALOG.DIR_WS_MINI_IMAGES);
              define('DIR_FS_CATALOG_THUMBNAIL_IMAGES',DIR_FS_CATALOG.DIR_WS_THUMBNAIL_IMAGES);
              define('DIR_FS_CATALOG_IMAGES',DIR_FS_CATALOG.DIR_WS_IMAGES);

              // generate resampled images
              require(DIR_FS_DOCUMENT_ROOT.'admin/includes/product_thumbnail_images.php');
              require(DIR_FS_DOCUMENT_ROOT.'admin/includes/product_info_images.php');
              require(DIR_FS_DOCUMENT_ROOT.'admin/includes/product_popup_images.php');
              require(DIR_FS_DOCUMENT_ROOT.'admin/includes/product_mini_images.php');
            // }

            $insert_sql_data= array(
              'products_id' => $Artikel_ID,
              'image_nr' => $i + 1, // ab 1
              'image_name' => $products_image_name);

            xtc_db_perform(TABLE_PRODUCTS_IMAGES, $insert_sql_data);
          }
        }
      } // Bilder laden
    } // Details beschreiben
  }

  if (!$exists || $ExportModus=='Overwrite' || $ExportModus=='PriceOnly' || $ExportModus=='PriceAndQuantityOnly')
  {
    $cmd = "select distinct(customers_status_id) from " . TABLE_CUSTOMERS_STATUS;
    $ss_query = xtc_db_query($cmd);
    while ($ss = xtc_db_fetch_array($ss_query))
    {
      xtc_db_query("delete from " . TABLE_PERSONAL_OFFERS_BY . $ss[customers_status_id] .
          " where products_id = '$Artikel_ID'");
    }
    foreach ($Artikel_Preise as $i => $APreis)
    {
      $sql_data_array = array(
        'products_id' => $Artikel_ID,
        'quantity' => $APreis['M'],
        'personal_offer' => $APreis['P']);

      xtc_db_perform(TABLE_PERSONAL_OFFERS_BY . $APreis['G'], $sql_data_array);
    }
  }

  echo '<?xml version="1.0" encoding="' . CHARSET . '"?>' . "\n" .
       "<STATUS>\n" .
       "  <STATUS_DATA>\n" .
       "    <MESSAGE>OK</MESSAGE>\n" .
       "    <MODE>$mode</MODE>\n" .
       "    <ID>$Artikel_ID</ID>\n" .
       "    <SCRIPT_VERSION_MAJOR>$version_major</SCRIPT_VERSION_MAJOR>\n" .
       "    <SCRIPT_VERSION_MINOR>$version_minor</SCRIPT_VERSION_MINOR>\n" .
       "  </STATUS_DATA>\n" .
       "</STATUS>\n\n";
}

function xtc_remove_product($product_id)
{
  global $LangID;

    $product_image_query = xtc_db_query("select products_image from " . TABLE_PRODUCTS . " where products_id = '" . xtc_db_input($product_id) . "'");
    $product_image = xtc_db_fetch_array($product_image_query);

    $duplicate_image_query = xtc_db_query("select count(*) as total from " . TABLE_PRODUCTS . " where products_image = '" . xtc_db_input($product_image['products_image']) . "'");
    $duplicate_image = xtc_db_fetch_array($duplicate_image_query);

    if ($duplicate_image['total'] < 2) {
      if (file_exists(DIR_FS_CATALOG_POPUP_IMAGES . $product_image['products_image'])) {
        @unlink(DIR_FS_CATALOG_POPUP_IMAGES . $product_image['products_image']);
      }
// START CHANGES
      $image_subdir = BIG_IMAGE_SUBDIR;
      if (substr($image_subdir, -1) != '/') $image_subdir .= '/';
      if (file_exists(DIR_FS_CATALOG_IMAGES . $image_subdir . $product_image['products_image'])) {
        @unlink(DIR_FS_CATALOG_IMAGES . $image_subdir . $product_image['products_image']);
      }
// END CHANGES
    }

    xtc_db_query("delete from " . TABLE_SPECIALS . " where products_id = '" . xtc_db_input($product_id) . "'");
    xtc_db_query("delete from " . TABLE_PRODUCTS . " where products_id = '" . xtc_db_input($product_id) . "'");
    xtc_db_query("delete from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . xtc_db_input($product_id) . "'");
    xtc_db_query("delete from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id = '" . xtc_db_input($product_id) . "'");
    xtc_db_query("delete from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id = '" . xtc_db_input($product_id) . "'");
    xtc_db_query("delete from " . TABLE_CUSTOMERS_BASKET . " where products_id = '" . xtc_db_input($product_id) . "'");
    xtc_db_query("delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where products_id = '" . xtc_db_input($product_id) . "'");


    // get statuses
    $customers_statuses_array = array(array());

     $customers_statuses_query = xtc_db_query("select * from " . TABLE_CUSTOMERS_STATUS . " where language_id = '".$LangID."' order by customers_status_id");

     while ($customers_statuses = xtc_db_fetch_array($customers_statuses_query)) {
       $customers_statuses_array[] = array('id' => $customers_statuses['customers_status_id'],
                                           'text' => $customers_statuses['customers_status_name']);

      }

    for ($i=0,$n=sizeof($customers_statuses_array);$i<$n;$i++) {
     xtc_db_query("delete from personal_offers_by_customers_status_" . $i . " where products_id = '" . xtc_db_input($product_id) . "'");

    }

    $product_reviews_query = xtc_db_query("select reviews_id from " . TABLE_REVIEWS . " where products_id = '" . xtc_db_input($product_id) . "'");
    while ($product_reviews = xtc_db_fetch_array($product_reviews_query)) {
      xtc_db_query("delete from " . TABLE_REVIEWS_DESCRIPTION . " where reviews_id = '" . $product_reviews['reviews_id'] . "'");
    }
    xtc_db_query("delete from " . TABLE_REVIEWS . " where products_id = '" . xtc_db_input($product_id) . "'");
}

function DeleteArtikel()
{
  global $action, $version_major, $version_minor;

  $Artikel_ID = (integer)(xtc_db_prepare_input($_POST['Artikel_ID']));

  xtc_db_query("delete from " . TABLE_PRODUCTS_TO_CATEGORIES .
    " where products_id='" . $Artikel_ID . "'");

  xtc_remove_product($Artikel_ID);

  echo '<?xml version="1.0" encoding="' . CHARSET . '"?>' . "\n" .
       "<STATUS>\n" .
       "  <STATUS_DATA>\n" .
       "    <MESSAGE>OK</MESSAGE>\n" .
       "    <SCRIPT_VERSION_MAJOR>$version_major</SCRIPT_VERSION_MAJOR>\n" .
       "    <SCRIPT_VERSION_MINOR>$version_minor</SCRIPT_VERSION_MINOR>\n" .
       "  </STATUS_DATA>\n" .
       "</STATUS>\n\n";
}

// ****************************************************************************
// Traegt eine neue Kategorie mit Bild zu einem Vaterknoten ein
// ****************************************************************************
function WriteCategorie()
{
  global $action, $version_major, $version_minor;

  $Kategorie_ID = (integer)($_POST['Artikel_Kategorie_ID']);
  $Kategorie_Vater_ID = (integer)(xtc_db_prepare_input($_POST['Kategorie_Vater_ID']));

  $Kategorie_Names = array(
    1 => array(
      'N' => xtc_db_prepare_input($_POST['Kategorie_Name1']),
      'L' => (integer)(xtc_db_prepare_input($_POST['Kategorie_NameLanguage1']))),
    2 => array(
      'N' => xtc_db_prepare_input($_POST['Kategorie_Name2']),
      'L' => (integer)(xtc_db_prepare_input($_POST['Kategorie_NameLanguage2']))),
    3 => array(
      'N' => xtc_db_prepare_input($_POST['Kategorie_Name3']),
      'L' => (integer)(xtc_db_prepare_input($_POST['Kategorie_NameLanguage3']))),
    4 => array(
      'N' => xtc_db_prepare_input($_POST['Kategorie_Name4']),
      'L' => (integer)(xtc_db_prepare_input($_POST['Kategorie_NameLanguage4']))));

  $exists = FALSE;

  if ($Kategorie_ID!=0)
  {
    $cmd = "select categories_id from " . TABLE_CATEGORIES .
      " where categories_id='" . $Kategorie_ID . "'";
    $cat_query = xtc_db_query($cmd);
    if ($cat = xtc_db_fetch_array($cat_query))
    {
      $exists = TRUE;
    }
  }

  if (!$exists)
  {
    // Kategorie erzeugen und ID ermitteln
    $insert_sql_data = array('parent_id' => $Kategorie_Vater_ID);
    xtc_db_perform(TABLE_CATEGORIES, $insert_sql_data);
    $Kategorie_ID = xtc_db_insert_id();
  }

  // Dateinamen aus der ID und der ueberlieferten Extension zusammensetzen, sofern Bild mitgeliefert
  // wird
  if (isset($_POST['Kategorie_Bildextension']))
  {
    $Kategorie_Bildextension = xtc_db_prepare_input($_POST['Kategorie_Bildextension']);
    $filename = "cat" . $Kategorie_ID . $Kategorie_Bildextension;
    $categories_image = new upload('Kategorie_image');
    $categories_image->set_destination(DIR_FS_CATALOG.DIR_WS_IMAGES.'categories/');
    if ($categories_image->parse())
    {
      $categories_image->set_filename($filename);
      $categories_image->save();
    }

    $sql_data_array = array('categories_image' => $filename);
    xtc_db_perform(TABLE_CATEGORIES, $sql_data_array, 'update',"categories_id='$Kategorie_ID'");
  }

  // Namen eintragen
  foreach ($Kategorie_Names as $i => $KName)
  {
    if ($KName['L'] <> 0)
    {
      // Bestehende Daten pruefen
      $cmd = "select categories_id from " . TABLE_CATEGORIES_DESCRIPTION .
        " where categories_id='$Kategorie_ID' and language_id='" . $KName['L'] . "'";

      $desc_query = xtc_db_query($cmd);
      if ($desc = xtc_db_fetch_array($desc_query))
      {
        $sql_data_array = array('categories_name' => $KName['N']);

        xtc_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array, 'update',
          "categories_id='$Kategorie_ID' and language_id = '" . $KName['L'] . "'");
      }
      else
      {
        $sql_data_array = array(
          'categories_id' => $Kategorie_ID,
          'language_id' => $KName['L'],
          'categories_name' => $KName['N']);

        xtc_db_perform(TABLE_CATEGORIES_DESCRIPTION, $sql_data_array);
      }
    }
  }

  echo '<?xml version="1.0" encoding="' . CHARSET . '"?>' . "\n" .
       "<STATUS>\n" .
       "  <STATUS_DATA>\n" .
       "    <MESSAGE>OK</MESSAGE>\n" .
       "    <ID>$Kategorie_ID</ID>\n" .
       "    <SCRIPT_VERSION_MAJOR>$version_major</SCRIPT_VERSION_MAJOR>\n" .
       "    <SCRIPT_VERSION_MINOR>$version_minor</SCRIPT_VERSION_MINOR>\n" .
       "  </STATUS_DATA>\n" .
       "</STATUS>\n\n";
}

// ****************************************************************************
// Liest alle Artikel aus
// ****************************************************************************
function ReadArtikel()
{
  global $action;

  $SkipImages = (bool)(xtc_db_prepare_input($_GET['SkipImages']));

  if (defined('SET_TIME_LIMIT')) { xtc_set_time_limit(0); }

  $cmd =
    "select products_id,products_quantity,products_model,products_image," .
    "products_price,products_weight,products_ean,products_status,products_tax_class_id," .
    "manufacturers_id,products_shippingtime,products_startpage,products_vpe," .
    "products_vpe_value,products_vpe_status from " . TABLE_PRODUCTS;

  if (isset($_GET['AbDatum']))
  {
    $cmd .= " where products_last_modified>='" . (xtc_db_prepare_input($_GET['AbDatum'])) ."'";
  }

  $HasLimit = (isset($_GET['LimitOffset']) && isset($_GET['LimitRowCount']));
  if ($HasLimit)
  {
    $cmd .= " limit " . (integer)(xtc_db_prepare_input($_GET['LimitOffset'])) . ', ' .
      (integer)(xtc_db_prepare_input($_GET['LimitRowCount']));
  }

  echo '<?xml version="1.0" encoding="' . CHARSET . '"?>' . "\n" .
       "<ARTIKEL";
  if ($HasLimit)
  {
    echo ' WithLimit="True"';
  }
  echo ">\n";


  $artikel_query = xtc_db_query($cmd);
  while ($artikel = xtc_db_fetch_array($artikel_query))
  {
    $cmdcat = "select categories_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = $artikel[products_id] LIMIT 1";
    $cat_query = xtc_db_query($cmdcat);
    $cat = xtc_db_fetch_array($cat_query);

    // Bild auslesen, wenn vorhanden
    $bildname = $artikel['products_image'];
    $bild = '';
    if ($bildname!='' && !$SkipImages && file_exists(DIR_FS_CATALOG.DIR_WS_ORIGINAL_IMAGES . $bildname) && $bildname!='no_picture.gif')
    {
      $bild = @implode("",@file(DIR_FS_CATALOG.DIR_WS_ORIGINAL_IMAGES . $bildname));
    }

    echo "<ARTIKEL_DATA>\n" .
         "<ID>$artikel[products_id]</ID>\n" .
         "<ARTIKELNR>" . htmlspecialchars($artikel['products_model']) . "</ARTIKELNR>\n" .
         "<TEXTE>\n";

    $cmd = "select language_id, products_name, products_description, products_short_description, products_meta_title," .
      " products_meta_description, products_meta_keywords, products_url from " . TABLE_PRODUCTS_DESCRIPTION .
      " where products_id=" . $artikel['products_id'];
    $texte_query = xtc_db_query($cmd);
    while ($texte = xtc_db_fetch_array($texte_query))
    {
      echo "<TEXT>\n" .
           "<LANGUAGEID>$texte[language_id]</LANGUAGEID>\n" .
           "<NAME>" . htmlspecialchars($texte['products_name']) ."</NAME>\n" .
           "<DESCRIPTION>" . htmlspecialchars($texte['products_description']) . "</DESCRIPTION>\n" .
           "<SHORTDESCRIPTION>" . htmlspecialchars($texte['products_short_description']) . "</SHORTDESCRIPTION>\n" .
           "<METATITLE>" . htmlspecialchars($texte['products_meta_title']) . "</METATITLE>\n" .
           "<METADESCRIPTION>" . htmlspecialchars($texte['products_meta_description']) . "</METADESCRIPTION>\n" .
           "<METAKEYWORDS>" . htmlspecialchars($texte['products_meta_keywords']) . "</METAKEYWORDS>\n" .
           "<URL>" . htmlspecialchars($texte['products_url']) . "</URL>\n" .
           "</TEXT>\n";
    }

    echo "</TEXTE>\n" .
         "<PREISE>\n";
    $cmd = "select distinct(customers_status_id) from " . TABLE_CUSTOMERS_STATUS;
    $ss_query = xtc_db_query($cmd);
    while ($ss = xtc_db_fetch_array($ss_query))
    {
      $cmd = "select quantity, personal_offer from " . TABLE_PERSONAL_OFFERS_BY . $ss[customers_status_id] .
      " where products_id=" . $artikel['products_id'];
      $preise_query = xtc_db_query($cmd);
      while ($preise = xtc_db_fetch_array($preise_query))
      {
        echo "<PREIS>\n" .
             "<GRUPPE>$ss[customers_status_id]</GRUPPE>\n" .
             "<MENGE>$preise[quantity]</MENGE>\n" .
             "<PREIS>$preise[personal_offer]</PREIS>\n" .
             "</PREIS>\n";
      }
    }
    echo "</PREISE>\n" .
         "<GEWICHT>$artikel[products_weight]</GEWICHT>\n" .
         "<EAN>" . htmlspecialchars($artikel['products_ean']) . "</EAN>\n" .
         "<PREIS>$artikel[products_price]</PREIS>\n" .
         "<MENGE>$artikel[products_quantity]</MENGE>\n" .
         "<STATUS>$artikel[products_status]</STATUS>\n" .
         "<STEUERSATZ>$artikel[products_tax_class_id]</STEUERSATZ>\n"  .
         "<HERSTELLER_ID>$artikel[manufacturers_id]</HERSTELLER_ID>\n" .
         "<KATEGORIE>$cat[categories_id]</KATEGORIE>\n" .
         "<BILDDATEI>" . htmlspecialchars($artikel['products_image']) . "</BILDDATEI>\n" .
         "<BILD>" . base64_encode($bild) . "</BILD>\n" .
         "<IMAGES>\n";

    $lastbild = $bild;
    if (!$SkipImages)
    {
      $cmd = "select image_name from " . TABLE_PRODUCTS_IMAGES .
        " where products_id=" . $artikel['products_id'];
      $images_query = xtc_db_query($cmd);
      while ($images = xtc_db_fetch_array($images_query))
      {
        $bildname = $images['image_name'];
        $bild = '';
        if ($bildname!='' && file_exists(DIR_FS_CATALOG.DIR_WS_ORIGINAL_IMAGES . $bildname) && $bildname!='no_picture.gif')
        {
          $bild = @implode("",@file(DIR_FS_CATALOG.DIR_WS_ORIGINAL_IMAGES . $bildname));
        }

        if ($bild != $lastbild)
        {
          echo "<IMAGE>\n" .
               "<NAME>" . htmlspecialchars($bildname) . "</NAME>" .
               "<BILD>" . base64_encode($bild) . "</BILD>\n" .
               "</IMAGE>\n";
          $lastbild = $bild;
        }
      }
    }

    echo "</IMAGES>\n" .
         "<LIEFERSTATUS>$artikel[products_shippingtime]</LIEFERSTATUS>\n" .
         "<STARTSEITE>$artikel[products_startpage]</STARTSEITE>\n";
    if ($artikel['products_vpe_status'] == 1)
    {
      echo "<VPEValue>$artikel[products_vpe_value]</VPEValue>";
    }
    if ($artikel['products_vpe'] != 0)
    {
      $cmd = "select products_vpe_name from " . TABLE_PRODUCTS_VPE . " where products_vpe_id='" . $artikel['products_vpe'] ."'";
      $vpe_query = xtc_db_query($cmd);
      if ($vpe = xtc_db_fetch_array($vpe_query))
      {
        echo "<VPEName>" . htmlspecialchars($vpe['products_vpe_name']) . "</VPEName>";
      }
    }
    echo "</ARTIKEL_DATA>\n";
  }

  echo "</ARTIKEL>\n";
}

function ReadShopData()
{
  global $action;

  echo '<?xml version="1.0" encoding="' . CHARSET . '"?>' . "\n" .
       "<SHOPDATA>\n";


  echo "<TAXRATES>\n";

  $cmd = "select tax_class_id,tax_rate from " . TABLE_TAX_RATES;
  $tax_query = xtc_db_query($cmd);
  while ($tax = xtc_db_fetch_array($tax_query))
  {
    echo "  <TAX>\n" .
         "    <ID>$tax[tax_class_id]</ID>\n" .
         "    <RATE>$tax[tax_rate]</RATE>\n" .
         "  </TAX>\n";
  }

  echo "</TAXRATES>\n";


  echo "<SHIPPINGSTATUS>\n";

  $cmd = "select shipping_status_id, language_id, shipping_status_name from " . TABLE_SHIPPING_STATUS;

  $ss_query = xtc_db_query($cmd);
  while ($ss = xtc_db_fetch_array($ss_query))
  {
    echo "<SHIPPINGSTATUS_DATA>\n" .
         "<ID>$ss[shipping_status_id]</ID>\n" .
         "<LANGUAGEID>$ss[language_id]</LANGUAGEID>\n" .
         "<NAME>" . htmlspecialchars($ss['shipping_status_name']) . "</NAME>\n" .
         "</SHIPPINGSTATUS_DATA>\n";
  }

  echo "</SHIPPINGSTATUS>\n";


  echo "<CUSTOMERSSTATUS>\n";

  $cmd = "select customers_status_id, language_id, customers_status_name from " . TABLE_CUSTOMERS_STATUS;

  $ss_query = xtc_db_query($cmd);
  while ($ss = xtc_db_fetch_array($ss_query))
  {
    echo "<CUSTOMERSSTATUS_DATA>\n" .
         "<ID>$ss[customers_status_id]</ID>\n" .
         "<LANGUAGEID>$ss[language_id]</LANGUAGEID>\n" .
         "<NAME>" . htmlspecialchars($ss['customers_status_name']) . "</NAME>\n" .
         "</CUSTOMERSSTATUS_DATA>\n";
  }

  echo "</CUSTOMERSSTATUS>\n";

  echo "</SHOPDATA>\n";
}

function ReadHersteller()
{
  global $action;

  echo '<?xml version="1.0" encoding="' . CHARSET . '"?>' . "\n" .
       "<MANUFACTURERS>\n";

  $cmd = "select manufacturers_id,manufacturers_name from " . TABLE_MANUFACTURERS;
  $manufacturers_query = xtc_db_query($cmd);
  while ($manufacturers = xtc_db_fetch_array($manufacturers_query))
  {
    echo "  <MANUFACTURERS_DATA>\n" .
         "    <ID>$manufacturers[manufacturers_id]</ID>\n" .
         "    <NAME>" . htmlspecialchars($manufacturers["manufacturers_name"]) . "</NAME>\n" .
         "  </MANUFACTURERS_DATA>\n";
  }

  echo "</MANUFACTURERS>\n";
}

function WriteHersteller()
{
  global $action, $version_major, $version_minor;

  $Hersteller_Name = xtc_db_prepare_input($_POST['Hersteller_Name']);

  $cmd = "select manufacturers_id,manufacturers_name from " . TABLE_MANUFACTURERS .
         " where manufacturers_name='$Hersteller_Name'";
  $manufacturers_query = xtc_db_query($cmd);
  if ($manufacturers = xtc_db_fetch_array($manufacturers_query))
  {
    $Hersteller_ID=$manufacturers['manufacturers_id'];
  }
  else
  {
    $insert_sql_data = array('manufacturers_name' => $Hersteller_Name);
    xtc_db_perform(TABLE_MANUFACTURERS, $insert_sql_data);
    $Hersteller_ID = xtc_db_insert_id();
  }

  echo '<?xml version="1.0" encoding="' . CHARSET . '"?>' . "\n" .
       "<STATUS>\n" .
       "  <STATUS_DATA>\n" .
       "    <MESSAGE>OK</MESSAGE>\n" .
       "    <ID>$Hersteller_ID</ID>\n" .
       "    <SCRIPT_VERSION_MAJOR>$version_major</SCRIPT_VERSION_MAJOR>\n" .
       "    <SCRIPT_VERSION_MINOR>$version_minor</SCRIPT_VERSION_MINOR>\n" .
       "  </STATUS_DATA>\n" .
       "</STATUS>\n\n";
}

// ****************************************************************************
// Aendert den Auftragsstatus
// ****************************************************************************
function OrderUpdate()
{
  global $action, $LangID, $version_major, $version_minor;

  $Order_ID = (integer)($_POST['Order_id']);
  $Status = (integer)($_POST['Status']);

  $orders_status_array = array();
  $cmd = "select orders_status_id, orders_status_name from " .
    TABLE_ORDERS_STATUS . " where language_id = '" . (int)$LangID . "'";
  $orders_status_query = xtc_db_query($cmd);
  while ($orders_status = xtc_db_fetch_array($orders_status_query))
  {
    $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
  }

  if ($Order_ID != 0 && isset($orders_status_array[$Status]))
  {
    $cmd = "select customers_name, customers_email_address, orders_status, date_purchased, language from " .
      TABLE_ORDERS . " where orders_id = '" . $Order_ID . "'";
    $Order_Query = xtc_db_query($cmd);
    if ($Order = xtc_db_fetch_array($Order_Query))
    {
      if ($Order['orders_status'] != $Status)
      {
        $update_sql_data = array(
          'orders_status' => $Status,
          'last_modified' => 'now()');
        xtc_db_perform(TABLE_ORDERS, $update_sql_data, 'update', "orders_id='" . $Order_ID . "'");

        // require functionblock for mails
        require_once(DIR_WS_CLASSES.'class.phpmailer.php');
        require_once(DIR_FS_INC . 'xtc_php_mail.inc.php');
        require_once(DIR_FS_INC . 'xtc_add_tax.inc.php');
        require_once(DIR_FS_INC . 'xtc_not_null.inc.php');
        require_once(DIR_FS_INC . 'xtc_href_link.inc.php');
        require_once(DIR_FS_INC . 'xtc_date_long.inc.php');
        require_once(DIR_FS_INC . 'xtc_check_agent.inc.php');
        $smarty = new Smarty;

        $smarty->assign('language', $Order['language']);
        $smarty->caching = false;
        $smarty->template_dir=DIR_FS_CATALOG.'templates';
        $smarty->compile_dir=DIR_FS_CATALOG.'templates_c';
        $smarty->config_dir=DIR_FS_CATALOG.'lang';
        $smarty->assign('tpl_path','templates/'.CURRENT_TEMPLATE.'/');
        $smarty->assign('logo_path',HTTP_SERVER  . DIR_WS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/img/');
        $smarty->assign('NAME',$Order['customers_name']);
        $smarty->assign('ORDER_NR',$Order_ID);
        $smarty->assign('ORDER_LINK',xtc_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $Order_ID, 'SSL'));
        $smarty->assign('ORDER_DATE',xtc_date_long($Order['date_purchased']));
        $smarty->assign('NOTIFY_COMMENTS', '');
        $smarty->assign('ORDER_STATUS', $orders_status_array[$Status]);

        $html_mail=$smarty->fetch(CURRENT_TEMPLATE . '/admin/mail/'.$Order['language'].'/change_order_mail.html');
        $txt_mail=$smarty->fetch(CURRENT_TEMPLATE . '/admin/mail/'.$Order['language'].'/change_order_mail.txt');

        // send mail with html/txt template
        xtc_php_mail(EMAIL_BILLING_ADDRESS,
                     EMAIL_BILLING_NAME ,
                     $Order['customers_email_address'],
                     $Order['customers_name'],
                     '',
                     EMAIL_BILLING_REPLY_ADDRESS,
                     EMAIL_BILLING_REPLY_ADDRESS_NAME,
                     '',
                     '',
                     EMAIL_BILLING_SUBJECT,
                     $html_mail ,
                     $txt_mail);

        $insert_sql_data = array(
          'orders_id' => $Order_ID,
          'orders_status_id' => $Status,
          'date_added' => 'now()',
          'customer_notified' => '1',
          'comments' => '');
        xtc_db_perform(TABLE_ORDERS_STATUS_HISTORY, $insert_sql_data);
      }
    }
  }

  echo '<?xml version="1.0" encoding="' . CHARSET . '"?>' . "\n" .
       "<STATUS>\n" .
       "  <STATUS_DATA>\n" .
       "    <MESSAGE>OK</MESSAGE>\n" .
       "    <SCRIPT_VERSION_MAJOR>$version_major</SCRIPT_VERSION_MAJOR>\n" .
       "    <SCRIPT_VERSION_MINOR>$version_minor</SCRIPT_VERSION_MINOR>\n" .
       "  </STATUS_DATA>\n" .
       "</STATUS>\n\n";
}

//county code abfragen CJ
function xtc_get_county_code($country_name)
{
  $zone_query = xtc_db_query("select zone_code from " . TABLE_ZONES . " where zone_name = '" . $country_name . "'");
  $zone = xtc_db_fetch_array($zone_query);
  return $zone['zone_code'];
}

function ReadAuftraege()
{
  global $LangID;

  $order_from = xtc_db_prepare_input($_GET['order_from']);
  $order_to = xtc_db_prepare_input($_GET['order_to']);
  $order_status = xtc_db_prepare_input($_GET['order_status']);

  $WithCountryCode = (bool)(xtc_db_prepare_input($_GET['WithCountryCode']));
  $WithAtrProduktModell = (bool)(xtc_db_prepare_input($_GET['WithAtrProduktModell']));

  // Steuer einstellungen
  $order_total_class['ot_cod_fee']['prefix'] = '+';
  $order_total_class['ot_cod_fee']['tax'] = '19';

  $order_total_class['ot_customer_discount']['prefix'] = '-';
  $order_total_class['ot_customer_discount']['tax'] = '19';

  $order_total_class['ot_gv']['prefix'] = '-';
  $order_total_class['ot_gv']['tax'] = '0';

  $order_total_class['ot_loworderfee']['prefix'] = '+';
  $order_total_class['ot_loworderfee']['tax'] = '19';

  $order_total_class['ot_shipping']['prefix'] = '+';
  $order_total_class['ot_shipping']['tax'] = '19';

  if (SET_TIME_LITMIT==1) xtc_set_time_limit(0);

  echo '<?xml version="1.0" encoding="' . CHARSET . '"?>' . "\n" .
       '<ORDER>' . "\n";

  $sql ="select * from " . TABLE_ORDERS . " where orders_id >= '" . xtc_db_input($order_from) . "'";
  if (!isset($order_status) && !isset($order_from))
  {
     $order_status = 1;
     $sql .= "and orders_status = " . $order_status;
  }
  if ($order_status!='') {

    $sql .= " and orders_status = " . $order_status;
  }
  $orders_query = xtc_db_query($sql);

  while ($orders = xtc_db_fetch_array($orders_query))
  {
    // Geburtsdatum laden
    $cust_sql = "select * from " . TABLE_CUSTOMERS . " where customers_id=" . $orders['customers_id'];
    $cust_query = xtc_db_query ($cust_sql);
    if (($cust_query) && ($cust_data = xtc_db_fetch_array($cust_query)))
    {
      $cust_dob = $cust_data['customers_dob'];
    }


    if ($orders['billing_company']=='') $orders['billing_company']=$orders['delivery_company'];
    if ($orders['billing_name']=='')  $orders['billing_name']=$orders['delivery_name'];
    if ($orders['billing_street_address']=='') $orders['billing_street_address']=$orders['delivery_street_address'];
    if ($orders['billing_postcode']=='')  $orders['billing_postcode']=$orders['delivery_postcode'];
    if ($orders['billing_city']=='')  $orders['billing_city']=$orders['delivery_city'];
    if ($orders['billing_suburb']=='') $orders['billing_suburb']=$orders['delivery_suburb'];
    if ($orders['billing_state']=='')  $orders['billing_state']=$orders['delivery_state'];
    if ($orders['billing_country_iso_code_2']=='')  $orders['billing_country_iso_code_2']=$orders['delivery_country_iso_code_2'];

    if ($WithCountryCode)
    {
      // county code auslesen CJ
      $billing_city = htmlspecialchars($orders['billing_city']) . ', ' .
        htmlspecialchars(xtc_get_county_code($orders['delivery_state']));
      $delivery_city = htmlspecialchars($orders['delivery_city']) . ', ' .
        htmlspecialchars(xtc_get_county_code($orders['billing_state']));
    }
    else
    {
      $billing_city = htmlspecialchars($orders['billing_city']);
      $delivery_city = htmlspecialchars($orders['delivery_city']);
    }

    echo '<ORDER_INFO>' . "\n" .
         '<ORDER_HEADER>' . "\n" .
         '<ORDER_ID>' . $orders['orders_id'] . '</ORDER_ID>' . "\n" .
         '<CUSTOMER_ID>' . $orders['customers_id'] . '</CUSTOMER_ID>' . "\n" .
         '<CUSTOMER_CID>' . $orders['customers_cid'] . '</CUSTOMER_CID>' . "\n" .
         '<CUSTOMER_GROUP>' . $orders['customers_status'] . '</CUSTOMER_GROUP>' . "\n" .
         '<ORDER_DATE>' . $orders['date_purchased'] . '</ORDER_DATE>' . "\n" .
         '<ORDER_STATUS>' . $orders['orders_status'] . '</ORDER_STATUS>' . "\n" .
         '<ORDER_IP>' . $orders['customers_ip'] . '</ORDER_IP>' . "\n" .
         '<ORDER_CURRENCY>' . htmlspecialchars($orders['currency']) . '</ORDER_CURRENCY>' . "\n" .
         '<ORDER_CURRENCY_VALUE>' . $orders['currency_value'] . '</ORDER_CURRENCY_VALUE>' . "\n" .
         '</ORDER_HEADER>' . "\n" .
         '<BILLING_ADDRESS>' . "\n" .
         '<COMPANY>' . htmlspecialchars($orders['billing_company']) . '</COMPANY>' . "\n" .
         '<NAME>' . htmlspecialchars($orders['billing_name']) . '</NAME>' . "\n" .
         '<STREET>' . htmlspecialchars($orders['billing_street_address']) . '</STREET>' . "\n" .
         '<ZIP>' . htmlspecialchars($orders['billing_postcode']) . '</ZIP>' . "\n" .
         '<CITY>' . $billing_city . '</CITY>' . "\n" .
         '<SUBURB>' . htmlspecialchars($orders['billing_suburb']) . '</SUBURB>' . "\n" .
         '<STATE>' . htmlspecialchars($orders['billing_state']) . '</STATE>' . "\n" .
         '<COUNTRY>' . htmlspecialchars($orders['billing_country_iso_code_2']) . '</COUNTRY>' . "\n" .
         '<TELEPHONE>' . htmlspecialchars($orders['customers_telephone']) . '</TELEPHONE>' . "\n" . // JAN
         '<TELEFAX>' . htmlspecialchars($cust_data['customers_fax']) . '</TELEFAX>' . "\n" .
         '<EMAIL>' . htmlspecialchars($orders['customers_email_address']) . '</EMAIL>' . "\n" . // JAN
         '<BIRTHDAY>' . htmlspecialchars($cust_dob) . '</BIRTHDAY>' . "\n" .
         '<VATID>' . htmlspecialchars($cust_data['customers_vat_id']) . '</VATID>' . "\n" .
         '</BILLING_ADDRESS>' . "\n" .
         '<DELIVERY_ADDRESS>' . "\n" .
         '<COMPANY>' . htmlspecialchars($orders['delivery_company']) . '</COMPANY>' . "\n" .
         '<NAME>' . htmlspecialchars($orders['delivery_name']) . '</NAME>' . "\n" .
         '<STREET>' . htmlspecialchars($orders['delivery_street_address']) . '</STREET>' . "\n" .
         '<ZIP>' . htmlspecialchars($orders['delivery_postcode']) . '</ZIP>' . "\n" .
         '<CITY>' . $delivery_city . '</CITY>' . "\n" .
         '<SUBURB>' . htmlspecialchars($orders['delivery_suburb']) . '</SUBURB>' . "\n" .
         '<STATE>' . htmlspecialchars($orders['delivery_state']) . '</STATE>' . "\n" .
         '<COUNTRY>' . htmlspecialchars($orders['delivery_country_iso_code_2']) . '</COUNTRY>' . "\n" .
         '</DELIVERY_ADDRESS>' . "\n" .
         '<PAYMENT>' . "\n" .
         '<PAYMENT_METHOD>' . htmlspecialchars($orders['payment_method']) . '</PAYMENT_METHOD>'  . "\n" .
         '<PAYMENT_CLASS>' . htmlspecialchars($orders['payment_class']) . '</PAYMENT_CLASS>'  . "\n";

    switch ($orders['payment_class'])
    {
      case 'banktransfer':
        // Bankverbindung laden, wenn aktiv
        $bank_name = '';
        $bank_blz  = '';
        $bank_kto  = '';
        $bank_inh  = '';
        $bank_stat = -1;
        $bank_sql = "select * from banktransfer where orders_id = " . $orders['orders_id'];
        $bank_query = xtc_db_query($bank_sql);
        if (($bank_query) && ($bankdata = xtc_db_fetch_array($bank_query)))
        {
          $bank_name = $bankdata['banktransfer_bankname'];
          $bank_blz  = $bankdata['banktransfer_blz'];
          $bank_kto  = $bankdata['banktransfer_number'];
          $bank_inh  = $bankdata['banktransfer_owner'];
          $bank_stat = $bankdata['banktransfer_status'];
        }
        echo '<PAYMENT_BANKTRANS_BNAME>' . htmlspecialchars($bank_name) . '</PAYMENT_BANKTRANS_BNAME>' . "\n" .
             '<PAYMENT_BANKTRANS_BLZ>' . htmlspecialchars($bank_blz) . '</PAYMENT_BANKTRANS_BLZ>' . "\n" .
             '<PAYMENT_BANKTRANS_NUMBER>' . htmlspecialchars($bank_kto) . '</PAYMENT_BANKTRANS_NUMBER>' . "\n" .
             '<PAYMENT_BANKTRANS_OWNER>' . htmlspecialchars($bank_inh) . '</PAYMENT_BANKTRANS_OWNER>' . "\n" .
             '<PAYMENT_BANKTRANS_STATUS>' . htmlspecialchars($bank_stat) . '</PAYMENT_BANKTRANS_STATUS>' . "\n";
        break;
    }
    echo '</PAYMENT>' . "\n" .
         '<SHIPPING>' . "\n" .
         '<SHIPPING_METHOD>' . htmlspecialchars($orders['shipping_method']) . '</SHIPPING_METHOD>'  . "\n" .
         '<SHIPPING_CLASS>' . htmlspecialchars($orders['shipping_class']) . '</SHIPPING_CLASS>'  . "\n" .
         '</SHIPPING>' . "\n" .
         '<ORDER_PRODUCTS>' . "\n";

    $products_query = xtc_db_query("select orders_products_id,allow_tax, products_id, products_model, products_name, final_price, products_tax, products_quantity from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . $orders['orders_id'] . "'");
    while ($products = xtc_db_fetch_array($products_query))
    {
      if ($products['allow_tax']==1)
      {
        $EPreisNetto=$products['final_price']/(1+$products['products_tax']*0.01);
      }
      else
      {
        $EPreisNetto=$products['final_price'];
      }
      echo '<PRODUCT>' . "\n" .
           '<PRODUCTS_ID>' . $products['products_id'] . '</PRODUCTS_ID>' . "\n" .
           '<PRODUCTS_QUANTITY>' . $products['products_quantity'] . '</PRODUCTS_QUANTITY>' . "\n" .
           '<PRODUCTS_MODEL>' . htmlspecialchars($products['products_model']) . '</PRODUCTS_MODEL>' . "\n" .
           '<PRODUCTS_NAME>' . htmlspecialchars($products['products_name']) . '</PRODUCTS_NAME>' . "\n" .
           '<PRODUCTS_PRICE>' . $EPreisNetto/$products['products_quantity'] . '</PRODUCTS_PRICE>' . "\n" .
           '<PRODUCTS_EPRICE>' . $products['final_price']/$products['products_quantity'] . '</PRODUCTS_EPRICE>' . "\n" .
           '<PRODUCTS_TAX>' . $products['products_tax'] . '</PRODUCTS_TAX>' . "\n".
           '<PRODUCTS_TAX_FLAG>' . $products['allow_tax'] . '</PRODUCTS_TAX_FLAG>' . "\n";

      $attributes_query = xtc_db_query("select products_options, products_options_values, options_values_price, price_prefix from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " where orders_id = '" .$orders['orders_id'] . "' and orders_products_id = '" . $products['orders_products_id'] . "'");
      if (xtc_db_num_rows($attributes_query))
      {
        while ($attributes = xtc_db_fetch_array($attributes_query))
        {
          require_once(DIR_FS_INC . 'xtc_get_attributes_model.inc.php');
          if ($WithAtrProduktModell)
          {
            $attributes_model = $products['products_model'] .
              xtc_get_attributes_model($products['products_id'], $attributes['products_options_values'], $attributes['products_options'], $LangID);
          }
          else
          {
            $attributes_model = xtc_get_attributes_model($products['products_id'], $attributes['products_options_values'], $attributes['products_options'], $LangID);
          }
          echo '<OPTION>' . "\n" .
               '<PRODUCTS_OPTIONS>' . htmlspecialchars($attributes['products_options']) . '</PRODUCTS_OPTIONS>' . "\n" .
               '<PRODUCTS_OPTIONS_VALUES>' . htmlspecialchars($attributes['products_options_values']) . '</PRODUCTS_OPTIONS_VALUES>' . "\n" .
               '<PRODUCTS_OPTIONS_MODEL>' . $attributes_model . '</PRODUCTS_OPTIONS_MODEL>'. "\n".
               '<PRODUCTS_OPTIONS_PRICE>' . $attributes['price_prefix'] . ' ' . $attributes['options_values_price'] . '</PRODUCTS_OPTIONS_PRICE>' . "\n" .
               '</OPTION>' . "\n";
        }
      }
      echo '</PRODUCT>' . "\n";
    }
    echo '</ORDER_PRODUCTS>' . "\n" .
         '<ORDER_TOTAL>' . "\n";

    $totals_query = xtc_db_query("select title, value, class, sort_order from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . $orders['orders_id'] . "' order by sort_order");
    while ($totals = xtc_db_fetch_array($totals_query))
    {
      $total_prefix = "";
      $total_tax  = "";
      $total_prefix = $order_total_class[$totals['class']]['prefix'];
      $total_tax = $order_total_class[$totals['class']]['tax'];
      echo '<TOTAL>' . "\n" .
           '<TOTAL_TITLE>' . htmlspecialchars($totals['title']) . '</TOTAL_TITLE>' . "\n" .
           '<TOTAL_VALUE>' . htmlspecialchars($totals['value']) . '</TOTAL_VALUE>' . "\n" .
           '<TOTAL_CLASS>' . htmlspecialchars($totals['class']) . '</TOTAL_CLASS>' . "\n" .
           '<TOTAL_SORT_ORDER>' . htmlspecialchars($totals['sort_order']) . '</TOTAL_SORT_ORDER>' . "\n" .
           '<TOTAL_PREFIX>' . htmlspecialchars($total_prefix) . '</TOTAL_PREFIX>' . "\n" .
           '<TOTAL_TAX>' . htmlspecialchars($total_tax) . '</TOTAL_TAX>' . "\n" .
           '</TOTAL>' . "\n";
    }
    echo '</ORDER_TOTAL>' . "\n";
    $comments_query = xtc_db_query("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . $orders['orders_id'] . "' and orders_status_id = '" . $orders['orders_status'] . "' ");
    if ($comments =  xtc_db_fetch_array($comments_query))
    {
      echo '<ORDER_COMMENTS>' . htmlspecialchars($comments['comments']) . '</ORDER_COMMENTS>' . "\n";
    }
    echo '</ORDER_INFO>' . "\n\n";
  }
  echo '</ORDER>' . "\n\n";
}

?>
