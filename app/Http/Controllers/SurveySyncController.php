<?php namespace App\Http\Controllers;

use \org\jsonrpcphp\JsonRPCClient;

class SurveySyncController extends SurveyHelper {


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


        //Start a JSON RPC Client for the requests
        $RPCClient = new JsonRPCClient( LS_BASEURL.'admin/remotecontrol' );

        //User private Token
        $sessionKey =  $this->authUser($RPCClient);

        // If survey is active send response
        $surveyStatus = $this->checkSurveyStatus($RPCClient,$sessionKey,$id);

        if($surveyStatus['status']){
            return $this->syncSurvey($RPCClient,$sessionKey,$id);
        }
        else{
            return $surveyStatus;
        }

	}

    private function syncSurvey($RPCClient,$sessionKey,$id){
        //Get all the Sections or Groups of questions using unique
        //Survey id from the Limesurvey core service
        $groups = $RPCClient->list_groups($sessionKey,$id);

        //Empty Sections array
        $sections = array();

        //Configure each group/section of questions to return a correct JSON
        foreach($groups as $group){
            //Get the group/section id
            $idG = $group['id']['gid'];

            //Get all the information of a given group/section
            $groupInfo = $this->getGroupProperties($RPCClient,$sessionKey,$idG);

            // Get all questions of a given Group, it is a list.
            $qOfGroup = $this->getQuestionsOfgroup($RPCClient,$sessionKey,$id, $idG);

            $groupInfo ['questions']= $qOfGroup;

            $sections[] = $groupInfo;

        }

        $suInfo = $this->getSurveyProperties($RPCClient,$sessionKey,$id);
        $suInfo['sections'] = $sections;

        // release the session key
        $RPCClient->release_session_key($sessionKey);
        return $suInfo;

}

    /** Get the properties of a given survey
     * @param $RPCClient
     * @param $sessionKey
     * @param $idSu
     * @return array
     */
    private function getSurveyProperties($RPCClient,$sessionKey, $idSu){

        $surveyProp = $RPCClient->get_survey_properties($sessionKey,$idSu,array(
            'sid','active',	'autonumber_start',	'owner_id','admin','expires',
            'adminemail','startdate','format',
            'tokenlength','usetokens',
            'datecreated','showprogress','datestamp'
        ));

        // Adding extra data to the Survey using get_language_properties
        $moreSurveyProp = $RPCClient->get_language_properties($sessionKey,$idSu,array(
            'surveyls_title','surveyls_description','surveyls_welcometext',
            'surveyls_endtext'
        ),'es');
        // Insert extra data to array
        $united = array_merge($moreSurveyProp,$surveyProp);
        return $united;
    }

    /** Get the properties of a given question
     * @param $sessionKey
     * @param $id
     * @return mixed
     */
    private function getQuestionProperty($RPCClient, $sessionKey, $id){

        $qList = $RPCClient->get_question_properties($sessionKey,$id, array(
            'type',	'help', 'parent_qid','title','other','scale_id',
            'sid',	'question',	'mandatory'	,'same_default',
            'gid',	'preg',	'question_order',	'relevance',
            'subquestions',	'attributes','attributes_lang','answeroptions'
        ));
        $qList['id'] = $id;
        $qList['question'] = strip_tags($qList['question']);

        //Add of "checked" to any subquestion for ease of render in app
        // Only if the array has subquestions
        if(count($qList['subquestions']) > 1){
            $subQuestions = array();
            foreach($qList['subquestions'] as $subQuestion){
                $subQuestion['checked'] = false;

                //Add 'answer' empty field to store answer in phone
                if(array_key_exists('scale_id',$subQuestion) && $subQuestion['scale_id'] == 1){
                    $subQuestion['answer'] = "";
                }

                //Search for a type in Question, using the ##type
                $questionText = $subQuestion['question'];
                preg_match('/(##)(.)/', $questionText, $match);
                if($match){
                    //Set type found. second position is the character
                    $subQuestion['type'] = strtoupper($match[2]);
                    //Remove specified ##type
                    $subQuestion['question'] = preg_replace('/(##)(.)/','',$questionText);
                }

                //Add modified question to question
                $subQuestions[] = $subQuestion;
            }

            $qList['subquestions'] = $subQuestions;
        }
        return $qList;
    }

    /** Get a list of questions of a given group/section. It contains properties
     * of every question
     * @param $RPCClient
     * @param $sessionKey
     * @param $surveyId
     * @param $groupId
     * @return array|null
     */
    private function getQuestionsOfgroup($RPCClient,$sessionKey,$surveyId,$groupId){

        $questionsList = $RPCClient->list_questions($sessionKey,$surveyId,$groupId);

        $qWithProps = null;
        foreach($questionsList as $question){

            $qId = $question['id']['qid'];

            //Question with property
            $qWithProperty = $this->getQuestionProperty($RPCClient,$sessionKey,$qId);
            //Quitar preguntas que son subpreguntas
            if($qWithProperty['parent_qid'] == 0 ){
                $qWithProps [] = $this->getQuestionProperty($RPCClient,$sessionKey,$qId);
            }
        }
        return $qWithProps;
    }

    /** Get all the information of a given Group/Section of
     *  Questions.
     * @param $RPCClient
     * @param $sessionKey
     * @param $gId
     * @return mixed
     */
    private function getGroupProperties($RPCClient,$sessionKey, $gId){

        $groupInfo = $RPCClient->get_group_properties($sessionKey,$gId,array(
            'gid','group_order','description','sid','group_name',

        ));

        return $groupInfo;
    }


}
