<?php

namespace Watamelo\Managers;

use PDO;
use PDOException;

/**
 * Manage users data
 */
class UserManager extends DataManager
{
    protected string $fetchClass = '\Watamelo\Data\User';
    protected string $tableName = 'user';
    protected string $tableAlias = 'u';
    protected array $defaultOrderBy = ['id' => 'asc'];

    protected array $properties = [
        'id' => ['type' => self::TYPE_INT, 'primary' => true],
        'login' => ['type' => self::TYPE_TEXT, 'insert' => true, 'update' => true, 'required' => true],
        'level' => [
            'type' => self::TYPE_INT,
            'foreignKey' => [
                'class' => 'UserLevel',
                'key' => 'level',
                'fields' => ['name' => 'level_name']
            ],
            'insert' => true,
            'update' => true,
            'required' => true
        ],
        'level_name' => ['type' => self::TYPE_TEXT, 'foreign' => ['class' => 'UserLevel', 'field' => 'name']],
        'password' => ['type' => self::TYPE_PASSWORD, 'insert' => true, 'update' => true, 'required' => true],
        'creation' => ['type' => self::TYPE_DATETIME, 'update' => true, 'required' => true],
        'bio' => ['type' => self::TYPE_TEXT_MULTI, 'insert' => true, 'update' => true, 'required' => true]
    ];

    /**
     * Gives a list of users
     * @param int $quantity Max number of items to be returned
     * @param int $offset Offset of items (for pagination)
     * @param string $sort field to sort on
     * @param string $order asc or desc
     * @param string $filters array of filters (key : filter name, value : filter value)
     * @param bool $resultCount false to return the results, true to only give the count
     * @return array                array of items (each item is a subarray)
     */
    /*public function getList(
        $quantity = 0,
        $offset = 0,
        $sort = "",
        $order = "",
        $filters = array(),
        $resultCount = false
    ) {
        $qry = $this->newSqlGenerator();

        if ($resultCount) {
            $qry->select('user', 'u', array('count' => 'count(u.id)'));
        } else {
            $qry->select('user', 'u', array('u.*'));
            $qry->leftJoin('user_level', 'ul', 'ul.level = u.level', array(
                'level_name' => 'ul.name'
            ));
        }

        foreach ($filters as $field => $value) {
            if ($field == 'level') {
                $qry->where('ul.nom = :level', 'level', $value, PDO::PARAM_INT);
            }
        }

        if (empty($sort)) {
            $sort = 'u.login';
        }
        if (empty($order)) {
            $order = 'asc';
        }

        $qry->orderBy($sort, $order);
        $qry->limit($quantity, $offset);

        return $qry->execute($resultCount ? 'fetchColumn' : 'fetchAll');
    }*/

    /**
     * Returns a user's information
     * @param mixed $value id or login to identify user
     * @param string $type possible values: "id", "login"
     * @param bool $includeSecureInfo whether to return password hash & activation key
     * @return array                      user information (or false if not found)
     */
    /* public function get($value, $type = 'id', $includeSecureInfo = false)
    {
        $qry = $this->newSqlGenerator();
        $qry->select('user', 'u', array('u.*'));

        if ($type == 'id') {
            $qry->where('u.id = :id', 'level', $value, PDO::PARAM_INT);
        } elseif ($type == 'login') {
            $qry->where('LOWER(u.login) = LOWER(:login)', 'login', $value, PDO::PARAM_STR);
        } else {
            //don't return anything if $type is not valid
            $qry->where('0 = 1');
        }

        try {
            $user = $qry->execute('fetch');

            if (!$includeSecureInfo) {
                unset($user->password);
            }

            return ($user);
        } catch (PDOException $e) {
            return false;
        }
    }*/

    /**
     * Returns a user's information (based on id)
     * @param int $id user id
     * @param bool $includeSecureInfo whether to return password hash & activation key
     * @return array                      user information (or false if not found)
     */
    /*public function getById($id, $includeSecureInfo = false)
    {
        return $this->get($id, 'id', $includeSecureInfo);
    }*/

    /**
     * Returns a user's information (based on login)
     * @param string $login user login
     * @param bool $includeSecureInfor whether to return password hash & activation key
     * @return array                       user information (or false if not found)
     */
    /*public function getByLogin($login, $includeSecureInfo = false)
    {
        return $this->get($login, 'login', $includeSecureInfo);
    }*/

    /**
     * Same as getByLogin() with $includeSecureInfor = true
     * @param string $login user login
     * @return array         user information (or false if not found)
     */
    public function getForAuthentication($login)
    {
        $user = $this->get(['login' => $login]);
        if (empty($user)) {
            $user = new \Watamelo\Data\User();
        }
        return $user;
    }

