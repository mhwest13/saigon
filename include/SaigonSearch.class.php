<?php
//
// Copyright (c) 2014, Zynga Inc.
// https://github-ca.corp.zynga.com/zcloud-tools/saigon
// Author: Leo Nishio
// License: BSD 2-Clause
//

class SaigonSearch {

	/*
	 * search - search each deployment based on what is stored in Redis for the current version
	 */
	public static function search($deploymentLimit, $search) {

		$results = array();
		
		if (! empty($search)) {
		
			$deployments = self::getDeployments($deploymentLimit);
		
			foreach ($deployments as $deployment) {
				$results[$deployment] = self::searchDeployment($deployment, $search);
			}
		}
		
		return $results;
	}
	
	/*
	 * getDeployments - get an authorized list of deployments
	 */
	private static function getDeployments($deployment = null) {

		$amodule = AUTH_MODULE;
		$authmodule = new $amodule();
		
		$viewDeployments = array();
		$deployments = RevDeploy::getDeployments();
		
		if (empty($deployment)) {
			foreach ($deployments as $deployment) {
				if ($authmodule->checkAuth($deployment) === true) {
					array_push($viewDeployments, $deployment);
				}
			}
		} elseif (in_array($deployment, $deployments)) {
			if ($authmodule->checkAuth($deployment) === true) {
				array_push($viewDeployments, $deployment);
			}
		}
		asort($viewDeployments);
		return $viewDeployments;

	}
	
	/*
	 * searchDeployment - search the component keys of a deployment 
	 */
	private static function searchDeployment($deployment, $search) {
		$results = array();
		$match = false;
		
		// Get the deployment version
		$results['version'] = RevDeploy::getDeploymentRev($deployment);
		
		// See if deployment name matches
		if (preg_match("/$search/", $deployment)) {
			$results['deployment_name'] = $deployment;
			$match = true;
		}
		
		// Get the key hash
		list($dKey) = array_shift(NagRedis::keys(md5('deployment:'.$deployment), false));
		$results['deployment_hash'] = $dKey;
		
		// Look through the deployment hash
		list($match_deployment_info, $match_flag) = self::searchHash($dKey, $search);
		if ($match_flag) {
			$results['deployment_info'] = $match_deployment_info;
			$match = true;
		}
		
		// Other non-versioned keys
		$nvKeys = array_shift(NagRedis::keys(md5('deployment:'.$deployment).":hostsearch*", true));
		sort($nvKeys);
		foreach ($nvKeys as $nvKey) {
			if (preg_match("/$search/", $nvKey)) {
				if (isset($results['nonversioned']['key_name'])) {
					array_push($results['nonversioned']['key_name'], $nvKey);
				} else {
					$results['nonversioned']['key_name'] = array($nvKey);
				}
				$match = true;
			}
			list($match_nv_info, $match_flag) = self::searchRedisKey($nvKey, $search);
			if ($match_flag) {
				if (isset($results['nonversioned']['key_value'][$nvKey])) {
					array_push($results['nonversioned']['key_value'][$nvKey], $match_nv_info);
				} else {
					$results['nonversioned']['key_value'][$nvKey] = $match_nv_info;
				}
				$match = true;
			}
		}

		// Other versioned keys
		$vKeys = array_shift(NagRedis::keys(md5('deployment:'.$deployment).":".$results['version'].":*", true));
		sort($vKeys);
		foreach ($vKeys as $vKey) {
			if (preg_match("/$search/", $vKey)) {
				if (isset($results['versioned']['key_name'])) {
					array_push($results['versioned']['key_name'], $vKey);
				} else {
					$results['versioned']['key_name'] = array($vKey);
				}
				$match = true;
			}
			list($match_v_info, $match_flag) = self::searchRedisKey($vKey, $search);
			if ($match_flag) {
				if (isset($results['versioned']['key_value'][$vKey])) {
					array_push($results['versioned']['key_value'][$vKey], $match_v_info);
				} else {
					$results['versioned']['key_value'][$vKey] = $match_v_info;
				}
				$match = true;
			}
		}
				
		$results['match'] = $match;
		return $results;
	}
    
	/*
	 * searchHash - search a hash type key
	 */
	private static function searchHash ($hashKey, $search) {
		$results = array();
		$match = false;

		//echo var_dump($hashKey,true); exit;
		foreach (NagRedis::hKeys($hashKey) as $hashKeyKey) {
			if (preg_match("/$search/", $hashKeyKey)) {
				if (isset($results['key_name'])) {
					array_push($results['key_name'], "$hashKeyKey");
				} else {
					$results['key_name'] = array("$hashKeyKey");
				}
				$match = true;
			}
			$keyValue = NagRedis::hget($hashKey, $hashKeyKey);
			if (preg_match("/$search/", $keyValue)) {
				if (isset($results['key_value'])) {
					array_push($results['key_value'], "$hashKeyKey = $keyValue");
				} else {
					$results['key_value'] = array("$hashKeyKey = $keyValue");
				}
				$match = true;
			}
		}
		return array($results, $match);
	}
    
	/*
	 * searchSet - search a set type key
	 */
	private static function searchSet ($key, $search) {
		
		$results = array();
		$match = false;
		
		$smembers = NagRedis::sMembers($key);
		
		foreach ($smembers as $smember) {
			if (preg_match("/$search/", $smember)) {
				if (isset($results['key_value'])) {
					array_push($results['key_value'], $smember);
				} else {
					$results['key_value'] = array($smember);
				}
				$match = true;
			}
		}
		return array($results, $match);
	}
	
	/*
	 * searchRedisKey - given a key, determine it's type and look at it's data
	 */
	private static function searchRedisKey ($key, $search) {
		/*  Redis Types
		*      string: Redis::REDIS_STRING
		*      set:    Redis::REDIS_SET
		*      list:   Redis::REDIS_LIST
		*      zset:   Redis::REDIS_ZSET
		*      hash:   Redis::REDIS_HASH
		*      other:  Redis::REDIS_NOT_FOUND
		*/
		$type = NagRedis::type($key);	
		switch ($type) {
			case Redis::REDIS_HASH:
				return self::searchHash($key, $search);
				break;
			case Redis::REDIS_SET:
				return self::searchSet($key, $search);
				break;
			default:
				error_log("ERROR: searchRedisKey: Unknown type: $key");
		}
		return array(array(), false);
	}
	
}
