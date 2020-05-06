<?php
/**
 * Ce fichier est développé pour la gestion de la lib MCE
 * 
 * Cette Librairie permet d'accèder aux données sans avoir à implémenter de couche SQL
 * Des objets génériques vont permettre d'accèder et de mettre à jour les données
 * 
 * ORM Mél Copyright © 2020 Groupe Messagerie/MTES
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace LibMelanie\Api\Defaut;

use LibMelanie\Lib\MceObject;
use LibMelanie\Objects\UserMelanie;
use LibMelanie\Log\M2Log;
use LibMelanie\Api\Defaut\UserPrefs;
use LibMelanie\Api\Defaut\Addressbook;
use LibMelanie\Api\Defaut\Calendar;
use LibMelanie\Api\Defaut\Taskslist;
use LibMelanie\Api\Defaut\Users\Share;

/**
 * Classe utilisateur par defaut
 * 
 * @author Groupe Messagerie/MTES - Apitech
 * @package LibMCE
 * @subpackage API/Defaut
 * @api
 * 
 * @property string $dn DN de l'utilisateur dans l'annuaire            
 * @property string $uid Identifiant unique de l'utilisateur
 * @property string $fullname Nom complet de l'utilisateur
 * @property string $name Nom de l'utilisateur
 * @property string $type Type de boite (voir Mce\Users\Type::*)
 * @property string $email Adresse email principale de l'utilisateur
 * @property array $email_list Liste de toutes les adresses email de l'utilisateur
 * @property string $email_send Adresse email d'émission principale de l'utilisateur
 * @property array $email_send_list Liste de toutes les adresses email d'émission de l'utilisateur
 * @property Share[] $shares Liste des partages de la boite
 * @property-read array $supported_shares Liste des droits supportés par cette boite
 * 
 * @method bool save() Enregistrement de l'utilisateur dans l'annuaire
 */
abstract class User extends MceObject {
  /**
   * Objet de partage associé a l'utilisateur courant si nécessaire
   * 
   * @var ObjectShare
   */
  protected $objectshare;

  /**
   * UserMelanie provenant d'un autre annuaire
   * 
   * @var UserMelanie
   */
  protected $otherldapobject;

  /**
   * Calendrier par défaut de l'utilisateur
   * 
   * @var Calendar
   */
  protected $_defaultCalendar;
  /**
   * Liste des calendriers de l'utilisateur
   * 
   * @var Calendar[]
   */
  protected $_userCalendars;
  /**
   * Liste de tous les calendriers auquel l'utilisateur a accés
   * 
   * @var Calendar[]
   */
  protected $_sharedCalendars;

  /**
   * Carnet d'adresses par défaut de l'utilisateur
   * 
   * @var Addressbook
   */
  protected $_defaultAddressbook;
  /**
   * Liste des carnets d'adresses de l'utilisateur
   * 
   * @var Addressbook
   */
  protected $_userAddressbooks;
  /**
   * Liste de tous les carnets d'adresses auquel l'utilisateur a accés
   * 
   * @var Addressbook
   */
  protected $_sharedAddressbooks;

  /**
   * Liste de tâches par défaut de l'utilisateur
   * 
   * @var Taskslist
   */
  protected $_defaultTaskslist;
  /**
   * Liste des listes de tâches de l'utilisateur
   * 
   * @var Taskslist
   */
  protected $_userTaskslists;
  /**
   * Liste de toutes les listes de tâches auquel l'utilisateur a accés
   * 
   * @var Taskslist
   */
  protected $_sharedTaskslists;

  /**
   * Liste des objets partagés accessibles à l'utilisateur
   * 
   * @var ObjectShare[]
   */
  protected $_objectsShared;
  /**
   * Liste des objets partagés accessibles en emission à l'utilisateur
   * 
   * @var ObjectShare[]
   */
  protected $_objectsSharedEmission;
  /**
   * Liste des objets partagés accessibles en gestionnaire à l'utilisateur
   * 
   * @var ObjectShare[]
   */
  protected $_objectsSharedGestionnaire;
  /**
   * Liste des partages pour l'objet courant
   * 
   * @var Share[]
   */
  protected $_shares;
  /**
   * Liste des groupes pour l'objet courant
   * 
   * @var Group[]
   */
  protected $_lists;

  /**
   * Nom de la conf serveur utilisé pour le LDAP
   * 
   * @var string
   */
  protected $_server;

  /**
   * Est-ce que l'objet est déjà chargé depuis l'annuaire ?
   * 
   * @var boolean
   */
  protected $_isLoaded;

  /**
   * Est-ce que l'objet existe dans l'annuaire ?
   * 
   * @var boolean
   */
  protected $_isExist;

  /**
   * Liste des preferences de l'utilisateur
   * 
   * @var UserPrefs[]
   */
  protected $_preferences;

  // **** Constantes pour les preferences
  /**
   * Scope de preference par defaut pour l'utilisateur
   */
  const PREF_SCOPE_DEFAULT = \LibMelanie\Config\ConfigMelanie::GENERAL_PREF_SCOPE;
  /**
   * Scope de preference pour les calendriers de l'utilisateur
   */
  const PREF_SCOPE_CALENDAR = \LibMelanie\Config\ConfigMelanie::CALENDAR_PREF_SCOPE;
  /**
   * Scope de preference pour les carnets d'adresses de l'utilisateur
   */
  const PREF_SCOPE_ADDRESSBOOK = \LibMelanie\Config\ConfigMelanie::ADDRESSBOOK_PREF_SCOPE;
  /**
   * Scope de preference pour les listes de taches de l'utilisateur
   */
  const PREF_SCOPE_TASKSLIST = \LibMelanie\Config\ConfigMelanie::TASKSLIST_PREF_SCOPE;

