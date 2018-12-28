<?php

/**
 * Ethereum API wrapper.
 */
class yf_wrapper_ethereum
{
    public $host = '127.0.0.1';
    public $port = 8545;
    public $version = '2.0';

    protected $id = 0;

    public function _format_response($response)
    {
        return @json_decode($response);
    }

    public function web3_clientVersion()
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function web3_sha3($input)
    {
        return $this->_ether_request(__FUNCTION__, [$input]);
    }

    public function net_version()
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function net_listening()
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function net_peerCount()
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function eth_protocolVersion()
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function eth_coinbase()
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function eth_mining()
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function eth_hashrate()
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function eth_gasPrice()
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function eth_accounts()
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function eth_blockNumber($decode_hex = false)
    {
        $block = $this->_ether_request(__FUNCTION__);

        if ($decode_hex) {
            $block = $this->_decode_hex($block);
        }

        return $block;
    }

    public function eth_getBalance($address, $block = 'latest', $decode_hex = false)
    {
        $balance = $this->_ether_request(__FUNCTION__, [$address, $block]);

        if ($decode_hex) {
            $balance = $this->_decode_hex($balance);
        }

        return $balance;
    }

    public function eth_getStorageAt($address, $at, $block = 'latest')
    {
        return $this->_ether_request(__FUNCTION__, [$address, $at, $block]);
    }

    public function eth_getTransactionCount($address, $block = 'latest', $decode_hex = false)
    {
        $count = $this->_ether_request(__FUNCTION__, [$address, $block]);

        if ($decode_hex) {
            $count = $this->_decode_hex($count);
        }

        return $count;
    }

    public function eth_getBlockTransactionCountByHash($tx_hash)
    {
        return $this->_ether_request(__FUNCTION__, [$tx_hash]);
    }

    public function eth_getBlockTransactionCountByNumber($tx = 'latest')
    {
        return $this->_ether_request(__FUNCTION__, [$tx]);
    }

    public function eth_getUncleCountByBlockHash($block_hash)
    {
        return $this->_ether_request(__FUNCTION__, [$block_hash]);
    }

    public function eth_getUncleCountByBlockNumber($block = 'latest')
    {
        return $this->_ether_request(__FUNCTION__, [$block]);
    }

    public function eth_getCode($address, $block = 'latest')
    {
        return $this->_ether_request(__FUNCTION__, [$address, $block]);
    }

    public function eth_sign($address, $input)
    {
        return $this->_ether_request(__FUNCTION__, [$address, $input]);
    }

    public function eth_sendTransaction($transaction)
    {
        return $this->_ether_request(__FUNCTION__, $transaction);
    }

    public function eth_call($message, $block)
    {
        return $this->_ether_request(__FUNCTION__, $message);
    }

    public function eth_estimateGas($message, $block)
    {
        return $this->_ether_request(__FUNCTION__, $message);
    }

    public function eth_getBlockByHash($hash, $full_tx = true)
    {
        return $this->_ether_request(__FUNCTION__, [$hash, $full_tx]);
    }

    public function eth_getBlockByNumber($block = 'latest', $full_tx = true)
    {
        return $this->_ether_request(__FUNCTION__, [$block, $full_tx]);
    }

    public function eth_getTransactionByHash($hash)
    {
        return $this->_ether_request(__FUNCTION__, [$hash]);
    }

    public function eth_getTransactionByBlockHashAndIndex($hash, $index)
    {
        return $this->_ether_request(__FUNCTION__, [$hash, $index]);
    }

    public function eth_getTransactionByBlockNumberAndIndex($block, $index)
    {
        return $this->_ether_request(__FUNCTION__, [$block, $index]);
    }

    public function eth_getTransactionReceipt($tx_hash)
    {
        return $this->_ether_request(__FUNCTION__, [$tx_hash]);
    }

    public function eth_getUncleByBlockHashAndIndex($hash, $index)
    {
        return $this->_ether_request(__FUNCTION__, [$hash, $index]);
    }

    public function eth_getUncleByBlockNumberAndIndex($block, $index)
    {
        return $this->_ether_request(__FUNCTION__, [$block, $index]);
    }

