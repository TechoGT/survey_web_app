<?php namespace app\Http\Controllers;
/**
 * Created by PhpStorm.
 * User: jrgr
 * Date: 14/06/15
 * Time: 07:54 PM
 */

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;



class SurveyHelper extends Controller{

    public function __construct(){
        // Global variables tu access Limesurvey core
        //URL of the Limesurvey RemoteControll based in JSON-RPC
        define( 'LS_BASEURL', $_ENV['LS_BASEURL']);
        //Administrator User in Limesurvey
        define( 'LS_USER', $_ENV['LS_USER'] );
        //Administrator User passwrod in Limesurvey
        define( 'LS_PASSWORD', $_ENV['LS_PASSWORD'] );
}
    /** For every request, a token is necessary
     * @param $JSONRPCClient
     * @return User private token
     */
    public function authUser($JSONRPCClient){

        // receive session key
        $sessionKey= $JSONRPCClient->get_session_key( LS_USER, LS_PASSWORD );

        return $sessionKey;
    }

    /**
     * Checks if the survey is active, to send response
     * @param $RPCClient
     * @param $sessionKey
     * @param $suId
     * @return array
     */
    public function checkSurveyStatus($RPCClient, $sessionKey,$suId){

        $surveyStatus= $RPCClient->get_survey_properties($sessionKey,$suId,array(
            'sid','active'));

        // If the survey is active, it will have an active key
        if(array_key_exists('active',$surveyStatus)) {

            switch($surveyStatus['active']){
                case 'Y':
                    return array(
                        'status'=>true
                    );

                case 'N':
                    return array(
                        'message' => 'Survey is not Active, notify administrator',
                        'status' => false
                    );
            }
        }

        return array(
            'message' => $surveyStatus['status'],
            'status' => false
        );
    }
}