  // **** Configuration des filtres et des attributs par défaut
  /**
   * Filtre pour la méthode load()
   * 
   * @ignore
   */
  const LOAD_FILTER = null;
  /**
   * Filtre pour la méthode load() avec un email
   * 
   * @ignore
   */
  const LOAD_FROM_EMAIL_FILTER = null;
  /**
   * Attributs par défauts pour la méthode load()
   * 
   * @ignore
   */
  const LOAD_ATTRIBUTES = ['dn','uid','fullname','type','email'];
  /**
   * Filtre pour la méthode getBalp()
   * 
   * @ignore
   */
  const GET_BALP_FILTER = null;
  /**
   * Attributs par défauts pour la méthode getBalp()
   * 
   * @ignore
   */
  const GET_BALP_ATTRIBUTES = ['dn','uid','fullname','type','email'];
  /**
   * Filtre pour la méthode getBalpEmission()
   * 
   * @ignore
   */
  const GET_BALP_EMISSION_FILTER = null;
  /**
   * Attributs par défauts pour la méthode getBalpEmission()
   * 
   * @ignore
   */
  const GET_BALP_EMISSION_ATTRIBUTES = ['dn','uid','fullname','type','email'];
  /**
   * Filtre pour la méthode getBalpGestionnaire()
   * 
   * @ignore
   */
  const GET_BALP_GESTIONNAIRE_FILTER = null;
  /**
   * Attributs par défauts pour la méthode getBalpGestionnaire()
   * 
   * @ignore
   */
  const GET_BALP_GESTIONNAIRE_ATTRIBUTES = ['dn','uid','fullname','type','email'];
  /**
   * Filtre pour la méthode getGroups()
   * 
   * @ignore
   */
  const GET_GROUPS_FILTER = null;
  /**
   * Attributs par défauts pour la méthode getGroups()
   * 
   * @ignore
   */
  const GET_GROUPS_ATTRIBUTES = ['dn','fullname','type','email','members'];
  /**
   * Filtre pour la méthode getGroupsIsMember()
   * 
   * @ignore
   */
  const GET_GROUPS_IS_MEMBER_FILTER = null;
  /**
   * Attributs par défauts pour la méthode getGroupsIsMember()
   * 
   * @ignore
   */
  const GET_GROUPS_IS_MEMBER_ATTRIBUTES = self::GET_GROUPS_ATTRIBUTES;
  /**
   * Filtre pour la méthode getListsIsMember()
   * 
   * @ignore
   */
  const GET_LISTS_IS_MEMBER_FILTER = null;
  /**
   * Attributs par défauts pour la méthode getListsIsMember()
   * 
   * @ignore
   */
  const GET_LISTS_IS_MEMBER_ATTRIBUTES = self::GET_GROUPS_ATTRIBUTES;

  /**
   * Configuration du mapping qui surcharge la conf
   */
  const MAPPING = [];

  /**
   * Constructeur de l'objet
   * 
   * @param string $server Serveur d'annuaire a utiliser en fonction de la configuration
   */
  public function __construct($server = null) {
    // Défini la classe courante
    $this->get_class = get_class($this);
    
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->__construct($server)");
    // Définition de l'utilisateur
    $this->objectmelanie = new UserMelanie($server, null, static::MAPPING);
    // Gestion d'un second serveur d'annuaire dans le cas ou les informations sont répartis
    if (isset(\LibMelanie\Config\Ldap::$OTHER_LDAP)) {
      $this->otherldapobject = new UserMelanie(\LibMelanie\Config\Ldap::$OTHER_LDAP, null, static::MAPPING);
    }
    $this->_server = $server ?: \LibMelanie\Config\Ldap::$SEARCH_LDAP;
  }

  /**
	 * Récupère le délimiteur d'un objet de partage
	 * 
	 * @return string ObjectShare::DELIMITER
	 */
	protected function getObjectShareDelimiter() {
    $class = $this->__getNamespace() . '\\ObjectShare';
		return $class::DELIMITER;
	}
   
  /**
   * ***************************************************
   * METHOD MAPPING
   */

  /**
   * Authentification sur le serveur LDAP
   *
   * @param string $password
   * @param boolean $master Utiliser le serveur maitre (nécessaire pour faire des modifications)
   * @param string $user_dn DN de l'utilisateur si ce n'est pas le courant a utiliser
   * @return boolean
   */
  public function authentification($password, $master = false, $user_dn = null) {
    if ($master) {
      $this->_server = \LibMelanie\Config\Ldap::$MASTER_LDAP;
    }
    return $this->objectmelanie->authentification($password, $master, $user_dn);
  }