    public function eth_getCompilers()
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function eth_compileSolidity($code)
    {
        return $this->_ether_request(__FUNCTION__, [$code]);
    }

    public function eth_compileLLL($code)
    {
        return $this->_ether_request(__FUNCTION__, [$code]);
    }

    public function eth_compileSerpent($code)
    {
        return $this->_ether_request(__FUNCTION__, [$code]);
    }

    public function eth_newFilter($filter, $decode_hex = false)
    {
        $id = $this->_ether_request(__FUNCTION__, $filter);

        if ($decode_hex) {
            $id = $this->_decode_hex($id);
        }

        return $id;
    }

    public function eth_newBlockFilter($decode_hex = false)
    {
        $id = $this->_ether_request(__FUNCTION__);

        if ($decode_hex) {
            $id = $this->_decode_hex($id);
        }

        return $id;
    }

    public function eth_newPendingTransactionFilter($decode_hex = false)
    {
        $id = $this->_ether_request(__FUNCTION__);

        if ($decode_hex) {
            $id = $this->_decode_hex($id);
        }

        return $id;
    }

    public function eth_uninstallFilter($id)
    {
        return $this->_ether_request(__FUNCTION__, [$id]);
    }

    public function eth_getFilterChanges($id)
    {
        return $this->_ether_request(__FUNCTION__, [$id]);
    }

    public function eth_getFilterLogs($id)
    {
        return $this->_ether_request(__FUNCTION__, [$id]);
    }

    public function eth_getLogs($filter)
    {
        return $this->_ether_request(__FUNCTION__, $filter);
    }

    public function eth_getWork()
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function eth_submitWork($nonce, $pow_hash, $mix_digest)
    {
        return $this->_ether_request(__FUNCTION__, [$nonce, $pow_hash, $mix_digest]);
    }

    public function db_putString($db, $key, $value)
    {
        return $this->_ether_request(__FUNCTION__, [$db, $key, $value]);
    }

    public function db_getString($db, $key)
    {
        return $this->_ether_request(__FUNCTION__, [$db, $key]);
    }

    public function db_putHex($db, $key, $value)
    {
        return $this->_ether_request(__FUNCTION__, [$db, $key, $value]);
    }

    public function db_getHex($db, $key)
    {
        return $this->_ether_request(__FUNCTION__, [$db, $key]);
    }

    public function shh_version()
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function shh_post($post)
    {
        return $this->_ether_request(__FUNCTION__, $post);
    }

    public function shh_newIdentinty()
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function shh_hasIdentity($id)
    {
        return $this->_ether_request(__FUNCTION__);
    }

    public function shh_newFilter($to = null, $topics = [])
    {
        return $this->_ether_request(__FUNCTION__, [['to' => $to, 'topics' => $topics]]);
    }

    public function shh_uninstallFilter($id)
    {
        return $this->_ether_request(__FUNCTION__, [$id]);
    }

    public function shh_getFilterChanges($id)
    {
        return $this->_ether_request(__FUNCTION__, [$id]);
    }

    public function shh_getMessages($id)
    {
        return $this->_ether_request(__FUNCTION__, [$id]);
    }

    private function _request($method, $params = [])
    {
        $data = [];
        $data['jsonrpc'] = $this->version;
        $data['id'] = $this->id++;
        $data['method'] = $method;
        $data['params'] = $params;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->host);
        curl_setopt($ch, CURLOPT_PORT, $this->port);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $ret = curl_exec($ch);

        if ($ret !== false) {
            $formatted = $this->_format_response($ret);

            if (isset($formatted->error)) {
                throw new Exception($formatted->error->code . ' : ' . $formatted->error->message);
            }
            return $formatted;
        }
        throw new Exception('Server did not respond');
    }

    private function _ether_request($method, $params = [])
    {
        try {
            $ret = $this->_request($method, $params);
            return $ret->result;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function _decode_hex($input)
    {
        if (substr($input, 0, 2) == '0x') {
            $input = substr($input, 2);
        }
        if (preg_match('/[a-f0-9]+/', $input)) {
            return hexdec($input);
        }
        return $input;
    }
}