    /**
     * List of existing levels
     * @return array
     */
    public function getLevels(): array
    {
        $qry = $this->newSqlGenerator();
        $qry->select('user_level', 'ul', array('ul.*'));
        $qry->orderBy('ul.level');

        try {
            return $user = $qry->execute();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Add/Update a user's information
     * On update: doesn't update the password (separate method)
     * @param object $values user info
     * @return int|false            user id on success, false on error
     */
//    public function set($values)
//    {
//        $isUpdate = false;
//
//        /**
//         * PREPARE STATEMENTS
//         */
//
//        if (isset($values->id) && $values->id !== false && Tools::isInt($values->id)) {
//            $isUpdate = true;
//
//            $qry = $this->dao->prepare(
//                'UPDATE ' . $this->tables['user']
//                . ' SET level = :level, login = :login, nom = :nom, email = :email'
//                . ' WHERE id = :id'
//            );
//            $qry->bindParam(':id', $values->id, PDO::PARAM_INT);
//        } else {
//            $qry = $this->dao->prepare(
//                'INSERT INTO ' . $this->tables['user']
//                . ' (level, login, nom, email, password)'
//                . ' VALUES (:level, :login, :nom, :email, :password)'
//            );
//            $qry->bindParam(':password', $values->hash, PDO::PARAM_STR);
//        }
//        $qry->bindParam(':level', $values->level, PDO::PARAM_INT);
//        $qry->bindParam(':login', $values->login, PDO::PARAM_STR);
//        $qry->bindParam(':nom', $values->name, PDO::PARAM_STR);
//        $qry->bindParam(':email', $values->email, PDO::PARAM_STR);
//
//        /**
//         * EXECUTE
//         */
//
//        $this->dao->beginTransaction();
//        try {
//            $qry->execute();
//            if (!$isUpdate) {
//                $values->id = $this->dao->lastInsertId();
//            }
//
//            $this->dao->commit();
//
//        } catch (PDOException $e) {
//            $this->dao->rollback();
//            return false;
//        }
//
//        return $values->id;
//    }

    /**
     * Update a user's password
     * @param int $id user's id
     * @param string $hash hash of user's password
     * @return bool       true on success
     */
    /*public function setPassword($id, $hash)
    {
        $qry = $this->newSqlGenerator();
        $qry->update('user');
        $qry->setField('password', $hash);
        $qry->where('id = :id', 'id', $id, PDO::PARAM_INT);

        try {
            return $user = $qry->execute();
        } catch (PDOException $e) {
            return false;
        }
    }*/

    /**
     * Update a user's EMAIL
     * @param int $id user's id
     * @param string $email new email
     * @return bool        true on success
     */
    /*public function setEmail($id, $email)
    {
        $qry = $this->newSqlGenerator();
        $qry->update('user');
        $qry->setField('email', $email);
        $qry->where('id = :id', 'id', $id, PDO::PARAM_INT);

        try {
            return $user = $qry->execute();
        } catch (PDOException $e) {
            return false;
        }
    }*/

    /**
     * Completely remove a user from database
     * @param int $id user's id
     * @return bool     whether the deletion was a success
     */
    /*public function delete($id)
    {
        //TODO
    }*/

    /**
     * Add a role to an existing user
     * @param int $userId User
     * @param string $nom Type of role
     * @param string $value Optional value related to the type
     * @return bool         true on success
     */
    public function addRole(int $userId, string $nom, string $value): bool
    {
        try {
            $qry = $this->dao->prepare(
                "INSERT INTO " . $this->tables['user_role'] . ' (userId_FK, nom, value)'
                . ' VALUES (:userId, :nom, :value)');
            $qry->bindParam(':userId', $userId, PDO::PARAM_INT);
            $qry->bindParam(':nom', $nom);
            $qry->bindParam(':value', $value);
            return $qry->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Remove a role from a user
     * @param int $userId User
     * @param string $nom Type of role
     * @param string|null $value Optional value related to the type
     * @return bool         true on success
     */
    public function removeRole(int $userId, string $nom, string $value = null): bool
    {
        try {

            $sql = 'DELETE FROM ' . $this->tables['user_role']
                . ' WHERE userId_FK = :userId AND nom = :nom';
            if (!is_null($value)) {
                $sql .= ' AND value = :value';
            }

            $qry = $this->dao->prepare($sql);
            $qry->bindParam(':userId', $userId, PDO::PARAM_INT);
            $qry->bindParam(':nom', $nom);
            if (!is_null($value)) {
                $qry->bindParam(':value', $value);
            }
            return $qry->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
