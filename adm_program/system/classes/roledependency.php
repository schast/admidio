<?php
/******************************************************************************
 * Class to manage dependencies between different roles.
 *
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 https://www.gnu.org/licenses/gpl-2.0.html
 *
 *****************************************************************************/

/**
 * Class RoleDependency
 */
class RoleDependency
{
    public $db;

    public $roleIdParent;
    public $roleIdChild;
    public $comment;
    public $usr_id;
    public $timestamp;

    public $roleIdParentOrig;
    public $roleIdChildOrig;

    public $persisted;

    /**
     * Constructor
     * @param object $db
     */
    public function __construct(&$db)
    {
        $this->db =& $db;
        $this->clear();
    }

    /**
     * alle Klassenvariablen wieder zuruecksetzen
     * @return void
     */
    public function clear()
    {
        $this->roleIdParent     = 0;
        $this->roleIdChild      = 0;
        $this->comment          = '';
        $this->usr_id           = 0;
        $this->timestamp        = '';

        $this->roleIdParentOrig = 0;
        $this->roleIdChildOrig  = 0;

        $this->persisted = false;
    }

    /**
     * aktuelle Rollenabhaengigkeit loeschen
     * @return void
     */
    public function delete()
    {
        $sql = 'DELETE FROM '. TBL_ROLE_DEPENDENCIES.
               ' WHERE rld_rol_id_child  = '.$this->roleIdChildOrig.
                 ' AND rld_rol_id_parent = '.$this->roleIdParentOrig;
        $this->db->query($sql);

        $this->clear();
    }

    /**
     * Rollenabhaengigkeit aus der Datenbank auslesen
     * @param int $childRoleId
     * @param int $parentRoleId
     */
    public function get($childRoleId, $parentRoleId)
    {

        $this->clear();

        if(is_numeric($childRoleId) && is_numeric($parentRoleId) && $childRoleId > 0 && $parentRoleId > 0)
        {
            $sql = 'SELECT * FROM '. TBL_ROLE_DEPENDENCIES.
                   ' WHERE rld_rol_id_child  = '.$childRoleId.'
                       AND rld_rol_id_parent = '.$parentRoleId;
            $this->db->query($sql);

            $row = $this->db->fetch_object();
            if($row)
            {
                $this->roleIdParent     = $row->rld_rol_id_parent;
                $this->roleIdChild      = $row->rld_rol_id_child;
                $this->comment          = $row->rld_comment;
                $this->timestamp        = $row->rld_timestamp;
                $this->usr_id           = $row->rld_usr_id;

                $this->roleIdParentOrig = $row->rld_rol_id_parent;
                $this->roleIdChildOrig  = $row->rld_rol_id_child;
            }
            else
            {
                $this->clear();
            }
        }
        else
        {
            $this->clear();
        }
    }

    /**
     * @param  object $db
     * @param  int    $parentId
     * @return array
     */
    public static function getChildRoles(&$db, $parentId)
    {
        $allChildIds = array();

        if(is_numeric($parentId) && $parentId > 0)
        {
            $sql = 'SELECT rld_rol_id_child FROM '. TBL_ROLE_DEPENDENCIES.
                   ' WHERE rld_rol_id_parent = '.$parentId;
            $db->query($sql);

            $num_rows = $db->num_rows();
            if ($num_rows)
            {
                while ($row = $db->fetch_object())
                {
                    $allChildIds[] = $row->rld_rol_id_child;
                }
            }
        }

        return $allChildIds;
    }

    /**
     * @param  object $db
     * @param  int    $childId
     * @return array
     */
    public static function getParentRoles(&$db, $childId)
    {
        $allParentIds = array();

        if(is_numeric($childId) && $childId > 0)
        {
            $sql = 'SELECT rld_rol_id_parent FROM '.TBL_ROLE_DEPENDENCIES.
                   ' WHERE rld_rol_id_child = '.$childId;
            $db->query($sql);

            $num_rows = $db->num_rows();
            if ($num_rows)
            {
                while ($row = $db->fetch_object())
                {
                    $allParentIds[] = $row->rld_rol_id_parent;
                }
            }
        }

        return $allParentIds;
    }

