<?php
use app\assets\AppAsset;
use app\components\FlashMessage;
use app\models\HomeSections;
use app\models\Slider;
use app\models\User;
use app\models\search\Contactdetail;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);
?>
<?php $this->beginPage()?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport"
	content="width=device-width,initial-scale=1,maximum-scale=1">
<meta charset="<?= Yii::$app->charset ?>" />
    <?= Html::csrfMetaTags()?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head()?>
    <link rel="stylesheet"
	href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<link href="https://fonts.googleapis.com/css?family=Poppins"
	rel="stylesheet">
<link href="<?php echo $this->theme->getUrl('css-new/style.css')?>"
	rel="stylesheet">
<link href="<?php echo $this->theme->getUrl('css-new/contact.css')?>"
	rel="stylesheet">
<link href="<?php echo $this->theme->getUrl('css-new/single.css')?>"
	rel="stylesheet">
<link
	href="<?php echo $this->theme->getUrl('css-new/fontawesome-all.css')?>"
	rel="stylesheet">
<link
	href="<?php echo $this->theme->getUrl('css-new/prettyPhoto.css')?>"
	rel="stylesheet">
<link href="<?php echo $this->theme->getUrl('css-new/owl.theme.css')?>"
	rel="stylesheet">
<link href="<?php echo $this->theme->getUrl('css-new/bootstrap.css')?>"
	rel="stylesheet">
<link
	href="<?php echo $this->theme->getUrl('css-new/owl.carousel.css')?>"
	rel="stylesheet">
<link href="<?php echo $this->theme->getUrl('css/font-awesome.css')?>"
	rel="stylesheet">
<script
	src="<?php echo $this->theme->getUrl('js-new/pretty-script.js')?>"></script>
<script src="<?php echo $this->theme->getUrl('js-new/move-top.js')?>"></script>
<script
	src="<?php echo $this->theme->getUrl('js-new/jquery-2.2.3.min.js')?>"></script>
<script
	src="<?php echo $this->theme->getUrl('js-new/jquery.quicksand.js')?>"></script>
<script
	src="<?php echo $this->theme->getUrl('js-new/jquery.prettyPhoto.js')?>"></script>
<script src="<?php echo $this->theme->getUrl('js-new/easing.js')?>"></script>
<script src="<?php echo $this->theme->getUrl('js-new/bootstrap.js')?>"></script>
<script
	src="<?php echo $this->theme->getUrl('js-new/owl.carousel.js')?>"></script>

<script>
    $(document).ready(function() {
        $('.owl-carousel').owlCarousel({
            loop: true,
            margin: 10,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 1,
                    nav: true
                },
                600: {
                    items: 1,
                    nav: false
                },
                900: {
                    items: 1,
                    nav: false
                },
                1000: {
                    items: 3,
                    nav: true,
                    loop: false,
                    margin: 20
                }
            }
        })
    })
    </script>

</head>

<body class="sticky-header theme-<?= Yii::$app->view->theme->style ?>">
	<?php $this->beginBody()?>
    <div class="clearfix"></div>
	<div class="main_wrapper">
		<?php
$contactData = Contactdetail::find()->one();

