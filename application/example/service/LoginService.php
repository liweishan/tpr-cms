<?php
/**
 * @author: Axios
 *
 * @email: axioscros@aliyun.com
 * @blog:  http://hanxv.cn
 * @datetime: 2017/4/26 10:46
 */
namespace app\example\service;

use axios\tpr\service\ToolService;
use axios\tpr\service\UserService;
use app\example\model\UserModel;

class LoginService {
    public static function login($user){
        $token = ToolService::token($user['login_name']);
        $time = time();
        $ip = get_client_ip();
        $data = [
            'token'=>$token,
            'last_login_ip'=>$ip,
            'last_login_time'=>$time
        ];
        if(UserModel::self()->updateUser($data,$user['id'])){
            return self::doLogin($user['id'],$token);
        }
        return 500;
    }

    public static function doLogin($user_id,$token){
        $user = UserModel::self()->findUser($user_id,'',"id , login_name  , nickname  , token,sex,mobile");
        UserService::setToken($token,$user);
        unset($user['id']);
        return $user;
    }
}