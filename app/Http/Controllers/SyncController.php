<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class SyncController extends Controller {


	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		//
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
        // LimeSurvey Core URL
        // LimeSurvey Core URL
        define( 'LS_BASEURL', '192.168.10.1/limesurvey/index.php');
        define( 'LS_USER', 'admin' );
        define( 'LS_PASSWORD', 'qwerty' );

        //token usuario
        $sessionKey =  $this->authUser();

        $JSONRPCClient = new \org\jsonrpcphp\JsonRPCClient( LS_BASEURL.'/admin/remotecontrol' );

        $groups = $JSONRPCClient->list_groups($sessionKey,$id);

        //secciones
        $sections = array();

        //listado de grupos
        foreach($groups as $group){
            $idG = $group['id']['gid'];

            //Informacion de grupo
            $groupInfo = $this->getGroupProperties($idG);

            // Preguntas de section
            $qOfGroup = $this->getQuestionsOfgroup($id, $idG);

            $groupInfo ['questions']= $qOfGroup;

            $sections[] = $groupInfo;

        }

        $suInfo = $this->getSurveyProperties($id);
        $suInfo['sections'] = $sections;
        return $suInfo;
	}

    private function getQuestionsOfgroup($surveyId,$groupId){

        //token usuario
        $sessionKey =  $this->authUser();

        $JSONRPCClient = new \org\jsonrpcphp\JsonRPCClient( LS_BASEURL.'/admin/remotecontrol' );


        $questionsList = $JSONRPCClient->list_questions($sessionKey,$surveyId,$groupId);

        $qWithProps = null;
        foreach($questionsList as $question){
            $qId = $question['id']['qid'];
            $qWithProps [] = $this->getQuestionProperty($qId);
        }

        return $qWithProps;
    }

    private function getGroupProperties($gId){
        //token usuario
        $sessionKey =  $this->authUser();

        $JSONRPCClient = new \org\jsonrpcphp\JsonRPCClient( LS_BASEURL.'/admin/remotecontrol' );


        $groupInfo = $JSONRPCClient->get_group_properties($sessionKey,$gId,array(
            'gid','group_order','description','sid','group_name',

        ));

        return $groupInfo;
    }

    private function getQuestionProperty($id){
        //token usuario
        $sessionKey =  $this->authUser();

        $JSONRPCClient = new \org\jsonrpcphp\JsonRPCClient( LS_BASEURL.'/admin/remotecontrol' );


        $qList = $JSONRPCClient->get_question_properties($sessionKey,$id, array(
            'type',	'help', 'parent_qid','title','other','scale_id',
            'sid',	'question',	'mandatory'	,'same_default',
            'gid',	'preg',	'question_order',	'relevance',
            'subquestions',	'attributes',	'attributes_lang',	'answeroptions'
        ));
        $qList['id'] = $id;
        $qList['question'] = strip_tags($qList['question']);
        return $qList;
    }

    private function getSurveyProperties($idsu){
        $sessionKey =  $this->authUser();

        $JSONRPCClient = new \org\jsonrpcphp\JsonRPCClient( LS_BASEURL.'/admin/remotecontrol' );

        $surveyProp = $JSONRPCClient->get_survey_properties($sessionKey,$idsu,array(
            'sid','active',	'autonumber_start',	'owner_id','admin','expires',
            'adminemail','startdate','format','template',
            'tokenlength','anonymized','usetokens',
            'datecreated','showprogress','datestamp','navigationdelay','showqnumcode'
        ));
        return $surveyProp;
    }

    private function authUser(){

        // instanciate a new client
        $JSONRPCClient = new \org\jsonrpcphp\JsonRPCClient( LS_BASEURL.'/admin/remotecontrol' );

        // receive session key
        $sessionKey= $JSONRPCClient->get_session_key( LS_USER, LS_PASSWORD );

        return $sessionKey;
    }

}
