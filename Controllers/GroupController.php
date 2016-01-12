<?php
class GroupController{
    /**
    * User Access check
    */  
    private static function hasRight($idGroup, $idUser){
        //3 Owner
        //4 Admin
        return User::find($idUser)->groups->find($idGroup)->pivot->role_id==3;
    }

    /**
     * @api {get} /managers/:id Check if user is manager
     * @apiName is Manager
     * @apiGroup User
     * @apiDescription check if the specified user is manager.
     *                 <p>If a user is a manager or owner of any group, he will be a manager.</p> 
     * @apiParam {Number} id Users unique ID.
     *
     * @apiError 404 User not found
     * @apiError 403 The user is not a manager 
     * @apiSuccess 200 The user is a manager
     */
    public static function isAdmin($idUser)
    {   
        $app = \Slim\Slim::getInstance();
        UserController::findUser($idUser);
        if (!User::find($idUser)->groups()->where('role_id','>=',3)->count()){
            $app->halt("403");
        }
        return json_encode(1); 
    }

    public static function adminCheck($idUser)
    {   
        UserController::findUser($idUser);
        return User::find($idUser)->groups()->where('role_id','>=',3)->count()>0;
    }

    /**
     * @api {get} /managers/:id/managers Get managers involves to a manager
     * @apiName Get managers involves to a manager
     * @apiGroup Manager
     * @apiDescription Returns a collection of managers involve with the manager specified by id.
     *                 <p>Authorization required.</p>
     *                 <p>Returns owners and admins from the groups which the specified manager has owner/admin permission.</p>
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam {Number} id Users unique ID.
     *
     * @apiError 404 User not found
     * @apiError 403 The user is not a manager 
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "user_id": "13",
     *       "full_name": "Ryan Uttech",
     *       "username": "ruttech@ignitorlabs.com",
     *       "email": "ruttech@ignitorlabs.com"
     *     }
     *
     */
    public static function getAdminManager($idUser)
    {   
        self::isAdmin($idUser);
        $groups = User::find($idUser)->groups()->where('role_id','>=',3)->get();
        $managers = [];
        foreach ($groups as $key => $group) {
            $users = $group->members()->get();
            foreach ($users as $key => $user) {
                if(!self::adminCheck($user->id)){
                    //user is a admin/owner of any group
                    continue;
                }
                $data = array(
                    "user_id"=>$user->id,
                    "full_name"=>$user->first_name. " ". $user->last_name,
                    "username"=>is_null($user->username)?$user->email:$user->username,
                    "email"=>$user->email
                    );
                if(!in_array($data, $managers)&&$user->id!=$idUser) array_push($managers, $data);
            }
        }
        return json_encode($managers);
        
    }

    /**
     * @api {get} /managers/:id/members Get members belongs to a manager
     * @apiName Get members belongs to a manager
     * @apiDescription As a manager, I can see all the users(None-manager) that under my groups except myself.
     *                 <p>Authorization required.</p>
     *                 <p>Returns user and reporter from the groups which the specified manager has owner/admin permission.</p>
     * @apiGroup Manager
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam {Number} id Users unique ID.
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "user_id": "13",
     *       "full_name": "Ryan Uttech",
     *       "username": "ruttech@ignitorlabs.com",
     *       "email": "ruttech@ignitorlabs.com"
     *     }
     *
     */
    public static function getAdminMember($idUser)
    {   
        self::isAdmin($idUser);
        $groups = User::find($idUser)->groups()->where('role_id','>=',1)->get();
        $members = [];
        foreach ($groups as $key => $group) {
            $users = $group->members()->get();
            foreach ($users as $key => $user) {
                if(self::adminCheck($user->id)){
                    continue;
                }
                $data = array(
                    "user_id"=>$user->id,
                    "full_name"=>$user->first_name. " ". $user->last_name,
                    "username"=>is_null($user->username)?$user->email:$user->username,
                    "email"=>$user->email
                    );
                if(!in_array($data, $members)&&$user->id!=$idUser) array_push($members, $data);
            }
        }
        return json_encode($members);
    }

    public static function isMemberOfAdmin($idUser,$idAdmin)
    {   
        self::isAdmin($idAdmin);
        $groups = User::find($idAdmin)->groups()->where('role_id','>=',3)->get();
        foreach ($groups as $key => $group) {
            if(self::isMember($group->id,$idUser)){
                return 1;
            }
        }
        return 0;
    }

