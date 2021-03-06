<?php
//
// Copyright (c) 2014, Pinterest
// https://github.com/zynga/saigon
// Author: Matt West (https://github.com/mhwest13)
// License: BSD 2-Clause
//

class PCMDBRegionsException extends Exception {}

class PCMDBRegions implements HostAPI
{

    public function getList()
    {
        return array_flip(array('us-east-1'));
    }

    public function getInput()
    {
        return null;
    }

    public function getSearchResults($input)
    {
        $param = $input->srchparam;
        $urlappend = 'api/cmdb/getnagioshosts/region/' . $param;
        $results = $this->getdata($urlappend);
        return $results;
    }

    private function getdata($urlappend)
    {
        $url = PCMDB_URL . $urlappend;
        /* Initialize Curl and Issue Request */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        /* Response or No Response ? */
        $response   = curl_exec($ch);
        $errno      = curl_errno($ch);
        $errstr     = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            throw new PCMDBRegionsException($errno." ".$errstr);
        }
        return json_decode($response, true);
    }

}
