<?php

/**
 * Manage users data
 */
class UserManager extends Manager  {
    /**
     * Gives a list of users
     * @param  integer $limit   Max number of users to be returned
     * @param  integer $offset  Offeset of users (for pagination)
     * @param  string  $sort    TODO
     * @param  string  $order   TODO
     * @param  string  $filters array of filters (key : filter name, value : filter value)
     * @return array            array of users (each user is a subarray)
     */
    public function getList($limit = 0, $offset = 0, $sort = "", $order = "", $filters = array()) {
        $sqlLimit = "";
        $sqlOrderBy = "";
        $sqlWhere = "";

        if($limit > 0) {
            if($offset > 0) {
                $sqlLimit = " LIMIT ".$offset.", ".$limit;
            } else {
                $sqlLimit = " LIMIT ".$limit;
            }
        }

        if(!empty($sort)) {
            //TODO
        } else {
            $sqlOrderBy = " ORDER BY u.userLogin";
        }

        if(!empty($filters)) {
            foreach($filters as $key => $value) {
                if($key == "level") {
                    $sqlWhere .= " AND ul.userLevelName = :level";
                }
            }
            $sqlWhere = preg_replace("/AND/", "WHERE", $sqlWhere, 1);
        }


        $sql = "SELECT u.userId as id, u.userLevel as level, ul.userLevelName as levelName"
            .", u.userLogin as login, u.userCreation as creation, u.userBio as bio"
            ." FROM wa_user u"
            ." INNER JOIN wa_user_level ul ON ul.userLevel = u.userLevel"
            .$sqlWhere
            .$sqlOrderBy
            .$sqlLimit;

        $qryUsers = $this->dao->prepare( $sql );

        if(!empty($filters)) {
            foreach($filters as $key => $value) {
                if($key == "level") {
                    $level = $value;
                    $qryUsers->bindParam(':level', $level, PDO::PARAM_STR);  
                }
            }
        }

        $qryUsers->execute();
        $users = $qryUsers->fetchAll(PDO::FETCH_ASSOC);
        
        return $users;
    }

    /**
     * Returns a user's information
     * @param  misc    $value             id or login to identify user
     * @param  string  $type              possible values: "id", "login"
     * @param  boolean $includeSecureInfo whether to return password hash & activation key
     * @return array                      user informations (or false if not found)
     */
    public function get($value, $type = 'id', $includeSecureInfo = false) {
        $sql = "SELECT u.userId as id, u.userLevel as level, u.userLogin as login"
            .", u.userCreation as creation, u.userBio as bio";
        if($includeSecureInfo) {
            $sql .= ", u.userPassword as password";
        }
        $sql .= " FROM wa_user u";
        if($type == "id") {
            $sql .= " WHERE u.userId = :id";
        } elseif($type == "login") {
            $sql .= " WHERE LOWER(u.userLogin) = LOWER(:login)";
        } else {
            //don't return anything if $type is not valid
            $sql .= " WHERE 0 = 1";
        }

        $qryUser = $this->dao->prepare( $sql );

        if($type == "id") {
            $qryUser->bindParam(':id', $value, PDO::PARAM_INT);
        } elseif($type == "login") {
            $qryUser->bindParam(':login', $value, PDO::PARAM_STR);
        }

        $qryUser->execute();
        $user = $qryUser->fetch(PDO::FETCH_ASSOC);

        return($user);
    }

    /**
     * Returns a user's information (based on id)
     * @param  integer $id                user id
     * @param  boolean $includeSecureInfo whether to return password hash & activation key
     * @return array                      user informations (or false if not found)
     */
    public function getById($id, $includeSecureInfo = false) {
        return $this->get($id, "id", $includeSecureInfo);
    }
    
    /**
     * Returns a user's information (based on login)
     * @param  string  $login              user login
     * @param  boolean $includeSecureInfor whether to return password hash & activation key
     * @return array                       user informations (or false if not found)
     */
    public function getByLogin($login, $includeSecureInfo = false) {
        return $this->get($login, "login", $includeSecureInfo);
    }

    /**
     * Same as getByLogin() with $includeSecureInfor = true
     * @param  string $login user login
     * @return array         user informations (or false if not found)
     */
    public function getForAuthentication($login) {
        return $this->get($login, "login", true);
    }

    public function getLevels() {
        $sql = "SELECT ul.userLevelId as id, ul.userLevelName as name, ul.userLevel as level"
            ." FROM wa_user_level ul"
            ." ORDER BY ul.userLevel DESC";
        $qryLevels = $this->dao->prepare( $sql );
        $qryLevels->execute();
        $levels = $qryLevels->fetchAll(PDO::FETCH_ASSOC);
        return $levels;
    }

    /**
     * Update a user's password
     * @param integer  $id   user's id
     * @param string   $hash hash of user's password
     * @return boolean       true on success
     */
    public function setPassword($id, $hash) {
        try {
            $qryUpdateUser = $this->dao->prepare(
                "UPDATE wa_user SET userPassword = :password"
                ." WHERE userId = :id");
            $qryUpdateUser->bindParam(':id', $id, PDO::PARAM_INT);
            $qryUpdateUser->bindParam(':password', $hash, PDO::PARAM_STR);
            return $qryUpdateUser->execute();
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
        try {
            $qryUpdateUser = $this->dao->prepare(
                "UPDATE wa_user SET userEmail = :email"
                ." WHERE userId = :id");
            $qryUpdateUser->bindParam(':id', $id, PDO::PARAM_INT);
            $qryUpdateUser->bindParam(':email', $email, PDO::PARAM_STR);
            return $qryUpdateUser->execute();
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