    public static function isManagerOfAdmin($idUser,$idAdmin)
    {   
        self::isAdmin($idAdmin);
        $groups = User::find($idUser)->groups()->where('role_id','>=',3)->get();
        $managers = [];
        foreach ($groups as $key => $group) {
            $users = $group->members()->where('role_id','>=',3)->get();
            foreach ($users as $key => $user) {
                if($user->id==$idUser){
                    return 1;
                }
            }
        }
        return 0;
    }

    /**
     * @api {get} /groups/:id/users/:idUser/role Get User Group Role
     * @apiName Get User Role of the Group
     * @apiGroup Group
     *
     * @apiDescription Return user's role of the group.<br>
     *                 <b>Note:</b><br>
     *                 <li>Authorization required.<li>
     *                 <li>If the user not enrolled, the returned id will be 0.<li>
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam id Group unique id
     * @apiParam idUser User id 
     *
     * @apiError 404 User or group not found
     * 
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "id": "1",
     *       "name": "User"
     *     }
     */ 
    public static function getRole($idGroup, $idUser){
        UserController::findUser($idUser);
        $group = self::getGroupInfo($idGroup);
        if(!Group::find($idGroup)->members->contains($idUser)){
            return (object) array(
                'id'=>0,
                'name'=>"notEnroll"
            );
        } else {
            $result = Group::find($idGroup)->members->find($idUser)->pivot;
            return Role::find($result->role_id);
        }
    }

    /**
     * @api {post} /groups/:idGroup/users/:id/role Set User Group Role
     * @apiName Set User Role of the Group
     * @apiGroup Group
     * @apiDescription Set user role by the group owner/admin.
     *                 <p>Authorization required.</p>
     *                 <p><b>Notes:</b>
     *                     <ul>
     *                         <li>Admin/Owner cannot set his own role.</li>
     *                         <li>Only Owner can remove/assign Admin role.</li>
     *                         <li>Admin/Owner can only set role of the users within the groups he has premission.</li>
     *                     </ul>
     *                 </p>  
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam  (url Parameter) {Number} idGroup Group unique ID.
     * @apiParam  (url Parameter) {Number} id Users unique ID. The operator's id, usually the admin of the group.
     * @apiParam  {Number} idUser User's unique ID. The change will apply to this user.
     * @apiParam  {Number} role Role unique ID.
     *                     <p>1 : User</p> 
     *                     <p>2 : Reporter</p>
     *                     <p>3 : Owner</p> 
     *                     <p>4 : Admin</p>  
     * @apiError 400 Input Invalid. This will happen if the param is missing or not the valid format.
     * @apiError 404 Role not found. This will happen if the role id is not in our system.
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 403 permission denied. The operater does not have right to make changes.
     * @apiError 412 The user is not a member of this group yet. 
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "id": "4",
     *       "name": "Admin"
     *     }
     *
     */ 
    public static function setRole($idGroup, $idUser){
        $app = \Slim\Slim::getInstance();
        $request = $app->request->post();
        $validata = $app->validata;
        $validator = $validata::key('role', $validata::digit()->notEmpty())
                                ->key('idUser', $validata::digit()->notEmpty());
        if (!$validator->validate($request)){
            $app->halt("400",json_encode("Input Invalid"));
        }
        $role_id = $request['role'];
        if(!Role::where('id',$role_id)->where('id','<=',4)->count()){
            $app->halt("404",json_encode("role does not exist"));
        }
        self::isAdmin($idUser);
        if(!self::isMember($idGroup,$request['idUser'])){
            $app->halt("412",json_encode("You don't have the permission to modify this user."));
        }
        if($idUser==$request['idUser']){
            //Admin/Owner cannot change his own role
            $app->halt("403",json_encode("permission denied"));
        }
        
        $role = self::getRole($idGroup,$idUser);
        if($role->id!=3&&$role_id==3){
            //outside user cannot set owner role
            $app->halt("403",json_encode("permission denied"));
        }
        $user_role = self::getRole($idGroup,$request['idUser']);
        if($user_role->id>=3&&$role->id!=3){
            //User cannot change a user's role if that user has same or higher role
            $app->halt("403",json_encode("permission denied"));
        }
        if($role->id!=3&&$role_id==4){
            //User cannot assign user as admin if not owner
            $app->halt("403",json_encode("permission denied"));
        }
        Group::find($idGroup)->members()->detach([$request['idUser']]);
        Group::find($idGroup)->members()->attach([$request['idUser']=>['role_id'=>$role_id]]);
        
        if($role->id==3&&$role_id==3){
            Group::find($idGroup)->members()->detach([$idUser]);
            Group::find($idGroup)->members()->attach([$idUser=>['role_id'=>4]]);
        }
        return $role = self::getRole($idGroup,$request['idUser']);
    }