if (Yii::$app->controller->action->id == "index") {
    ?>
 			<div class="mian-content-wthree">
			<header>
				<div class="container">
					<div class="top-head-w3ls text-left">
						<div class="row top-content-info">
							<div class="col-lg-5 top-content-left"></div>
							<div class="col-lg-7 top-content-right">
								<div class="row">
									<div class="col-md-12 top-social-icons p-0">
										<div class="logo pull-right">
											<a class="navbar-brand" href="<?=Url::toRoute(['/'])?>"> <img
												src="<?php echo $this->theme->getUrl('img/web_logo.png') ?>">
											</a>
										</div>

									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="clearfix"></div>
					<nav class="navbar navbar-expand-lg navbar-light">
						<div class="logo text-left fullscreen">
							<a class="navbar-brand" href="<?=Url::toRoute(['/'])?>"> <img
								src="<?php echo $this->theme->getUrl('img/web_logo.png') ?>">
							</a>
						</div>
						<button class="navbar-toggler" type="button"
							data-toggle="collapse" data-target="#navbarSupportedContent"
							aria-controls="navbarSupportedContent" aria-expanded="false"
							aria-label="Toggle navigation">
							<span class="navbar-toggler-icon"> </span>
						</button>
						<div class="collapse navbar-collapse" id="navbarSupportedContent">
							<ul class="navbar-nav ml-lg-auto text-right">
								<li class="nav-item active"><a class="nav-link"
									href="<?=Url::toRoute(['/'])?>">Home</a></li>
								<li class="nav-item"><a class="nav-link"
									href="<?=Url::toRoute(['/site/about'])?>">About</a></li>
								<li class="nav-item"><a class="nav-link "
									href="<?=Url::toRoute(['/site/services'])?>">Services</a></li>
								<li class="nav-item"><a href="<?=Url::toRoute(['/site/irb'])?>"
									class="nav-link">IRB</a></li>
								<li class="nav-item"><a
									href="<?=Url::toRoute(['/site/healthnbeautify'])?>"
									class="nav-link">Health & Beauty</a></li>
								<li class="nav-item"><a
									href="<?=Url::toRoute(['/site/certifications'])?>"
									class="nav-link">Certification</a></li>
								<li class="nav-item"><a
									href="<?=Url::toRoute(['/site/dietary'])?>" class="nav-link">Dietary
										Product Info</a></li>
								<li class="nav-item"><a href="<?=Url::toRoute(['/site/news'])?>"
									class="nav-link">News & Events</a></li>
								<li class="nav-item"><a
									href="<?=Url::toRoute(['/site/subscription-plans'])?>"
									class="nav-link">Subscription Plans</a></li>
							<!-- 	<li class="nav-item"><a
									href="<?=Url::toRoute(['/site/contact'])?>" class="nav-link">Contact
										Us</a></li> -->
									<li class="nav-item dropdown">
       								<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Accounts</a>
       								<div class="dropdown-menu" aria-labelledby="navbarDropdown">
         							 <a class="dropdown-item" href="<?=Url::toRoute(['/site/signupdoctor'])?>">Doctor</a>
          							<a class="dropdown-item" href="<?=Url::toRoute(['/site/signuppatient'])?>">Patient</a>				
          							<a class="dropdown-item" href="<?=Url::toRoute(['/site/signup'])?>">Participant</a>				
          							<a class="dropdown-item" href="<?=Url::toRoute(['/site/signupadmin'])?>">Administrator</a>		
       								 </div>
     								 </li>
     								  <li class="nav-item dropdown">
       								<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Login</a>
       								<div class="dropdown-menu" aria-labelledby="navbarDropdown">
         							 <a class="dropdown-item" href="<?=Url::toRoute(['/user/doctor-login'])?>">Doctor</a>
          							<a class="dropdown-item" href="<?=Url::toRoute(['/user/patient-login'])?>">Patient</a>				
          							<a class="dropdown-item" href="<?=Url::toRoute(['/user/user-login'])?>">Participant</a>				
          							<a class="dropdown-item" href="<?=Url::toRoute(['/user/admin-login'])?>">Administrator</a>			
       								 </div>
     								 </li>
    					            <?php if(User::isGuest()){?>
    									<!-- <li class="nav-item"><a
									
									</li> -->
							
                                    <?php }else{?>
                                        <li class="dropdown nav-item"><a
									class="dropdown-toggle nav-link" data-toggle="dropdown">Profile</a>
									<ul class="dropdown-menu">
												<?php if(User::isManager()){?>
													<li class="nav-item"><a
											href="<?=Url::toRoute(['/user/profile'])?>" class="nav-link">My
												Profile</a></li>
    												<li class="nav-item"><a
											href="<?=Url::toRoute(['/research-program/research-list'])?>"
											class="nav-link">My Research</a></li>
										<li class="nav-item"><a
											href="<?=Url::toRoute(['/research-program/shared-research-list'])?>"
											class="nav-link">Shared Research</a></li>
										<li class="nav-item"><a href="<?=Url::toRoute(['/research-program/research-history'])?>" class="nav-link">Research
												History</a></li>
    											<?php  }
    											if(User::isDoctor()){?>
    												<li class="nav-item"><a
											href="<?=Url::toRoute(['/user/profile'])?>" class="nav-link">My
												Profile</a></li>
												<li class="nav-item"><a
											href="<?=Url::toRoute(['/user/patient-list'])?>" class="nav-link">Patient List
												</a></li>

    											<?php }
    											if(User::isPatient()){?>

    												<li class="nav-item"><a
											href="<?=Url::toRoute(['/user/profile'])?>" class="nav-link">My
												Profile</a></li>
												<li class="nav-item"><a
											href="#" class="nav-link">Appointments
												</a></li>
												<li class="nav-item"><a
											href="#" class="nav-link">Prescribed Activities
												</a></li>
												<li class="nav-item"><a
											href="#" class="nav-link">My Reports
												</a></li>
											

    											<?php }
                                                    if (User::isUser()) {
                                                    ?>
                                                    <li class="nav-item"><a
											href="<?=Url::toRoute(['/user/profile'])?>" class="nav-link">My
												Profile</a></li>
    									
    											<li class="nav-item"><a href="<?=Url::toRoute(['/research-program/current-survey'])?>" class="nav-link">Current
												Survey</a></li>
										<li class="nav-item"><a
											href="<?=Url::toRoute(['/research-program/view-survey'])?>"
											class="nav-link">View Survey</a></li>
										<li class="nav-item"><a
											href="<?=Url::toRoute(['/research-program/research-list'])?>"
											class="nav-link">Quick Product Review</a></li>
										<li class="nav-item"><a
											href="<?=Url::toRoute(['/research-program/notification'])?>"
											class="nav-link">Notifications</a></li>
										<li class="nav-item"><a href="<?=Url::toRoute(['/research-program/gallery-images'])?>" class="nav-link">Gallery</a></li>
										<li class="nav-item"><a href="#" class="nav-link">Research History</a></li>
    											<?php } ?>
    											<li class="nav-item"><a
											href="<?=Url::toRoute(['/user/logout'])?>" class="nav-link">Logout</a>
										</li>
									</ul></li>
                                    <?php } ?>
								</ul>
						</div>
					</nav>
				</div>
			</header>
    			<?php $slides = Slider::find()->all(); ?>
				<div id="myCarousel" class="carousel slide" data-ride="carousel">
				<ol class="carousel-indicators">
    					<?php
                         foreach ($slides as $key => $slide) {
                        ?>
        					<li data-target="#myCarousel" data-slide-to="<?= $key ?>"
						class="<?php echo $key == 0 ? 'active' : '' ?>"></li>
    					<?php } ?>
    				</ol>
				<div class="carousel-inner">
                  		<?php
                          foreach ($slides as $key => $slide) {
                          ?>
                        	<div
						class="item <?php echo $key == 0 ? 'active' : '' ?>">
						<div class="inner-image">
							<div class="overlay-top"></div>
                             		<?= $slide->displayImage($slide->image,['class'=>'img-responsive'])  ?>
                        		</div>
						<div class="carousel-caption">
							<div class="slide-caption">
								<div id="table">
									<div id="centeralign">
										<h3><?php echo $slide->title; ?></h3>
									</div>
								</div>
								<div class="cont-btn">
									<a class="btn text-uppercase" href="#">About</a> <a
										class="btn active text-uppercase" href="#">Contact</a>
								</div>
							</div>
						</div>
					</div>
        				<?php } ?>
                  	</div>
				<a class="left carousel-control" href="#myCarousel"
					data-slide="prev"> <span class="glyphicon glyphicon-chevron-left"></span>
					<span class="sr-only">Previous</span>
				</a> <a class="right carousel-control" href="#myCarousel"
					data-slide="next"> <span class="glyphicon glyphicon-chevron-right"></span>
					<span class="sr-only">Next</span>
				</a>
			</div>
		</div>
		<?php } else { ?>
			<div class="mian-content-wthree inner">
			<div class="container">
				<header>
					<div class="top-head-w3ls text-left">
						<div class="row top-content-info">
							<div class="col-lg-5 top-content-left"></div>
							<div class="col-lg-7 top-content-right">
								<div class="row">
									<div class="col-md-12 top-social-icons p-0">
										<div class="logo pull-right">
											<a class="navbar-brand" href="<?=Url::toRoute(['/'])?>"> <img
												src="<?php echo $this->theme->getUrl('img/web_logo.png') ?>">
											</a>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="clearfix"></div>
					<nav class="navbar navbar-expand-lg navbar-light">
						<div class="logo text-left fullscreen">
							<a class="navbar-brand" href="/aser-web-983/"> <img
								src="/aser-web-983/themes/base/img/web_logo.png">
							</a>
						</div>
						<button class="navbar-toggler" type="button"
							data-toggle="collapse" data-target="#navbarSupportedContent"
							aria-controls="navbarSupportedContent" aria-expanded="false"
							aria-label="Toggle navigation">
							<span class="navbar-toggler-icon"> </span>
						</button>
						<div class="collapse navbar-collapse" id="navbarSupportedContent">
							<ul class="navbar-nav ml-lg-auto text-right">
    								<?php $page = Yii::$app->controller->action->id; ?>

    								<li
									class="nav-item <?php if( $page == "index" )  { ?>active<?php } ?>">
									<a class="nav-link" href="<?=Url::toRoute(['/'])?>">Home</a>
								</li>
								<li
									class="nav-item <?php if( $page == "about" )  { ?>active<?php } ?>">
									<a class="nav-link" href="<?=Url::toRoute(['/site/about'])?>">About</a>
								</li>
								<li
									class="nav-item <?php if( $page =="services" )  { ?>active<?php } ?>">
									<a class="nav-link"
									href="<?=Url::toRoute(['/site/services'])?>">Services</a>
								</li>
								<li
									class="nav-item <?php if( $page =="irb" )  { ?>active<?php } ?>">
									<a href="<?=Url::toRoute(['/site/irb'])?>" class="nav-link">IRB</a>
								</li>
								<li
									class="nav-item <?php if( $page =="healthnbeautify" )  { ?>active<?php } ?>">
									<a href="<?=Url::toRoute(['/site/healthnbeautify'])?>"
									class="nav-link">Health & Beauty</a>
								</li>
								<li
									class="nav-item <?php if( $page =="certifications" )  { ?>active<?php } ?>">
									<a href="<?=Url::toRoute(['/site/certifications'])?>"
									class="nav-link">Certification</a>
								</li>
								<li
									class="nav-item <?php if( $page =="dietary" )  { ?>active<?php } ?>">
									<a href="<?=Url::toRoute(['/site/dietary'])?>" class="nav-link">Dietary
										Product Info</a>
								</li>
								<li
									class="nav-item <?php if( $page =="news" )  { ?>active<?php } ?>">
									<a href="<?=Url::toRoute(['/site/news'])?>" class="nav-link">News
										& Events</a>
								</li>
								<li
									class="nav-item <?php if( $page =="news" )  { ?>active<?php } ?>">
									<a href="<?=Url::toRoute(['/site/subscription-plans'])?>"
									class="nav-link">Subscription Plans</a>
								</li>
							<!-- 	<li
									class="nav-item <?php if( $page =="contact" )  { ?>active<?php } ?>">
									<a href="<?=Url::toRoute(['/site/contact'])?>" class="nav-link">Contact
										Us</a>
								</li> -->
										<li class="nav-item dropdown">
       								<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Accounts</a>
       								<div class="dropdown-menu" aria-labelledby="navbarDropdown">
         							 <a class="dropdown-item" href="<?=Url::toRoute(['/site/signupdoctor'])?>">Doctor</a>
          							<a class="dropdown-item" href="<?=Url::toRoute(['/site/signuppatient'])?>">Patient</a>				
          							<a class="dropdown-item" href="<?=Url::toRoute(['/site/signup'])?>">Participant</a>				
          							<a class="dropdown-item" href="<?=Url::toRoute(['/site/signupadmin'])?>">Administrator</a>			
       								 </div>
     								 </li>
     								 <li class="nav-item dropdown">
       								<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Login</a>
       								<div class="dropdown-menu" aria-labelledby="navbarDropdown">
         							 <a class="dropdown-item" href="<?=Url::toRoute(['/user/doctor-login'])?>">Doctor</a>
          							<a class="dropdown-item" href="<?=Url::toRoute(['/user/patient-login'])?>">Patient</a>				
          							<a class="dropdown-item" href="<?=Url::toRoute(['/user/user-login'])?>">Participant</a>				
          							<a class="dropdown-item" href="<?=Url::toRoute(['/user/admin-login'])?>">Administrator</a>			
       								 </div>
     								 </li>

    								<?php if(User::isGuest()){?>
    									<!-- <li class="nav-item"><a
									href="<?=Url::toRoute(['/user/login'])?>" class="nav-link">Login</a>
								</li> -->
                                    <?php }else{?>
                                        <li class="dropdown nav-item"><a
									class="dropdown-toggle nav-link" data-toggle="dropdown">Profile</a>
									<ul class="dropdown-menu">
												<?php if(User::isManager()){?>
    									<li class="nav-item"><a
											href="<?=Url::toRoute(['/user/profile'])?>" class="nav-link">My
												Profile</a></li>
    									
    										<li class="nav-item"><a
											href="<?=Url::toRoute(['/research-program/research-list'])?>"
											class="nav-link">My Research</a></li>
										<li class="nav-item"><a
											href="<?=Url::toRoute(['/research-program/shared-research-list'])?>"
											class="nav-link">Shared Research</a></li>
										<li class="nav-item"><a href="<?=Url::toRoute(['/research-program/research-history'])?>" class="nav-link">Research
												History</a></li>
    											<?php  }
    												if(User::isDoctor()){?>
    											<li class="nav-item"><a
											href="<?=Url::toRoute(['/user/profile'])?>" class="nav-link">My
												Profile</a></li>
												<li class="nav-item"><a
											href="<?=Url::toRoute(['/user/patient-list'])?>" class="nav-link">Patient List
												</a></li>

    												<?php }

    												if(User::isPatient()){?>
    													<li class="nav-item"><a
											href="<?=Url::toRoute(['/user/profile'])?>" class="nav-link">My
												Profile</a></li>
												<li class="nav-item"><a
											href="#" class="nav-link">Appointments
												</a></li>
												<li class="nav-item"><a
											href="#" class="nav-link">Prescribed Activities
												</a></li>
												<li class="nav-item"><a
											href="#" class="nav-link">My Reports
												</a></li>
												

    												<?php }
                                                    if (User::isUser()) {
                                                    ?>
                                                    <li class="nav-item"><a
											href="<?=Url::toRoute(['/user/profile'])?>" class="nav-link">My
												Profile</a></li>
    									
    											<li class="nav-item"><a href="<?=Url::toRoute(['/research-program/current-survey'])?>" class="nav-link">Current
												Survey</a></li>
										<li class="nav-item"><a
											href="<?=Url::toRoute(['/research-program/view-survey'])?>"
											class="nav-link">View Survey</a></li>
										<li class="nav-item"><a
											href="<?=Url::toRoute(['/research-program/research-list'])?>"
											class="nav-link">Quick Product Review</a></li>
										<li class="nav-item"><a
											href="<?=Url::toRoute(['/research-program/notification'])?>"
											class="nav-link">Notifications</a></li>
										<li class="nav-item"><a href="<?=Url::toRoute(['/research-program/gallery-images'])?>" class="nav-link">Gallery</a></li>
										<li class="nav-item"><a href="#" class="nav-link">Research History</a></li>
    											<?php } ?>
    											<li class="nav-item"><a
											href="<?=Url::toRoute(['/user/logout'])?>" class="nav-link">Logout</a>
										</li>
									</ul></li>
                                    <?php } ?>
    							</ul>
						</div>
					</nav>
				</header>
			</div>
		</div>
		<div class="site-about">
			<div class="list-bread">
				<!-- /breadcrumb -->
				<ol class="breadcrumb">
					<li class="breadcrumb-item"><a href="<?=Url::toRoute(['/'])?>">Home</a>
					</li>
					<li class="breadcrumb-item active"><?php echo ucwords(str_replace("-"," ", Yii::$app->controller->action->id))?></li>
				</ol>
			</div>
		</div>
		<?php } ?>
        <?= FlashMessage::widget () ?>
        <section class="inner_page">
        	<?= $content ?>
        </section>
		<!--body wrapper end-->
		<section class="newsletter-section">
			<div class="container">
				<div class="newsletter-inner mb-3">
					<h3 class="tittle-w3ls foot mb-lg-4 mb-3">Join our mailing list</h3>
					<div class="newsright mb-lg-4 mb-3">
						<input class="form-control" type="email" name="email" required> <input
							class="form-control" type="submit" value="Join">
						<div class="clearfix"></div>
					</div>
				</div>
			</div>
		</section>
		<footer class="footer-emp-w3ls py-md-5 py-4">
			<div class="container">
				<div class="row footer-top mt-lg-5">
					<div class="col-lg-4 footer-grid-wthree">
						<div class="footer-title">
							<h3>About Us</h3>
						</div>
						<div class="footer-text">
                       		<?php $learntext = HomeSections::find()->where(['section' => 'about-us'])->one();  ?>
                       		<?php  if (!empty($learntext) && ! empty($learntext->description)) { ?>
                           		<p><?php echo substr($learntext->description,0,239); ?></p>
                           	<?php } ?>
							<ul class="social-icons footer-icons d-flex mt-3">
								<li class="mr-1"><a
									href="<?php  if (!empty($contactData) && ! empty($contactData->fb_link)) {   echo $contactData->fb_link; } ?>"
									target="_blank"><span class="fab fa-facebook-f"></span></a></li>
								<li class="mx-1"><a
									href="<?php  if (!empty($contactData) && ! empty($contactData->twitter_link)) {   echo $contactData->twitter_link; } ?>"
									target="_blank"><span class="fab fa-twitter"></span></a></li>
								<li class="mx-1"><a
									href="<?php  if (!empty($contactData) && ! empty($contactData->google_plus)) {   echo $contactData->google_plus; } ?>"
									target="_blank"><span class="fab fa-instagram"></span></a></li>
								<li class="mx-1"><a
									href="<?php  if (!empty($contactData) && ! empty($contactData->you_tube)) {  echo $contactData->you_tube; } ?>"
									target="_blank"><span class="fab fa-youtube"></span></a></li>
							</ul>
						</div>
					</div>
					<div class="col-lg-5 footer-grid-wthree">
						<div class="footer-title">
							<h3>Get in touch</h3>
						</div>
						<div class="contact-info row">
							<div class="col-lg-6">
								<h4>HQ Mailing Address :</h4>
                    			<?php  if (!empty($contactData) && ! empty($contactData->hq_mailing_address)) {   echo $contactData->hq_mailing_address; } ?>
                                <br>
								<h4>Intern Training & Research Address:</h4> 
                          		<?php  if (!empty($contactData) && ! empty($contactData->intern_training_address)) {    echo $contactData->intern_training_address; } ?>
                        	</div>
							<div class="col-lg-6">
								<h4>San Francisco Admin Support Address:</h4>
                         		<?php  echo isset($contactData->san_francisco_admin_support_address)?$contactData->san_francisco_admin_support_address:''; ?>
                        		<div class="phone">
									<h4>Contact :</h4>
									<p>
										Phone : <a
											href="tel: <?php  if (!empty($contactData) && ! empty($contactData->office_phone)) {    echo $contactData->office_phone; } ?>"> <?php if (!empty($contactData) && ! empty($contactData->email)) {    echo $contactData->office_phone; } ?></a>
									</p>
									<p>
										Email : <a
											href="mailto: <?php if (!empty($contactData) && ! empty($contactData->email)) {     echo $contactData->email; } ?>"><?php if (!empty($contactData) && ! empty($contactData->email)) {    echo $contactData->email; } ?> </a>
									</p>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-3 footer-grid-wthree">
						<div class="footer-title">
							<h3>Quick Links</h3>
						</div>
						<ul class="links">
							<li><a href="<?=Url::toRoute(['/'])?>">Home</a></li>
							<li><a href="<?=Url::toRoute(['/site/about'])?>">About</a></li>
							<li><a href="<?=Url::toRoute(['/site/services'])?>">Services</a></li>
							<li><a href="<?=Url::toRoute(['/site/certifications'])?>">Certification</a></li>
							<li><a href="<?=Url::toRoute(['/site/dietary'])?>">Dietary
									Product Info</a></li>
						</ul>
						<ul class="links">
							<li><a href="<?=Url::toRoute(['/site/irb'])?>">IRB</a></li>
							<li><a href="<?=Url::toRoute(['/site/healthnbeautify'])?>">Health
									& beauty</a></li>
							<li><a href="<?=Url::toRoute(['/site/news'])?>">News & Events</a></li>
							<li><a href="<?=Url::toRoute(['/site/contact'])?>">Contact Us</a></li>
							<li><a href="<?=Url::toRoute(['/site/privacy'])?>">Privacy Policy</a></li>
						</ul>
						<!-- <ul class="links full-width">
						</ul> -->
					</div>
				</div>
				<div class="clearfix"></div>
			</div>
		</footer>
		<div class="copyright-w3ls">
			<p>&copy; <?php echo date('Y')?>  <?= Yii::$app->name;?>  | All Rights Reserved	</p>
		</div>
	</div>
<?php $this->endBody()?>
</body>
<?php $this->endPage()?>
</html>
