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
						<h5>Survey Answer</h5>
    					
<table class="table">
  <thead>
    <tr>
     
      <th scope="col">Question</th>
      <th scope="col">Answer</th>
    </tr>
  </thead>
  <tbody> 
      <?php foreach ($answer as $value) {?>s
      <tr>  
      <?php //echo $value['id'];?>
      <td> <?php echo $value['answer'];?></td>
       <?php foreach ($value['surveyansweroptions'] as $value) {?>
          <?php foreach($value['Option'] as $val){?>
        <td><?php print_r($value['Option']->option);?></td>
        
       
<?php }?>
  
    <?php }?>
 </td>

  </tr>

  <?php }?>
  </tbody>
</table>                           
   
        			</div>
				</div>
			</div>
		</div>
	<?php } ?>
</section>