    private static function isMember($idGroup, $idUser){
        $app = \Slim\Slim::getInstance();
        UserController::findUser($idUser);
        $group = self::getGroupInfo($idGroup);
        return Group::find($idGroup)->members->contains($idUser);
    }

    /**
     * @api {post} /groups/:id/users/:id Update Group Info
     * @apiName Update Group Info
     * @apiGroup Group
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam  (url Parameter) {Number} id Group unique ID.
     * @apiParam  (url Parameter) {Number} id Users unique ID. The operator's id, usually the admin of the group.
     * @apiParam  {String} [access_code] Group's new access code.
     * @apiParam  {String} [name] Group's new name.
     * @apiParam  {String} [description] Group's new description.
     * 
     * @apiError 400 Input Invalid. This will happen if the param is missing or not the valid format.
     * @apiError 404 Not found. This will happen if the role id/user id/group id is not in our system.
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 403 permission denied. The operater does not have right to make changes.
     * @apiError 409 Access code is occupied. 
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "id": 1,
     *       "parent_id":0,
     *       "name": "new course here",
     *       "description": "new description here",
     *       "access_code": "new access code here"
     *     }
     */   
    public static function updateGroupInfo($idGroup,$idUser){   
        $app = \Slim\Slim::getInstance();
        $role = self::getRole($idGroup,$idUser);
        if($role->id<3){
            $app->halt("401",json_encode("permission denied"));
        }
        $data = $app->request->post();

        $validata = $app->validata;
        $rules = array(
            'access_code'=> $validata::alnum()->notEmpty()->length(1,10),
        	'name'=> $validata::stringType()->notEmpty(),
            'description'=> $validata::stringType()->notEmpty(),
        );
        foreach ($data as $key => $value) {
            if(array_key_exists($key, $rules)){
                if(!$rules[$key]->validate($value)){
                    $app->halt("400",json_encode($key." has error."));
                }
            }else{
                unset($data[$key]);
            }
        }
        if(!$data || empty($data)){
            $app->halt("400",json_encode("Invaild Input"));
        }
        if(isset($data['name'])){
            if(Group::where('name','=',$data['name'])->first()){
                $app->halt("409",json_encode("Group name must be unique."));
            }
        }
        if(isset($data['access_code'])){
        	if(Group::where('access_code','=',$data['access_code'])->first()){
        		$app->halt("409",json_encode("Access code must be unique."));
        	}
        }
        try {
            $effectRow = Group::where('id','=',$idGroup)->update($data);
            return self::getGroupInfo($idGroup);
        } catch (Exception $e) {
            $app->halt("500",json_encode($e->getMessage()));
        }
    }

    /**
     * @api {get} /groups/:id Get Group Info
     * @apiName Get Group Info
     * @apiGroup Group
     * @apiDescription Return basic group infomation<br>
     *          <b>Note:</b>
     *          <li>parent_id equals 0 means this is a company group.</li>
     * @apiError 404 Group not found.
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "id": 1,
     *       "parent_id":0,
     *       "name": "General",
     *       "description": "description here",
     *       "access_code": "GENERAL"
     *     }
     */   
    public static function getGroupInfo($idGroup){
        $app = \Slim\Slim::getInstance();   
        $group = Group::find($idGroup);
        if(!$group){
            $app->halt("404",json_encode("Group not found."));
        }
        return $group;
    }