    /**
     * @param  int $login_user_id
     * @return int
     */
    public function insert($login_user_id)
    {
        if(!$this->isEmpty() && is_numeric($login_user_id) && $login_user_id > 0)
        {
            $sql = 'INSERT INTO '.TBL_ROLE_DEPENDENCIES.'
                                (rld_rol_id_parent,rld_rol_id_child,rld_comment,rld_usr_id,rld_timestamp)
                         VALUES ('.$this->roleIdParent.', '.$this->roleIdChild.', \''.$this->comment.'\', '.$login_user_id.', \''.DATETIME_NOW.'\') ';
            $this->db->query($sql);
            $this->persisted = true;

            return 0;
        }

        return -1;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        if ($this->roleIdParent === 0 && $this->roleIdChild === 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * @param  object $db
     * @param  int    $parentId
     * @return int
     */
    public static function removeChildRoles(&$db, $parentId)
    {
        if(is_numeric($parentId) && $parentId > 0)
        {
            $sql = 'DELETE FROM '.TBL_ROLE_DEPENDENCIES.
                   ' WHERE rld_rol_id_parent = '.$parentId;
            $db->query($sql);

            return 0;
        }

        return -1;
    }

    /**
     * @param  int $parentId
     * @return int
     */
    public function setParent($parentId)
    {
        if(is_numeric($parentId) && $parentId > 0)
        {
            $this->roleIdParent = $parentId;
            $this->persisted = false;

            return 0;
        }

        return -1;
    }

    /**
     * @param  int $childId
     * @return int
     */
    public function setChild($childId)
    {
        if(is_numeric($childId) && $childId > 0)
        {
            $this->roleIdChild = $childId;
            $this->persisted = false;

            return 0;
        }

        return -1;
    }

    /**
     * Es muss die ID des eingeloggten Users uebergeben werden, damit die Aenderung protokolliert werden kann
     * @param  int $login_user_id
     * @return int
     */
    public function update($login_user_id)
    {
        if(!$this->isEmpty() && is_numeric($login_user_id) && $login_user_id > 0)
        {
            $sql = 'UPDATE '.TBL_ROLE_DEPENDENCIES.' SET rld_rol_id_parent = \''.$this->roleIdParent.'\'
                                                       , rld_rol_id_child  = \''.$this->roleIdChild.'\'
                                                       , rld_comment       = \''.$this->comment.'\'
                                                       , rld_timestamp     = \''.DATETIME_NOW.'\'
                                                       , rld_usr_id        = '.$login_user_id.'
                     WHERE rld_rol_id_parent = '.$this->roleIdParentOrig.'
                       AND rld_rol_id_child  = '.$this->roleIdChildOrig;
            $this->db->query($sql);
            $this->persisted = true;

            return 0;
        }

        return -1;
    }

    /**
     * Adds all active memberships of the child role to the parent role.
     * If a membership still exists than start date will not be changed. Only
     * the end date will be set to 31.12.9999.
     * @return int Returns -1 if no parent or child row exists
     */
    public function updateMembership()
    {
        if($this->roleIdParent > 0 && $this->roleIdChild > 0)
        {
            $sql = 'SELECT mem_usr_id FROM '.TBL_MEMBERS.
                   ' WHERE mem_rol_id = '.$this->roleIdChild.'
                       AND mem_begin <= \''.DATE_NOW.'\'
                       AND mem_end    > \''.DATE_NOW.'\'';
            $result = $this->db->query($sql);

            $num_rows = $this->db->num_rows($result);
            if ($num_rows)
            {
                $member = new TableMembers($this->db);

                while ($row = $this->db->fetch_object($result))
                {
                    $member->startMembership($this->roleIdParent, $row->mem_usr_id);
                }
            }

            return 0;
        }

        return -1;
    }
}
?>
