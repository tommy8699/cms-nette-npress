<?php

use Nette\Environment;
use Nette\Localization\ITranslator;
use Nette\Object;

/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */

class TranslationsModel extends Object implements ITranslator
{
  public static function getAll()
  {
    self::checkColumns();

    $res = dibi::query('SELECT * FROM translations');
    return $res->fetchAssoc('id');
  }

  private static function checkColumns()
  {
    $langs = Environment::getVariable('langs');

    $columns = dibi::query('SHOW COLUMNS FROM `translations`')->fetchAssoc(
      'Field'
    );

    foreach ($langs as $lang => $txt) {
      if (!isset($columns[$lang])) {
        dibi::query(
          'ALTER TABLE `translations` ADD %n',
          $lang,
          ' text NOT NULL'
        );
        $res = dibi::query('SELECT * FROM translations');
      }
    }
  }

  public static function replace($values)
  {
    dibi::query('REPLACE INTO translations', $values);
  }

  public static function delete($id)
  {
    dibi::query('DELETE FROM translations WHERE id=%i', $id);
  }

  private $lang;
  private static $cache = array();
  public function __construct($lang)
  {
    //translator
    $this->lang = $lang;

    //suppres the query for one-language
    $langs = Environment::getVariable('langs');
    if (count($langs) <= 1) {
      return self::$cache[$lang] = array();
    }

    //fill the cache
    try {
      self::$cache[$lang] = dibi::query(
        'SELECT * FROM translations'
      )->fetchPairs('key', $lang);
    } catch (Exception $e) {
      self::$cache[$lang] = array();
    }
  }

  public function translate($message, $count = null)
  {
    if (
      isset(self::$cache[$this->lang][$message]) &&
      self::$cache[$this->lang][$message]
    ) {
      return self::$cache[$this->lang][$message];
    }

    return $message;
  }
}