    /**
     * @api {get} /groups/:idGroup/users/:idUser/member Get Group Members by group Admin/Owner
     * @apiName Get Group Members
     * @apiGroup Group
     * @apiDescription Return a collection of users within the group specified by group id.
     *                 <p>Only Admin/Owner of the specified group can use this function.<br>
     *                    Return users with role of this group. 
     *                 </p>                   
     * 
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam idGroup Group id
     * @apiParam idUser Admin/Owner's id of the specified group.
     * 
     * @apiError 404 Not found. This will happen if the role id/user id/group id is not in our system.
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 403 permission denied. The operater does not have right to make changes.
     * 
     * @apiSuccessExample {json} Success-Response:
     *   HTTP/1.1 200 OK
     *   [
     *    {
     *        "id": 5,
     *        "email": "test@test.com",
     *        "first_name": "SpongeBob",
     *        "last_name": "asdfadfds",
     *        "phone": "123243215435",
     *        "theme": "default",
     *        "active": 1,
     *        "address": "123 Test St",
     *        "city": "Schaumburg",
     *        "province": "Illinois",
     *        "country": "US",
     *        "zip_code": "111",
     *        "last_login": "2015-11-03 11:34:02",
     *        "updated_at": "2015-11-03 11:38:27",
     *        "created_at": "2015-06-12 16:49:08",
     *        "role_id": 1,
     *        "role": "User",
     *        "avatar": "http://localhost:9090/ignitor-api/users/avatar/5"
     *    }
     *   ]
     */   
    public static function getGroupMember($idGroup,$idUser){
        $app = \Slim\Slim::getInstance();
        $role = self::getRole($idGroup,$idUser);
        if($role->id<3){
            $app->halt("403",json_encode("permission denied"));
        }
        $members = Group::find($idGroup)->members;
        foreach ($members as $key => &$value) {
            $value['role_id'] = $value->pivot->role_id;
            $value['role'] = Role::find($value->pivot->role_id)->name;
            unset($value->pivot);
            unset($value->password);
        }
        return $members;
    }

    /**
     * @api {post} /groups/:code/users/:idUser/add Join Group by Code
     * @apiName Join Group by Code
     * @apiGroup Group
     * @apiDescription Join Group by Code
     * 
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam code Group Access Code
     * @apiParam idUser 
     * 
     * @apiError 400 Not found. This will happen if the code is not in our system.
     * @apiError 412 You must leave the exclusive group before joining this new group.
     * 
     */
    public static function attachUserByCode($code,$idUser){
        $app = \Slim\Slim::getInstance();
        UserController::findUser($idUser);
        $group = Group::where('access_code','=',$code)->first();
        if(!$group){
            $app->halt("400",json_encode("Code invalid."));
        }
        self::attachUser($group->id,$idUser);
    }
    public static function activeEnroll($idUser){
        $companies = User::find($idUser)->companies;
        foreach ($companies as $key => $company) {
            $group = Group::where('company_id','=',$company->id)->first();
            self::attachUser($group->id,$idUser);
        }
    }
    /**
    * attach Memember
    */   
    public static function attachUser($idGroup,$idUser){
        UserController::findUser($idUser);   
        $group = self::getGroupInfo($idGroup);
        
        //put the restriction here
        //1. user can join none exclusive group without any restriction
        //2. if join child group, do the same company restriction check 
        if(self::isNoneExclusive($idGroup)){
            if(!$group->members->contains($idUser)){
                $group->members()->attach($idUser,array('role_id'=>1));
            }   
        } else {
            self::sameCompanyRestriction($idGroup,$idUser);
            if(!$group->members->contains($idUser)){
                $role_id = 1;
                if($group->members->count()==0){
                    $role_id = 3;
                }
                $group->members()->attach($idUser,array('role_id'=>$role_id));
            }   
        }
    }

