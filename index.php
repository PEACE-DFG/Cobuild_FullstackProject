<?php
ob_start();
session_start();
?>
<!DOCTYPE HTML>
<html>
	<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Cobuild</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
  	<!-- Facebook and Twitter integration -->
	<meta property="og:title" content=""/>
	<meta property="og:image" content=""/>
	<meta property="og:url" content=""/>
	<meta property="og:site_name" content=""/>
	<meta property="og:description" content=""/>
	<meta name="twitter:title" content="" />
	<meta name="twitter:image" content="" />
	<meta name="twitter:url" content="" />
	<meta name="twitter:card" content="" />
	
	<!-- Animate.css -->
	<link rel="stylesheet" href="css/animate.css">
	<!-- Icomoon Icon Fonts-->
	<link rel="stylesheet" href="css/icomoon.css">
	<!-- Themify Icons-->
	<link rel="stylesheet" href="css/themify-icons.css">
	<!-- Bootstrap  -->
	<link rel="stylesheet" href="css/bootstrap.css">
	<!-- Magnific Popup -->
	<link rel="stylesheet" href="css/magnific-popup.css">
	<!-- Owl Carousel  -->
	<link rel="stylesheet" href="css/owl.carousel.min.css">
	<link rel="stylesheet" href="css/owl.theme.default.min.css">
	<!-- Flexslider -->
	<link rel="stylesheet" href="css/flexslider.css">
	<!-- Theme style  -->
	<link rel="stylesheet" href="css/style.css">

	<!-- Modernizr JS -->
	<script src="js/modernizr-2.6.2.min.js"></script>

	<!-- fontawesme cdn -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
	<!-- FOR IE9 below -->
	<!--[if lt IE 9]>
	<script src="js/respond.min.js"></script>
	<![endif]-->
	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3.10.0/dist/email.min.js"></script>


	<style>
		.icon {
			font-size: 70px;
			color: #040b90;
	}
	h3 {
			margin-top: 20px;
	}
/* Accordion Container */
#contain {
	max-width: 900px;
	margin: auto;
	padding: 20px;
}

