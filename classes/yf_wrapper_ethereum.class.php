<?php

/**
* Ethereum API wrapper
*/
class yf_wrapper_ethereum {

    public $host   = '127.0.0.1';
	public $port   = 8545;
    public $version = '2.0';
    
	protected $id = 0;
	
	private function _request($method, $params = []) {
		$data = [];
		$data['jsonrpc'] = $this->version;
		$data['id'] = $this->id++;
		$data['method'] = $method;
		$data['params'] = $params;
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $this->host);
		curl_setopt($ch, CURLOPT_PORT, $this->port);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); 
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		
		$ret = curl_exec($ch);
		
		if($ret !== FALSE) {
			$formatted = $this->_format_response($ret);
			
			if(isset($formatted->error)) {
				throw new Exception($formatted->error->message, $formatted->error->code);
			} else {
				return $formatted;
			}
		} else {
			throw new Exception("Server did not respond");
		}
	}
	
	function _format_response($response) {
		return @json_decode($response);
	}
    
	private function _ether_request($method, $params = []) {
		try {
			$ret = $this->_request($method, $params);
			return $ret->result;
		} catch(Exception $e) {
			throw $e;
		}
	}
	
	private function _decode_hex($input) {
		if(substr($input, 0, 2) == '0x')
			$input = substr($input, 2);
		if(preg_match('/[a-f0-9]+/', $input))
			return hexdec($input);
		return $input;
	}
	
	function web3_clientVersion() {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function web3_sha3($input) {
		return $this->_ether_request(__FUNCTION__, array($input));
	}
	
	function net_version() {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function net_listening() {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function net_peerCount() {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function eth_protocolVersion() {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function eth_coinbase() {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function eth_mining() {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function eth_hashrate() {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function eth_gasPrice() {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function eth_accounts() {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function eth_blockNumber($decode_hex=FALSE) {
		$block = $this->_ether_request(__FUNCTION__);
		
		if($decode_hex)
			$block = $this->_decode_hex($block);
		
		return $block;
	}
	
	function eth_getBalance($address, $block='latest', $decode_hex=FALSE) {
		$balance = $this->_ether_request(__FUNCTION__, array($address, $block));
		
		if($decode_hex)
			$balance = $this->_decode_hex($balance);
		
		return $balance;
	}
	
	function eth_getStorageAt($address, $at, $block='latest') {
		return $this->_ether_request(__FUNCTION__, array($address, $at, $block));
	}
	
	function eth_getTransactionCount($address, $block='latest', $decode_hex=FALSE) {
		$count = $this->_ether_request(__FUNCTION__, array($address, $block));
        
        if($decode_hex)
            $count = $this->_decode_hex($count);
            
        return $count;   
	}
	
	function eth_getBlockTransactionCountByHash($tx_hash) {
		return $this->_ether_request(__FUNCTION__, array($tx_hash));
	}
	
	function eth_getBlockTransactionCountByNumber($tx='latest') {
		return $this->_ether_request(__FUNCTION__, array($tx));
	}
	
	function eth_getUncleCountByBlockHash($block_hash) {
		return $this->_ether_request(__FUNCTION__, array($block_hash));
	}
	
	function eth_getUncleCountByBlockNumber($block='latest') {
		return $this->_ether_request(__FUNCTION__, array($block));
	}
	
	function eth_getCode($address, $block='latest') {
		return $this->_ether_request(__FUNCTION__, array($address, $block));
	}
	
	function eth_sign($address, $input) {
		return $this->_ether_request(__FUNCTION__, array($address, $input));
	}
	
	function eth_sendTransaction($transaction) {
        return $this->_ether_request(__FUNCTION__, $transaction);	
	}
	
	function eth_call($message, $block) {
        return $this->_ether_request(__FUNCTION__, $message);
	}
	
	function eth_estimateGas($message, $block) {
        return $this->_ether_request(__FUNCTION__, $message);
	}
	
	function eth_getBlockByHash($hash, $full_tx=TRUE) {
		return $this->_ether_request(__FUNCTION__, array($hash, $full_tx));
	}
	
	function eth_getBlockByNumber($block='latest', $full_tx=TRUE) {
		return $this->_ether_request(__FUNCTION__, array($block, $full_tx));
	}
	
	function eth_getTransactionByHash($hash) {
		return $this->_ether_request(__FUNCTION__, array($hash));
	}
	
	function eth_getTransactionByBlockHashAndIndex($hash, $index)
	{
		return $this->_ether_request(__FUNCTION__, array($hash, $index));
	}
	
	function eth_getTransactionByBlockNumberAndIndex($block, $index) {
		return $this->_ether_request(__FUNCTION__, array($block, $index));
	}
	
	function eth_getTransactionReceipt($tx_hash) {
		return $this->_ether_request(__FUNCTION__, array($tx_hash));
	}
	
	function eth_getUncleByBlockHashAndIndex($hash, $index) {
		return $this->_ether_request(__FUNCTION__, array($hash, $index));
	}
	
	function eth_getUncleByBlockNumberAndIndex($block, $index) {
		return $this->_ether_request(__FUNCTION__, array($block, $index));
	}
	
	function eth_getCompilers() {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function eth_compileSolidity($code) {
		return $this->_ether_request(__FUNCTION__, array($code));
	}
	
	function eth_compileLLL($code) {
		return $this->_ether_request(__FUNCTION__, array($code));
	}
	
	function eth_compileSerpent($code) {
		return $this->_ether_request(__FUNCTION__, array($code));
	}
	
	function eth_newFilter($filter, $decode_hex=FALSE) {
        $id = $this->_ether_request(__FUNCTION__, $filter);

        if($decode_hex)
            $id = $this->_decode_hex($id);

        return $id;
	}
	
	function eth_newBlockFilter($decode_hex=FALSE) {
		$id = $this->_ether_request(__FUNCTION__);
		
		if($decode_hex)
			$id = $this->_decode_hex($id);
		
		return $id;
	}
	
	function eth_newPendingTransactionFilter($decode_hex=FALSE) {
		$id = $this->_ether_request(__FUNCTION__);
		
		if($decode_hex)
			$id = $this->_decode_hex($id);
		
		return $id;
	}
	
	function eth_uninstallFilter($id) {
		return $this->_ether_request(__FUNCTION__, array($id));
	}
	
	function eth_getFilterChanges($id) {
		return $this->_ether_request(__FUNCTION__, array($id));
	}
	
	function eth_getFilterLogs($id) {
		return $this->_ether_request(__FUNCTION__, array($id));
	}
	
	function eth_getLogs($filter) {
        return $this->_ether_request(__FUNCTION__, $filter);
	}
	
	function eth_getWork() {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function eth_submitWork($nonce, $pow_hash, $mix_digest)
	{
		return $this->_ether_request(__FUNCTION__, array($nonce, $pow_hash, $mix_digest));
	}
	
	function db_putString($db, $key, $value) {
		return $this->_ether_request(__FUNCTION__, array($db, $key, $value));
	}
	
	function db_getString($db, $key) {
		return $this->_ether_request(__FUNCTION__, array($db, $key));
	}
	
	function db_putHex($db, $key, $value) {
		return $this->_ether_request(__FUNCTION__, array($db, $key, $value));
	}
	
	function db_getHex($db, $key) {
		return $this->_ether_request(__FUNCTION__, array($db, $key));
	}
	
	function shh_version() {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function shh_post($post) {
        return $this->_ether_request(__FUNCTION__, $post);
	}
	
	function shh_newIdentinty() {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function shh_hasIdentity($id) {
		return $this->_ether_request(__FUNCTION__);
	}
	
	function shh_newFilter($to=NULL, $topics=array()) {
		return $this->_ether_request(__FUNCTION__, [['to'=>$to, 'topics'=>$topics]]);
	}
	
	function shh_uninstallFilter($id) {
		return $this->_ether_request(__FUNCTION__, [$id]);
	}
	
	function shh_getFilterChanges($id) {
		return $this->_ether_request(__FUNCTION__, [$id]);
	}
	
	function shh_getMessages($id) {
		return $this->_ether_request(__FUNCTION__, [$id]);
	}
    
}
