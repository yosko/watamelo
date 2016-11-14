<?php
namespace Watamelo\Managers;

/**
 * Manage users data
 */
class UserManager extends WatameloManager
{
    /**
     * Gives a list of users
     * @param  integer $quantity    Max number of items to be returned
     * @param  integer $offset      Offset of items (for pagination)
     * @param  string  $sort        field to sort on
     * @param  string  $order       asc or desc
     * @param  string  $filters     array of filters (key : filter name, value : filter value)
     * @param  boolean $resultCount false to return the results, true to only give the count
     * @return array                array of items (each item is a subarray)
     */
    public function getList($quantity = 0, $offset = 0, $sort = "", $order = "", $filters = array(), $resultCount = false)
    {
        $qry = $this->newSqlGenerator();

        if ($resultCount) {
            $qry->select('user', 'u', array('count' => 'count(u.id)'));
        } else {
            $qry->select('user', 'u', array('u.*'));
            $qry->leftJoin('user_level', 'ul', 'ul.level = u.level', array(
                'levelName' => 'ul.name'
            ));
        }

        foreach ($filters as $field => $value) {
            if ($field == 'level') {
                $qry->where('ul.name = :level', 'level', $value, \PDO::PARAM_INT);
            }
        }

        if (empty($sort))
            $sort = 'u.login';
        if (empty($order))
            $order = 'asc';

        $qry->orderBy($sort, $order);
        $qry->limit($quantity, $offset);

        return $qry->execute($resultCount?'fetchColumn':'fetchAll');
    }

    /**
     * Returns a user's information
     * @param  misc    $value             id or login to identify user
     * @param  string  $type              possible values: "id", "login"
     * @param  boolean $includeSecureInfo whether to return password hash & activation key
     * @return array                      user informations (or false if not found)
     */
    public function get($value, $type = 'id', $includeSecureInfo = false)
    {
        $qry = $this->newSqlGenerator();
        $qry->select('user', 'u', array('u.*'));

        if ($type == 'id') {
            $qry->where('u.id = :id', 'level', $value, \PDO::PARAM_INT);
        } elseif ($type == 'login') {
            $qry->where('LOWER(u.login) = LOWER(:login)', 'login', $value, \PDO::PARAM_STR);
        } else {
            //don't return anything if $type is not valid
            $qry->where('0 = 1');
        }

        try {
            $user = $qry->execute('fetch');

            if (!$includeSecureInfo) {
                unset($user->password);
            }

            return($user);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Returns a user's information (based on id)
     * @param  integer $id                user id
     * @param  boolean $includeSecureInfo whether to return password hash & activation key
     * @return array                      user informations (or false if not found)
     */
    public function getById($id, $includeSecureInfo = false)
    {
        return $this->get($id, 'id', $includeSecureInfo);
    }

    /**
     * Returns a user's information (based on login)
     * @param  string  $login              user login
     * @param  boolean $includeSecureInfor whether to return password hash & activation key
     * @return array                       user informations (or false if not found)
     */
    public function getByLogin($login, $includeSecureInfo = false)
    {
        return $this->get($login, 'login', $includeSecureInfo);
    }

    /**
     * Same as getByLogin() with $includeSecureInfor = true
     * @param  string $login user login
     * @return array         user informations (or false if not found)
     */
    public function getForAuthentication($login)
    {
        return $this->get($login, 'login', true);
    }

    public function getLevels()
    {
        $qry = $this->newSqlGenerator();
        $qry->select('user_level', 'ul', array('ul.*'));
        $qry->orderBy('ul.level');

        try {
            return $user = $qry->execute('fetchAll');
        } catch (PDOException $e) {
            return array();
        }
    }

    /**
     * Add/Update a user's information
     * On update: doesn't update the password (separate method)
     * @param  object  $values user info
     * @return misc            user id on success, false on error
     */
    public function set($values)
    {
        $isUpdate = false;

        /**
         * PREPARE STATEMENTS
         */

        if (isset($values->id) && $values->id !== false && \Watamelo\Utils\Tools::isInt($values->id)) {
            $isUpdate = true;

            $qry = $this->dao->prepare(
                'UPDATE '.$this->tables['user']
                .' SET level = :level, login = :login, name = :name, email = :email'
                .' WHERE id = :id'
            );
            $qry->bindParam(':id', $values->id, \PDO::PARAM_INT);
        } else {
            $qry = $this->dao->prepare(
                'INSERT INTO '.$this->tables['user']
                .' (level, login, name, email, password)'
                .' VALUES (:level, :login, :name, :email, :password)'
            );
            $qry->bindParam(':password', $values->hash, \PDO::PARAM_STR);
        }
        $qry->bindParam(':level', $values->level, \PDO::PARAM_INT);
        $qry->bindParam(':login', $values->login, \PDO::PARAM_STR);
        $qry->bindParam(':name', $values->name, \PDO::PARAM_STR);
        $qry->bindParam(':email', $values->email, \PDO::PARAM_STR);

        /**
         * EXECUTE
         */

        $this->dao->beginTransaction();
        try {
            $qry->execute();
            if(!$isUpdate) {
                $values->id = $this->dao->lastInsertId();
            }

            $this->dao->commit();

        } catch (PDOException $e) {
            $this->dao->rollback();
            return false;
        }

        return $values->id;
    }

    /**
     * Update a user's password
     * @param integer  $id   user's id
     * @param string   $hash hash of user's password
     * @return boolean       true on success
     */
    public function setPassword($id, $hash)
    {
        $qry = $this->newSqlGenerator();
        $qry->update('user');
        $qry->setField('password', $hash);
        $qry->where('id = :id', 'id', $id, \PDO::PARAM_INT);

        try {
            return $user = $qry->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Update a user's EMAIL
     * @param integer  $id    user's id
     * @param string   $email new email
     * @return boolean        true on success
     */
    public function setEmail($id, $email)
    {
        $qry = $this->newSqlGenerator();
        $qry->update('user');
        $qry->setField('email', $email);
        $qry->where('id = :id', 'id', $id, \PDO::PARAM_INT);

        try {
            return $user = $qry->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Completely remove a user from database
     * @param  integer $id user's id
     * @return boolean     whether the deletion was a success
     */
    public function delete($id)
    {
        //TODO
    }

    /**
     * Add a role to an existing user
     * @param integer  $userId User
     * @param string   $name   Type of role
     * @param string   $value  Optional value related to the type
     * @return boolean         true on success
     */
    public function addRole($userId, $name, $value)
    {
        try {
            $qry = $this->dao->prepare(
                "INSERT INTO ".$this->tables['user_role'].' (userId_FK, name, value)'
                .' VALUES (:userId, :name, :value)');
            $qry->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $qry->bindParam(':name', $name, \PDO::PARAM_STR);
            $qry->bindParam(':value', $value, \PDO::PARAM_STR);
            return $qry->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Remove a role from a user
     * @param integer  $userId User
     * @param string   $name   Type of role
     * @param string   $value  Optional value related to the type
     * @return boolean         true on success
     */
    public function removeRole($userId, $name, $value = null)
    {
        try {

            $sql = 'DELETE FROM '.$this->tables['user_role']
                .' WHERE userId_FK = :userId AND name = :name';
            if(!is_null($value)) {
                $sql .= ' AND value = :value';
            }

            $qry = $this->dao->prepare( $sql );
            $qry->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $qry->bindParam(':name', $name, \PDO::PARAM_STR);
            if(!is_null($value)) {
                $qry->bindParam(':value', $value, \PDO::PARAM_STR);
            }
            return $qry->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getOrphans()
    {
        //TODO: find orphan records
        return array();
    }
}
