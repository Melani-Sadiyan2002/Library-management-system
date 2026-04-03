<?php include('header.php'); ?>
<?php include('navbar.php'); ?>
<style>
body {
background: linear-gradient(rgba(255, 248, 239, 0.42), rgba(255, 248, 239, 0.42)), url('images5/new1.jpg') center/cover no-repeat fixed;
}
.home-wrap {
margin-top: 16px;
padding: 12px;
background: transparent;
border: 0;
}
.home-grid {
display: grid;
grid-template-columns: repeat(3, minmax(0, 1fr));
gap: 14px;
margin-bottom: 16px;
}
.home-card {
position: relative;
overflow: hidden;
background: rgba(235, 235, 235, 0.88);
border: 1px solid rgba(187, 187, 187, 0.78);
border-radius: 14px;
padding: 14px 16px;
min-height: 175px;
box-shadow: 0 12px 22px rgba(94, 94, 94, 0.12);
transition: transform 0.24s ease, box-shadow 0.24s ease, background 0.24s ease, border-color 0.24s ease;
backdrop-filter: blur(4px);
}
.home-card:before {
content: "";
position: absolute;
inset: 0 auto auto 0;
width: 100%;
height: 4px;
background: linear-gradient(90deg, #c5c5c5, #9e9e9e);
}
.home-card:hover {
transform: translateY(-5px) scale(1.035);
background: rgba(49, 106, 255, 0.9);
border-color: rgba(49, 106, 255, 0.95);
box-shadow: 0 18px 30px rgba(49, 106, 255, 0.22);
}
.home-card h3 {
margin: 8px 0 8px;
font-size: 19px;
line-height: 1.15;
color: #1f2d44;
}
.home-card p {
margin: 0;
font-size: 12px;
color: #546274;
line-height: 1.55;
}
.home-card .home-link {
display: inline-flex;
align-items: center;
margin-top: 12px;
padding: 7px 12px;
border-radius: 999px;
font-weight: 700;
font-size: 12px;
color: #2c2c2c;
background: rgba(255, 255, 255, 0.52);
transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease;
}
.home-card:hover h3,
.home-card:hover p,
.home-card:hover .home-link {
color: #ffffff;
}
.home-card:hover .home-link {
background: rgba(255, 255, 255, 0.2);
transform: translateX(2px);
}
</style>

<div class="container home-wrap">
<div class="row">
<?php include('head.php'); ?>
</div>

<div class="home-grid">
<div class="home-card">
<h3>Search Books</h3>
<p>Browse titles, authors, and categories to find the book you need quickly.</p>
<a class="home-link" href="student">Go to Login</a>
</div>
<div class="home-card">
<h3>Borrow and Return</h3>
<p>Track borrow records and return schedules in a simple student-friendly flow.</p>
<a class="home-link" href="signup.php">Create Student Account</a>
</div>
<div class="home-card">
<h3>Library Updates</h3>
<p>Stay connected with the latest announcements and availability information.</p>
<a class="home-link" href="about.php">Read More</a>
</div>
</div>

</div>
<?php include('footer.php') ?>
