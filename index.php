<?php
// FILE: /consignxAnti/index.php
require_once 'includes/config.php';
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$user_role = $_SESSION['user_role'] ?? '';
$dashboard_link = 'auth/login.php';
if ($is_logged_in) {
    if ($user_role === 'admin') $dashboard_link = 'admin/dashboard.php';
    elseif ($user_role === 'agent') $dashboard_link = 'agent/dashboard.php';
    elseif ($user_role === 'customer') $dashboard_link = 'customer/dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConsignX — Global Logistics Intelligence</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
        --bg:#050505;--bg2:#0a0a0a;--bg3:#0f0f0f;
        --white:#ffffff;--dim:#a1a1aa;--muted:#52525b;
        --blue:#3b82f6;--blue-g:rgba(59,130,246,0.15);--blue-b:#60a5fa;
        --bdr:rgba(255,255,255,0.06);--bdr2:rgba(255,255,255,0.12);
        --head:'Space Grotesk',sans-serif;--body:'Inter',sans-serif;
    }
    html{overflow-x:hidden;scroll-behavior: auto !important;}
    body{font-family:var(--body);background:var(--bg);color:var(--white);overflow-x:hidden;-webkit-font-smoothing:antialiased}

    /* ===== ELITE PRELOADER (Circular + Glass) ===== */
    #preloader{position:fixed;inset:0;z-index:999999;background:var(--bg);display:flex;flex-direction:column;align-items:center;justify-content:center}
    .p-glass{position:absolute;inset:0;background:radial-gradient(circle at center, rgba(59,130,246,0.1) 0%, transparent 70%);backdrop-filter:blur(100px);z-index:-1}
    .p-circle-wrap{position:relative;width:240px;height:240px;display:flex;align-items:center;justify-content:center}
    .p-svg{position:absolute;inset:0;transform:rotate(-90deg)}
    .p-circle-bg{fill:none;stroke:var(--bdr);stroke-width:2}
    .p-circle-val{fill:none;stroke:var(--blue);stroke-width:2;stroke-dasharray:628;stroke-dashoffset:628;transition:stroke-dashoffset 0.1s linear}
    .p-truck-node{position:absolute;width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;z-index:10;transform-origin: center center}
    .p-counter{font-family:var(--head);font-size:3.5rem;font-weight:700;letter-spacing:-2px}
    .p-label{margin-top:20px;font-size:0.7rem;letter-spacing:4px;color:var(--muted);text-transform:uppercase;font-weight:600}

    /* ===== MAIN CONTENT ===== */
    #mainContent{opacity:0;visibility:hidden} /* Hidden until preloader finishes */

    /* ===== NAV ===== */
    .nav-cx{position:fixed;top:0;width:100%;padding:24px 48px;display:flex;justify-content:space-between;align-items:center;z-index:10000;transition:all .4s ease;opacity:0}
    .nav-cx.scrolled{padding:14px 48px;background:rgba(5,5,5,.85);backdrop-filter:blur(20px);border-bottom:1px solid var(--bdr)}
    .n-brand{font-family:var(--head);font-weight:700;font-size:1.4rem;letter-spacing:-1px;color:var(--white);text-decoration:none}
    .n-links{display:flex;align-items:center;gap:32px}
    .n-link{color:var(--dim);text-decoration:none;font-size:.875rem;font-weight:500;transition:color .3s}
    .n-link:hover{color:var(--white)}
    .n-cta{padding:10px 28px;background:var(--white);color:var(--bg);border-radius:100px;text-decoration:none;font-size:.875rem;font-weight:600;transition:all .3s}
    .n-cta:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,255,255,.1);color:var(--bg)}

    /* ===== FIDELITY HERO (Pinning 2.0) ===== */
    .pin-wrap{height:1200vh;position:relative;z-index:1}
    .pin-panel{position:sticky;top:0;height:100vh;width:100%;overflow:hidden;display:flex;align-items:center;justify-content:center}
    
    /* Brand Zoom Layer */
    .brand-base{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;z-index:100;pointer-events:none}
    .brand-zoom-el{font-family:var(--head);font-size:clamp(120px, 20vw, 300px);font-weight:800;letter-spacing:-12px;color:var(--white);white-space:nowrap;will-change:transform, opacity, filter}

    /* Hero Background & Overlay */
    .hero-base{position:absolute;inset:0;z-index:10;opacity:0;visibility:hidden}
    .h-visual{position:absolute;inset:0;background:url('https://images.unsplash.com/photo-1586528116311-ad8ed7c663be?auto=format&fit=crop&w=1920&q=80') center/cover;filter:brightness(.25) saturate(1.1)}
    .h-glow{position:absolute;inset:0;background:radial-gradient(circle at 50% 50%, rgba(59,130,246,0.1) 0%, transparent 80%)}
    .h-vignette{position:absolute;inset:0;background:radial-gradient(circle at center, transparent 0%, var(--bg) 100%)}
    
    /* Sequential Titles */
    .h-titles-wrap{position:relative;z-index:20;width:100%;height:100%;display:flex;align-items:center;justify-content:center;text-align:center;padding:0 24px}
    .h-seq-title{position:absolute;font-family:var(--head);font-size:clamp(2.8rem,7.5vw,6.5rem);font-weight:800;line-height:1;letter-spacing:-3px;opacity:0;transform:translateY(60px);max-width:900px}
    
    /* Final CTA */
    .h-final-box{position:absolute;bottom:10vh;opacity:0;transform:translateY(30px);display:flex;gap:16px;z-index:30}
    .btn-p{padding:16px 40px;background:var(--white);color:var(--bg);border-radius:100px;font-weight:700;text-decoration:none;transition:all .4s}
    .btn-s{padding:16px 40px;background:rgba(255,255,255,0.05);color:var(--white);border:1px solid var(--bdr);border-radius:100px;font-weight:600;text-decoration:none;backdrop-filter:blur(10px);transition:all .4s}
    .btn-p:hover{transform:translateY(-4px);box-shadow:0 15px 35px rgba(255,255,255,0.1)}
    .btn-s:hover{background:rgba(255,255,255,0.1);border-color:var(--white)}

    /* ===== TYPOGRAPHY & SECTIONS ===== */
    .sec{padding:160px 48px;position:relative;z-index:100;background:var(--bg)}
    .sec-lab{font-size:.75rem;font-weight:700;letter-spacing:4px;text-transform:uppercase;color:var(--blue);margin-bottom:20px;display:block}
    .sec-tit{font-family:var(--head);font-size:clamp(2.5rem,6vw,4.5rem);font-weight:850;letter-spacing:-4px;line-height:.95;margin-bottom:30px}
    .sec-sub{font-size:1.15rem;color:var(--dim);line-height:1.7;max-width:580px}

    /* SERVICES */
    .svc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:80px}
    .svc-c{background:var(--bg2);border:1px solid var(--bdr);border-radius:24px;padding:48px 40px;transition:all .5s cubic-bezier(.16,1,.3,1);opacity:0;transform:translateY(40px)}
    .svc-c:hover{border-color:var(--white);background:var(--bg3);transform:translateY(-12px)}
    .svc-i{width:56px;height:56px;border-radius:16px;background:var(--blue-g);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--blue-b);margin-bottom:32px}
    .svc-c h4{font-family:var(--head);font-weight:700;font-size:1.3rem;margin-bottom:12px}
    .svc-c p{color:var(--dim);font-size:.95rem;line-height:1.7}

    /* PROCESS */
    .proc-wrap{margin-top:100px;position:relative;padding-left:100px}
    .proc-line-bg{position:absolute;left:39px;top:0;width:2px;height:100%;background:var(--bdr)}
    .proc-line-active{position:absolute;left:39px;top:0;width:2px;height:0%;background:var(--blue);z-index:2;box-shadow:0 0 20px var(--blue)}
    .proc-item{position:relative;padding:60px 0;opacity:0;transform:translateX(40px);transition:all .8s cubic-bezier(.16,1,.3,1)}
    .p-node{position:absolute;left:-100px;top:60px;width:78px;height:78px;border-radius:50%;background:var(--bg);border:1px solid var(--bdr);display:flex;align-items:center;justify-content:center;font-family:var(--head);font-size:1.5rem;font-weight:800;z-index:10;transition:all .5s}
    .proc-item.active .p-node{border-color:var(--blue);color:var(--white);background:var(--blue-g);box-shadow:0 0 40px var(--blue-g)}
    .proc-item h4{font-family:var(--head);font-size:1.6rem;font-weight:700;margin-bottom:10px}
    .proc-item p{color:var(--dim);max-width:480px}

    /* STATS */
    .stat-sec{border-top:1px solid var(--bdr);border-bottom:1px solid var(--bdr);padding:100px 48px}
    .stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:40px;text-align:center}
    .st-v{font-family:var(--head);font-size:5rem;font-weight:800;letter-spacing:-4px;line-height:1}
    .st-l{font-size:.7rem;letter-spacing:3px;text-transform:uppercase;color:var(--muted);font-weight:700;margin-top:15px}

    /* MASK REVEAL */
    .mask-wrap{height:250vh;position:relative}
    .mask-sticky{position:sticky;top:0;height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden}
    .mask-full-img{position:absolute;inset:0;background:url('https://images.unsplash.com/photo-1590247813693-5541d1c609fd?auto=format&fit=crop&w=2400&q=80') center/cover;filter:brightness(.4);clip-path:circle(0% at 50% 50%)}
    .mask-inner-txt{position:relative;z-index:10;text-align:center;padding:0 24px}
    .mask-inner-txt h2{font-family:var(--head);font-size:clamp(3rem,8vw,7rem);font-weight:850;letter-spacing:-5px;line-height:.9;opacity:0;transform:translateY(60px)}

    /* TRACKING */
    .trk-terminal{max-width:800px;margin:80px auto 0;background:linear-gradient(135deg,rgba(15,15,15,1),rgba(10,10,10,1));border:1px solid var(--bdr);border-radius:40px;padding:80px 60px;position:relative;overflow:hidden}
    .trk-terminal::before{content:'';position:absolute;inset:0;background:url('https://www.transparenttextures.com/patterns/carbon-fibre.png');opacity:.05}
    .trk-input{width:100%;background:rgba(0,0,0,0.3);border:1px solid var(--bdr);border-radius:20px;padding:24px;font-family:var(--head);font-size:1.5rem;font-weight:700;color:var(--white);text-align:center;letter-spacing:8px;margin-bottom:24px}
    .trk-input:focus{outline:none;border-color:var(--blue);box-shadow:0 0 30px var(--blue-g)}
    .trk-go{width:100%;padding:20px;background:var(--white);color:var(--bg);border-radius:18px;border:none;font-weight:800;font-size:1.1rem;cursor:pointer;transition:all .3s}
    .trk-go:hover{background:var(--blue);color:white;transform:translateY(-2px)}

    /* FOOTER */
    .footer{padding:120px 48px 60px;border-top:1px solid var(--bdr)}
    .f-huge{font-family:var(--head);font-size:clamp(3rem,14vw,14rem);font-weight:850;letter-spacing:-10px;line-height:.8;text-align:center;margin-top:100px;background:linear-gradient(180deg,#fff,rgba(255,255,255,0.05));-webkit-background-clip:text;-webkit-text-fill-color:transparent;opacity:0;transform:translateY(80px)}

    /* REVEALS */
    .reveal-box{opacity:0;transform:translateY(50px);transition:all 1s cubic-bezier(.16,1,.3,1)}
    .reveal-box.reveal-active{opacity:1;transform:translateY(0)}

    @media(max-width:992px){.svc-grid{grid-template-columns:repeat(2,1fr)}.stat-row{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:768px){.svc-grid,.stat-row{grid-template-columns:1fr}.nav-cx{padding:20px}.n-links{display:none}}
    </style>
</head>
<body>

<!-- ULTIMATE PRELOADER -->
<div id="preloader">
    <div class="p-glass"></div>
    <div class="p-circle-wrap">
        <svg class="p-svg" width="220" height="220">
            <circle class="p-circle-bg" cx="110" cy="110" r="100"></circle>
            <circle class="p-circle-val" id="pVal" cx="110" cy="110" r="100"></circle>
        </svg>
        <div class="p-truck-node" id="pTruck">🚚</div>
        <div class="p-counter" id="pCount">0</div>
    </div>
    <div class="p-label">Initializing Terminal</div>
</div>

<nav class="nav-cx" id="nav">
    <a href="#" class="n-brand">CONSIGNX</a>
    <div class="n-links">
        <a href="#services" class="n-link">Services</a>
        <a href="#process" class="n-link">Process</a>
        <a href="#track" class="n-link">Track</a>
        <a href="<?= $dashboard_link ?>" class="n-cta"><?= $is_logged_in ? 'Dashboard' : 'Login' ?></a>
    </div>
</nav>

<div id="mainContent">
    <!-- HERO PINNING 2.0 -->
    <div class="pin-wrap">
        <div class="pin-panel">
            <!-- Brand Zoom Layer -->
            <div class="brand-base">
                <div class="brand-zoom-el" id="zBrand">CONSIGNX</div>
            </div>

            <!-- Hero Panels -->
            <div class="hero-base" id="hBase">
                <div class="h-visual"></div>
                <div class="h-glow"></div>
                <div class="h-vignette"></div>
                <div class="h-titles-wrap">
                    <h1 class="h-seq-title" id="t1">The Future Of<br>Global Enterprise Logistics.</h1>
                    <h1 class="h-seq-title" id="t2">Intelligent Tracking.<br>Digital-First Delivery.</h1>
                    <h1 class="h-seq-title" id="t3">Autonomous Node<br>Route Optimization.</h1>
                    <h1 class="h-seq-title" id="t4">Built For Scales That<br>Demand Absolute Precision.</h1>
                    <div class="h-final-box" id="hCTA">
                        <a href="auth/register.php" class="btn-p">Start Enterprise Trial</a>
                        <a href="#services" class="btn-s">Explore Features</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SERVICES -->
    <section class="sec" id="services">
        <div class="container-fluid">
            <span class="sec-lab reveal-box">Platform Ecosystem</span>
            <h2 class="sec-tit reveal-box">Next-Generation<br>Delivery Logic.</h2>
            <div class="svc-grid">
                <div class="svc-c reveal-box"><div class="svc-i"><i class="bi bi-cpu"></i></div><h4>Neural Routing</h4><p>Machine learning pathways that evolve with every successful delivery across the globe.</p></div>
                <div class="svc-c reveal-box"><div class="svc-i"><i class="bi bi-shield-lock"></i></div><h4>Encrypted Chain</h4><p>End-to-end cryptographic security for every shipment ledger and client transaction.</p></div>
                <div class="svc-c reveal-box"><div class="svc-i"><i class="bi bi-kanban"></i></div><h4>Fleet Control</h4><p>Unified command center for managing thousands of autonomous and human nodes.</p></div>
                <div class="svc-c reveal-box"><div class="svc-i"><i class="bi bi-lightning"></i></div><h4>Zero-Delay Express</h4><p>Priority lane processing for urgent medical, legal, and enterprise-critical assets.</p></div>
                <div class="svc-c reveal-box"><div class="svc-i"><i class="bi bi-box-fill"></i></div><h4>Adaptive Storage</h4><p>Self-optimizing warehouse modules that reconfigure based on real-time demand flux.</p></div>
                <div class="svc-c reveal-box"><div class="svc-i"><i class="bi bi-graph-up"></i></div><h4>Hyper Analytics</h4><p>Predictive KPI modeling for logistics companies targeting 100% efficiency.</p></div>
            </div>
        </div>
    </section>

    <!-- PROCESS -->
    <section class="sec bg2" id="process">
        <div class="container-fluid">
            <span class="sec-lab reveal-box">The Workflow</span>
            <h2 class="sec-tit reveal-box">Autonomous<br>Pathfinding.</h2>
            <div class="proc-wrap" id="procWrap">
                <div class="proc-line-bg"></div>
                <div class="proc-line-active" id="procLine"></div>
                <div class="proc-item reveal-box"><div class="p-node">01</div><h4>Node Integration</h4><p>Shipment enters the digital mesh. Immediate path calculation triggered across all global nodes.</p></div>
                <div class="proc-item reveal-box"><div class="p-node">02</div><h4>Dynamic Pickup</h4><p>Proximity-based node assignment ensures pickup within minutes, not hours.</p></div>
                <div class="proc-item reveal-box"><div class="p-node">03</div><h4>In-Mesh Transit</h4><p>Package flows through optimized transit gates. Real-time telemetry broadcasted over 5G.</p></div>
                <div class="proc-item reveal-box"><div class="p-node">04</div><h4>Final Mile Logic</h4><p>Automated terminal handover for the last phase of the journey. Consumer notified via secure push.</p></div>
                <div class="proc-item reveal-box"><div class="p-node">05</div><h4>Ledger Settlement</h4><p>Delivery verified. Proof-of-arrival written to secondary ledger. Mission complete.</p></div>
            </div>
        </div>
    </section>

    <!-- STATS -->
    <section class="stat-sec">
        <div class="container-fluid">
            <div class="stat-row">
                <div class="reveal-box"><div class="st-v" data-v="10" data-s="K+">0</div><div class="st-l">Active Shipments</div></div>
                <div class="reveal-box"><div class="st-v" data-v="200" data-s="+">0</div><div class="st-l">Platform Partners</div></div>
                <div class="reveal-box"><div class="st-v" data-v="50" data-s="+">0</div><div class="st-l">Global Nodes</div></div>
                <div class="reveal-box"><div class="st-v" data-v="99" data-s=".9%">0</div><div class="st-l">Reliability Matrix</div></div>
            </div>
        </div>
    </section>

    <!-- MASK REVEAL -->
    <div class="mask-wrap">
        <div class="mask-sticky">
            <div class="mask-full-img" id="mImg"></div>
            <div class="mask-inner-txt">
                <h2 id="mTit">Absolute Visibility.<br>Universal Control.</h2>
            </div>
        </div>
    </div>

    <!-- TRACKING TERMINAL -->
    <section class="sec" id="track">
        <div class="container-fluid text-center">
            <span class="sec-lab reveal-box">Terminal Access</span>
            <h2 class="sec-tit reveal-box">Track Shipment.</h2>
            <div class="trk-terminal reveal-box">
                <form action="customer/track.php" method="GET">
                    <input type="text" name="id" class="trk-input" placeholder="CX-TRACE-000" required>
                    <button type="submit" class="trk-go">INITIALIZE TRACE →</button>
                </form>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-start reveal-box">
                <div>
                    <h3 class="n-brand" style="font-size:2rem">CONSIGNX</h3>
                    <p style="color:var(--muted);max-width:300px;margin-top:10px">The operating system for physical world connectivity.</p>
                </div>
                <div class="d-flex gap-5">
                    <div>
                        <h6 class="st-l">Platform</h6>
                        <a href="auth/login.php" class="n-link d-block mt-2">Login</a>
                        <a href="auth/register.php" class="n-link d-block mt-2">Create Account</a>
                        <a href="#" class="n-link d-block mt-2">Network Status</a>
                    </div>
                </div>
            </div>
            <h1 class="f-huge" id="fHuge">CONSIGNX</h1>
            <p style="text-align:center;color:var(--muted);font-size:.7rem;margin-top:80px">© <?= date('Y') ?> ConsignX Intelligence. Unified Core v4.0</p>
        </div>
    </footer>
</div>

<script>
gsap.registerPlugin(ScrollTrigger);

// 1. FIDELITY PRELOADER
(function(){
    const pVal = document.getElementById('pVal');
    const pCount = document.getElementById('pCount');
    const pTruck = document.getElementById('pTruck');
    const preloader = document.getElementById('preloader');
    const mainContent = document.getElementById('mainContent');
    const nav = document.getElementById('nav');
    
    const circ = 2 * Math.PI * 100;
    let p = 0;
    
    const interval = setInterval(() => {
        p += Math.floor(Math.random() * 3) + 1;
        if (p > 100) p = 100;
        
        // Circular progress
        const offset = circ - (p / 100 * circ);
        pVal.style.strokeDashoffset = offset;
        
        // Counter
        pCount.textContent = p;
        
        // Truck rotation path
        const angle = (p / 100 * 360) - 90;
        const rad = angle * (Math.PI / 180);
        const tx = Math.cos(rad) * 100;
        const ty = Math.sin(rad) * 100;
        pTruck.style.transform = `translate(${tx}px, ${ty}px) rotate(${angle + 90}deg)`;
        
        if (p >= 100) {
            clearInterval(interval);
            setTimeout(onLoadingDone, 500);
        }
    }, 30);

    function onLoadingDone() {
        const tl = gsap.timeline();
        tl.to(preloader, {
            yPercent: -100,
            duration: 1.4,
            ease: 'expo.inOut'
        })
        .set(mainContent, { visibility: 'visible' }, '-=1')
        .to(mainContent, {
            opacity: 1,
            duration: 1.2,
            ease: 'power2.out'
        }, '-=0.8')
        .to(nav, { opacity: 1, duration: 1 }, '-=0.6')
        .add(() => {
            ScrollTrigger.refresh();
            // Refresher failsafes
            setTimeout(() => ScrollTrigger.refresh(), 500);
            setTimeout(() => ScrollTrigger.refresh(), 1500);
        });
    }
    
    // Safety
    setTimeout(() => { if(p < 100) { clearInterval(interval); onLoadingDone(); } }, 7000);
})();

// 2. ULTIMATE HERO PINNING & ZOOM
const masterTl = gsap.timeline({
    scrollTrigger: {
        trigger: '.pin-wrap',
        start: 'top top',
        end: 'bottom bottom',
        scrub: 1,
        pin: true,
        anticipatePin: 1
    }
});

// Brand Zoom (Phase 1) - Extreme speed pass-through
masterTl.to('#zBrand', {
    scale: 150,
    opacity: 0,
    filter: 'blur(20px)',
    duration: 5,
    ease: 'power2.in'
});

// Reveal Hero Background (Phase 2)
masterTl.to('#hBase', {
    autoAlpha: 1,
    duration: 1,
    ease: 'power2.out'
}, '-=3.5');

// Sequential Storytelling (Phase 3)
const titles = ['#t1', '#t2', '#t3', '#t4'];
titles.forEach((t, i) => {
    masterTl.fromTo(t, 
        { opacity: 0, y: 100, filter: 'blur(10px)' }, 
        { opacity: 1, y: 0, filter: 'blur(0px)', duration: 2, ease: 'power3.out' },
        `+=0.5`
    );
    if(i < titles.length - 1) {
        masterTl.to(t, 
            { opacity: 0, y: -100, filter: 'blur(10px)', duration: 2, ease: 'power3.in' },
            `+=2`
        );
    }
});

// Final CTA (Phase 4)
masterTl.fromTo('#hCTA', 
    { opacity: 0, y: 40 }, 
    { opacity: 1, y: 0, duration: 1.5, ease: 'back.out(1.7)' },
    '+=0.5'
);

// 3. NAV SCROLL
window.addEventListener('scroll', () => {
    document.getElementById('nav').classList.toggle('scrolled', window.scrollY > 80);
});

// 4. SECTION REVEALS (Intersection Observer)
const obs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            e.target.classList.add('reveal-active');
            obs.unobserve(e.target);
        }
    });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal-box').forEach(el => obs.observe(el));

