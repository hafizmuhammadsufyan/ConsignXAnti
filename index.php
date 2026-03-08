<?php
// FILE: /consignxAnti/index.php

require_once 'includes/config.php';
require_once 'includes/functions.php';

session_start();
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$user_role = $_SESSION['user_role'] ?? '';

// Determine Dashboard link based on role
$dashboard_link = 'auth/login.php';
if ($is_logged_in) {
    if ($user_role === 'admin')
        $dashboard_link = 'admin/dashboard.php';
    elseif ($user_role === 'agent')
        $dashboard_link = 'agent/dashboard.php';
    elseif ($user_role === 'customer')
        $dashboard_link = 'customer/dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= escape(APP_NAME) ?> - Modern SaaS Logistics Platform
    </title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts for Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* Specific Landing Page Styles - Non-Neumorphic, Clean SaaS Look */
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar-brand {
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .nav-link {
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: #3182ce !important;
        }

        /* Hero Section */
        .hero {
            padding: 120px 0 80px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            position: relative;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1.2;
        }

        .hero .lead {
            font-size: 1.25rem;
            font-weight: 400;
            color: #64748b;
        }

        /* Blob Background Animation for Hero */
        .blob {
            position: absolute;
            filter: blur(80px);
            z-index: 0;
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
        }

        .blob-1 {
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            background: rgba(49, 130, 206, 0.2);
        }

        .blob-2 {
            bottom: -100px;
            left: -100px;
            width: 300px;
            height: 300px;
            background: rgba(66, 153, 225, 0.2);
            animation-delay: 2s;
        }

        @keyframes float {
            0% {
                transform: translateY(0px) scale(1);
            }

            50% {
                transform: translateY(-20px) scale(1.05);
            }

            100% {
                transform: translateY(0px) scale(1);
            }
        }

        /* Tracking Component (Glassmorphism concept for SaaS, distinctly *not* neumorphism) */
        .glass-track {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.05);
            z-index: 2;
            position: relative;
        }

        /* Features */
        .feature-card {
            border: none;
            border-radius: 16px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: #fff;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
        }

        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
        }

        /* Testimonials Slider Customizations */
        .carousel-item {
            padding: 40px 0;
        }

        .testimonial-text {
            font-size: 1.2rem;
            font-style: italic;
            color: #475569;
        }

        .carousel-indicators [data-bs-target] {
            background-color: #3182ce;
        }

        /* Footer */
        .footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 80px 0 40px;
        }

        .footer a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer a:hover {
            color: #fff;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand text-primary fs-3" href="#">ConsignX</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-3">
                    <li class="nav-item"><a class="nav-link text-dark" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="#testimonials">Testimonials</a></li>

                    <?php if ($is_logged_in): ?>
                        <li class="nav-item">
                            <a class="btn btn-primary px-4 fw-bold rounded-pill" href="<?= $dashboard_link ?>">Go to
                                Dashboard</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link fw-bold text-dark" href="auth/login.php">Login</a></li>
                        <li class="nav-item">
                            <a class="btn btn-primary px-4 fw-bold rounded-pill" href="auth/register.php">Register
                                Company</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero d-flex align-items-center" id="home">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="container relative">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 z-1">
                    <h1 class="text-dark mb-4">The Operating System for Modern <span
                            class="text-primary">Couriers</span></h1>
                    <p class="lead mb-5">Seamlessly manage agents, track shipments globally, and delight your customers
                        with our enterprise-grade logistics platform.</p>
                    <div class="d-flex gap-3">
                        <a href="#track" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-sm">Track
                            Package</a>
                        <a href="#features"
                            class="btn btn-outline-dark btn-lg rounded-pill px-5 fw-bold bg-white shadow-sm">Learn
                            More</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <!-- Tracking Glass Widget -->
                    <div class="glass-track p-5" id="track">
                        <h3 class="fw-bold mb-2">Track & Trace</h3>
                        <p class="text-muted mb-4 small">Enter your Global Tracking Number right away.</p>
                        <form action="customer/track.php" method="GET" id="quickTrackForm">
                            <div class="mb-4">
                                <input type="text" name="id" id="tracking_id"
                                    class="form-control form-control-lg bg-white bg-opacity-75 border-0 shadow-sm rounded-3 py-3 px-4 fw-bold fs-5"
                                    placeholder="e.g. C-ABCD-1234" required pattern="C-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}">
                                <div class="invalid-feedback fw-bold mt-2">Invalid format. Must be C-XXXX-XXXX.</div>
                            </div>
                            <button type="submit"
                                class="btn btn-primary w-100 btn-lg rounded-3 fw-bold shadow-sm py-3">Locate
                                Shipment</button>
                        </form>
                        <div class="mt-4 border-top pt-3">
                            <small class="text-muted"><i class="bi bi-shield-check me-1 text-success"></i> 256-bit
                                Secure Transport Node</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 my-5 bg-white" id="features">
        <div class="container">
            <div class="text-center mb-5 pb-3">
                <span class="text-primary fw-bold text-uppercase tracking-wider small">Capabilities</span>
                <h2 class="fw-bold mt-2 display-6 text-dark">Built for Scale and Speed</h2>
                <p class="lead text-muted mx-auto" style="max-width: 600px;">ConsignX brings commercial grade
                    infrastructure directly to your logistics network without the heavy lifting.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card p-4 h-100 shadow-sm border border-light">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Multi-Agent Network</h4>
                        <p class="text-muted mb-0">Onboard hundreds of third-party courier companies under your brand
                            umbrella. Manage quotas, revenues, and routing dynamically.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card p-4 h-100 shadow-sm border border-light">
                        <div class="icon-box bg-success bg-opacity-10 text-success">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Real-time Tracking</h4>
                        <p class="text-muted mb-0">Provide customers with precision tracking data, estimated delivery
                            timelines, and transit location pins every step of the way.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card p-4 h-100 shadow-sm border border-light">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Insightful Reporting</h4>
                        <p class="text-muted mb-0">Generate comprehensive Excel and CSV reporting on shipments, delays,
                            and agent performance to optimize your supply chain.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="py-5 bg-light" id="about">
        <div class="container py-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <!-- Aesthetic placeholder for Company Image -->
                    <div class="position-relative rounded-4 overflow-hidden shadow-lg"
                        style="height: 400px; background: url('https://images.unsplash.com/photo-1586528116311-ad8ed7c663be?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80') center/cover;">
                        <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-25"></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <span class="text-primary fw-bold text-uppercase tracking-wider small">The ConsignX Story</span>
                    <h2 class="fw-bold mt-2 display-6 text-dark mb-4">Connecting the Global Supply Chain</h2>
                    <p class="text-muted fs-5 mb-4">Founded with a vision to democratize logistics software, ConsignX
                        empowers local couriers with the technological leverage previously reserved for multinational
                        titans.</p>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-check-circle-fill text-primary me-3 fs-5"></i>
                        <span class="fw-medium">Trusted by 500+ Regional Delivery Networks</span>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-check-circle-fill text-primary me-3 fs-5"></i>
                        <span class="fw-medium">Handling 10M+ Packages Annually</span>
                    </div>
                    <div class="d-flex align-items-center mb-5">
                        <i class="bi bi-check-circle-fill text-primary me-3 fs-5"></i>
                        <span class="fw-medium">99.99% Guaranteed SLA Uptime</span>
                    </div>
                    <a href="#contact" class="fw-bold text-decoration-none border-bottom border-primary pb-1">Get in
                        touch with our founders &rarr;</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Slider Section -->
    <section class="py-5 my-5" id="testimonials">
        <div class="container">
            <div class="text-center mb-5 pb-3">
                <h2 class="fw-bold display-6 text-dark">Don't Take Our Word For It</h2>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-indicators mb-0 pb-0" style="bottom: -20px;">
                            <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="0"
                                class="active" aria-current="true" aria-label="Slide 1"></button>
                            <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="1"
                                aria-label="Slide 2"></button>
                            <button type="button" data-bs-target="#testimonialCarousel" data-bs-slide-to="2"
                                aria-label="Slide 3"></button>
                        </div>
                        <div class="carousel-inner text-center px-4">
                            <div class="carousel-item active">
                                <i class="bi bi-quote text-primary opacity-25" style="font-size: 4rem;"></i>
                                <p class="testimonial-text mb-4">"ConsignX transformed how we manage our 50+ regional
                                    fleets. The automated tracking and customer transparency alone cut our support calls
                                    by 40%."</p>
                                <h6 class="fw-bold mb-0">Sarah Jenkins</h6>
                                <small class="text-muted">Operations Director, SwiftLine Logistics</small>
                            </div>
                            <div class="carousel-item">
                                <i class="bi bi-quote text-primary opacity-25" style="font-size: 4rem;"></i>
                                <p class="testimonial-text mb-4">"The Agent Portal is phenomenal. Our third-party
                                    vendors can now issue shipments on our network flawlessly without touching a
                                    difficult interface."</p>
                                <h6 class="fw-bold mb-0">Marcus Thorne</h6>
                                <small class="text-muted">CEO, Global Freight Co.</small>
                            </div>
                            <div class="carousel-item">
                                <i class="bi bi-quote text-primary opacity-25" style="font-size: 4rem;"></i>
                                <p class="testimonial-text mb-4">"As a customer, having a standalone tracking timeline
                                    that is beautiful and accurate makes me trust the courier company handling my
                                    valuable items."</p>
                                <h6 class="fw-bold mb-0">Emily R.</h6>
                                <small class="text-muted">Frequent Sender</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="py-5 bg-light" id="contact">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="fw-bold display-6 text-dark mb-4">Ready to Modernize?</h2>
                    <p class="text-muted mb-5">Leave your details below and a solution architect will get in touch
                        within 24 hours.</p>

                    <form class="bg-white p-4 p-md-5 rounded-4 shadow-sm border border-light text-start"
                        onsubmit="event.preventDefault(); alert('We will reach out soon!');">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Name</label>
                                <input type="text" class="form-control py-2 bg-light border-0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Company Name</label>
                                <input type="text" class="form-control py-2 bg-light border-0" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Work Email</label>
                                <input type="email" class="form-control py-2 bg-light border-0" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Message / Questions</label>
                                <textarea class="form-control py-2 bg-light border-0" rows="3"></textarea>
                            </div>
                            <div class="col-12 mt-4 text-center">
                                <button type="submit" class="btn btn-primary px-5 py-3 fw-bold rounded-pill w-100">Send
                                    Inquiry</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <h4 class="text-white fw-bold mb-4">ConsignX</h4>
                    <p class="small opacity-75 mb-4 pe-md-5">The enterprise-grade logistics and courier management
                        platform. Managing fleets, empowering agents, and delighting customers.</p>
                    <div class="d-flex gap-3 fs-5">
                        <a href="#"><i class="bi bi-twitter"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                        <a href="#"><i class="bi bi-github"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <h6 class="text-white fw-bold mb-4">Product</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2 small">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#">Pricing</a></li>
                        <li><a href="#">API Documentation</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6">
                    <h6 class="text-white fw-bold mb-4">Company</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2 small">
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h6 class="text-white fw-bold mb-4">Portals</h6>
                    <div class="d-flex gap-2 mb-2">
                        <a href="auth/login.php" class="btn btn-sm btn-outline-light rounded-pill px-3">Agent Login</a>
                        <a href="auth/register.php"
                            class="btn btn-sm btn-light text-dark rounded-pill px-3 fw-bold">Agent Register</a>
                    </div>
                    <a href="auth/login.php"
                        class="btn btn-sm btn-outline-secondary border-0 text-decoration-underline p-0 mt-2">Admin
                        Access</a>
                </div>
            </div>
            <div class="text-center mt-5 pt-4 border-top border-secondary border-opacity-25 small">
                &copy;
                <?= date('Y') ?> ConsignX Ltd. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>