  /**
   * Charge les données de l'utilisateur depuis l'annuaire (en fonction de l'uid ou l'email)
   * 
   * @param array $attributes [Optionnal] List of attributes to load
   * 
   * @return boolean true si l'objet existe dans l'annuaire false sinon
   */
  public function load($attributes = null) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->load() [" . $this->_server . "]");
    if (isset($attributes) && is_string($attributes)) {
      $attributes = [$attributes];
    }
    if (isset($this->_isLoaded) && !isset($attributes)) {
      return $this->_isLoaded;
    }
    $useIsLoaded = !isset($attributes);
    if (!isset($attributes)) {
      $attributes = static::LOAD_ATTRIBUTES;
    }
    $filter = static::LOAD_FILTER;
    $filterFromEmail = static::LOAD_FROM_EMAIL_FILTER;
    if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server])) {
      if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_infos_filter'])) {
        $filter = \LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_infos_filter'];
      }
      if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_infos_from_email_filter'])) {
        $filterFromEmail = \LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_infos_from_email_filter'];
      }
    }
    $ret = $this->objectmelanie->load($attributes, $filter, $filterFromEmail);
    if ($useIsLoaded) {
      $this->_isLoaded = $ret;
    }
    return $ret;
  }
  /**
   * Est-ce que l'utilisateur existe dans l'annuaire (en fonction de l'uid ou l'email)
   * Effectue un load cette méthode a donc peu d'intéret dans cet objet
   * 
   * @param array $attributes [Optionnal] List of attributes to load
   * 
   * @return boolean true si l'objet existe dans l'annuaire false sinon
   */
  public function exists($attributes = null) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->exists() [" . $this->_server . "]");
    if (!isset($this->_isExist)) {
      if (!isset($attributes)) {
        $attributes = static::LOAD_ATTRIBUTES;
      }
      $filter = static::LOAD_FILTER;
      $filterFromEmail = static::LOAD_FROM_EMAIL_FILTER;
      if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server])) {
        if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_infos_filter'])) {
          $filter = \LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_infos_filter'];
        }
        if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_infos_from_email_filter'])) {
          $filterFromEmail = \LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_infos_from_email_filter'];
        }
      }
      $this->_isExist = $this->objectmelanie->exists($attributes, $filter, $filterFromEmail);
    }
    return $this->_isExist;
  }

  /**
   * Récupère la liste des objets de partage accessibles à l'utilisateur
   * 
   * @param array $attributes [Optionnal] List of attributes to load
   *
   * @return ObjectShare[] Liste d'objets
   */
  public function getObjectsShared($attributes = null) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getObjectsShared() [" . $this->_server . "]");
    if (!isset($this->_objectsShared)) {
      if (isset($attributes) && is_string($attributes)) {
        $attributes = [$attributes];
      }
      if (!isset($attributes)) {
        $attributes = static::GET_BALP_ATTRIBUTES;
      }
      $filter = static::GET_BALP_FILTER;
      if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]) 
          && isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_bal_partagees_filter'])) {
        $filter = \LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_bal_partagees_filter'];
      }
      $list = $this->objectmelanie->getBalp($attributes, $filter);
      $this->_objectsShared = [];
      $class = $this->__getNamespace() . '\\ObjectShare';
      foreach ($list as $key => $object) {
        $this->_objectsShared[$key] = new $class($this->_server);
        $this->_objectsShared[$key]->setObjectMelanie($object);
      }
    }
    return $this->_objectsShared;
  }

  /**
   * Récupère la liste des boites partagées à l'utilisateur
   * 
   * @param array $attributes [Optionnal] List of attributes to load
   *
   * @return User[] Liste de boites
   */
  public function getShared($attributes = null) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getShared() [" . $this->_server . "]");
    if (!isset($this->_objectsShared)) {
      if (isset($attributes) && is_string($attributes)) {
        $attributes = [$attributes];
      }
      if (!isset($attributes)) {
        $attributes = static::GET_BALP_ATTRIBUTES;
      }
      $filter = static::GET_BALP_FILTER;
      if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]) 
          && isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_bal_partagees_filter'])) {
        $filter = \LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_bal_partagees_filter'];
      }
      $list = $this->objectmelanie->getBalp($attributes, $filter);
      $this->_objectsShared = [];
      foreach ($list as $key => $object) {
        $this->_objectsShared[$key] = new static($this->_server);
        $this->_objectsShared[$key]->setObjectMelanie($object);
      }
    }
    return $this->_objectsShared;
  }

  /**
   * Récupère la liste des objets de partage accessibles au moins en émission à l'utilisateur
   * 
   * @param array $attributes [Optionnal] List of attributes to load
   *
   * @return ObjectShare[] Liste d'objets
   */
  public function getObjectsSharedEmission($attributes = null) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getObjectsSharedEmission() [" . $this->_server . "]");
    if (!isset($this->_objectsSharedEmission)) {
      if (isset($attributes) && is_string($attributes)) {
        $attributes = [$attributes];
      }
      if (!isset($attributes)) {
        $attributes = static::GET_BALP_EMISSION_ATTRIBUTES;
      }
      $filter = static::GET_BALP_EMISSION_FILTER;
      if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]) 
          && isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_bal_emission_filter'])) {
        $filter = \LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_bal_emission_filter'];
      }
      $list = $this->objectmelanie->getBalpEmission($attributes, $filter);
      $this->_objectsSharedEmission = [];
      $class = $this->__getNamespace() . '\\ObjectShare';
      foreach ($list as $key => $object) {
        $this->_objectsSharedEmission[$key] = new $class($this->_server);
        $this->_objectsSharedEmission[$key]->setObjectMelanie($object);
      }
    }
    return $this->_objectsSharedEmission;
  }

  /**
   * Récupère la liste des boites accessibles au moins en émission à l'utilisateur
   * 
   * @param array $attributes [Optionnal] List of attributes to load
   *
   * @return User[] Liste d'objets
   */
  public function getSharedEmission($attributes = null) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getSharedEmission() [" . $this->_server . "]");
    if (!isset($this->_objectsSharedEmission)) {
      if (isset($attributes) && is_string($attributes)) {
        $attributes = [$attributes];
      }
      if (!isset($attributes)) {
        $attributes = static::GET_BALP_EMISSION_ATTRIBUTES;
      }
      $filter = static::GET_BALP_EMISSION_FILTER;
      if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]) 
          && isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_bal_emission_filter'])) {
        $filter = \LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_bal_emission_filter'];
      }
      $list = $this->objectmelanie->getBalpEmission($attributes, $filter);
      $this->_objectsSharedEmission = [];
      foreach ($list as $key => $object) {
        $this->_objectsSharedEmission[$key] = new static($this->_server);
        $this->_objectsSharedEmission[$key]->setObjectMelanie($object);
      }
    }
    return $this->_objectsSharedEmission;
  }

  /**
   * Récupère la liste des objets de partage accessibles en tant que gestionnaire pour l'utilisateur
   * 
   * @param array $attributes [Optionnal] List of attributes to load
   *
   * @return ObjectShare[] Liste d'objets
   */
  public function getObjectsSharedGestionnaire($attributes = null) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getObjectsSharedGestionnaire() [" . $this->_server . "]");
    if (!isset($this->_objectsSharedGestionnaire)) {
      if (isset($attributes) && is_string($attributes)) {
        $attributes = [$attributes];
      }
      if (!isset($attributes)) {
        $attributes = static::GET_BALP_GESTIONNAIRE_ATTRIBUTES;
      }
      $filter = static::GET_BALP_GESTIONNAIRE_FILTER;
      if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]) 
          && isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_bal_gestionnaire_filter'])) {
        $filter = \LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_bal_gestionnaire_filter'];
      }
      $list = $this->objectmelanie->getBalpGestionnaire($attributes, $filter);
      $this->_objectsSharedGestionnaire = [];
      $class = $this->__getNamespace() . '\\ObjectShare';
      foreach ($list as $key => $object) {
        $this->_objectsSharedGestionnaire[$key] = new $class($this->_server);
        $this->_objectsSharedGestionnaire[$key]->setObjectMelanie($object);
      }
    }
    return $this->_objectsSharedGestionnaire;
  }

  /**
   * Récupère la liste des boites accessibles en tant que gestionnaire pour l'utilisateur
   * 
   * @param array $attributes [Optionnal] List of attributes to load
   *
   * @return User[] Liste d'objets
   */
  public function getSharedGestionnaire($attributes = null) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getSharedGestionnaire() [" . $this->_server . "]");
    if (!isset($this->_objectsSharedGestionnaire)) {
      if (isset($attributes) && is_string($attributes)) {
        $attributes = [$attributes];
      }
      if (!isset($attributes)) {
        $attributes = static::GET_BALP_GESTIONNAIRE_ATTRIBUTES;
      }
      $filter = static::GET_BALP_GESTIONNAIRE_FILTER;
      if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]) 
          && isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_bal_gestionnaire_filter'])) {
        $filter = \LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_bal_gestionnaire_filter'];
      }
      $list = $this->objectmelanie->getBalpGestionnaire($attributes, $filter);
      $this->_objectsSharedGestionnaire = [];
      foreach ($list as $key => $object) {
        $this->_objectsSharedGestionnaire[$key] = new static($this->_server);
        $this->_objectsSharedGestionnaire[$key]->setObjectMelanie($object);
      }
    }
    return $this->_objectsSharedGestionnaire;
  }

  /**
   * Récupère la liste des groupes dont l'utilisateur est propriétaire
   * 
   * @param array $attributes [Optionnal] List of attributes to load
   *
   * @return Group[] Liste d'objets
   */
  public function getGroups($attributes = null) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getGroups() [" . $this->_server . "]");
    if (!isset($this->_lists)) {
      if (isset($attributes) && is_string($attributes)) {
        $attributes = [$attributes];
      }
      if (!isset($attributes)) {
        $attributes = static::GET_GROUPS_ATTRIBUTES;
      }
      $filter = static::GET_GROUPS_FILTER;
      if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]) 
          && isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_groups_filter'])) {
        $filter = \LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_user_groups_filter'];
      }
      $list = $this->objectmelanie->getGroups($attributes, $filter);
      $this->_lists = [];
      $class = $this->__getNamespace() . '\\Group';
      foreach ($list as $key => $object) {
        $this->_lists[$key] = new $class($this->_server);
        $this->_lists[$key]->setObjectMelanie($object);
      }
    }
    return $this->_lists;
  }

  /**
   * Récupère la liste des groupes dont l'utilisateur est membre
   * 
   * @param array $attributes [Optionnal] List of attributes to load
   *
   * @return Group[] Liste d'objets
   */
  public function getGroupsIsMember($attributes = null) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getGroupsIsMember() [" . $this->_server . "]");
    if (!isset($this->_lists)) {
      if (isset($attributes) && is_string($attributes)) {
        $attributes = [$attributes];
      }
      if (!isset($attributes)) {
        $attributes = static::GET_GROUPS_IS_MEMBER_ATTRIBUTES;
      }
      $filter = static::GET_GROUPS_IS_MEMBER_ATTRIBUTES;
      if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]) 
          && isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_groups_user_member_filter'])) {
        $filter = \LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_groups_user_member_filter'];
      }
      $list = $this->objectmelanie->getGroupsIsMember($attributes, $filter);
      $this->_lists = [];
      $class = $this->__getNamespace() . '\\Group';
      foreach ($list as $key => $object) {
        $this->_lists[$key] = new $class($this->_server);
        $this->_lists[$key]->setObjectMelanie($object);
      }
    }
    return $this->_lists;
  }

  /**
   * Récupère la liste des listes de diffusion dont l'utilisateur est membre
   * 
   * @param array $attributes [Optionnal] List of attributes to load
   *
   * @return Group[] Liste d'objets
   */
  public function getListsIsMember($attributes = null) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getListsIsMember() [" . $this->_server . "]");
    if (!isset($this->_lists)) {
      if (isset($attributes) && is_string($attributes)) {
        $attributes = [$attributes];
      }
      if (!isset($attributes)) {
        $attributes = static::GET_LISTS_IS_MEMBER_ATTRIBUTES;
      }
      $filter = static::GET_LISTS_IS_MEMBER_ATTRIBUTES;
      if (isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]) 
          && isset(\LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_lists_user_member_filter'])) {
        $filter = \LibMelanie\Config\Ldap::$SERVERS[$this->_server]['get_lists_user_member_filter'];
      }
      $list = $this->objectmelanie->getListsIsMember($attributes, $filter);
      $this->_lists = [];
      $class = $this->__getNamespace() . '\\Group';
      foreach ($list as $key => $object) {
        $this->_lists[$key] = new $class($this->_server);
        $this->_lists[$key]->setObjectMelanie($object);
      }
    }
    return $this->_lists;
  }

  /**
   * Récupère la préférence de l'utilisateur
   * 
   * @param string $scope Scope de la préférence, voir User::PREF_SCOPE*
   * @param string $name Nom de la préférence
   * 
   * @return string La valeur de la préférence si elle existe, null sinon
   */
  public function getPreference($scope, $name) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getPreference($scope, $name)");
    if (!isset($this->_preferences)) {
      $this->_get_preferences();
    }
    if (isset($this->_preferences["$scope:$name"])) {
      return $this->_preferences["$scope:$name"]->value;
    }
    return null;
  }
  /**
   * Récupération de la préférence avec un scope Default
   * 
   * @param string $name Nom de la préférence
   * 
   * @return string La valeur de la préférence si elle existe, null sinon
   */
  public function getDefaultPreference($name) {
    return $this->getPreference(self::PREF_SCOPE_DEFAULT, $name);
  }
  /**
   * Récupération de la préférence avec un scope Calendar
   * 
   * @param string $name Nom de la préférence
   * 
   * @return string La valeur de la préférence si elle existe, null sinon
   */
  public function getCalendarPreference($name) {
    return $this->getPreference(self::PREF_SCOPE_CALENDAR, $name);
  }
  /**
   * Récupération de la préférence avec un scope Taskslist
   * 
   * @param string $name Nom de la préférence
   * 
   * @return string La valeur de la préférence si elle existe, null sinon
   */
  public function getTaskslistPreference($name) {
    return $this->getPreference(self::PREF_SCOPE_TASKSLIST, $name);
  }
  /**
   * Récupération de la préférence avec un scope Addressbook
   * 
   * @param string $name Nom de la préférence
   * 
   * @return string La valeur de la préférence si elle existe, null sinon
   */
  public function getAddressbookPreference($name) {
    return $this->getPreference(self::PREF_SCOPE_ADDRESSBOOK, $name);
  }

  /**
   * Liste les préférences de l'utilisateur et les conserves en mémoire
   */
  protected function _get_preferences() {
    if (isset($this->_preferences)) {
      return;
    }
    $this->_preferences = [];
    $UserPrefs = $this->__getNamespace() . '\\UserPrefs';
    $preferences = (new $UserPrefs($this))->getList();
    if (is_array($preferences)) {
      foreach ($preferences as $pref) {
        $this->_preferences[$pref->scope.":".$pref->name] = $pref;
      }
    }
  }

  /**
   * Enregistre la préférence de l'utilisateur
   * 
   * @param string $scope Scope de la préférence, voir User::PREF_SCOPE*
   * @param string $name Nom de la préférence
   * @param string $value Valeur de la préférence a enregistrer
   * 
   * @return boolean
   */
  public function savePreference($scope, $name, $value) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->savePreference($scope, $name)");
    if (!isset($this->_preferences)) {
      $this->_get_preferences();
    }
    if (!isset($this->_preferences["$scope:$name"])) {
      $UserPrefs = $this->__getNamespace() . '\\UserPrefs';
      $this->_preferences["$scope:$name"] = new $UserPrefs($this);
      $this->_preferences["$scope:$name"]->scope = $scope;
      $this->_preferences["$scope:$name"]->name = $name;
    }
    $this->_preferences["$scope:$name"]->value = $value;
    $ret = $this->_preferences["$scope:$name"]->save();
    return !is_null($ret);  
  }
  /**
   * Enregistre la préférence de l'utilisateur avec le scope Default
   * 
   * @param string $name Nom de la préférence
   * @param string $value Valeur de la préférence a enregistrer
   * 
   * @return boolean
   */
  public function saveDefaultPreference($name, $value) {
    return $this->savePreference(self::PREF_SCOPE_DEFAULT, $name, $value);
  }
  /**
   * Enregistre la préférence de l'utilisateur avec le scope Calendar
   * 
   * @param string $name Nom de la préférence
   * @param string $value Valeur de la préférence a enregistrer
   * 
   * @return boolean
   */
  public function saveCalendarPreference($name, $value) {
    return $this->savePreference(self::PREF_SCOPE_CALENDAR, $name, $value);
  }
  /**
   * Enregistre la préférence de l'utilisateur avec le scope Taskslist
   * 
   * @param string $name Nom de la préférence
   * @param string $value Valeur de la préférence a enregistrer
   * 
   * @return boolean
   */
  public function saveTaskslistPreference($name, $value) {
    return $this->savePreference(self::PREF_SCOPE_TASKSLIST, $name, $value);
  }
  /**
   * Enregistre la préférence de l'utilisateur avec le scope Addressbook
   * 
   * @param string $name Nom de la préférence
   * @param string $value Valeur de la préférence a enregistrer
   * 
   * @return boolean
   */
  public function saveAddressbookPreference($name, $value) {
    return $this->savePreference(self::PREF_SCOPE_ADDRESSBOOK, $name, $value);
  }

  /**
   * Supprime la préférence de l'utilisateur
   * 
   * @param string $scope Scope de la préférence, voir User::PREF_SCOPE*
   * @param string $name Nom de la préférence
   * 
   * @return boolean
   */
  public function deletePreference($scope, $name) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->savePreference($scope, $name)");
    if (!isset($this->_preferences)) {
      $this->_get_preferences();
    }
    if (!isset($this->_preferences["$scope:$name"])) {
      $UserPrefs = $this->__getNamespace() . '\\UserPrefs';
      $this->_preferences["$scope:$name"] = new $UserPrefs($this);
      $this->_preferences["$scope:$name"]->scope = $scope;
      $this->_preferences["$scope:$name"]->name = $name;
    }
    $ret = $this->_preferences["$scope:$name"]->delete();
    unset($this->_preferences["$scope:$name"]);
    return !is_null($ret);  
  }

  /**
   * Retourne le calendrier par défaut
   * 
   * @return Calendar Calendrier par défaut de l'utilisateur, null s'il n'existe pas
   */
  public function getDefaultCalendar() {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getDefaultCalendar()");
    // Si le calendrier n'est pas déjà chargé
    if (!isset($this->_defaultCalendar)) {
      // Le calendrier principal est peut être déjà dans _sharedCalendars ou _userCalendars
      if (isset($this->_sharedCalendars)) {
        $this->_defaultCalendar = $this->_sharedCalendars[$this->uid] ?: null;
      }
      else if (isset($this->_userCalendars)) {
        $this->_defaultCalendar = $this->_userCalendars[$this->uid] ?: null;
      }
      else {
        // Sinon on le charge depuis la base
        $_calendar = $this->objectmelanie->getDefaultCalendar();
        if (!$_calendar || !isset($_calendar)) {
          return null;
        }
        $Calendar = $this->__getNamespace() . '\\Calendar';
        $this->_defaultCalendar = new $Calendar($this);
        $this->_defaultCalendar->setObjectMelanie($_calendar);
      }
    }
    return $this->_defaultCalendar;
  }

  /**
   * Modifie le calendrier par défaut de l'utilisateur
   * 
   * @param string|Calendar $calendar Calendrier à mettre par défaut pour l'utilisateur
   * 
   * @return boolean
   */
  public function setDefaultCalendar($calendar) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->setDefaultCalendar()");
    if (is_object($calendar)) {
      $calendar_id = $calendar->id;
    }
    else if (is_string($calendar)) {
      $calendar_id = $calendar;
    }
    else {
      return false;
    }
    if ($this->savePreference(self::PREF_SCOPE_CALENDAR, \LibMelanie\Config\ConfigMelanie::CALENDAR_PREF_DEFAULT_NAME, $calendar_id)) {
      if (is_object($calendar)) {
        $this->_defaultCalendar = $calendar;
      }
      else {
        $this->_defaultCalendar = null;
      }
      return true;
    }
    return false;
  }

  /**
   * Création du calendrier par défaut pour l'utilisateur courant
   * 
   * @param string $calendarName [Optionnel] Nom du calendrier
   * 
   * @return true si la création est OK, false sinon
   */
  public function createDefaultCalendar($calendarName = null) {
    // Gestion du nom du calendrier
    $calendarName = str_replace('%%fullname%%', $this->fullname, $calendarName);
    $calendarName = str_replace('%%name%%', $this->name, $calendarName);
    $calendarName = str_replace('%%email%%', $this->email, $calendarName);
    $calendarName = str_replace('%%uid%%', $this->uid, $calendarName);
    // Création du calendrier
    $Calendar = $this->__getNamespace() . '\\Calendar';
    $calendar = new $Calendar($this);
    $calendar->name = $calendarName ?: $this->fullname;
    $calendar->id = $this->uid;
    $calendar->owner = $this->uid;
    if ($calendar->save()) {
      // Création du default calendar
      $this->setDefaultCalendar($calendar->id);
      // Création du display_cals (utile pour que pacome fonctionne)
      $this->savePreference(self::PREF_SCOPE_CALENDAR, 'display_cals', 'a:0:{}');
      return true;
    }
    return false;
  }

  /**
   * Retourne la liste des calendriers de l'utilisateur
   * 
   * @return Calendar[]
   */
  public function getUserCalendars() {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getUserCalendars()");
    // Si la liste des calendriers n'est pas encore chargée
    if (!isset($this->_userCalendars)) {
      $this->_userCalendars = [];
      // Si les calendriers partagés sont chargés on utilise les données
      if (isset($this->_sharedCalendars)) {
        foreach ($this->_sharedCalendars as $_key => $_cal) {
          if ($_cal->owner == $this->uid) {
            $this->_userCalendars[$_key] = $_cal;
          }
        }
      }
      // Sinon on charge depuis la base de données
      else {
        $_calendars = $this->objectmelanie->getUserCalendars();
        if (!isset($_calendars)) {
          return null;
        }
        $Calendar = $this->__getNamespace() . '\\Calendar';
        foreach ($_calendars as $_calendar) {
          $calendar = new $Calendar($this);
          $calendar->setObjectMelanie($_calendar);
          $this->_userCalendars[$_calendar->id] = $calendar;
        }
      }
      
    }
    return $this->_userCalendars;
  }

  /**
   * Retourne la liste des calendriers de l'utilisateur et ceux qui lui sont partagés
   * 
   * @return Calendar[]
   */
  public function getSharedCalendars() {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getSharedCalendars()");
    // Si la liste des calendriers n'est pas encore chargée on liste depuis la base
    if (!isset($this->_sharedCalendars)) {
      $_calendars = $this->objectmelanie->getSharedCalendars();
      if (!isset($_calendars)) {
        return null;
      }
      $this->_sharedCalendars = [];
      $Calendar = $this->__getNamespace() . '\\Calendar';
      foreach ($_calendars as $_calendar) {
        $calendar = new $Calendar($this);
        $calendar->setObjectMelanie($_calendar);
        $this->_sharedCalendars[$_calendar->id] = $calendar;
      }
    }
    return $this->_sharedCalendars;
  }
  
  /**
   * Retourne la liste de tâches par défaut
   * 
   * @return Taskslist
   */
  public function getDefaultTaskslist() {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getDefaultTaskslist()");
    // Si la liste de taches n'est pas déjà chargée
    if (!isset($this->_defaultTaskslist)) {
      // La listes de taches principale est peut être déjà dans _sharedTaskslists ou _userTaskslists
      if (isset($this->_sharedTaskslists)) {
        $this->_defaultTaskslist = $this->_sharedTaskslists[$this->uid] ?: null;
      }
      else if (isset($this->_userTaskslists)) {
        $this->_defaultTaskslist = $this->_userTaskslists[$this->uid] ?: null;
      }
      // Sinon on charge depuis la base de données
      else {
        $_taskslist = $this->objectmelanie->getDefaultTaskslist();
        if (!$_taskslist || !isset($_taskslist)) {
          return null;
        }
        $Taskslist = $this->__getNamespace() . '\\Taskslist';
        $this->_defaultTaskslist = new $Taskslist($this);
        $this->_defaultTaskslist->setObjectMelanie($_taskslist);
      }
      
    }
    return $this->_defaultTaskslist;
  }

  /**
   * Modifie la liste de tâches par défaut de l'utilisateur
   * 
   * @param string|Taskslist $taskslist Liste de tâches à mettre par défaut pour l'utilisateur
   * 
   * @return boolean
   */
  public function setDefaultTaskslist($taskslist) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->setDefaultTaskslist()");
    if (is_object($taskslist)) {
      $taskslist_id = $taskslist->id;
    }
    else if (is_string($taskslist)) {
      $taskslist_id = $taskslist;
    }
    else {
      return false;
    }
    if ($this->savePreference(self::PREF_SCOPE_TASKSLIST, \LibMelanie\Config\ConfigMelanie::TASKSLIST_PREF_DEFAULT_NAME, $taskslist_id)) {
      if (is_object($taskslist)) {
        $this->_defaultTaskslist = $taskslist;
      }
      else {
        $this->_defaultTaskslist = null;
      }
      return true;
    }
    return false;
  }

  /**
   * Création de la liste de taches par défaut pour l'utilisateur courant
   * 
   * @param string $taskslistName [Optionnel] Nom de la liste de taches
   * 
   * @return true si la création est OK, false sinon
   */
  public function createDefaultTaskslist($taskslistName = null) {
    // Gestion du nom de la liste de taches
    $taskslistName = str_replace('%%fullname%%', $this->fullname, $taskslistName);
    $taskslistName = str_replace('%%name%%', $this->name, $taskslistName);
    $taskslistName = str_replace('%%email%%', $this->email, $taskslistName);
    $taskslistName = str_replace('%%uid%%', $this->uid, $taskslistName);
    // Création de la liste de taches
    $Taskslist = $this->__getNamespace() . '\\Taskslist';
    $taskslist = new $Taskslist($this);
    $taskslist->name = $taskslistName ?: $this->fullname;
    $taskslist->id = $this->uid;
    // Création de la liste de tâches
    if ($taskslist->save()) {
      // Création du default taskslist
      $this->setDefaultTaskslist($taskslist->id);
      return true;
    }
    return false;
  }

  /**
   * Retourne la liste des liste de tâches de l'utilisateur
   * 
   * @return Taskslist[]
   */
  public function getUserTaskslists() {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getUserTaskslists()");
    // Si la liste des listes de taches n'est pas encore chargée
    if (!isset($this->_userTaskslists)) {
      $this->_userTaskslists = [];
      // Si les listes de taches partagés sont chargés on utilise les données
      if (isset($this->_sharedTaskslists)) {
        foreach ($this->_sharedTaskslists as $_key => $_list) {
          if ($_list->owner == $this->uid) {
            $this->_userTaskslists[$_key] = $_list;
          }
        }
      }
      else {
        $_taskslists = $this->objectmelanie->getUserTaskslists();
        if (!isset($_taskslists)) {
          return null;
        }
        $Taskslist = $this->__getNamespace() . '\\Taskslist';
        foreach ($_taskslists as $_taskslist) {
          $taskslist = new $Taskslist($this);
          $taskslist->setObjectMelanie($_taskslist);
          $this->_userTaskslists[$_taskslist->id] = $taskslist;
        }
      }
      
    }
    return $this->_userTaskslists;
  }

  /**
   * Retourne la liste des liste de taches de l'utilisateur et celles qui lui sont partagés
   * 
   * @return Taskslist[]
   */
  public function getSharedTaskslists() {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getSharedTaskslists()");
    // Si la liste des listes de tâches n'est pas encore chargée on liste depuis la base
    if (!isset($this->_sharedTaskslists)) {
      $_taskslists = $this->objectmelanie->getSharedTaskslists();
      if (!isset($_taskslists)) {
        return null;
      }
      $this->_sharedTaskslists = [];
      $Taskslist = $this->__getNamespace() . '\\Taskslist';
      foreach ($_taskslists as $_taskslist) {
        $taskslist = new $Taskslist($this);
        $taskslist->setObjectMelanie($_taskslist);
        $this->_sharedTaskslists[$_taskslist->id] = $taskslist;
      }
    }
    return $this->_sharedTaskslists;
  }
  
  /**
   * Retourne la liste de contacts par défaut
   * 
   * @return Addressbook
   */
  public function getDefaultAddressbook() {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getDefaultAddressbook()");
    // Si le carnet n'est pas déjà chargé
    if (!isset($this->_defaultAddressbook)) {
      // La listes de taches principale est peut être déjà dans _sharedAddressbooks ou _userAddressbooks
      if (isset($this->_sharedAddressbooks)) {
        $this->_defaultAddressbook = $this->_sharedAddressbooks[$this->uid] ?: null;
      }
      else if (isset($this->_userAddressbooks)) {
        $this->_defaultAddressbook = $this->_userAddressbooks[$this->uid] ?: null;
      }
      // Sinon on charge depuis la base de données
      else {
        $_addressbook = $this->objectmelanie->getDefaultAddressbook();
        if (!$_addressbook) {
          return null;
        }
        $Addressbook = $this->__getNamespace() . '\\Addressbook';
        $this->_defaultAddressbook = new $Addressbook($this);
        $this->_defaultAddressbook->setObjectMelanie($_addressbook);
      }
    }
    return $this->_defaultAddressbook;
  }

  /**
   * Modifie le carnet d'adresses par défaut de l'utilisateur
   * 
   * @param string|Addressbook $addressbook Carnet d'adresses à mettre par défaut pour l'utilisateur
   * 
   * @return boolean
   */
  public function setDefaultAddressbook($addressbook) {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->setDefaultAddressbook()");
    if (is_object($addressbook)) {
      $addressbook_id = $addressbook->id;
    }
    else if (is_string($addressbook)) {
      $addressbook_id = $addressbook;
    }
    else {
      return false;
    }
    if ($this->savePreference(self::PREF_SCOPE_ADDRESSBOOK, \LibMelanie\Config\ConfigMelanie::ADDRESSBOOK_PREF_DEFAULT_NAME, $addressbook_id)) {
      if (is_object($addressbook)) {
        $this->_defaultAddressbook = $addressbook;
      }
      else {
        $this->_defaultAddressbook = null;
      }
      return true;
    }
    return false;
  }

  /**
   * Création du carnet d'adresses par défaut pour l'utilisateur courant
   * 
   * @param string $addressbookName [Optionnel] Nom du carnet d'adresses
   * 
   * @return true si la création est OK, false sinon
   */
  public function createDefaultAddressbook($addressbookName = null) {
    // Gestion du nom du carnet d'adresses
    $addressbookName = str_replace('%%fullname%%', $this->fullname, $addressbookName);
    $addressbookName = str_replace('%%name%%', $this->name, $addressbookName);
    $addressbookName = str_replace('%%email%%', $this->email, $addressbookName);
    $addressbookName = str_replace('%%uid%%', $this->uid, $addressbookName);
    // Création du carnet d'adresses
    $Addressbook = $this->__getNamespace() . '\\Addressbook';
    $addressbook = new $Addressbook($this);
    $addressbook->name = $addressbookName ?: $this->fullname;
    $addressbook->id = $this->uid;
    // Création du carnet d'adresses
    if ($addressbook->save()) {
      // Création du default addressbook
      $this->setDefaultAddressbook($addressbook->id);
      return true;
    }
    return false;
  }

  /**
   * Retourne la liste des liste de contacts de l'utilisateur
   * 
   * @return Addressbook[]
   */
  public function getUserAddressbooks() {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getUserAddressbooks()");
    // Si la liste des carnets n'est pas encore chargée
    if (!isset($this->_userAddressbooks)) {
      $this->_userAddressbooks = [];
      // Si les listes de carnets partagés sont chargés on utilise les données
      if (isset($this->_sharedAddressbooks)) {
        foreach ($this->_sharedAddressbooks as $_key => $_book) {
          if ($_book->owner == $this->uid) {
            $this->_userAddressbooks[$_key] = $_book;
          }
        }
      }
      else {
        $_addressbooks = $this->objectmelanie->getUserAddressbooks();
        if (!isset($_addressbooks)) {
          return null;
        }
        $Addressbook = $this->__getNamespace() . '\\Addressbook';
        foreach ($_addressbooks as $_addressbook) {
          $addressbook = new $Addressbook($this);
          $addressbook->setObjectMelanie($_addressbook);
          $this->_userAddressbooks[$_addressbook->id] = $addressbook;
        }
      }
    }
    return $this->_userAddressbooks;
  }
  /**
   * Retourne la liste des liste de contacts de l'utilisateur et celles qui lui sont partagés
   * 
   * @return Addressbook[]
   */
  public function getSharedAddressbooks() {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getSharedAddressbooks()");
    // Si la liste des carnets n'est pas encore chargée on liste depuis la base
    if (!isset($this->_sharedAddressbooks)) {
      $_addressbooks = $this->objectmelanie->getSharedAddressbooks();
      if (!isset($_addressbooks)) {
        return null;
      }
      $this->_sharedAddressbooks = [];
      $Addressbook = $this->__getNamespace() . '\\Addressbook';
      foreach ($_addressbooks as $_addressbook) {
        $addressbook = new $Addressbook($this);
        $addressbook->setObjectMelanie($_addressbook);
        $this->_sharedAddressbooks[$_addressbook->id] = $addressbook;
      }
    }
    return $this->_sharedAddressbooks;
  }
  
  /**
   * ***************************************************
   * DATA MAPPING
   */
  /**
   * Est-ce que l'utilisateur est en fait un objet de partage ?
   * 
   * @return boolean true s'il s'agit d'un objet de partage, false sinon
   */
  protected function getMapIs_objectshare() {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getMapIs_objectshare()");
    return isset($this->uid) && strpos($this->uid, $this->getObjectShareDelimiter()) !== false 
        || isset($this->email) && strpos($this->email, $this->getObjectShareDelimiter()) !== false;
  }

  /**
   * Récupère l'objet de partage associé à l'utilisateur courant
   * si celui ci est bien un objet de partage
   * 
   * @return ObjectShare Objet de partage associé, null si pas d'objet de partage
   */
  protected function getMapObjectshare() {
    M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getMapObjectshare()");
    if (!isset($this->objectshare)) {
      if (isset($this->uid) && strpos($this->uid, $this->getObjectShareDelimiter()) !== false 
          || isset($this->email) && strpos($this->email, $this->getObjectShareDelimiter()) !== false) {
        $class = $this->__getNamespace() . '\\ObjectShare';
        $this->objectshare = new $class();
        $this->objectshare->setObjectMelanie($this->objectmelanie);
      }
    }
    return $this->objectshare;
  }
}
