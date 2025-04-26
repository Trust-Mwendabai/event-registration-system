<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get database connection
$conn = getDBConnection();

// Get selected category filter
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Fetch event categories
$categoriesSQL = "SELECT DISTINCT category FROM events ORDER BY category";
$categoriesResult = $conn->query($categoriesSQL);
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row['category'];
}

// Fetch featured events (upcoming events with highest capacity)
$featuredSQL = "SELECT e.*, 
                (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id AND r.status = 'confirmed') as registered_count 
                FROM events e 
                WHERE e.date >= CURRENT_TIMESTAMP 
                ORDER BY e.capacity DESC, e.date ASC 
                LIMIT 3";
$featuredResult = $conn->query($featuredSQL);

// Fetch upcoming events with registration count
$sql = "SELECT e.*, 
        (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id AND r.status = 'confirmed') as registered_count 
        FROM events e 
        WHERE e.date >= CURRENT_TIMESTAMP";
if ($category !== 'all') {
    $sql .= " AND category = '" . $conn->real_escape_string($category) . "'";
}
$sql .= " ORDER BY e.date ASC";
$result = $conn->query($sql);

// Fetch trending events (most registered)
$trendingSQL = "SELECT e.*, 
                COUNT(r.reg_id) as registration_count,
                (SELECT COUNT(*) FROM registrations r2 WHERE r2.event_id = e.event_id AND r2.status = 'confirmed') as registered_count
                FROM events e 
                LEFT JOIN registrations r ON e.event_id = r.event_id 
                WHERE e.date >= CURRENT_TIMESTAMP 
                GROUP BY e.event_id 
                ORDER BY registration_count DESC 
                LIMIT 3";
$trendingResult = $conn->query($trendingSQL);

// Fetch this week's events
$thisWeekSQL = "SELECT e.*, 
                (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id AND r.status = 'confirmed') as registered_count 
                FROM events e 
                WHERE e.date >= CURRENT_TIMESTAMP 
                AND e.date <= DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 7 DAY) 
                ORDER BY e.date ASC";