    /**
     * @api {delete} /groups/:id/users/:id/delete Leave Group by user self
     * @apiName Delete from Group
     * @apiGroup Group
     * @apiDescription This api allow user leave group by him self.<br>
     *                  <ul>
     *                  <li>Nobody can leave unaffiliated company by himself.</li>
     *                  <li>Owner cannot leave group if there is more than one member in this group.</li>
     *                  <li>Owner cannot leave group if there is any child group of this group.</li>
     *                  <li>Admin/Owner cannot leave group if there is any content in his bin and he is going to lose the manager role if he left.</li>
     *                  </ul>
     *                  <p>If user leaves company group, auto leave this user from the company, and enroll user to Unaffilited Company.<br>
     *                     The group will be removed if the user is the last person in this group.<br>
     *                     The company will be removed if the company group is removed.
     *                  </p>
     * 
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     *
     * @apiSuccess (200) {json} SuccessDelete
     * @apiError 404 Not found. This will happen if the role id/user id/group id is not in our system.
     * @apiError 401 UserNotAuthorized
     * @apiError 403 Permission denied.
     * @apiError 412 There is unassigned content in your bin.  The content in your bin must be assigned, or transferred to another manager user within the Company group, before you can leave the group.
     *   
     */    
    public static function detachUser($idGroup,$idUser){
        $app = \Slim\Slim::getInstance();   
        $user = UserController::findUser($idUser);   
        $group = self::getGroupInfo($idGroup);
        $role = self::getRole($idGroup,$idUser);
        if($idGroup==0){
            //nobody can leave unaffiliated company by themselves
            $app->halt("403",json_encode("permission denied"));
        }
        $childGroups = Group::where('parent_id',$idGroup)->get();
        if($role->id==3 && count($childGroups)>0){
            $app->halt('409',json_encode('Please transfer your ownership out before you leave.'));
        }
        if($role->id>=3){
            $contents= ManagerController::getBin($idUser);
            if(count($contents)>0){
                //check if user will lose manager role if leave
                if($user->groups()->where('role_id','>=',3)->where('group_id','!=',$idGroup)->count()==0){
                    $app->halt("412",json_encode("You have unassigned contents."));
                }
            }
        }
        $members = Group::find($idGroup)->members;
        if(count($members)==1 && $members[0]->id == $idUser){
            //last member of the group
            if($group->members->contains($idUser)){
                $group->members()->detach($idUser);
            }
            self::removeGroup($idGroup);
            if($group->parent_id==0){
                self::addToUnaffilitedCompany($idUser);
            }
        } else if($role->id==3){
            //owner cannot leave group by himself
            $app->halt("403",json_encode("permission denied"));
        } else {
            if($group->members->contains($idUser)){
                $group->members()->detach($idUser);
                if($group->parent_id==0){
                    self::addToUnaffilitedCompany($idUser);
                }
            }
        }
    }
    public static function addToUnaffilitedCompany($idUser){
        $user = User::find($idUser);
        $user->companies()->sync([0]);
        self::attachUser(0,$idUser);
    }
    public static function removeGroup($idGroup){
        $app = \Slim\Slim::getInstance();   
        $group = self::getGroupInfo($idGroup);
        if($group->parent_id==0){
            $company = Company::find($group->company_id);
            $company->delete();
        }
        $group->delete();
    }
    public static function isChildGroup($idGroup){
        $group = self::getGroupInfo($idGroup);
        if($group->parent_id!=0 && $group->is_exclusive==0){
            return true;
        }
        return false;
    }
    public static function isNoneExclusive($idGroup){
        $group = self::getGroupInfo($idGroup);
        if($group->parent_id==0 && $group->is_exclusive==0){
            return true;
        }
        return false;
    }
    public static function isCompanyGroup($idGroup){
        $group = self::getGroupInfo($idGroup);
        if($group->parent_id==0 && $group->is_exclusive==1){
            return true;
        }
        return false;
    }

