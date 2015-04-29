<?php
/**
 * Created by PhpStorm.
 * User: jrgr
 * Date: 27/04/15
 * Time: 10:16 PM
 */

namespace app\Http;


class APIAuth {

    public function getAuthorizedToken($user,$password){

        // LimeSurvey URL
        define( 'LS_BASEURL', 'http://192.168.10.1/limesurvey/index.php');
        define( 'LS_USER', 'admin' );
        define( 'LS_PASSWORD', 'qwerty' );

        // JSON RPC  new client
        $jsonRpcClient = new \org\jsonrpcphp\JsonRPCClient( LS_BASEURL.'/admin/remotecontrol' );

        // receive session key
        $sessionKey= $jsonRpcClient->get_session_key( LS_USER, LS_PASSWORD );

        return $sessionKey;
    }
}