// 5. PROCESS LINE
gsap.to('#procLine', {
    height: '100%',
    ease: 'none',
    scrollTrigger: {
        trigger: '#procWrap',
        start: 'top 60%',
        end: 'bottom 60%',
        scrub: true
    }
});
document.querySelectorAll('.proc-item').forEach(item => {
    ScrollTrigger.create({
        trigger: item,
        start: 'top 65%',
        onEnter: () => item.classList.add('active')
    });
});

// 6. STATS COUNTER
document.querySelectorAll('.st-v').forEach(st => {
    const v = parseFloat(st.dataset.v);
    const s = st.dataset.s || '';
    ScrollTrigger.create({
        trigger: st,
        start: 'top 90%',
        once: true,
        onEnter: () => {
            gsap.to({ val: 0 }, {
                val: v,
                duration: 3,
                ease: 'power2.out',
                onUpdate: function() {
                    const current = Math.floor(this.targets()[0].val);
                    st.textContent = current + s;
                }
            });
        }
    });
});

// 7. MASK REVEAL
gsap.to('#mImg', {
    clipPath: 'circle(100% at 50% 50%)',
    ease: 'none',
    scrollTrigger: {
        trigger: '.mask-wrap',
        start: 'top top',
        end: 'bottom bottom',
        scrub: 1
    }
});
gsap.to('#mTit', {
    opacity: 1,
    y: 0,
    duration: 1.5,
    scrollTrigger: {
        trigger: '.mask-wrap',
        start: 'center center',
        scrub: 1
    }
});

// 8. FOOTER REVEAL
gsap.to('#fHuge', {
    opacity: 1,
    y: 0,
    duration: 1.5,
    ease: 'power4.out',
    scrollTrigger: {
        trigger: '.footer',
        start: 'top 70%'
    }
});

// 9. RE-INVENT HOVERS
document.querySelectorAll('.btn-p, .btn-s, .trk-go, .svc-c').forEach(el => {
    el.addEventListener('mouseenter', () => gsap.to(el, { scale: 1.03, duration: 0.4, ease: 'power2.out' }));
    el.addEventListener('mouseleave', () => gsap.to(el, { scale: 1, duration: 0.4, ease: 'power2.out' }));
});

</script>
</body>
</html>