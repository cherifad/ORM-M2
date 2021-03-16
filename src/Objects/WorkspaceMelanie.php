<?php
/**
 * Ce fichier est développé pour la gestion de la librairie Mélanie2
 * Cette Librairie permet d'accèder aux données sans avoir à implémenter de couche SQL
 * Des objets génériques vont permettre d'accèder et de mettre à jour les données
 *
 * ORM M2 Copyright © 2017  PNE Annuaire et Messagerie/MEDDE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace LibMelanie\Objects;

use LibMelanie\Sql;
use LibMelanie\Log\M2Log;

/**
 * Traitement des workspaces Melanie2
 * 
 * @author PNE Messagerie/Apitech
 * @package Librairie Mélanie2
 * @subpackage ORM
 */
class WorkspaceMelanie extends ObjectMelanie {
    /**
	 * Constructeur par défaut, appelé par PDO
	 */
	public function __construct() {
	    parent::__construct('Workspace');
	}

    /**
     * Lister les workspaces par hashtag
     * 
     * @param string $hashtag Hashtag recherché
     * @return WorkspaceMelanie[]
     */
	public function listWorkspacesByHashtag($hashtag) {
        M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->listWorkspacesByHashtag()");
        $query = Sql\SqlWorkspaceRequests::listWorkspacesByHashtag;
        $query = str_replace('{order_by}', '', $query);
        $query = str_replace('{limit}', '', $query);
        // Params
        $params = [
            "hashtag" => $hashtag,
        ];
        // Liste les workspaces par hashtag
        return Sql\Sql::GetInstance()->executeQuery($query, $params, 'LibMelanie\\Objects\\WorkspaceMelanie', 'Workspace');
    }

    /**
     * Lister les hashtags du workspace courant
     * 
     * @return ObjectMelanie[]
     */
    public function getWorkspaceHashtags() {
        M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getUserWorkspaces()");
        // Gestion du mapping global
        if (!isset($this->id)) {
            return false;
        }
        $query = Sql\SqlWorkspaceRequests::listWorkspaceHashtags;
        $query = str_replace('{order_by}', '', $query);
        $query = str_replace('{limit}', '', $query);
        // Params
        $params = [
            "workspace_id" => $this->id,
        ];
        // Liste les calendriers de l'utilisateur
        return Sql\Sql::GetInstance()->executeQuery($query, $params, 'LibMelanie\\Objects\\ObjectMelanie', 'WorkspaceHashtag');
    }

    /**
     * Lister les shares du workspace courant
     * 
     * @return ObjectMelanie[]
     */
    public function getWorkspaceShares() {
        M2Log::Log(M2Log::LEVEL_DEBUG, $this->get_class . "->getWorkspaceShares()");
        // Gestion du mapping global
        if (!isset($this->id)) {
            return false;
        }
        $query = Sql\SqlWorkspaceRequests::listWorkspaceShares;
        $query = str_replace('{order_by}', '', $query);
        $query = str_replace('{limit}', '', $query);
        // Params
        $params = [
            "workspace_id" => $this->id,
        ];
        // Liste les calendriers de l'utilisateur
        return Sql\Sql::GetInstance()->executeQuery($query, $params, 'LibMelanie\\Objects\\ObjectMelanie', 'WorkspaceShare');
    }
}