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
namespace LibMelanie\Api\Defaut\Users;

use LibMelanie\Lib\MceObject;

/**
 * Classe utilisateur par defaut
 * pour la gestion des partages de messagerie
 * 
 * @author Groupe Messagerie/MTES - Apitech
 * @package LibMCE
 * @subpackage API/Defaut/Users
 * @api
 * 
 * @property string $user Identifiant de l'utilisateur
 * @property string $type Type de partage (Voir Share::TYPE_*)
 */
class Share extends MceObject {
  // Type de partage : Lecture seule, Ecriture, Emission, Gestionnaire
  const TYPE_READ = 'L';
  const TYPE_WRITE = 'E';
  const TYPE_SEND = 'C';
  const TYPE_ADMIN = 'G';
}