/* Accordion Item */
.accordion-item {
	border: 1px solid #ddd;
	border-radius: 5px;
	margin-bottom: 10px;
	overflow: hidden;
	background-color: #fff;
	box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

/* Accordion Button */
.accordion-button {
	display: flex;
	justify-content: space-between;
	align-items: center;
	width: 100%;
	padding: 15px;
	background-color: #f8f9fa;
	color: #333;
	font-weight: bold;
	border: none;
	outline: none;
	cursor: pointer;
	transition: background-color 0.3s ease, color 0.3s ease;
	text-align: left;
}

.accordion-button:hover {
	background-color: #0044cc;
	color: white;
}

/* Rotate Icon when active */
.accordion-button i {
	transition: transform 0.3s ease;
}

/* Accordion Collapse */
.accordion-collapse {
	max-height: 0;
	overflow: hidden;
	transition: max-height 0.3s ease-out;
	background-color: #f1f1f1;
}

/* Accordion Body */
.accordion-body {
	padding: 15px;
	font-size: 16px;
	background-color: #fff;
	border-top: 1px solid #ddd;
	transition: background-color 0.3s ease;
}

/* Active Tab Styles */
.accordion-button.active {
	background-color: #0044cc;
	color: white;
}

.accordion-button.active i {
	transform: rotate(180deg);
}

.accordion-item.active .accordion-collapse {
	max-height: 200px; /* Adjust as per content */
}


	/* Message Display Styles */
	.message-display {
		margin-top: 20px;
		padding: 10px;
		background-color: #f1f1f1;
		border: 1px solid #ddd;
		font-size: 20px;
		color: #333;
	}
	.accordion-item h2{
		font-size: 18px;
		padding-top:5px;
	}

	</style>

	</head>
	<body>
		
	<div class="gtco-loader"></div>
	

	
	<div id="page">


<nav class="gtco-nav" role="navigation">
    <div class="container">
        <div class="row">
            <div class="col-sm-2 col-xs-12">
                <div id="gtco-logo">
                    <a href="index.html"><img src="images/Cobuild_logo.png" class="w-25 img-fluid" style="width: 135px;" alt=""></a>
                </div>
            </div>
            <div class="col-xs-10 text-right text-white menu-1 main-nav" style="color: white;">
                <ul>
                    <li class="active"><a href="#" data-nav-section="home">Home</a></li>
                    <li><a href="#" data-nav-section="about">About</a></li>
                    <li><a href="#" data-nav-section="practice-areas">Projects</a></li>
                    <li class="btn-cta"><a href="#" data-nav-section="contact"><span>FAQ</span></a></li>
                    <li><a href="#" data-nav-section="our-team">Contacts</a></li>

                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <!-- User is not logged in, show Sign In and Sign Up buttons -->
                        <li class="btn-cta" id="signInButton"><a href="#"><span>Sign In</span></a></li>
                        <li class="btn-cta" id="signUpButton"><a href="#"><span>Sign Up</span></a></li>
                        <li class="btn-cta"><a href="#" data-nav-section="user"><span>Invest</span></a></li>
                        <li class="btn-cta"><a href="#" data-nav-section="user"><span>Raise Funds</span></a></li>
                    <?php else: ?>
                        <!-- User is logged in, show user email with dropdown and logout option -->
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <?php echo $_SESSION['user_email']; ?> <span class="caret"></span>
                            </a>
                        </li>
												<li class="btn-cta">
														<a href="#" id="logoutButton"><span>Logout</span></a>
												</li>

                    <?php endif; ?>

                </ul>
            </div>
        </div>
    </div>
</nav>

<script>
    // Function to navigate to the login page
    document.getElementById('signInButton').addEventListener('click', function() {
        window.location.href = 'pages/authentication/user/login.php';
    });

    // Function to navigate to the registration page
    document.getElementById('signUpButton').addEventListener('click', function() {
        window.location.href = 'pages/authentication/user/register.php';
    });
</script>
<script>
document.getElementById('logoutButton').addEventListener('click', function(event) {
    event.preventDefault(); // Prevent the default link behavior

    // Display a confirmation dialog using SweetAlert
    Swal.fire({
        title: 'Are you sure?',
        text: "You will be logged out!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, log me out!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create a form to send POST request to logout.php
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'pages/authentication/user/logout.php';

            // Create a hidden input to signal logout confirmation
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'confirm_logout';
            input.value = 'yes';

            form.appendChild(input);
            document.body.appendChild(form);

            // Submit the form, logging out the user
            form.submit();
        }
    });
});
</script>



	<section id="gtco-hero" class="gtco-cover" data-section="home" data-stellar-background-ratio="0.5">
    <!-- Background Video -->
    <!-- <video autoplay muted loop playsinline id="background-video">
        <source src="images/cobuild_back1.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video> -->

    <!-- Overlay -->
    <div class="overlay"></div>

    <!-- Content -->
    <div class="container">
			
        <div class="row">
            <div class="col-md-12 col-md-offset-0 text-center">
                <div class="display-t">
                    <div class="display-tc">
                        <h3 class="animate-box text-light" style="color: white;" data-animate-effect="fadeIn">
                            Raise funds, invest & Trade investments
                        </h3>
                        <h1 class="animate-box" data-animate-effect="fadeIn">
                            Unlocking Abundance in real estate Development
                        </h1>
                        <p><a href="">
                            <button class="btn btn" style="font-weight: 900; padding: 20px;background-color:rgb(255, 196, 0);color:white;">
                                Get Started For Free
                            </button>
                        </a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<marquee behavior="" direction="" style="background-color: rgb(9, 9, 153); color:white;">
		
	Raise or invest skills, labor, materials and cash for your real estate projects
		</marquee>
	<section id="gtco-about" data-section="about">
		<div class="container">
		
			<div class="row row-pb-md ">
				<div class="col-md-8 col-md-offset-2 heading animate-box" data-animate-effect="fadeIn">
					<h1>About Us</h1>
					<p class="sub" style="
  color:  #0f1133;
					" >Cobuild is a leading global platform to raise funds, invest and trade investments in real estate projects at your fingertips. Our creative platform seeks to unlock abundance in real estate financing: offering democratized access to investors and fund raisers in form of building materials, land, building services, labor, sales and cash.</p>
					<p class="subtle-text animate-box" data-animate-effect="fadeIn">AboutUs</p>
				</div>
			</div>
			<div class="row">
				
				<div class="col-md-6 col-md-pull-1 animate-box" data-animate-effect="fadeInLeft">
					<div class="img-shadow">
						<!-- <video autoplay muted loop playsinline class="responsive-video">
							<source src="images/cobuild_2.mp4" type="video/mp4">
							Your browser does not support the video tag.
					</video> -->
					
						<img src="images/side2.jpg" class="img-responsive" alt="">
					</div>
				</div>
				<div class="col-md-6 animate-box pt-3" data-animate-effect="fadeInLeft">
					<!-- <h2 class="heading-colored">Excellence &amp; Honesty</h2> -->
					<p style="text-align: justify;">Cobuild is a cutting-edge global platform revolutionizing real estate investment, fundraising, and trading. Our innovative platform empowers users to access and invest in real estate projects with ease, putting opportunities at their fingertips. Cobuild unlocks new avenues for financing, offering a democratized approach that benefits both investors and fundraisers. Whether through building materials, land, construction services, labor, sales, or cash, we aim to make real estate financing more accessible, transparent, and efficient for everyone. Join us in reshaping the future of real estate investment with a platform designed to unlock abundance for all.</p>
					<p><a href="#" class="read-more">Join Us Today <i class="icon-chevron-right"></i></a></p>
				</div>
			</div>
		</div>
	</section>
	<section id="gtco-our-team" data-section="">
		<div class="container">
			<div class="row row-pb-md">
				<div class="col-md-8 col-md-offset-2 heading animate-box" data-animate-effect="fadeIn">
					<!-- <h1>Our Team</h1> -->
					<!-- <p class="sub">Dignissimos asperiores vitae velit veniam totam fuga molestias accusamus alias autem provident. Odit ab aliquam dolor eius.</p> -->
					<p class="subtle-text animate-box" data-animate-effect="fadeIn">invest&raisefunds</p>
				</div>
			</div>
			<div class="row team-item gtco-team-reverse container">
				<div class="col-md-6 col-md-push-7 animate-box" data-animate-effect="fadeInRight">
					<div >
						<div class="card text-center" >
							<video autoplay muted loop playsinline class="responsive-video">
								<source src="images/fundraising.mp4" type="video/mp4">
								Your browser does not support the video tag.
						</video>
							<!-- <img src="images/rfs.png" class="card-img-top img-fluid" style="height: 150px;" alt="..."> -->
							<div class="card-body">
								<h2 class="card-title mt-4" style="padding-top: 20px;">Raise Funds</h2>
								<p class="card-text">Funds shouldn't stop you from
									developing a project.
									Find an investor  who believes
									in your dreams</p>
									<button class="btn btn-secondary"><a href="#" class="read-more">Read More <i class="icon-chevron-right"></i></a></button>

							</div>
						</div>
						<!-- <img src="images/img_team_1.jpg" class="img-responsive" alt="Free HTML5 Bootstrap Template by FreeHTML5.co"> -->
					</div>
				</div>

				<div class="col-md-6 col-md-pull-7 animate-box" data-animate-effect="fadeInRight">
					<div >
						<div class="card text-center" >
							<video autoplay muted loop playsinline class="responsive-video">
								<source src="images/invest.mp4" type="video/mp4">
								Your browser does not support the video tag.
						</video>
							<!-- <img src="images/invest.jpg" class="card-img-top img-fluid" style="height: 150px;" alt="..."> -->
							<div class="card-body">
								<h2 class="card-title mt-4 " style="padding-top: 20px;">Invest</h2>
								<p class="card-text">Invest skills, Labour, Building
									materials or funds
									in a project you believe in
									Build a future</p>

									<button class="btn btn-secondary"><a href="#" class="read-more">Read More <i class="icon-chevron-right"></i></a></button>


							</div>
						</div>
						<!-- <img src="images/img_team_1.jpg" class="img-responsive" alt="Free HTML5 Bootstrap Template by FreeHTML5.co"> -->
					</div>
				</div>
				
			</div>

		</div>
	</section>


	<section id="gtco-practice-areas" data-section="practice-areas">
		<div class="container">
			<div class="row row-pb-md">
				<div class="col-md-8 col-md-offset-2 heading animate-box" data-animate-effect="fadeIn">
					<h1>Featured Investment Projects</h1>
					<!-- <p class="sub">Dignissimos asperiores vitae velit veniam totam fuga molestias accusamus alias autem provident. Odit ab aliquam dolor eius.</p> -->
					<p class="subtle-text animate-box" data-animate-effect="fadeIn">Featured  <span>Projects</span></p>
				</div>
			</div>
			<div class="row ">
				<div class="col-lg-4 ">
					<div class="gtco-practice-area-item animate-box">
						<div class="card" style="width: 35rem;box-shadow:5px 5px 3px grey;border-radius:25px;margin: auto;">
							<img src="images/project.jpg" class="card-img-top img-fluid " style="max-width: 350px; border-radius:10px;" alt="...">
							<div class="card-body text-center pt-2">
								<h3 class="card-title" style="padding-top:10px">Investment Raised: N200,000,000</h3>
								<h4 class="card-text" style="padding-bottom:30px;color:rgb(5, 5, 158)">Project Tittle: Oluwanimi Resort</h4>
								<!-- <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p> -->
							</div>
						</div>
					</div>

				</div>
				<div class="col-lg-4">
					<div class="gtco-practice-area-item animate-box">
						<div class="card" style="width: 35rem;box-shadow:5px 5px 3px grey;border-radius:25px;margin: auto;">
							<img src="images/project.jpg" class="card-img-top img-fluid " style="max-width: 350px;border-radius:10px;" alt="...">
							<div class="card-body text-center pt-2">
								<h3 class="card-title" style="padding-top:10px">Investment Raised: N200,000,000</h3>
								<h4 class="card-text" style="padding-bottom:30px;color:rgb(5, 5, 158)">Project Tittle: Oluwanimi Resort</h4>
								<!-- <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p> -->
							</div>
						</div>
					</div>

				</div>
				<div class="col-lg-4" style="margin: auto;">
					<div class="gtco-practice-area-item animate-box ">
						<div class="card" style="width: 35rem;box-shadow:5px 5px 3px grey;border-radius:25px;margin: auto;	">
							<img src="images/project.jpg" class="card-img-top img-fluid " style="max-width: 350px;border-radius:10px;" alt="...">
							<div class="card-body text-center pt-2">
								<h3 class="card-title" style="padding-top:10px">Investment Raised: N200,000,000</h3>
								<h4 class="card-text" style="padding-bottom:30px;color:rgb(5, 5, 158)">Project Tittle: Oluwanimi Resort</h4>
								<!-- <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p> -->
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>
	</section>

	
	
	<section class="container">
 <div class="container mt-5">
	<div class="row container mt-5">
    <div class="col-md-3">
        <div class="icon text-center">
            <i class="fa-solid fa-list-check icon"></i>
            <h3 class="count" data-target="560">0</h3>
            <h3>Project Completed</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="icon text-center">
            <i class="fa-solid fa-hand-holding-dollar icon"></i>
            <h3 class="count" data-target="7680">0</h3>
            <h3>Raised Till Date</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="icon text-center">
            <i class="fa-solid fa-handshake icon"></i>
            <h3 class="count" data-target="340">0</h3>
            <h3>Partners Funding</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="icon text-center">
            <i class="fa-solid fa-face-smile-beam icon"></i>
            <h3 class="count" data-target="2870">0</h3>
            <h3>Happy Customers</h3>
        </div>
    </div>