    /**
     * @api {post} /groups/:id/users/:id/remove Remove User from Group
     * @apiName Delete from Group by Admin
     * @apiGroup Group
     * @apiDescription Remove user from Group by group Admin or Owner.<br>
     *                 <ul>
     *                  <li>Only Owner can remove Admin.</li>
     *                  <li>Owner cannot remove owner(himself). Owner has to use leave group function.</li>
     *                  <li>Admin cannot be removed if he has content in his bin.</li>
     *                 </ul>
     *                 <p>Upcoming features: If remove user from company group? If user is owner of child group?</p>
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam  (url Parameter) {Number} idGroup Group unique ID.
     * @apiParam  (url Parameter) {Number} id Users unique ID. The operator's id, usually the admin of the group.
     * @apiParam  {Number} idUser User's unique ID. The change will apply to this user.
     * 
     * @apiError 400 Input Invalid. This will happen if the param is missing or not the valid format.
     * @apiError 404 Not found. This will happen if the role id/user id/group id is not in our system.
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 403 permission denied. The operater does not have right to make changes.
     * @apiError 412 Precondition. There is unassigned content in your bin.  The content in your bin must be assigned, or transferred to another manager user within the Company group, before you can leave the group. 
     */    
    public static function removeUser($idGroup,$idUser){
        $app = \Slim\Slim::getInstance();
        $request = $app->request->post();
        $validata = $app->validata;
        $validator = $validata::key('idUser', $validata::digit()->notEmpty());
        if (!$validator->validate($request)) {
            $app->halt("400",json_encode("Input Invalid"));
        }
        $role = self::getRole($idGroup,$idUser);
        if($role->id<3){
            $app->halt("403",json_encode("permission denied"));
        }
        if(!self::isMember($idGroup,$request['idUser'])){
            $app->halt("403",json_encode("user not enrolled yet"));
        }
        $user_role = self::getRole($idGroup,$request['idUser']);
        if($user_role->id>=3&&$role->id!=3){
            $app->halt("403",json_encode("permission denied"));
        }
        if($user_role->id==3){
            //outside user cannot remove owner
            $app->halt("403",json_encode("permission denied"));
        }
        if($user_role->id>=3){
            $contents= ManagerController::getBin($request['idUser']);
            if(count($contents)>0){
                $app->halt("412",json_encode("You have unassigned contents."));
            }
        }   
        Group::find($idGroup)->members()->detach($request['idUser']);
    }

    /**
     * @api {post} /groups/:idGroup/users/:id/invite Invite User
     * @apiName Invite User by Admin
     * @apiGroup Group
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam  (url Parameter) {Number} idGroup Group unique ID.
     * @apiParam  (url Parameter) {Number} id Users unique ID. The operator's id, usually the admin of the group.
     * @apiParam  {String} email User's email.
     * 
     * @apiError 400 Input Invalid. This will happen if the param is missing or not the valid format.
     * @apiError 404 Not found. This will happen if the role id/user id/group id is not in our system.
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 409 The User already enrolled to this group. 
     * @apiError 412 The User are not in the same company as the manager.
     * 
     * @apiSuccess 200 New Invitation is created and email sent out.
     * @apiSuccess 203 The Invitation to this group already exist. No new recored created, no email send out.
     */    
    public static function inviteUser($idGroup,$idUser){
        $app = \Slim\Slim::getInstance();
        $request = $app->request->post();
        $validata = $app->validata;
        $validator = $validata::key('email', $validata::email()->notEmpty());
        if (!$validator->validate($request)) {
            $app->halt("400",json_encode("Input Invalid"));
        }
        $role = self::getRole($idGroup,$idUser);
        if($role->id<3){
            $app->halt("403",json_encode("permission denied"));
        }
        $user = User::where('email','=',$request['email'])->first();
        if(!$user){
            $app->halt("404",json_encode("User not found."));
        }
        if(self::isMember($idGroup,$user->id)){
            $app->halt("409",json_encode("User already in group."));
        }
        //self::sameCompanyRestriction($idGroup,$user->id);
        $invitation = Invitation::where('receiver_id','=',$user->id)->where('group_id','=',$idGroup)->first();
        if($invitation){
            $invitation->invited_at = date('Y-m-d H:i:s');
            $invitation->group_jointed_at = null;
            $invitation->save();
            $app->halt("202",json_encode("This user already has the same group invitation in record."));
        }
        User::find($user->id)->invitations()->create([
            'sender_id'=>$idUser,
            'group_id'=>$idGroup,
            'invited_at'=>date('Y-m-d H:i:s')
        ]);
        EmailController::noticificationReminder($user->id);
    }
    public static function sameCompanyRestriction($idGroup,$idUser){
        $app = \Slim\Slim::getInstance();
        $user = User::find($idUser);
        $companys = $user->companies;
        $companys_id = [];
        foreach ($companys as $key => $company) {
            array_push($companys_id, $company->id);
        }
        if( !in_array(Group::find($idGroup)->company_id,$companys_id )){
            $app->halt("412",json_encode("Only same company invitation can be send out."));
        }
    }
    /**
     * @api {get} /invitations/:id/users/:idUser/accept Accept group invitation
     * @apiName Accept group invitation
     * @apiGroup Invitation
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam  (url Parameter) {Number} id Invitation unique ID.
     * @apiParam  (url Parameter) {Number} idUser Users unique ID.
     * 
     * @apiError 404 Not found. This will happen if the role id/user id/group id is not in our system.
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 409 The link expired.  
     * @apiError 412 You must leave the exclusive group before joining this new group.
     */ 
    public static function acceptInvite($id,$idUser){
        $app = \Slim\Slim::getInstance();
        $invitation = Invitation::find($id);
        if(!$invitation){
            $app->halt('404');
        }
        if($invitation->receiver_id!=$idUser){
            $app->halt('401');
        }
        if($invitation->group_jointed_at){
            $app->halt('409',json_encode('expired'));
        }
        self::attachUser($invitation->group_id,$invitation->receiver_id);
        $invitation->update(['group_jointed_at'=>date('Y-m-d H:i:s')]);
    }
    /**
     * @api {delete} /invitations/:id/users/:idUser/decline Decline group invitation
     * @apiName Decline group invitation
     * @apiGroup Invitation
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam  (url Parameter) {Number} id Invitation unique ID.
     * @apiParam  (url Parameter) {Number} idUser Users unique ID.
     * 
     * @apiError 404 Not found. This will happen if the role id/user id/group id is not in our system.
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 409 The link expired.  
     */ 
    public static function declineInvite($id,$idUser){
        $app = \Slim\Slim::getInstance();
        $invitation = Invitation::find($id);
        if(!$invitation){
            $app->halt('404');
        }
        if($invitation->receiver_id!=$idUser){
            $app->halt('401');
        }
        if($invitation->group_jointed_at){
            $app->halt('409',json_encode('expired'));
        }
        //self::attachUser($invitation->group_id,$invitation->receiver_id);
        $invitation->delete();
    }

