<?php
use app\components\TActiveForm;
use app\models\Surveyquestion;
use yii\helpers\Html;
?>

<section class="banner-bottom-w3-agile py-lg-5 py-md-5 py-3">
	<div class="container">
		<div class="inner-sec-w3ls py-md-5 py-3">
			<h3 class="tittle-w3ls text-center mb-md-5 mb-4"><?php echo ucwords(str_replace("-"," ", Yii::$app->controller->action->id))?></h3>
		</div>
	</div>
	<div class="container">
		<br>
		<?php
        if (! empty($model)) { ?>
    		<div class="row">
    			<div class="col-lg-12">
    				<div class="card listcard">
    					<div class="card-header">
    						<h4><?=ucfirst($model->product_name)?></h4>
    					</div>
						<h5>Survey Questions</h5>
    					<?php
    					$form = TActiveForm::begin(['action' => ['research-program/save-answer'],'options' => ['enctype' => 'multipart/form-data'],'enableAjaxValidation' => false]);
                            //$models = $dataProvider->getModels();
                            foreach ($questions as $question) { ?>

								<div class="card-body">
                                    <h5>Q.<?= ucfirst($question->question)?></h5>
                                    
                                    <?= $form->field($surveyAnswer, 'research_id')->hiddenInput(['value' => $question->research_id])->label(false); ?>
                                    
                                    <?= $form->field($surveyAnswer, 'question_id[]')->hiddenInput(['value' => $question->id])->label(false); ?>
                                    
                                    <?php if($question->type_id == Surveyquestion::TYPE_CHECK){ // question type multiple select ?>
                                    
    									<?php foreach ($question->options as $srNo => $option){ ?>
    									
                                    <?= $form->field($answerOption, 'option_id['.$question->id.'][]')->checkbox(['value' => $option->id])->label(false); ?>
                                            
                                            <p><b> (<?= $srNo + 1 ?>) <?= ucfirst($option->option)?></b></p>
                                            
                                        <?php } ?>
                                       
                                  		<?php  }else if($question->type_id == Surveyquestion::TYPE_RADIO) { // question type radio ?>
                                    
    									<?php foreach ($question->options as $srNo=>$option){ ?>
    									
                                            <?= $form->field($answerOption, 'option_id['.$question->id.'][]')->radio(['value' => $option->id])->label(false); ?>
                                            
                                            <p><b> (<?=$srNo+1?>) <?= ucfirst($option->option)?></b></p>
                                            
                                        <?php }
                                        
                                    } else if($question->type_id == Surveyquestion::TYPE_TEXT){ // question type text ?>
                                    
                                        <?= $form->field($surveyAnswer, 'answer')->textInput()->label(false); ?>
                                        
                                    <?php } ?>
                                    <!-- Picture Submission -->
                                    <?php if($question->picture_submission){ ?>
                                    
                                        <?= $form->field($question, 'image[]')->fileInput() ?>
                                        
                                    <?php } ?>
                                    
                                </div>
                                
                            <?php }?>
                    		<div class="col-md-12 log-in">
								 <?= Html::submitButton('Submit', ['class' => 'btn mainbtn text-uppercase pull-right']) ?>
							</div>
                         <?php TActiveForm::end();?>
        			</div>
				</div>
			</div>
		</div>
	<?php } ?>
</section>