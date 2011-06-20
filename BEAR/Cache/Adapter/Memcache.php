<?php
/**
 * BEAR
 *
 * PHP versions 5
 *
 * @category   BEAR
 * @package    BEAR_Cache
 * @subpackage Adapter
 * @author     Akihito Koriyama <koriyama@bear-project.net>
 * @copyright  2008-2011 Akihito Koriyama All rights reserved.
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @version    SVN: Release: @package_version@ $Id: Memcache.php 2486 2011-06-06 07:44:05Z koriyama@bear-project.net $
 * @link      http://www.bear-project.net/
 */

/**
 * Memcacheアダプター
 *
 * @category   BEAR
 * @package    BEAR_Cache
 * @subpackage Adapter
 * @author     Akihito Koriyama <koriyama@bear-project.net>
 * @copyright  2008-2011 Akihito Koriyama All rights reserved.
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @version    Release: @package_version@ $Id: Memcache.php 2486 2011-06-06 07:44:05Z koriyama@bear-project.net $
 * @link       http://www.bear-project.net
 *
 * @Singleton
 *
 * @config int BEAR_Cache_Adapter_Memcache memcacheポート番号
 */
class BEAR_Cache_Adapter_Memcache extends BEAR_Cache_Adapter
{
    /**
     * Constructor
     *
     * @param array $config
     *
     * @see http://jp.php.net/manual/ja/function.memcache-addserver.php
     * @throws BEAR_Cache_Exception
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        if (!extension_loaded('memcache')) {
            throw BEAR_Cache_Exception('Memcached extention is not loaded');
        }
        $this->_adapter = new Memcache();
        //キャッシュサーバー追加
        if (isset($this->_config['path'])) {
            $this->_adapter->connect($this->_config['path']);
        }
        $log = array();
        if ($this->_config['debug'] && isset($this->_config['path'])) {
            $log['Ver'] = $this->_adapter->getVersion();
            BEAR::dependency('BEAR_Log')->log('Memcache', $log);
        }
        return $this->_adapter;
    }

    /**
     * キャッシュを保存
     *
     * @param string $key   キャッシュキー
     * @param mixed  $value 値
     *
     * @return bool
     */
    public function set($key, $value)
    {
        $result = $this->_adapter->replace($this->_config['prefix'] . $key, $value, MEMCACHE_COMPRESSED, $this->_life);
        if (!$result) {
            $result = $this->_adapter->set($this->_config['prefix'] . $key, $value, MEMCACHE_COMPRESSED, $this->_life);
        }
        $this->_log->log('Memcache[W]', array('key' => $key, 'result' => $result));
        return $result;
    }

    /**
     * キャッシュを取得
     *
     * キーを基にキャッシュデータを取得します
     *
     * @param string $key     キー
     * @param mixed  $default 値
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $result = $this->_adapter->get($this->_config['prefix'] . $key);
        if (!$result) {
            $result = $default;
        }
        if ($result) {
            $this->_log->log('Memcache[R]', $key);
        }
        return $result;
    }

    /**
     * キャッシュの削除
     *
     * @param string $key キー
     *
     * @return bool
     */
    public function delete($key)
    {
        $result = $this->_adapter->delete($this->_config['prefix'] . $key);
        $this->_log->log('Memcache[D]', $key);
        return $result;
    }

    /**
     * キャッシュの全削除
     *
     * @return bool
     */
    public function deleteAll()
    {
        return $this->_adapter->flush();
    }

    /**
     * キャッシュ生存時間を決める
     *
     * <pre>
     * 無制限にしたいときはは0ではなくnullです。
     * ０を指定すると最小キャッシュ時間がセットされます。
     * これはCache_Liteとインターフェイスを合わせるためです。
     * </pre>
     *
     * @param mixed $life 秒 nullで無期限
     *
     * @return BEAR_Cache_Adapter_Memcache
     */
    public function setLife($life = null)
    {
        if ($life == null) {
            $life = 0;
        } elseif ($life == 0) {
            $life = 1;
        }
        $this->_life = $life;
        return $this;
    }
}