</div>
    </div>
	</section>
	<a class="appointlet-button" data-appointlet-modal href="https://appt.link/meet-with-cobuild-2nJj3WQd" style="background-color:#040b90;position:fixed; top:450px;color:white;z-index:1000">Schedule a Meeting</a><script async defer src="https://js.appointlet.com/"></script><link href="https://js.appointlet.com/styles.css" rel="stylesheet">

	
	<section id="gtco-contact" data-section="contact">
		<div class="container">
			<div class="col-md-8 col-md-offset-2 heading animate-box" data-animate-effect="fadeIn">
			<h1>FAQs</h1>
			<p class="sub" style="
  color:  #0f1133;
			">Some Answered Questions Below</p>
			<p class="subtle-text animate-box"  data-animate-effect="fadeIn">FAQs</p>
				</div>
		</div>
		<section class="container" id="contain" >
			<div class="accordion animate-box" id="accordionExample">
				<div class="accordion-item">
						<h2 class="accordion-header" id="headingOne">
								<button class="accordion-button" type="button" data-tab="tab1">
									How can I invest in a project? <i class="fa-solid fa-chevron-down"></i>
								</button>
						</h2>
						<div id="tab1" class="accordion-collapse">
								<div class="accordion-body" style="
  color:  #0f1133;
								">
									You can invest by browsing projects and finding one that suits your interest. Once you do, You choose the labour/skill or fund you want to invest.
								</div>
						</div>
				</div>
				<div class="accordion-item">
						<h2 class="accordion-header" id="headingTwo">
								<button class="accordion-button" type="button" data-tab="tab2">
									How is my Investment secured? <i class="fa-solid fa-chevron-down"></i>
								</button>
						</h2>
						<div id="tab2" class="accordion-collapse">
								<div class="accordion-body" style="
  color:  #0f1133;
								">
									Once you Invest, You are given a certificate of Investment as well as the developer's contact information and location. Every Project on Cobuild has been verified. You can also request for an immediate realization from the investor.
								</div>
						</div>
				</div>
				<div class="accordion-item">
						<h2 class="accordion-header" id="headingThree">
								<button class="accordion-button" type="button" data-tab="tab3">
									What can i Invest? <i class="fa-solid fa-chevron-down"></i>
								</button>
						</h2>
						<div id="tab3" class="accordion-collapse">
								<div class="accordion-body" style="
  color:  #0f1133;
								">
									Investments may come in the form of skill, labour or materials, where you give in exchange for shares, free tickets or whatever the developer has to offer.
								</div>
						</div>
				</div>
				<div class="accordion-item">
						<h2 class="accordion-header" id="headingFour">
								<button class="accordion-button" type="button" data-tab="tab4">
									What kind of skills can I invest in cobuild? <i class="fa-solid fa-chevron-down"></i>
								</button>
						</h2>
						<div id="tab4" class="accordion-collapse">
								<div class="accordion-body" style="
  color:  #0f1133;
								">
									Any skill as may be listed and demanded by developers including Masons, carpentry, bricklaying, electricians, plumbers, draught, artisans, welders, accounting clerks, heaver machine drivers, Engineers, Architects, quantity surveying, technicians, Civil and mechanical engineers, web developers,  Accountants, Real estate marketers and sales persons.
								</div>
						</div>
				</div>
				<div class="accordion-item">
						<h2 class="accordion-header" id="headingFive">
								<button class="accordion-button" type="button" data-tab="tab5">
									What building material can I invest in a project? <i class="fa-solid fa-chevron-down"></i>
								</button>
						</h2>
						<div id="tab5" class="accordion-collapse">
								<div class="accordion-body" style="
  color:  #0f1133;
								">
									You can invest any building material as may be requested by the developer. So check for investment opportunities that allow you to invest my  building material you have.
								</div>
						</div>
				</div>
				<div class="accordion-item">
						<h2 class="accordion-header" id="headingSix">
								<button class="accordion-button" type="button" data-tab="tab6">
									Are there instant payments for building materials supplied or skills offered? <i class="fa-solid fa-chevron-down"></i>
								</button>
						</h2>
						<div id="tab6" class="accordion-collapse">
								<div class="accordion-body" style="
  color:  #0f1133;
								">
									The pattern of payment for your investment is project-specific. However, all projects would pay a minimum of 10% of our skill or materials investment as soon as your job is certified.
								</div>
						</div>
				</div>
				<div class="accordion-item">
						<h2 class="accordion-header" id="headingSeven">
								<button class="accordion-button" type="button" data-tab="tab7">
									Can I retract an Investment? <i class="fa-solid fa-chevron-down"></i>
								</button>
						</h2>
						<div id="tab7" class="accordion-collapse">
								<div class="accordion-body"
								style="
  color:  #0f1133;
								">
									If you choose to pull out of your investment, you can trade your investment on the platform and receive your investment back. In case of dormant or fraudulent activity investments can also be returned.
								</div>
						</div>
				</div>
		</div>
		</section>

	</section>

	<section id="gtco-contact" data-section="our-team">
		<div class="container">
			<div class="row row-pb-md">
				<div class="col-md-8 col-md-offset-2 heading animate-box" data-animate-effect="fadeIn">
					<h1>Contact Us</h1>
					<p class="sub" style="
  color:  #0f1133;
					">You can send us a direct message, through the form below.</p>
					<p class="subtle-text animate-box" data-animate-effect="fadeIn">Contact</p>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6 col-md-push-6 animate-box">
					<form id="contact-form" onsubmit="sendEmail(event)">
							<div class="form-group">
									<label for="name" class="sr-only">Name</label>
									<input type="text" class="form-control" placeholder="Name" id="name" required>
							</div>
							<div class="form-group">
									<label for="email" class="sr-only">Email</label>
									<input type="email" class="form-control" placeholder="Email" id="email" required>
							</div>
							<div class="form-group">
									<label for="message" class="sr-only">Message</label>
									<textarea name="message" id="message" class="form-control" cols="30" rows="7" placeholder="Message" required></textarea>
							</div>
							<div class="form-group">
									<input type="submit" value="Send Message" class="btn btn-primary">
							</div>
					</form>
			</div>
				<div class="col-md-4 col-md-pull-6 animate-box">
					<div class="gtco-contact-info">
						<ul>
							<li class="address" ><a href="">24, Valley View Close, Valley Estate, Idi Mangoro, Ikeja, Lagos, Nigeria.</a></li>
							<li class="phone"><a href="tel://+234 803 711 0870">+234 803 711 0870</a></li>
							<li class="email"><a href="mailto:Officialcobuild@gmail.com">Officialcobuild@gmail.com</a></li>
							<!-- <li class="url"><a href="#">http://example.com</a></li> -->
						</ul>
					</div>
				</div>
			</div>
		</div>
	</section>
	
	<footer id="gtco-footer" role="contentinfo">
		<div class="container">
			<div class="row">
				<div class="col-md-6">
					<div>
						<h2>Get In Touch</h2>
						<ul style="list-style-type: none;">
							<li class="address"> <i class="fa-solid fa-location pe-5" style="color: rgb(7, 7, 142);padding-right:10px;"></i><a href="">24, Valley View Close, Valley Estate, Idi Mangoro, Ikeja, Lagos, Nigeria.</a></li>
							<li class="phone"><a href="tel://+234 803 711 0870"> <i class="fa-solid fa-phone" style="padding-right: 10px;"></i>+234 803 711 0870</a></li>
							<li class="email"><a href="mailto:Officialcobuild@gmail.com"> <i class="fa-solid fa-envelope" style="padding-right: 10px;"></i> Officialcobuild@gmail.com</a></li>
							<!-- <li class="url"><a href="#">http://example.com</a></li> -->
						</ul>
					</div>
					
				</div>

				<div class="col-md-6">
					<div>
						<h2>Quick Links</h2>
						<ul style="list-style-type: none;">
							<li ><a href="#" > <i class="fa-solid fa-chevron-right" style="padding-right: 10px;"></i>Home</a></li>
						<li><a href="#" ><i class="fa-solid fa-chevron-right" style="padding-right: 10px;"></i>About</a></li>
						<li><a href="#" ><i class="fa-solid fa-chevron-right" style="padding-right: 10px;"></i>Projects</a></li>
						<li class="btn-cta"><a href="#" ><i class="fa-solid fa-chevron-right" style="padding-right: 10px;"></i><span>FAQ</span></a></li>
						<li><a href="#" ><i class="fa-solid fa-chevron-right" style="padding-right: 10px;"></i>Contacts</a></li>
						<li class="btn-cta"><a href="#" data-nav-section="user"><span><i class="fa-solid fa-chevron-right" style="padding-right: 10px;"></i>Invest</span></a></li>
						<li class="btn-cta"><a href="#" data-nav-section="user"><span><i class="fa-solid fa-chevron-right" style="padding-right: 10px;"></i>Raise Funds</span></a></li>
							<!-- <li class="url"><a href="#">http://example.com</a></li> -->
						</ul>
					</div>
				</div>
		
			</div>
		</div>
		<hr>
		<div class="container">
			
			<div class="row copyright">
				<div class="col-md-12">
					<p class="pull-left">
						<small class="block">&copy; Cobuild <span id="year"></span>. all right reserved <a href="https://bgwfoundation.org/" target="_blank" rel="noopener noreferrer">Bridgewaters</a></small>
						<small class="block">Designed by <a href="https://github.com/PEACE-DFG" target="_blank">CODEMaster</a></small>
					</p>
					<p class="pull-right">
						<ul class="gtco-social-icons pull-right">
							<li><a href="#" target="_blank"><i class="icon-twitter"></i></a></li>
							<li><a href="https://youtu.be/E0ciDUHEwSo?si=SPb2nNKDEvD52UVD" target="_blank"><i class="icon-youtube"></i></a></li>
							<li><a href="https://web.facebook.com/profile.php?id=61551699093735&sk=about_contact_and_basic_info" target="_blank"><i class="icon-facebook"></i></a></li>
							<li><a href="https://www.linkedin.com/company/100059257/admin/dashboard/" target="_blank"><i class="icon-linkedin"></i></a></li>
							<li><a href="https://www.instagram.com/official_cobuild/" target="_blank"><i class="icon-instagram"></i></a></li>
						</ul>
					</p>
				</div>
			</div>

		</div>
	</footer>
	</div>

	<div class="gototop js-top">
		<a href="#" class="js-gotop"><i class="icon-arrow-up"></i></a>
	</div>
	
	<!-- jQuery -->
	<script src="js/jquery.min.js"></script>
	<!-- jQuery Easing -->
	<script src="js/jquery.easing.1.3.js"></script>
	<!-- Bootstrap -->
	<script src="js/bootstrap.min.js"></script>
	<!-- Waypoints -->
	<script src="js/jquery.waypoints.min.js"></script>
	<!-- Stellar -->
	<script src="js/jquery.stellar.min.js"></script>
	<!-- Magnific Popup -->
	<script src="js/jquery.magnific-popup.min.js"></script>
	<script src="js/magnific-popup-options.js"></script>
	<!-- Main -->
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

	<script src="js/main.js"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const counters = document.querySelectorAll('.count');
			const speed = 200; // Adjust the speed for counting

			// Function to animate the counting effect
			const countUp = () => {
					counters.forEach(counter => {
							const updateCount = () => {
									const target = +counter.getAttribute('data-target');
									const count = +counter.innerText;
									const increment = target / speed;
									
									if (count < target) {
											counter.innerText = Math.ceil(count + increment);
											setTimeout(updateCount, 20);
									} else {
											counter.innerText = target;
									}
							};
							updateCount();
					});
			};

			// IntersectionObserver to trigger counting when the section is in view
			const observer = new IntersectionObserver(entries => {
					entries.forEach(entry => {
							if (entry.isIntersecting) {
									countUp();
									observer.disconnect(); // Stop observing once the animation has started
							}
					});
			});

			observer.observe(document.querySelector('.row.container'));
	});

	document.addEventListener('DOMContentLoaded', function() {
		const yearElement = document.getElementById('year');
		const currentYear = new Date().getFullYear();  // Get the current year
		yearElement.textContent = currentYear;         // Set the year in the span
});

