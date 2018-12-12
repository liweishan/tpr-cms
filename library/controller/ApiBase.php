<?php
/**
 * @author  : Axios
 * @email   : axioscros@aliyun.com
 * @blog    :  http://hanxv.cn
 * @datetime: 2017/7/26 12:48
 */

namespace library\controller;

use library\logic\Crypt;
use library\connector\Mysql;
use think\facade\Config;
use think\facade\Log;


class ApiBase extends BaseController
{

    protected static $rsa = PROJECT_NAME;

    protected static $debug;

    protected static $app_key, $timestamp, $sign, $app_status, $app_secret;

    /**
     * ApiBase constructor.
     * @throws \ErrorException
     * @throws \tpr\db\exception\BindParamException
     * @throws \tpr\db\exception\Exception
     * @throws \tpr\db\exception\PDOException
     */
    public function __construct()
    {
        parent::__construct();

        self::$debug = Config::get('app_debug');

        self::$timestamp = $this->request->header('x-timestamp', 0);

        self::$sign = $this->request->header('x-sign', '');

        self::$app_key = $this->request->header('x-app-key', '');

        $this->checkSign();

        $this->checkAppKey();
    }

    /**
     * 检验app_key的有效性
     * @throws \ErrorException
     * @throws \tpr\db\exception\BindParamException
     * @throws \tpr\db\exception\Exception
     * @throws \tpr\db\exception\PDOException
     */
    protected function checkAppKey()
    {
        $app_version = Mysql::name('app_version')->where('app_key', self::$app_key)->find();
        if (empty($app_version)) {
            $this->wrong(400, 'app_key not exist');
        }

        self::$app_status = data($app_version, 'app_status', 0);

        $app = Mysql::name('app')->where('app_id', $app_version['app_id'])->field('app_id, app_secret')->find();
        if (empty($app)) {
            $this->wrong(400, 'app not exist');
        }

        self::$app_secret = $app['app_secret'];
    }

    /**
     * 加密
     *
     * @param        $data
     * @param string $type 默认pri
     *
     * @return null|string
     */
    protected function encrypt($data, $type = 'pri')
    {
        $save = $data;
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $data = Crypt::rsa(self::$rsa)->encrypt($data, $type);
        return self::$debug ? $save : $data;
    }

    /**
     * 解密
     *
     * @param        $data
     * @param string $type
     *
     * @return mixed|string
     */
    protected function decrypt($data, $type = 'pri')
    {
        $save = $data;
        $data = Crypt::rsa(self::$rsa)->decrypt($data, $type);
        $tmp  = json_decode($data, true);
        if (!empty($tmp)) {
            $data = $tmp;
        }
        return self::$debug ? $save : $data;
    }

    /**
     * 验证数字签名
     * @return bool
     */
    public function checkSign()
    {
        if (self::$debug) {
            return false;
        }

        if (empty($this->timestamp) || empty($this->sign)) {
            $this->wrong(406, 'sign error');
        }

        if (time() - $this->timestamp > 30) {
            $this->wrong(406, 'sign timeout');
        }


        $sign_auth = $this->makeSign(self::$timestamp);

        if (self::$sign != $sign_auth) {
            Log::record(['t' => self::$timestamp, 's' => self::$sign, 'right' => $sign_auth], 'debug');
            $this->wrong(406, 'wrong sign');
        }
        return false;
    }

    /**
     * 生成数字签名
     *
     * @param $timestamp
     *
     * @return string
     */
    private function makeSign($timestamp)
    {
        return md5($timestamp);
    }
}