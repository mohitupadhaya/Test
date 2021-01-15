<?php
namespace app\modules\api2\controllers;

use app\models\ResearchProgram;
use app\models\ShareSarvey;
use app\models\SurveyQuestionDay;
use app\models\Surveyquestion;
use app\models\Surveyquestionoption;
use app\models\User;
use app\models\Userprogram;
use app\modules\api2\components\ApiTxController;
use app\modules\api2\components\TPagination;
use yii\data\ActiveDataProvider;
use app\models\SurveyAnswer;
use app\models\SurveyAnswerOption;
use yii\web\UploadedFile;
use app\models\File;
use app\models\Notification;
use Mpdf\Mpdf;
use yii\data\ArrayDataProvider;

class ResearchProgramController extends ApiTxController
{

    protected function verbs()
    {
        $verbs = parent::verbs();
        $verbs['get'] = [
            'GET'
        ];
        $verbs['index'] = [
            'POST'
        ];
        return $verbs;
    }

    public function actionGet($id)
    {
        $this->modelClass = "app\models\ResearchProgram";
        return $this->txget($id);
    }

    public function actionIndex($page = NULL)
    {
        $query = ResearchProgram::find()->where([
            'created_by_id' => \Yii::$app->user->id
        ]);
        // print_r($query->createCommand()->getRawSql());die;
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'page' => $page
            ]
        ]);
        
        $data = (new TPagination())->serialize($dataProvider);
        $data['status'] = self::API_OK;
        
        $this->response = $data;
    }

    public function actionProgramList($page = NULL, $completed = null)
    {
        if ($completed) {
            $query = ResearchProgram::find()->where([
                'created_by_id' => \Yii::$app->user->id,
                'state_id' => ResearchProgram::STATE_COMPLETED
            ])->orderBy([
                'id' => SORT_DESC
            ]);
        } else {
            $query = ResearchProgram::find()->where([
                'created_by_id' => \Yii::$app->user->id,
                'state_id' => ResearchProgram::STATE_ACTIVE
            ])->orderBy([
                'id' => SORT_DESC
            ]);
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'page' => $page
            ]
        ]);
        
        $data = (new TPagination())->serialize($dataProvider);
        $data['status'] = self::API_OK;
        
        $this->response = $data;
    }

    public function actionAdd()
    {
        $model = new ResearchProgram();
        $data = [];
        $post = \Yii::$app->request->post();
        $flag = true;
        if (! empty($post)) {
            $db = \Yii::$app->db;
            $transaction = $db->beginTransaction();
            try {
                $model->state_id = ResearchProgram::STATE_ACTIVE;
                $model->type_id = ResearchProgram::TYPE_ADMIN;
                $researchProgram['ResearchProgram'] = (array) json_decode($post['main']['ResearchProgram']);
                if ($model->load($researchProgram) && $model->save()) {
                    $questions = json_decode($post['main']['Surveyquestion']);
                    if (! empty($questions) || $model->picture_submission) {
                        foreach ($questions as $ques) {
                            if (($ques->type_id == Surveyquestion::TYPE_NONE && empty($ques->picture_submission))) {
                                $data['error'] = \Yii::t('app', 'Please select an option');
                                $flag = false;
                                break;
                            }
                            $survey_ques = new Surveyquestion();
                            $survey_ques->question = $ques->question;
                            $survey_ques->type_id = $ques->type_id;
                            $survey_ques->state_id = Surveyquestion::STATE_ACTIVE;
                            $survey_ques->research_id = $model->id;
                            $survey_ques->day = $ques->day;
                            $survey_ques->picture_submission = $ques->picture_submission;
                            if ($survey_ques->save()) {
                                $questionDays = explode(',', $survey_ques->day);
                                foreach ($questionDays as $questionDay) {
                                    $questionDayModel = new SurveyQuestionDay();
                                    $questionDayModel->day = date('Y-m-d', strtotime($model->start_date . ' +' . ($questionDay - 1) . ' day'));
                                    $questionDayModel->state_id = SurveyQuestionDay::STATE_ACTIVE;
                                    $questionDayModel->research_id = $model->id;
                                    $questionDayModel->survey_question_id = $survey_ques->id;
                                    if (! $questionDayModel->save()) {
                                        $data['error'] = $questionDayModel->getErrorsString();
                                        $flag = false;
                                        break;
                                    }
                                }
                                /*
                                 * if ((in_array($ques->type_id, [ Surveyquestion::TYPE_TEXT, Surveyquestion::TYPE_NONE ]) && ! empty($ques->option)) || (! in_array($ques->type_id, [
                                 * Surveyquestion::TYPE_TEXT,
                                 * Surveyquestion::TYPE_NONE
                                 * ]) && empty($ques->option))) {
                                 * $flag = false;
                                 * break;
                                 * } else {
                                 */
                                foreach ($ques->option as $option) {
                                    $ques_option = new Surveyquestionoption();
                                    $ques_option->question_id = $survey_ques->id;
                                    $ques_option->option = $option->title;
                                    $ques_option->is_answer = $option->ans;
                                    $ques_option->state_id = Surveyquestionoption::STATE_ACTIVE;
                                    $ques_option->type_id = $ques->type_id;
                                    if (! $ques_option->save()) {
                                        $flag = false;
                                        break;
                                    }
                                }
                                if (! $flag) {
                                    break;
                                }
                                // }
                            } else {
                                $flag = false;
                                $data['error'] = $survey_ques->getErrors();
                                break;
                            }
                        }
                        if (! $flag) {
                            $transaction->rollBack();
                        } else {
                            $transaction->commit();
                            $data['status'] = self::API_OK;
                            $data['message'] = "Data Saved Succesfully";
                            // $data['detail'] = $model->asJson(true);
                        }
                    } else {
                        $transaction->rollBack();
                        $data['error'] = \Yii::t('app', 'No questions posted');
                    }
                } else {
                    $data['error'] = $model->getErrorsString();
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        } else {
            $data['error'] = \Yii::t('app', 'No Data Posted');
        }
        $this->response = $data;
    }

    public function actionUpdate($id)
    {
        $data = [];
        $post = \Yii::$app->request->post();
        $flag = true;
        if (! empty($post)) {
            $research = ResearchProgram::find()->where([
                'id' => $id
            ])->one();
            if (! empty($research)) {
                $db = \Yii::$app->db;
                $transaction = $db->beginTransaction();
                try {
                    $research->company_name = json_decode($post['ResearchProgram'])->company_name;
                    $research->product_name = json_decode($post['ResearchProgram'])->product_name;
                    if($research->save()){
                        // here update
                        $date = date('Y-m-d');
                        $oldquestions = Surveyquestion::find()->alias('sq')
                            ->joinWith('surveyQuestionDay as sQd')
                            ->where([
                            'sQd.research_id' => $id
                        ])
                            ->andWhere([
                            '<=',
                            'sQd.day',
                            $date
                        ])
                            ->select('sq.id')
                            ->column();
                        if (empty($oldquestions)) {
                            $oldquestions = [];
                        }
                        $update_questions = Surveyquestion::find()->alias('sq')
                            ->joinWith('surveyQuestionDay as sQd')
                            ->where([
                            'sQd.research_id' => $id
                        ])
                            ->andWhere([
                            'NOT IN',
                            'sq.id',
                            $oldquestions
                        ])
                            ->select('sq.id')
                            ->column();
                        if (! empty($update_questions)) {
                            
                            $temp_questions = Surveyquestion::find()->where([
                                'AND',
                                'created_by_id' => \Yii::$app->user->id,
                                'research_id' => $research->id,
                                [
                                    'IN',
                                    'id',
                                    $update_questions
                                ]
                            ])->all();
                            if(!empty($temp_questions)) {
                                foreach ($temp_questions as $temp_question) {
                                    if(!$temp_question->delete()){
                                        $data['error'] = 'Some error occured';
                                    }
                                }
                            }
                            
                        }
                        $questions = json_decode($post['Surveyquestion']);
                        if (! empty($questions) || $research->picture_submission) {
                            foreach ($questions as $ques) {
                                if (($ques->type_id == Surveyquestion::TYPE_NONE && empty($ques->picture_submission))) {
                                    $data['error'] = \Yii::t('app', 'Please select an option');
                                    $flag = false;
                                    break;
                                }
                                $survey_ques = new Surveyquestion();
                                $survey_ques->question = $ques->question;
                                $survey_ques->type_id = $ques->type_id;
                                $survey_ques->state_id = Surveyquestion::STATE_ACTIVE;
                                $survey_ques->research_id = $research->id;
                                $survey_ques->day = $ques->day;
                                $survey_ques->picture_submission = $ques->picture_submission;
                                if ($survey_ques->save()) {
                                    $questionDays = explode(',', $survey_ques->day);
                                    foreach ($questionDays as $questionDay) {
                                        $questionDayModel = new SurveyQuestionDay();
                                        $questionDayModel->day = date('Y-m-d', strtotime($research->start_date . ' +' . ($questionDay - 1) . ' day'));
                                        $questionDayModel->state_id = SurveyQuestionDay::STATE_ACTIVE;
                                        $questionDayModel->research_id = $research->id;
                                        $questionDayModel->survey_question_id = $survey_ques->id;
                                        if (! $questionDayModel->save()) {
                                            $data['error'] = $questionDayModel->getErrorsString();
                                            $flag = false;
                                            break;
                                        }
                                    }
                                    if ((in_array($ques->type_id, [
                                        Surveyquestion::TYPE_TEXT,
                                        Surveyquestion::TYPE_NONE
                                    ]) && ! empty($ques->option)) || (! in_array($ques->type_id, [
                                        Surveyquestion::TYPE_TEXT,
                                        Surveyquestion::TYPE_NONE
                                    ]) && empty($ques->option))) {
                                        $data['error'] = "Invalid Data";
                                        $flag = false;
                                        break;
                                    } else {
                                        foreach ($ques->option as $option) {
                                            $ques_option = new Surveyquestionoption();
                                            $ques_option->question_id = $survey_ques->id;
                                            $ques_option->option = $option->title;
                                            $ques_option->is_answer = $option->ans;
                                            $ques_option->state_id = Surveyquestionoption::STATE_ACTIVE;
                                            $ques_option->type_id = $ques->type_id;
                                            if (! $ques_option->save()) {
                                                $data['error'] = $ques_option->getErrorsString();
                                                $flag = false;
                                                break;
                                            }
                                        }
                                        if (! $flag) {
                                            break;
                                        }
                                    }
                                } else {
                                    $flag = false;
                                    $data['error'] = $survey_ques->getErrors();
                                    break;
                                }
                            }
                            if (! $flag) {
                                $transaction->rollBack();
                            } else {
                                $transaction->commit();
                                ResearchProgram::saveNotification($research);
                                $data['status'] = self::API_OK;
                                $data['message'] = 'Research updated succesfully';
                            }
                        } else {
                            $transaction->rollBack();
                            $data['error'] = \Yii::t('app', 'No questions posted');
                        }
                    }else{
                        $transaction->rollBack();
                        $data['error'] = $research->getErrors();
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    throw $e;
                }
            } else {
                $data['error'] = "Research Not Found";
            }
        } else {
            $data['error'] = \Yii::t('app', 'No Data Posted');
        }
        $this->response = $data;
    }

    public function actionProgramDetails($id = null)
    {
        $data = [];
        
        $research = ResearchProgram::find()->where([
            'id' => $id
        ])->one();
        if (! empty($research)) {
            $data['status'] = self::API_OK;
            $data['research'] = $research->asJson(true);
        } else {
            $data['error'] = "research Not Found";
        }
        
        $this->response = $data;
    }

    // assign code
    public function actionUserCode($readonly = null)
    {
        $data = [];
        $flag = false;
        $post = \Yii::$app->request->post();
        if (! empty($post)) {
            $program = ResearchProgram::find()->where([
                'assign_code' => $post['code']
            
            ])->one();
            if (! empty($program)) {
                if ($program->start_date <= date('Y-m-d')) {
                    if ($program->end_date >= date('Y-m-d')) {
                        $is_blocked = Userprogram::find()->where([
                            'created_by_id' => \yii::$app->user->id,
                            'research_id' => $program->id,
                            'state_id' => Userprogram::STATE_DELETED
                        ])->exists();
                        if (! $is_blocked) {
                            if ($readonly === null) {
                                $is_exist = Userprogram::find()->where([
                                    'created_by_id' => \Yii::$app->user->id,
                                    'state_id' => Userprogram::STATE_ACTIVE
                                ])->exists();
                                if (! $is_exist) {
                                    $userprogram = new Userprogram();
                                    $userprogram->research_id = $program->id;
                                    $userprogram->state_id = Userprogram::STATE_ACTIVE;
                                    if (! $userprogram->save()) {
                                        $data['error'] = $userprogram->getErrorsString();
                                        $this->response = $data;
                                        return;
                                    }
                                } else {
                                    $data['error'] = "Already 1 Survey Under Process";
                                    $this->response = $data;
                                    return;
                                }
                            }
                            $data['status'] = self::API_OK;
                            $data['program'] = $program->asJson();
                        } else {
                            $data['error'] = "You are not allowed to enter the research";
                        }
                    } else {
                        $data['error'] = "This research has expired";
                    }
                } else {
                    $data['error'] = "Research is not started yet";
                }
            } else {
                $data['error'] = "Invalid Code";
            }
        } else {
            $data['error'] = \Yii::t('app', 'No Data Posted');
        }
        $this->response = $data;
    }

    public function actionAssignedList($page = NULL, $completed = null)
    {
        if ($completed) {
            $query = ResearchProgram::find()->alias('rp')
                ->joinWith('userProgram as up')
                ->where([
                'rp.state_id' => ResearchProgram::STATE_COMPLETED,
                'rp.type_id' => ResearchProgram::TYPE_ADMIN,
                'up.state_id' => Userprogram::STATE_COMPLETED,
                'up.created_by_id' => \Yii::$app->user->id
            ])
                ->andWhere([
                '>',
                'Date(end_date)',
                date('Y-m-d')
            ]);
            $dataProvider1 = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'page' => $page
                ]
            ]);
            
            $query2 = ResearchProgram::find()->where([
                'created_by_id' => \Yii::$app->user->id,
                'state_id' => ResearchProgram::STATE_COMPLETED,
                'type_id' => ResearchProgram::TYPE_PARTICIPANT
            ]);
            $dataProvider2 = new ActiveDataProvider([
                'query' => $query2,
                'pagination' => [
                    'page' => $page
                ]
            ]);
            
        } else {
            $query = ResearchProgram::find()->alias('rp')
                ->joinWith('userProgram as up')
                ->where([
                'rp.state_id' => ResearchProgram::STATE_ACTIVE,
                'rp.type_id' => ResearchProgram::TYPE_ADMIN,
                'up.state_id' => Userprogram::STATE_ACTIVE,
                'up.created_by_id' => \Yii::$app->user->id
            ])
                ->andWhere([
                '>=',
                'Date(end_date)',
                date('Y-m-d')
            ]);
            $dataProvider1 = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'page' => $page
                ]
            ]);
            $query2 = ResearchProgram::find()->where([
                'created_by_id' => \Yii::$app->user->id,
                'state_id' => ResearchProgram::STATE_ACTIVE,
                'type_id' => ResearchProgram::TYPE_PARTICIPANT
            ]);
            $dataProvider2 = new ActiveDataProvider([
                'query' => $query2,
                'pagination' => [
                    'page' => $page
                ]
            ]);
        }
        
        $data = array_merge($dataProvider1->getModels(), $dataProvider2->getModels());
        $dataProvider = new ArrayDataProvider([
            'allModels' => $data
        ]);
        
        $data_new = (new TPagination())->serialize($dataProvider);
        $data_new['status'] = self::API_OK;
        $this->response = $data_new;
    }

    public function actionParticipateList($page = null, $research_id)
    {
        $query = User::find()->alias('u')
            ->joinWith('participateUser as pu')
            ->where([
            'pu.research_id' => $research_id,
            'pu.state_id' => Userprogram::STATE_ACTIVE
        ]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'page' => $page
            ]
        ]);
        
        $pagination = new TPagination();
        $pagination->function = "asCustomJson";
        $data = $pagination->serialize($dataProvider);
        $data['status'] = self::API_OK;
        
        $this->response = $data;
    }

    public function actionResearchQuestion($page = null, $day = NULL, $research_id)
    {
        $researchProgram = ResearchProgram::find()->where([
            'id' => $research_id
        ])->one();
        $lessDay = true;
        if (empty($researchProgram)) {
            $data['error'] = "Research id not valid";
            $this->response = $data;
            return;
        } else {
            $start = strtotime($researchProgram->start_date);
            $end = strtotime(date('Y-m-d'));
            $days_between = (ceil(abs($end - $start) / 86400)) + 1;
        }
        $query = Surveyquestion::find()->alias('sq')
            ->joinWith('surveyQuestionDay as sQd')
            ->where([
            'sQd.day' => $days_between,
            'sQd.research_id' => $research_id
        ]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'page' => $page
            ]
        ]);
        $data = (new TPagination())->serialize($dataProvider);
        $data['status'] = self::API_OK;
        $this->response = $data;
    }

    public function actionGetQuestion($page = null, $research_id, $day = null)
    {
        $researchProgram = ResearchProgram::find()->where([
            'id' => $research_id
        ])->one();
        if (! empty($researchProgram)) {
            $exist = SurveyAnswer::find()->where([
                'created_by_id' => \Yii::$app->user->id,
                'research_id' => $research_id,
                'date(created_on)' => date('Y-m-d')
            ])->exists();
            if (! $exist) {
                $exist = File::find()->where([
                    'created_by_id' => \Yii::$app->user->id,
                    'model_id' => $research_id,
                    'model_type' => get_class($researchProgram),
                    'date(created_on)' => date('Y-m-d')
                ])->exists();
                if (! $exist) {
                    $date = date('Y-m-d');
                    if ($day != null) {
                        $date = date('Y-m-d', strtotime($researchProgram->start_date . ' +' . ($day - 1) . ' day'));
                    }
                    $query = Surveyquestion::find()->alias('sq')
                        ->joinWith('surveyQuestionDay as sQd')
                        ->where([
                        'sQd.day' => $date,
                        'sQd.research_id' => $research_id
                    ]);
                    
                    $dataProvider = new ActiveDataProvider([
                        'query' => $query,
                        'pagination' => [
                            'page' => $page
                        ]
                    ]);
                    
                    $pagination = new TPagination();
                    $pagination->params = [
                        true
                    ];
                    $data = $pagination->serialize($dataProvider);
                    $data['status'] = self::API_OK;
                } else {
                    $data['error'] = "You have already submitted the questions";
                    $data['is_submitted'] = true;
                }
            } else {
                $data['error'] = "You have already submitted the questions";
                $data['is_submitted'] = true;
            }
        } else {
            $data['error'] = "Research Not Found";
        }
        $this->response = $data;
    }

    public function actionGetUpdateQuestion($research_id)
    {
        $researchProgram = ResearchProgram::find()->where([
            'id' => $research_id,
            'created_by_id' => \Yii::$app->user->id
        ])->one();
        if (! empty($researchProgram)) {
            $date = date('Y-m-d');
            $questions = Surveyquestion::find()->alias('sq')
                ->joinWith('surveyQuestionDay as sQd')
                ->where([
                'sQd.research_id' => $research_id
            ])
                ->andWhere([
                '<=',
                'sQd.day',
                $date
            ])
                ->select('sq.id')
                ->column();
            if (empty($questions)) {
                $questions = [];
            }
            $update_questions = Surveyquestion::find()->alias('sq')
                ->joinWith('surveyQuestionDay as sQd')
                ->where([
                'sQd.research_id' => $research_id
            ])
                ->andWhere([
                'NOT IN',
                'sq.id',
                $questions
            ])
                ->all();
            if (! empty($update_questions)) {
                $ques = [];
                foreach ($update_questions as $question) {
                    $ques[] = $question->asJson(true);
                }
                $data['questions'] = $ques;
                $data['status'] = self::API_OK;
            } else {
                $data['error'] = 'No Questions Available';
            }
        } else {
            $data['error'] = "Research Not Found";
        }
        $this->response = $data;
    }

    public function actionDeleteResearch($id)
    {
        $data = [];
        $research = ResearchProgram::find()->where([
            'id' => $id
        ])->one();
        if (! empty($research)) {
            $research->delete();
            $data['status'] = self::API_OK;
            $data['message'] = 'Research deleted succesfully';
        } else {
            $data['error'] = "Research not Found";
        }
        $this->response = $data;
    }

    public function actionRemoveParticipation()
    {
        $data = [];
        $post = \Yii::$app->request->bodyParams;
        if (! empty($post)) {
            $model = Userprogram::findOne([
                'research_id' => $post['ResearchProgram']['research_id'],
                'created_by_id' => $post['ResearchProgram']['id']
            ]);
            if (! empty($model)) {
                $model->state_id = Userprogram::STATE_DELETED;
                if ($model->save()) {
                    /*
                     * BasicQuestion::deleteAll([
                     * 'created_by_id' => $post['ResearchProgram']['id']
                     * ]);
                     */
                    $data['status'] = self::API_OK;
                    $data['message'] = 'Participant deleted succesfully';
                } else {
                    $data['error'] = $model->getErrorsString();
                }
            } else {
                $data['error'] = 'Record not found.';
            }
        } else {
            $data['error'] = 'No Data Posting.';
        }
        $this->response = $data;
    }

    public function actionParticipantList($page = null)
    {
        $data = [];
        $post = \Yii::$app->request->bodyParams;
        $research_id = $post['research_id'];
        
        $userProgram = Userprogram::find()->select('created_by_id')
            ->where([
            '!=',
            'state_id',
            Userprogram::STATE_DELETED
        ])
            ->groupBy('created_by_id')
            ->column();
        
        $query = User::find()->where([
            'AND',
            [
                'role_id' => User::ROLE_USER
            ],
            [
                'not in',
                'id',
                $userProgram
            ],
            [
                '!=',
                'id',
                \Yii::$app->user->id
            ]
        
        ])->andWhere([
            'state_id' => User::STATE_ACTIVE
        ]);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'page' => $page
            ]
        ]);
        
        $pagination = new TPagination();
        $pagination->function = "asCustomJson";
        $data = $pagination->serialize($dataProvider);
        $data['status'] = self::API_OK;
        
        $this->response = $data;
    }

    public function actionAddParticipation()
    {
        $data = [];
        $post = \Yii::$app->request->bodyParams;
        if (! empty($post)) {
            $exists = Userprogram::find()->where([
                'created_by_id' => $post['user_id'],
                'state_id' => Userprogram::STATE_ACTIVE
            ])->exists();
            if (! $exists) {
                $is_exist = Userprogram::find()->where([
                    'created_by_id' => $post['user_id'],
                    'state_id' => Userprogram::STATE_DELETED,
                    'research_id' => $post['research_id']
                ])->one();
                if (! empty($is_exist)) {
                    $is_exist->state_id = Userprogram::STATE_ACTIVE;
                    if (! $is_exist->save()) {
                        $data['error'] = $is_exist->getErrors();
                    }
                    $data['status'] = self::API_OK;
                    $data['message'] = "Participant added successfully.";
                } else {
                    $model = new Userprogram();
                    $model->created_by_id = $post['user_id'];
                    $model->research_id = $post['research_id'];
                    $model->state_id = Userprogram::STATE_ACTIVE;
                    if ($model->save()) {
                        $data['status'] = self::API_OK;
                        $data['message'] = "Participant added successfully.";
                    } else {
                        $data['error'] = $model->getErrorsString();
                    }
                }
            } else {
                $data['error'] = "Participant already exist in some other research.";
            }
        } else {
            $data['error'] = "No data posted.";
        }
        $this->response = $data;
    }

    public function actionListAdministrator($page = 0)
    {
        $data = [];
        $post = \Yii::$app->request->bodyParams;
        $query = User::find()->where([
            'AND',
            [
                'role_id' => User::ROLE_MANAGER
            ],
            [
                '!=',
                'state_id',
                User::STATE_INACTIVE
            ],
            [
                '!=',
                'id',
                \Yii::$app->user->id
            ]
        ]);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'page' => $page
            ]
        ]);
        $pagination = new TPagination();
        $pagination->function = "asAdminListJson";
        $pagination->params = [
            'research_id' => $post['research_id']
        ];
        $data = $pagination->serialize($dataProvider);
        
        $data['status'] = self::API_OK;
        
        $this->response = $data;
    }

    public function actionShareProgram()
    {
        $data = [];
        $model = new ShareSarvey();
        $post = \Yii::$app->request->bodyParams;
        
        if ($model->load($post)) {
            $model->created_by_id = \Yii::$app->user->id;
            $model->state_id = ShareSarvey::STATE_ACTIVE;
            $exist = ShareSarvey::find()->where([
                'research_id' => $model->research_id,
                'share_with_id' => $model->share_with_id,
                'state_id' => ShareSarvey::STATE_ACTIVE
            ])->one();
            if (! empty($exist)) {
                $data['error'] = "Research already shared with selected user.";
            } else {
                if ($model->save()) {
                    Notification::saveNotification($model->share_with_id, $model->createdBy->full_name . '  has shared  survey with you ', $model, $model->research_id);
                    $data['status'] = self::API_OK;
                    $data['message'] = "Research shared successfully.";
                    $data['detail'] = $model->asJson();
                } else {
                    $data['error'] = $model->getErrorsString();
                }
            }
        } else {
            $data['error'] = "No data posted.";
        }
        $this->response = $data;
    }

    public function actionSharedProgramList($page = 0)
    {
        $data = [];
        \Yii::$app->response->format = 'json';
        
        $query = ShareSarvey::find()->where([
            'share_with_id' => \Yii::$app->user->id
        ]);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC
                ]
            ],
            'pagination' => [
                'page' => $page
            ]
        ]);
        
        $pagination = new TPagination();
        $pagination->params = [
            true
        ];
        $data = $pagination->serialize($dataProvider);
        $data['status'] = self::API_OK;
        $this->response = $data;
    }

    public function actionRemoveSharedUser($research_id, $id = null)
    {
        $data = [];
        \Yii::$app->response->format = 'json';
        if ($id) {
            $share = ShareSarvey::find()->where([
                'share_with_id' => $id,
                'research_id' => $research_id
            ])->one();
        } else {
            $share = ShareSarvey::find()->where([
                'share_with_id' => \Yii::$app->user->id,
                'research_id' => $research_id
            ])->one();
        }
        if (! empty($share)) {
            $share->delete();
            $data['status'] = self::API_OK;
            $data['message'] = "Removed Succesfully";
        } else {
            $data['error'] = "User is not shared.";
        }
        $this->response = $data;
    }

    public function actionSaveAnswer($day = null)
    {
        $data = [];
        \Yii::$app->response->format = 'json';
        $flag = false;
        $post = \Yii::$app->request->post();
        
        if (! empty($post)) {
            $research = ResearchProgram::find()->where([
                'id' => $post['main']['research_id']
            ])
                ->andWhere([
                '>=',
                'end_date',
                date('y-m-d')
            ])
                ->one();
            if (! empty($research)) {
                $db = \Yii::$app->db;
                $transaction = $db->beginTransaction();
                $post['main']['SurveyAnswer'] = json_decode($post['main']['SurveyAnswer'], true);
                $file = new File();
                $image = UploadedFile::getInstance($file, 'file');
                if (! empty($image)) {
                    $res = $file->saveImage($image, $research, $day);
                    if (isset($res['error'])) {
                        $data['error'] = $res['error'];
                        $this->response = $data;
                        return;
                    }
                }
                foreach ($post['main']['SurveyAnswer'] as $answerList) {
                    $answer = new SurveyAnswer();
                    $answer->question_id = $answerList['question_id'];
                    $answer->answer = isset($answerList['answer']) ? $answerList['answer'] : '';
                    $answer->type_id = $answerList['type_id'];
                    $answer->research_id = $post['main']['research_id'];
                    $answer->state_id = SurveyAnswer::STATE_ACTIVE;
                    if ($day) {
                        $date = date('Y-m-d h:i:s', strtotime($research->start_date . ' +' . ($day - 1) . ' day'));
                        $answer->created_on = $date;
                    }
                    if ($answer->save()) {
                        if (isset($_FILES["SurveyAnswer"]) && isset($_FILES["SurveyAnswer"]["name"]["image"][$answer->question_id])) {
                            $path = UPLOAD_PATH;
                            $file = isset($_FILES["SurveyAnswer"]["name"]["image"][$answer->question_id]) ? $_FILES["SurveyAnswer"]["name"]["image"][$answer->question_id] : 'demo';
                            $target_file = $path . $file;
                            if (! move_uploaded_file($_FILES["SurveyAnswer"]["tmp_name"]['image'][$answer->question_id], $target_file)) {
                                $data['error'] = "Error in image upload";
                                $flag = true;
                                break;
                            }
                            $fileModel = new File();
                            $fileModel->model_id = $answer->id;
                            $fileModel->model_type = get_class($answer);
                            $fileModel->created_by_id = $answer->created_by_id;
                            $fileModel->size = $_FILES["SurveyAnswer"]["size"]["image"][$answer->question_id];
                            $fileModel->title = $file;
                            $fileModel->file = $file;
                            $fileModel->state_id = File::STATE_ACTIVE;
                            $fileModel->type_id = File::STATE_INACTIVE;
                            $fileModel->extension = '.png';
                            if (! $fileModel->save()) {
                                $data['error'] = $imagefile->getErrorsString();
                                $flag = false;
                                break;
                            }
                        }
                        if ($flag) {
                            break;
                        }
                        foreach ($answerList['SurveyAnswerOption'] as $option) {
                            $answerOption = new SurveyAnswerOption();
                            $answerOption->option_id = $option['option_id'];
                            $answerOption->answer_id = $answer->id;
                            $answerOption->state_id = SurveyAnswerOption::STATE_ACTIVE;
                            if (! $answerOption->save()) {
                                $flag = true;
                                $data['error'] = $answerOption->getErrorsString();
                                break;
                            }
                        }
                        if ($flag) {
                            break;
                        }
                    } else {
                        $data['error'] = $answer->getErrorsString();
                        $flag = true;
                        break;
                    }
                }
                if ($flag) {
                    $transaction->rollBack();
                } else {
                    $user = User::find()->where([
                        'id' => \Yii::$app->user->id
                    ])->one();
                    if (! empty($user)) {
                        $user->rating = $user->rating + 1;
                        if ($user->save()) {
                            $transaction->commit();
                            $msg = $user->full_name . " has submitted answer on your research.";
                            if($research->created_by_id != \Yii::$app->user->id){
                                Notification::saveNotification($research->created_by_id, $msg, $research, $research->id);
                            }
                            $is_exist = SurveyQuestionDay::find()->where([
                                'AND',
                                ['research_id' => $research->id],
                                [
                                    '>',
                                    'day',
                                    date('Y-m-d')
                                ]
                            ])->exists();
                            $data['status'] = self::API_OK;
                            $sata['message'] = "Survey saved succesfully";
                            $data['details'] = $user->asJson();
                            $data['is_last_day'] = $research->created_by_id == \Yii::$app->user->id ? !$is_exist : false;
                        } else {
                            $data['error'] = $user->getErrors();
                        }
                    } else {
                        $data['error'] = "User not found";
                    }
                }
            } else {
                $data['error'] = "Research Not Found";
            }
        } else {
            $data['error'] = \Yii::t('app', 'No Data Posted');
        }
        $this->response = $data;
    }

    public function actionGetAnswer($admin = null)
    {
        $data = [];
        \Yii::$app->response->format = 'json';
        $post = \Yii::$app->request->post();
        if (! empty($post)) {
            $research = ResearchProgram::find()->where([
                'id' => $post['research_id']
            ])->one();
            if (! empty($research)) {
                if ($admin) {
                    $answers = SurveyAnswer::find()->where([
                        'research_id' => $post['research_id'],
                        'created_by_id' => $post['user_id']
                    ])->all();
                } else {
                    $answers = SurveyAnswer::find()->where([
                        'research_id' => $post['research_id'],
                        'created_by_id' => \Yii::$app->user->id
                    ])->all();
                }
                if (! empty($answers)) {
                    $ans = [];
                    foreach ($answers as $answer) {
                        $ans[] = $answer->asJson(true);
                    }
                    $data['answers'] = $ans;
                    $this->response = $data;
                    return;
                } else {
                    $data['error'] = "No Answers Submitted Yet";
                }
            } else {
                $data['error'] = "Research Not Found";
            }
        } else {
            $data['error'] = \Yii::t('app', 'No Data Posted');
        }
        $this->response = $data;
    }

    public function actionGetDailyImge($research_id)
    {
        $data = [];
        \Yii::$app->response->format = 'json';
        
        $research = ResearchProgram::find()->where([
            'id' => $research_id
        ])->one();
        if (! empty($research)) {
            $finalImage = [];
            for ($i = 0; $i < $research->submission_timeline; $i ++) {
                $date = date('Y-m-d', strtotime($research->start_date . ' +' . $i . ' day'));
                $dailyImages = File::find()->where([
                    'model_id' => $research->id,
                    'model_type' => get_class($research),
                    'date(created_on)' => $date
                ])->all();
                $tempImages = [];
                if (! empty($dailyImages)) {
                    foreach ($dailyImages as $dailyImage) {
                        $tempImages[] = $dailyImage->asGallaryJson();
                    }
                }
                $finalImage[] = $tempImages;
            }
            $data['dailyImages'] = $finalImage;
            $data['status'] = self::API_OK;
        } else {
            $data['error'] = "Research Not Found";
        }
        
        $this->response = $data;
    }

    public function actionSurveyReview($research_id)
    {
        $data = [];
        \Yii::$app->response->format = 'json';
        $research = ResearchProgram::find()->where([
            'id' => $research_id
        ])->one();
        if (! empty($research)) {
            $questions = Surveyquestion::find()->where([
                'research_id' => $research_id
            ])->all();
            if (! empty($questions)) {
                $question = [];
                foreach ($questions as $ques) {
                    $question[] = $ques->asCustomJson(true);
                }
                $data['status'] = self::API_OK;
                $data['questions'] = $question;
            } else {
                $data['error'] = "No Questions available";
            }
        } else {
            $data['error'] = "Research Not Found";
        }
        $this->response = $data;
    }

    public function actionDeleteGalleryImage()
    {
        $data = [];
        \Yii::$app->response->format = 'json';
        $post = \Yii::$app->request->post();
        $ids = $post['id'];
        $id = explode(',', $ids);
        
        $file = File::find()->where([
            'id' => $id,
            'created_by_id' => \Yii::$app->user->id
        ])->all();
        
        if (! empty($id)) {
            if (! empty($file)) {
                
                foreach ($file as $files) {
                    if ($files->state_id == File::STATE_ACTIVE) {
                        $files->state_id = File::STATE_DELETED;
                        if (! $files->save(false)) {
                            $data['error'] = $file->getErrorsString();
                        } else {
                            
                            $data['message'] = "File deleted successfully";
                            $data['status'] = self::API_OK;
                        }
                    } else {
                        $data['error'] = "You have already deleted the image";
                    }
                }
            } else {
                $data['error'] = "Image Not Found";
            }
        } else {
            $data['error'] = "Please select an image";
        }
        $this->response = $data;
    }

    public function actionDownloadData($research_id)
    {
         // print_r($research_id);die();
        $data = [];
        $mpdf = new Mpdf();
        \Yii::$app->response->format = 'json';
        $research = ResearchProgram::find()->where([
            'id' => $research_id
        ])->one();
          // print_r($research);die();
        if (! empty($research)) {
            $questions = Surveyquestion::find()->where([
                'research_id' => $research_id
            ])->all();
            // print_r($questions);die();
            if (! empty($questions)) {
                $question = [];
                foreach ($questions as $ques) {
                    $question[] = $ques->asCustomJson(true);
                }

                $output = $this->renderPartial('/../../../views/research-program/download', [
                    'questions' => $question,
                    'research' => $research
                ]);
                $mpdf->WriteHTML($output);
                $data['status'] = self::API_OK;

                
                // $data['download-link']=$mpdf->output();
            $data['download link ']="https://asernv.org/site/download-data?id=".$research_id."";
          
                // $data['download view']=$output;
                $this->response=$data;

                // print_r($output);die();
                 // 
                 // $mpdf->output();
                // $mpdf->Output('myPdf.pdf', 'D');

                
            } else {
                $data['error'] = "No Questions available";
            }
        } else {
            $data['error'] = "Research Not Found";
              $this->response=$data;
        }
        
    }

    public function actionCheckAccessCode()
    {
        $data = [];
        \Yii::$app->response->format = 'json';
        $post = \Yii::$app->request->post();
        $research = ResearchProgram::find()->where([
            'assign_code' => $post['assign_code']
        ])->one();
        if (! empty($research)) {
            $data['error'] = "Code already exists";
        } else {
            $data['status'] = self::API_OK;
            $data['message'] = "good to go";
        }
        $this->response = $data;
    }
    
    public function actionAddParticipantResearch()
    {
        $model = new ResearchProgram();
        $data = [];
        $post = \Yii::$app->request->post();
        $flag = true;
        if (! empty($post)) {
            $is_exist = ResearchProgram::find()->where([
                'created_by_id' => \Yii::$app->user->id,
                'state_id' => ResearchProgram::STATE_ACTIVE,
                'type_id' => ResearchProgram::TYPE_PARTICIPANT
            ])->exists();
            if(!$is_exist){
                $db = \Yii::$app->db;
                $transaction = $db->beginTransaction();
                try {
                    $model->state_id = ResearchProgram::STATE_ACTIVE;
                    $model->type_id = ResearchProgram::TYPE_PARTICIPANT;
                    $model->scenario = "participant-research";
                    $researchProgram['ResearchProgram'] = (array) json_decode($post['main']['ResearchProgram']);
                    if ($model->load($researchProgram) && $model->save()) {
                        $questions = json_decode($post['main']['Surveyquestion']);
                        if (! empty($questions) || $model->picture_submission) {
                            foreach ($questions as $ques) {
                                if (($ques->type_id == Surveyquestion::TYPE_NONE && empty($ques->picture_submission))) {
                                    $data['error'] = \Yii::t('app', 'Please select an option');
                                    $flag = false;
                                    break;
                                }
                                $survey_ques = new Surveyquestion();
                                $survey_ques->question = $ques->question;
                                $survey_ques->type_id = $ques->type_id;
                                $survey_ques->state_id = Surveyquestion::STATE_ACTIVE;
                                $survey_ques->research_id = $model->id;
                                $survey_ques->day = $ques->day;
                                $survey_ques->picture_submission = $ques->picture_submission;
                                if ($survey_ques->save()) {
                                    $questionDays = explode(',', $survey_ques->day);
                                    foreach ($questionDays as $questionDay) {
                                        $questionDayModel = new SurveyQuestionDay();
                                        $questionDayModel->day = date('Y-m-d', strtotime($model->start_date . ' +' . ($questionDay - 1) . ' day'));
                                        $questionDayModel->state_id = SurveyQuestionDay::STATE_ACTIVE;
                                        $questionDayModel->research_id = $model->id;
                                        $questionDayModel->survey_question_id = $survey_ques->id;
                                        if (! $questionDayModel->save()) {
                                            $data['error'] = $questionDayModel->getErrorsString();
                                            $flag = false;
                                            break;
                                        }
                                    }
                                    foreach ($ques->option as $option) {
                                        $ques_option = new Surveyquestionoption();
                                        $ques_option->question_id = $survey_ques->id;
                                        $ques_option->option = $option->title;
                                        $ques_option->is_answer = $option->ans;
                                        $ques_option->state_id = Surveyquestionoption::STATE_ACTIVE;
                                        $ques_option->type_id = $ques->type_id;
                                        if (! $ques_option->save()) {
                                            $flag = false;
                                            break;
                                        }
                                    }
                                    if (! $flag) {
                                        break;
                                    }
                                } else {
                                    $flag = false;
                                    $data['error'] = $survey_ques->getErrors();
                                    break;
                                }
                            }
                            if (! $flag) {
                                $transaction->rollBack();
                            } else {
                                $transaction->commit();
                                $data['status'] = self::API_OK;
                                $data['message'] = "Data Saved Succesfully";
                            }
                        } else {
                            $transaction->rollBack();
                            $data['error'] = \Yii::t('app', 'No questions posted');
                        }
                    } else {
                        $data['error'] = $model->getErrorsString();
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    throw $e;
                }
            }else{
                $data['error'] = \Yii::t('app', 'You already have 1 active research');
            }
        } else {
            $data['error'] = \Yii::t('app', 'No Data Posted');
        }
        $this->response = $data;
    }
    
    public function actionSetView($research_id)
    {
        $data = [];
        \Yii::$app->response->format = 'json';
        $research = ResearchProgram::find()->where([
            'id' => $research_id
        ])->one();
        if(!empty($research)){
            $userProgram = Userprogram::find()->where([
                'research_id' => $research_id,
                'created_by_id' => \Yii::$app->user->id
            ])->one();
            if (! empty($userProgram)) {
                $userProgram->is_viewed = Userprogram::TYPE_VIEWED;
                if($userProgram->save()) {
                    $now = time();
                    $your_date = strtotime($research->start_date);
                    $days = floor(($now - $your_date) / (60 * 60 * 24));
                    $data['remaining_days'] = $days;
                    $data['status'] = self::API_OK;
                }
            } else {
                $data['error'] = "You are not allowed to perform this action";
            }
        }else{
            $data['error'] = "Research does not exist";
        }
        $this->response = $data;
    }

}

?>