// JavaScript to handle the accordion toggle
document.querySelectorAll('.accordion-button').forEach(button => {
	button.addEventListener('click', function() {
			const collapseElement = document.querySelector(`#${this.getAttribute('data-tab')}`); // Select the collapse element using ID
			const parentAccordionItem = this.parentNode.parentNode;

			// Close all open accordions
			document.querySelectorAll('.accordion-item').forEach(item => {
					const collapse = item.querySelector('.accordion-collapse');
					if (item !== parentAccordionItem) {
							collapse.style.maxHeight = '0';
							item.querySelector('.accordion-button').classList.remove('active');
							item.classList.remove('active');
					}
			});

			// Toggle the current accordion
			parentAccordionItem.classList.toggle('active');
			this.classList.toggle('active');

			if (parentAccordionItem.classList.contains('active')) {
					collapseElement.style.maxHeight = collapseElement.scrollHeight + 'px';
			} else {
					collapseElement.style.maxHeight = '0';
			}
	});
});


//sending email through the contact page
(function(){
	emailjs.init("-ueGO7vgxSgl70FOZ"); // Replace with your public key from EmailJS
})();

function sendEmail(event) {
	event.preventDefault(); // Prevent form submission
	
	const name = document.getElementById('name').value;
	const email = document.getElementById('email').value;
	const message = document.getElementById('message').value;
	
	emailjs.send("service_3sbhs0f", "template_yabvp9l", {
			from_name: name,
			reply_to: email,
			message: message,
	})
	.then(function(response) {
		// Display success message using SweetAlert
		Swal.fire({
				icon: 'success',
				title: 'Message Sent!',
				text: 'Your message has been sent successfully.We would get back to you shortly',
				confirmButtonColor: '#007bff'
		});
}, function(error) {
		// Display error message using SweetAlert
		Swal.fire({
				icon: 'error',
				title: 'Failed to Send!',
				text: 'Failed to send the message. Please try again later or send a message to the email on the left.',
				confirmButtonColor: '#007bff'
		});
});
}
	</script>
	

	<!-- Start of HubSpot Embed Code -->
  <script type="text/javascript" id="hs-script-loader" async defer src="//js-na1.hs-scripts.com/47650118.js"></script>
<!-- End of HubSpot Embed Code -->

<?php
ob_end_flush()
?>
	</body>
</html>