$thisWeekResult = $conn->query($thisWeekSQL);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registration System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .featured-event {
            border: 2px solid #ffc107;
            transition: transform 0.2s;
        }
        .featured-event:hover {
            transform: translateY(-5px);
        }
        .trending-event {
            border: 2px solid #dc3545;
        }
        .trending-event:hover {
            transform: translateY(-5px);
        }
        .category-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .capacity-bar {
            height: 5px;
            margin-top: 10px;
        }
        .nav-pills .nav-link {
            color: #0d6efd;
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1511795409834-ef04bbd61622?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 150px 0;
            margin-bottom: 3rem;
            position: relative;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(13, 110, 253, 0.3), rgba(0, 0, 0, 0.7));
            z-index: 1;
        }
        .hero-section .container {
            position: relative;
            z-index: 2;
        }
        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 1.5rem;
        }
        .hero-section .lead {
            font-size: 1.5rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            margin-bottom: 2rem;
        }
        .hero-section .btn {
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        .hero-section .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .footer-section {
            background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), url('https://images.unsplash.com/photo-1511795409834-ef04bbd61622?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 80px 0 40px;
            position: relative;
        }
        .footer-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(13, 110, 253, 0.3), rgba(0, 0, 0, 0.7));
            z-index: 1;
        }
        .footer-section .container {
            position: relative;
            z-index: 2;
        }
        .footer-section h5 {
            color: #fff;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        .footer-section ul li {
            margin-bottom: 0.8rem;
        }
        .footer-section ul li a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .footer-section ul li a:hover {
            color: #fff;
            transform: translateX(5px);
        }
        .footer-section .bi {
            margin-right: 8px;
            color: #0d6efd;
        }
        .footer-section hr {
            border-color: rgba(255,255,255,0.1);
            margin: 2rem 0;
        }
        .footer-section .text-center {
            color: rgba(255,255,255,0.8);
        }
        .footer-section .text-center p {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-calendar-event"></i> Event Registration System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#featured">Featured Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#trending">Trending</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#this-week">This Week</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#all-events">All Events</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="user/dashboard.php">
                                <i class="bi bi-person-circle"></i> My Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="user/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="user/login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="user/register.php">
                                <i class="bi bi-person-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 mb-4">Discover Amazing Events</h1>
            <p class="lead mb-4">Join our community and participate in exciting events near you. From conferences to workshops, find your next great experience.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="#all-events" class="btn btn-light btn-lg">
                    <i class="bi bi-search"></i> Browse Events
                </a>
                <a href="#featured" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-star-fill"></i> Featured Events
                </a>
            </div>
        </div>
    </section>

    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Featured Events Section -->
        <?php if ($featuredResult && $featuredResult->num_rows > 0): ?>
        <section id="featured" class="mb-5">
            <h2 class="mb-4"><i class="bi bi-star-fill text-warning"></i> Featured Events</h2>
            <div class="row">
                <?php while ($event = $featuredResult->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 featured-event">
                            <div class="card-body">
                                <span class="badge bg-warning text-dark category-badge">
                                    <?php echo htmlspecialchars($event['category']); ?>
                                </span>
                                <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <div class="event-meta">
                                    <div><i class="bi bi-calendar"></i> <?php echo formatDate($event['date']); ?></div>
                                    <div><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['location']); ?></div>
                                    <div><i class="bi bi-people"></i> <?php echo $event['registered_count']; ?>/<?php echo $event['capacity']; ?> registered</div>
                                </div>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                <div class="progress capacity-bar">
                                    <div class="progress-bar bg-warning" role="progressbar" 
                                         style="width: <?php echo ($event['registered_count'] / $event['capacity']) * 100; ?>%"
                                         aria-valuenow="<?php echo ($event['registered_count'] / $event['capacity']) * 100; ?>"
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <?php if (isEventOpen($event['date'])): ?>
                                    <a href="register.php?event_id=<?php echo $event['event_id']; ?>" 
                                       class="btn btn-warning mt-3 w-100">
                                       <i class="bi bi-calendar-check"></i> Register Now
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary mt-3 w-100" disabled>
                                        <i class="bi bi-calendar-x"></i> Registration Closed
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Trending Events Section -->
        <?php if ($trendingResult && $trendingResult->num_rows > 0): ?>
        <section id="trending" class="mb-5">
            <h2 class="mb-4"><i class="bi bi-graph-up text-danger"></i> Trending Events</h2>
            <div class="row">
                <?php while ($event = $trendingResult->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 trending-event">
                            <div class="card-body">
                                <span class="badge bg-danger category-badge">
                                    <?php echo htmlspecialchars($event['category']); ?>
                                </span>
                                <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <div class="event-meta">
                                    <div><i class="bi bi-calendar"></i> <?php echo formatDate($event['date']); ?></div>
                                    <div><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['location']); ?></div>
                                    <div><i class="bi bi-people"></i> <?php echo $event['registered_count']; ?>/<?php echo $event['capacity']; ?> registered</div>
                                </div>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                <div class="progress capacity-bar">
                                    <div class="progress-bar bg-danger" role="progressbar" 
                                         style="width: <?php echo ($event['registered_count'] / $event['capacity']) * 100; ?>%"
                                         aria-valuenow="<?php echo ($event['registered_count'] / $event['capacity']) * 100; ?>"
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <?php if (isEventOpen($event['date'])): ?>
                                    <a href="register.php?event_id=<?php echo $event['event_id']; ?>" 
                                       class="btn btn-danger mt-3 w-100">
                                       <i class="bi bi-calendar-check"></i> Register Now
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary mt-3 w-100" disabled>
                                        <i class="bi bi-calendar-x"></i> Registration Closed
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- This Week's Events Section -->
        <?php if ($thisWeekResult && $thisWeekResult->num_rows > 0): ?>
        <section id="this-week" class="mb-5">
            <h2 class="mb-4"><i class="bi bi-calendar-week text-success"></i> This Week's Events</h2>
            <div class="row">
                <?php while ($event = $thisWeekResult->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <span class="badge bg-success category-badge">
                                    <?php echo htmlspecialchars($event['category']); ?>
                                </span>
                                <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <div class="event-meta">
                                    <div><i class="bi bi-calendar"></i> <?php echo formatDate($event['date']); ?></div>
                                    <div><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['location']); ?></div>
                                    <div><i class="bi bi-people"></i> <?php echo $event['registered_count']; ?>/<?php echo $event['capacity']; ?> registered</div>
                                </div>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                <div class="progress capacity-bar">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo ($event['registered_count'] / $event['capacity']) * 100; ?>%"
                                         aria-valuenow="<?php echo ($event['registered_count'] / $event['capacity']) * 100; ?>"
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <?php if (isEventOpen($event['date'])): ?>
                                    <a href="register.php?event_id=<?php echo $event['event_id']; ?>" 
                                       class="btn btn-success mt-3 w-100">
                                       <i class="bi bi-calendar-check"></i> Register Now
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary mt-3 w-100" disabled>
                                        <i class="bi bi-calendar-x"></i> Registration Closed
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- All Events Section -->
        <section id="all-events">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-calendar2-week"></i> All Upcoming Events</h2>
                <div class="btn-group">
                    <a href="index.php" class="btn btn-outline-primary <?php echo $category === 'all' ? 'active' : ''; ?>">
                        <i class="bi bi-grid"></i> All
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="index.php?category=<?php echo urlencode($cat); ?>" 
                           class="btn btn-outline-primary <?php echo $category === $cat ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="row">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($event = $result->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <span class="badge bg-primary category-badge">
                                        <?php echo htmlspecialchars($event['category']); ?>
                                    </span>
                                    <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                    <div class="event-meta">
                                        <div><i class="bi bi-calendar"></i> <?php echo formatDate($event['date']); ?></div>
                                        <div><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['location']); ?></div>
                                        <div><i class="bi bi-people"></i> <?php echo $event['registered_count']; ?>/<?php echo $event['capacity']; ?> registered</div>
                                    </div>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                    <div class="progress capacity-bar">
                                        <div class="progress-bar bg-primary" role="progressbar" 
                                             style="width: <?php echo ($event['registered_count'] / $event['capacity']) * 100; ?>%"
                                             aria-valuenow="<?php echo ($event['registered_count'] / $event['capacity']) * 100; ?>"
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <?php if (isEventOpen($event['date'])): ?>
                                        <a href="register.php?event_id=<?php echo $event['event_id']; ?>" 
                                           class="btn btn-primary mt-3 w-100">
                                           <i class="bi bi-calendar-check"></i> Register Now
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary mt-3 w-100" disabled>
                                            <i class="bi bi-calendar-x"></i> Registration Closed
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No upcoming events at this time. Please check back later!
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <footer class="footer-section">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="bi bi-link-45deg"></i> Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#featured"><i class="bi bi-star"></i> Featured Events</a></li>
                        <li><a href="#trending"><i class="bi bi-graph-up"></i> Trending Events</a></li>
                        <li><a href="#this-week"><i class="bi bi-calendar-week"></i> This Week's Events</a></li>
                        <li><a href="#all-events"><i class="bi bi-calendar2-week"></i> All Events</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5><i class="bi bi-tags"></i> Categories</h5>
                    <ul class="list-unstyled">
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="index.php?category=<?php echo urlencode($cat); ?>">
                                    <i class="bi bi-tag"></i> <?php echo htmlspecialchars($cat); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5><i class="bi bi-envelope"></i> Contact Us</h5>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-envelope"></i> info@eventregistration.com</li>
                        <li><i class="bi bi-phone"></i> +1 234 567 890</li>
                        <li><i class="bi bi-geo-alt"></i> 123 Event Street, City</li>
                        <li><i class="bi bi-clock"></i> Mon-Fri: 9:00 AM - 5:00 PM</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center py-3">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Event Registration System. All rights reserved.</p>
                <p class="mb-0">Monica Kabwe</p>
                <p class="mb-0">SIN: 2403443685</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add active class to nav links on scroll
        window.addEventListener('scroll', function() {
            let sections = document.querySelectorAll('section');
            let navLinks = document.querySelectorAll('.navbar-nav .nav-link');
            
            sections.forEach(section => {
                let top = section.offsetTop - 100;
                let bottom = top + section.offsetHeight;
                let scroll = window.scrollY;
                let id = section.getAttribute('id');
                
                if (scroll >= top && scroll < bottom) {
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === '#' + id) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
<?php closeDBConnection($conn); ?> 