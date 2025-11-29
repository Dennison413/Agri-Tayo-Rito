<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Mang Juan's Farm</title>

<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #c8e6c9;
    }

    /* NAVIGATION */
    .nav {
        display: flex;
        justify-content: center;
        gap: 60px;
        padding: 20px 0;
        font-weight: bold;
    }
    .nav a {
        text-decoration: none;
        color: black;
        padding: 6px 20px;
        border-radius: 20px;
    }
    .active {
        background: #4CAF50;
        color: white !important;
    }

    /* ABOUT BOX */
    .about-box {
        background: #e1f5e0;
        margin: 40px auto;
        width: 80%;
        padding: 25px 30px;
        border-radius: 15px;
    }
    .about-title {
        font-size: 26px;
        font-weight: bold;
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }

    /* REVIEWS SECTION */
    .summary {
        background: white;
        width: 55%;
        margin: 40px auto;
        border-radius: 15px;
        padding: 25px;
        text-align: center;
    }

    .bars { margin-top: 15px; }
    .bar-row { display: flex; align-items: center; margin: 5px 0; }
    .bar-label { width: 30px; }
    .bar { width: 60%; height: 8px; background: #eee; border-radius: 4px; margin: 0 10px; }
    .bar-fill { height: 100%; background: #ffca28; border-radius: 4px; }
    .bar-percent { width: 40px; }

    .review-card {
        background: white;
        width: 80%;
        margin: 20px auto;
        border-radius: 15px;
        padding: 20px;
        display: flex;
        gap: 20px;
    }
    .review-card img {
        width: 55px;
        height: 55px;
        border-radius: 50%;
    }
</style>
</head>

<body>

<!-- NAVIGATION -->
<div class="nav">
    <a href="#" onclick="showSection('about')" id="btn-about">ℹ️ About</a>
    <a href="#" onclick="showSection('reviews')" id="btn-reviews">⭐ Reviews</a>
</div>


<!-- ABOUT SECTION -->
<div id="about" class="about-box">
    <div class="about-title">📖 About Our Farm</div>

    <p>
        Welcome to Mang Juan's Farm! We are a family-owned organic farm located in the 
        heart of Nueva Ecija. For over 20 years, we have been dedicated to sustainable 
        farming practices and providing fresh, high-quality produce to our community.
    </p>
</div>


<!-- REVIEWS SECTION -->
<div id="reviews" style="display:none;">
    <!-- REVIEW SUMMARY -->
    <div class="summary">
        <h2 style="color:#4CAF50;">4.8</h2>
        <div style="color:#ffca28; font-size:20px;">★★★★★</div>
        <p style="font-size:12px; color:gray;">Based on 1,204 reviews</p>

        <div class="bars">
            <div class="bar-row">
                <div class="bar-label">5★</div>
                <div class="bar"><div class="bar-fill" style="width:80%;"></div></div>
                <div class="bar-percent">80%</div>
            </div>

            <div class="bar-row">
                <div class="bar-label">4★</div>
                <div class="bar"><div class="bar-fill" style="width:72%;"></div></div>
                <div class="bar-percent">72%</div>
            </div>

            <div class="bar-row">
                <div class="bar-label">3★</div>
                <div class="bar"><div class="bar-fill" style="width:50%;"></div></div>
                <div class="bar-percent">50%</div>
            </div>

            <div class="bar-row">
                <div class="bar-label">2★</div>
                <div class="bar"><div class="bar-fill" style="width:20%;"></div></div>
                <div class="bar-percent">20%</div>
            </div>

            <div class="bar-row">
                <div class="bar-label">1★</div>
                <div class="bar"><div class="bar-fill" style="width:20%;"></div></div>
                <div class="bar-percent">20%</div>
            </div>
        </div>
    </div>

    <!-- REVIEW 1 -->
    <div class="review-card">
        <img src="https://i.pravatar.cc/100?img=12">
        <div>
            <b>Joshua Santos</b><br>
            ⭐⭐⭐⭐☆<br>
            Excellent quality vegetables! Very fresh and flavorful.
        </div>
    </div>

    <!-- REVIEW 2 -->
    <div class="review-card">
        <img src="https://i.pravatar.cc/100?img=33">
        <div>
            <b>Juan Santos</b><br>
            ⭐⭐⭐⭐⭐<br>
            Fast delivery and good quality produce!
        </div>
    </div>
</div>


<script>
function showSection(section) {
    document.getElementById("about").style.display = "none";
    document.getElementById("reviews").style.display = "none";

    document.getElementById(section).style.display = "block";

    // update active button
    document.getElementById("btn-about").classList.remove("active");
    document.getElementById("btn-reviews").classList.remove("active");

    document.getElementById("btn-" + section).classList.add("active");
}
</script>

</body>
</html>