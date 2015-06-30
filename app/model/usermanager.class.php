<?php

/**
 * Manage users data
 */
class UserManager extends WatameloManager  {
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
    public function getList($quantity = 0, $offset = 0, $sort = "", $order = "", $filters = array(), $resultCount = false) {
        $qry = $this->newSqlGenerator();

        if($resultCount) {
            $qry->select('user', 'u', array('count' => 'count(u.id)'));
        } else {
            $qry->select('user', 'u', array('u.*'));
            $qry->leftJoin('user_level', 'ul', 'ul.level = u.level', array(
                'levelName' => 'ul.name'
            ));
        }

        foreach($filters as $field => $value) {
            if($field == 'level') {
                $qry->where('ul.name = :level', 'level', $value, PDO::PARAM_INT);
            }
        }

        if(empty($sort))
            $sort = 'u.login';
        if(empty($order))
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
    public function get($value, $type = 'id', $includeSecureInfo = false) {
        $qry = $this->newSqlGenerator();
        $qry->select('user', 'u', array('u.*'));

        if($type == 'id') {
            $qry->where('u.id = :id', 'level', $value, PDO::PARAM_INT);
        } elseif($type == 'login') {
            $qry->where('LOWER(u.login) = LOWER(:login)', 'login', $value, PDO::PARAM_STR);
        } else {
            //don't return anything if $type is not valid
            $qry->where('0 = 1');
        }

        try {
            $user = $qry->execute('fetch');

            if(!$includeSecureInfo) {
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
    public function getById($id, $includeSecureInfo = false) {
        return $this->get($id, 'id', $includeSecureInfo);
    }

    /**
     * Returns a user's information (based on login)
     * @param  string  $login              user login
     * @param  boolean $includeSecureInfor whether to return password hash & activation key
     * @return array                       user informations (or false if not found)
     */
    public function getByLogin($login, $includeSecureInfo = false) {
        return $this->get($login, 'login', $includeSecureInfo);
    }

    /**
     * Same as getByLogin() with $includeSecureInfor = true
     * @param  string $login user login
     * @return array         user informations (or false if not found)
     */
    public function getForAuthentication($login) {
        return $this->get($login, 'login', true);
    }

    public function getLevels() {
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
     * Update a user's password
     * @param integer  $id   user's id
     * @param string   $hash hash of user's password
     * @return boolean       true on success
     */
    public function setPassword($id, $hash) {
        $qry = $this->newSqlGenerator();
        $qry->update('user');
        $qry->setField('password', $hash);
        $qry->where('id = :id', 'id', $id, PDO::PARAM_INT);

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
    public function setEmail($id, $email) {
        $qry = $this->newSqlGenerator();
        $qry->update('user');
        $qry->setField('email', $email);
        $qry->where('id = :id', 'id', $id, PDO::PARAM_INT);

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
    public function delete($id) {
        //TODO
    }

    public function getOrphans() {
        //TODO: find orphan records
        return array();
    }
}

?>