<?php
require_once "../admin/db_connect.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News | Time and Tide Education</title>
    <meta name="description" content="The latest news and updates from Time and Tide Education.">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Source+Sans+3:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="../assets/images/about.jpg" alt="Time and Tide Education" class="logo-img">
                <span class="logo-text">Time & Tide Education</span>
            </div>
            <div class="nav-menu" id="nav-menu">
                <a href="../index.php#home" class="nav-link">Home</a>
                <a href="../index.php#about" class="nav-link">About</a>
                <a href="../index.php#services" class="nav-link">Services</a>
                <a href="../index.php#countries" class="nav-link">Countries</a>
                <a href="success-stories.php" class="nav-link">Voices of Success</a>
                <a href="news.php" class="nav-link">News</a>
                <a href="../index.php#contact" class="nav-link">Contact</a>
            </div>
            <div class="nav-toggle" id="nav-toggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav>

    <!-- News Section -->
    <section id="news" class="section news">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Latest News</h2>
                <p class="section-subtitle">Stay up to date with the latest news and announcements from Time & Tide Education.</p>
            </div>
            <div class="news-grid">
                <?php
                $sql = "SELECT * FROM news ORDER BY posted_date DESC";
                if($result = mysqli_query($link, $sql)){
                    if(mysqli_num_rows($result) > 0){
                        $news_id = 0;
                        while($row = mysqli_fetch_array($result)){
                            $news_id++;
                            echo "<div class='news-card'>";
                                $images = json_decode($row['image_paths']);
                                if(is_array($images) && count($images) > 0){
                                    echo "<div class='swiper-container news-carousel-" . $news_id . "'>";
                                        echo "<div class='swiper-wrapper'>";
                                            foreach($images as $image){
                                                echo "<div class='swiper-slide'><img src='../" . htmlspecialchars($image) . "' alt='" . htmlspecialchars($row['title']) . "'></div>";
                                            }
                                        echo "</div>";
                                        echo "<div class='swiper-pagination'></div>";
                                    echo "</div>";
                                }
                                echo "<div class='news-content'>";
                                    echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
                                    echo "<p>" . nl2br(htmlspecialchars($row['content'])) . "</p>";
                                    echo "<p class='news-date'>" . date("F j, Y", strtotime($row['posted_date'])) . "</p>";
                                echo "</div>";
                            echo "</div>";
                        }
                        mysqli_free_result($result);
                    } else{
                        echo "<p class='lead'><em>No news articles were found.</em></p>";
                    }
                } else{
                    echo "ERROR: Could not able to execute $sql. " . mysqli_error($link);
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <img src="../assets/images/about.jpg" alt="Time and Tide Education">
                        <span>Time & Tide Education</span>
                    </div>
                    <p>Your trusted partner for international education and student visa services.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Services</h3>
                    <ul>
                        <li><a href="student-visa-support.php">Student Visa Support</a></li>
                        <li><a href="university-placement.php">University Placement</a></li>
                        <li><a href="documentation.php">Documentation</a></li>
                        <li><a href="scholarship-support.php">Scholarship Support</a></li>
                        <li><a href="pre-departure-support.php">Pre-departure Support</a></li>
                        <li><a href="visa-resubmission.php">Visa Resubmission</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Countries</h3>
                    <ul>
                        <li><a href="../index.php#countries">United Kingdom</a></li>
                        <li><a href="../index.php#countries">Canada</a></li>
                        <li><a href="../index.php#countries">Australia</a></li>
                        <li><a href="../index.php#countries">Italy</a></li>
                        <li><a href="../index.php#countries">Latvia</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> Hill Street, Dehiwala, Sri Lanka</li>
                        <li><i class="fas fa-phone"></i> +94 777701206</li>
                        <li><i class="fas fa-envelope"></i> info@timeandtide.lk</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Time & Tide Education. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            for (let i = 1; i <= <?php echo $news_id ?? 0; ?>; i++) {
                new Swiper('.news-carousel-' + i, {
                    loop: true,
                    autoplay: {
                        delay: 3000,
                        disableOnInteraction: false,
                    },
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    },
                });
            }
        });
    </script>
</body>
</html>