    /**
     * @api {post} /groups/users/:idUser/add Add New Group by Manager
     * @apiName Add New Group by Manager
     * @apiGroup Group
     * @apiDescription Create a child group of the company the specified user is in.
     *                 <p>The user must be admin/owner of his company group.</p>
     *                 <p>The user will be the owner of the new group.</p>
     * @apiHeader (Header) {String} X_Authorization Authorization value.
     * @apiParam  (url Parameter) {Number} idUser User's unique ID.
     * @apiParam  (Parameter) {String} name New group name. <b>Unique</b>
     * @apiParam  (Parameter) {String} description New group description.
     *
     * @apiError 400 Input Invalid. This will happen if the param is missing or not in the valid format.
     * @apiError 404 Not found. This will happen if the role id/user id/group id is not in our system.
     * @apiError 403 Permission denied. 
     * @apiError 401 Not authorized. This will happen if the header value is not attached.
     * @apiError 409 Group name already exists. 
     *
     * @apiSuccess 200 
     */ 
    public static function add($idUser){
        $app = \Slim\Slim::getInstance();
        $data = $app->request->post();
        //validate input
        $validata = $app->validata;
        $validator = $validata::key('name', $validata::stringType()->notEmpty())
                                ->key('description', $validata::stringType()->notEmpty());
        if (!$validator->validate((array)$data)){
            $app->halt(400,json_encode("Input Invalid"));
        }
        $group = Group::where('name',$data['name'])->first();
        if($group){
            $app->halt(409,json_encode("Group name already exists"));
        }
        $companies = User::find($idUser)->companies;
        $parent_group = CompanyController::getCompanyGroup($companies[0]->id);
        if(!$parent_group){
            $app->halt(404,json_encode("Parent group does not exist"));
        }
        if($companies){
            $role = self::getRole($parent_group->id,$idUser);
            if($role->id<3){
                $app->halt("403",json_encode("Permission denied"));
            }
            $group = new Group;
            $group->company_id = $companies[0]->id;
            $group->is_exclusive = 0;
            $group->parent_id = $parent_group->id;
            $group->name = $data['name'];
            $group->description = $data['description'];
            $group->access_code = CompanyController::generateRandomString(8);
            $group->save();
            if($group->id){
                $group->members()->attach($idUser,array('role_id'=>3));
            }
        }
    }
}
?>
