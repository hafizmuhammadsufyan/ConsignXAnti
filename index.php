<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConsignX — Courier Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

    <style>
    /* ═══════════════════════════════════════════════════════════════
   RESET + TOKENS
   ═══════════════════════════════════════════════════════════════ */
    *,
    *::before,
    *::after {
        margin: 0;
        padding: 0;
        box-sizing: border-box
    }

    :root {
        --bg0: #05080e;
        --bg1: #080d18;
        --bg2: #0c1525;
        --bg3: #101e34;
        --card: #0f1b2e;
        --muted: #52525b;
        --ln: rgba(255, 255, 255, .07);
        --lnh: rgba(255, 255, 255, .13);
        --t1: #edf0ff;
        --t2: #a1a7b1;
        --bdr: rgba(255, 255, 255, 0.06);
        --t3: #7695be;
        --a: #3b7cfd;
        --am: #6366f1;
        --aw: #f59e0b;
        --at: #14b8a6;
        --fd: 'Syne', sans-serif;
        --fb: 'DM Sans', sans-serif;
        --head: 'Space Grotesk', sans-serif;
        --expo: cubic-bezier(.16, 1, .3, 1);
        --snap: cubic-bezier(.34, 1.56, .64, 1);
        --ease: cubic-bezier(.65, 0, .35, 1)
    }

    html {
        scroll-behavior: auto;
        overflow-x: hidden
    }

    body {
        background: var(--bg0);
        color: var(--t1);
        font-family: var(--fb);
        font-size: 16px;
        line-height: 1.6;
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased
    }

    ::-webkit-scrollbar {
        width: 3px
    }

    ::-webkit-scrollbar-track {
        background: var(--bg0)
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(59, 124, 253, .5);
        border-radius: 3px
    }

    /* ═══════════════════════════════════════════════════════════════
   LOADER
   ═══════════════════════════════════════════════════════════════ */
    #loader {
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: var(--bg0);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    #loader::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background-image:
            linear-gradient(rgba(59, 124, 253, .04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(59, 124, 253, .04) 1px, transparent 1px);
        background-size: 52px 52px;
    }

    #loader::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 700px;
        height: 380px;
        background: radial-gradient(ellipse, rgba(59, 124, 253, .1), transparent 70%);
        pointer-events: none;
        animation: lPulse 3s ease-in-out infinite alternate;
    }

    @keyframes lPulse {
        from {
            opacity: .6;
            transform: translate(-50%, -50%) scale(1)
        }

        to {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1.1)
        }
    }

    .ld-inner {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 40px
    }

    .ld-brand {
        font-family: var(--fd);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .4em;
        text-transform: uppercase;
        color: var(--t3)
    }

    .ld-brand em {
        font-style: normal;
        color: var(--a)
    }

    #ld-pct {
        font-family: var(--fd);
        font-size: clamp(80px, 13vw, 136px);
        font-weight: 800;
        letter-spacing: -.04em;
        color: var(--t1);
        line-height: 1;
        min-width: 4ch;
        text-align: center;
        position: relative;
    }

    #ld-pct::after {
        content: '%';
        position: absolute;
        top: 4px;
        right: -.52em;
        font-size: .34em;
        color: var(--a);
        letter-spacing: 0
    }

    .ld-road-wrap {
        width: min(500px, 90vw);
        display: flex;
        flex-direction: column;
        gap: 10px
    }

    .ld-road {
        position: relative;
        height: 52px;
        background: var(--bg2);
        border: 1px solid var(--ln);
        border-radius: 6px;
        overflow: hidden
    }

    .ld-fill {
        position: absolute;
        inset: 0;
        width: 0%;
        background: linear-gradient(90deg, rgba(59, 124, 253, .12), rgba(99, 102, 241, .3));
        transition: width .07s linear
    }

    .ld-dashes {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        overflow: hidden
    }

    .ld-dashes::after {
        content: '';
        display: block;
        width: 200%;
        height: 2px;
        background: repeating-linear-gradient(90deg, transparent 0, transparent 12px, rgba(255, 255, 255, .1) 12px, rgba(255, 255, 255, .1) 24px);
        animation: ldash .5s linear infinite
    }

    @keyframes ldash {
        to {
            transform: translateX(-50%)
        }
    }

    #ld-truck {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        left: 3%;
        width: 62px;
        height: 36px;
        transition: left .07s linear;
        filter: drop-shadow(0 0 8px rgba(59, 124, 253, .5))
    }

    .ld-lbl {
        font-size: 11px;
        letter-spacing: .22em;
        text-transform: uppercase;
        color: var(--t3);
        text-align: center
    }

    .ld-dust {
        position: absolute;
        inset: 0;
        z-index: 1;
        pointer-events: none;
        overflow: hidden
    }

    .dp {
        position: absolute;
        width: 2px;
        height: 2px;
        border-radius: 50%;
        background: var(--a);
        opacity: 0;
        animation: dprise var(--d) var(--dl) ease-in-out infinite
    }

    @keyframes dprise {
        0% {
            opacity: 0;
            transform: translateY(0) scale(.5)
        }

        30% {
            opacity: .7
        }

        80% {
            opacity: .2
        }

        100% {
            opacity: 0;
            transform: translateY(-130px) scale(1.8)
        }
    }

    /* ═══════════════════════════════════════════════════════════════
   NAVBAR
   ═══════════════════════════════════════════════════════════════ */
    #nav {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 800;
        padding: 22px 5%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all .35s ease;
        border-bottom: 1px solid transparent;
        opacity: 0;
        pointer-events: none;
    }

    #nav.vis {
        opacity: 1;
        pointer-events: auto
    }

    #nav.sc {
        padding: 14px 5%;
        background: rgba(5, 8, 14, .9);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border-color: var(--ln)
    }

    .nlogo {
        font-family: var(--fd);
        font-size: 21px;
        font-weight: 800;
        letter-spacing: -.025em;
        color: var(--t1);
        text-decoration: none
    }

    .nlogo span {
        color: var(--a)
    }

    .nlinks {
        display: flex;
        gap: 36px;
        list-style: none;
    }

    .nlinks a {
        font-size: 13.5px;
        color: var(--t1);
        text-decoration: none;
        position: relative;
        transition: color .25s, background .25s;
        padding: 6px 8px;
        border-radius: 8px;
    }

    .nlinks a::after {
        content: '';
        position: absolute;
        bottom: -4px;
        left: 0;
        right: 0;
        height: 1px;
        background: var(--a);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .3s var(--expo);
    }

    .nlinks a:hover {
        color: #ffffff;
        background: rgba(59, 124, 253, .16);
    }

    .nlinks a:hover::after {
        transform: scaleX(1);
    }

    .nlinks a:focus-visible {
        outline: 2px solid var(--a);
        outline-offset: 2px;
    }

    .nr {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .nr a {
        color: #fff;
    }

    .btn-g {
        font-family: var(--fb);
        font-size: 13.5px;
        color: #fff;
        background: none;
        border: 1px solid rgba(255, 255, 255, .2);
        border-radius: 8px;
        padding: 8px 16px;
        cursor: pointer;
        text-decoration: none;
        transition: color .25s, background .25s;
    }

    .btn-g:hover {
        color: #fff;
        background: rgba(59, 124, 253, .2);
    }

    .btn-g:hover {
        color: var(--t1)
    }

    .btn-p {
        font-family: var(--fb);
        font-size: 13.5px;
        font-weight: 500;
        color: #fff;
        background: var(--a);
        border: none;
        border-radius: 7px;
        padding: 9px 22px;
        cursor: pointer;
        text-decoration: none;
        position: relative;
        overflow: hidden;
        transition: transform .25s var(--expo), box-shadow .25s
    }

    .btn-p::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, .18), transparent);
        opacity: 0;
        transition: opacity .25s
    }

    .btn-p:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 28px rgba(59, 124, 253, .42)
    }

    .btn-p:hover::before {
        opacity: 1
    }

    #nav-toggle {
        display: none;
        position: absolute;
        top: 50%;
        right: 5%;
        transform: translateY(-50%);
        background: rgba(59, 124, 253, .22);
        border: 1px solid rgba(255, 255, 255, .24);
        color: #fff;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 20px;
        z-index: 900;
    }

    #nav.open .nlinks {
        display: flex;
    }

    #nav.open .nr {
        display: flex;
    }

    /* ═══════════════════════════════════════════════════════════════
   SECTION A: BRAND ZOOM (GSAP-PINNED — THE FIX)
   ═══════════════════════════════════════════════════════════════
   KEY FIX: We do NOT use CSS position:sticky here at all.
   Instead the element is a normal block div. GSAP ScrollTrigger
   handles ALL pinning via pin:true on #brand-scene.
   This means the scroller (window) keeps working perfectly —
   ScrollTrigger pins the element by lifting it to fixed positioning
   internally while the scroll container stays unlocked.
   ═══════════════════════════════════════════════════════════════ */
    #brand-scene {
        /* GSAP will pin this; do not add position:sticky */
        position: relative;
        width: 100%;
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: var(--bg0);
    }

    .bs-grid {
        position: absolute;
        inset: 0;
        pointer-events: none;
        background-image: linear-gradient(rgba(59, 124, 253, .04) 1px, transparent 1px), linear-gradient(90deg, rgba(59, 124, 253, .04) 1px, transparent 1px);
        background-size: 56px 56px;
    }

    .bs-glow {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 1000px;
        height: 600px;
        background: radial-gradient(ellipse, rgba(59, 124, 253, .13), transparent 65%);
        pointer-events: none;
        animation: bsglow 4s ease-in-out infinite alternate;
    }

    @keyframes bsglow {
        from {
            opacity: .7;
            transform: translate(-50%, -50%) scale(1)
        }

        to {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1.12)
        }
    }

    /* Hero bg that fades in behind the zooming text */
    #hero-bg {
        position: absolute;
        will-change: transform;

        inset: 0;
        z-index: 2;
        opacity: 0;
        transform: translateY(120px) scale(1.1);
        overflow: hidden;
    }

    img {
        display: block;
        max-width: 100%;
    }

    #hero-bg img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scale(1.06)
    }

    .hero-content {
        position: absolute;
        top: 50%;
        left: 8%;
        transform: translateY(-50%) scale(.9);
        max-width: 720px;
        color: #fff;
        z-index: 10;
        opacity: 0;
    }

    .hero-badge {
        display: inline-block;
        padding: 8px 14px;
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .18);
        border-radius: 30px;
        font-size: 13px;
        margin-bottom: 18px;
        backdrop-filter: blur(10px);
    }

    .hero-title {
        font-size: clamp(48px, 8vw, 64px);
        line-height: 1.1;
        font-weight: 700;
        margin-bottom: 20px;
    }

    .hero-title span {
        color: #4d7cff;
    }

    .hero-desc {
        font-size: 18px;
        opacity: .85;
        margin-bottom: 28px;
        max-width: 520px;
    }

    .hero-actions {
        display: flex;
        gap: 14px;
    }

    .btn-primary {
        padding: 12px 22px;
        background: #3b7cff;
        color: #fff;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
    }

    .btn-secondary {
        padding: 12px 22px;
        border: 1px solid rgba(255, 255, 255, .3);
        border-radius: 8px;
        color: #fff;
        text-decoration: none;
    }

    .hb-ov {
        position: absolute;
        inset: 0;
        background: linear-gradient(115deg, rgba(5, 8, 14, .92) 0%, rgba(5, 8, 14, .55) 55%, rgba(5, 8, 14, .18) 100%)
    }

    #b-canvas {
        position: absolute;
        inset: 0;
        z-index: 3;
        pointer-events: none
    }

    /* THE ZOOM TEXT */
    #bzt {
        position: absolute;
        /* Center it absolutely so transform-origin:center works cleanly */
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(1);
        z-index: 4;
        font-family: var(--fd);
        font-size: clamp(60px, 11.6vw, 210px);
        font-weight: 800;
        letter-spacing: -.04em;
        color: var(--t1);
        will-change: transform;

        line-height: 1;
        text-align: center;
        white-space: nowrap;
        will-change: transform, opacity;
        transform-origin: center center;
        user-select: none;
    }

    .bz-l {
        display: inline-block;
        overflow: hidden;
        vertical-align: bottom
    }

    .bz-i {
        display: inline-block;
        transform: translateY(115%)
    }

    #b-tag {
        position: absolute;
        bottom: 25%;
        left: 50%;
        transform: translateX(-50%);
        z-index: 4;
        font-size: 11px;
        font-weight: 400;
        letter-spacing: .3em;
        text-transform: uppercase;
        color: var(--t3);
        white-space: nowrap;
        opacity: 0;
    }

    #b-tag span {
        color: var(--a)
    }

    #b-sh {
        position: absolute;
        bottom: 42px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 4;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        opacity: 0;
    }

    .smou {
        width: 22px;
        height: 34px;
        border: 1.5px solid var(--t3);
        border-radius: 12px;
        position: relative
    }

    .smou-d {
        position: absolute;
        top: 5px;
        left: 50%;
        transform: translateX(-50%);
        width: 3px;
        height: 6px;
        background: var(--a);
        border-radius: 3px;
        animation: sdot 1.8s ease-in-out infinite
    }

    @keyframes sdot {

        0%,
        100% {
            top: 5px;
            opacity: 1
        }

        80% {
            top: 18px;
            opacity: .2
        }
    }

    .smou-l {
        font-size: 10px;
        letter-spacing: .18em;
        text-transform: uppercase;
        color: var(--t3)
    }

    /* ═══════════════════════════════════════════════════════════════
   SECTION B: HERO STORYTELLING (ALSO GSAP-PINNED)
   ═══════════════════════════════════════════════════════════════ */
    #hs-scene {
        position: relative;
        width: 100%;
        height: 100vh;
        overflow: hidden;
    }

    .hs-bg {
        position: absolute;
        inset: 0;
        background-image: url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=1800&q=80');
        background-size: cover;
        background-position: center;
        transform: scale(1.06);
        will-change: transform;
    }

    .hs-ov {
        position: absolute;
        inset: 0;
        background: linear-gradient(110deg, rgba(5, 8, 14, .9) 0%, rgba(5, 8, 14, .6) 55%, rgba(5, 8, 14, .18) 100%);
    }

    #h-canvas {
        position: absolute;
        inset: 0;
        z-index: 5;
        pointer-events: none
    }

    .hs-prog {
        position: absolute;
        left: 4%;
        top: 50%;
        transform: translateY(-50%);
        display: flex;
        flex-direction: column;
        gap: 16px;
        z-index: 10;
    }

    .hpd {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--t3);
        transition: background .3s, transform .3s, box-shadow .3s
    }

    .hpd.on {
        background: var(--a);
        transform: scale(1.6);
        box-shadow: 0 0 12px var(--a)
    }

    .hs-msgs {
        position: absolute;
        inset: 0
    }

    .hs-msg {
        position: absolute;
        left: 8%;
        top: 20vh;
        transform: translateY(-50%) translateY(60px);
        max-width: 700px;
        opacity: 0;
        will-change: transform, opacity;
    }

    .hm-eye {
        font-size: 11px;
        font-weight: 500;
        letter-spacing: .3em;
        text-transform: uppercase;
        color: var(--a);
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 10px
    }

    .hm-eye::before {
        content: '';
        display: inline-block;
        width: 36px;
        height: 1px;
        background: var(--a)
    }

    .hm-h {
        font-family: var(--fd);
        font-size: clamp(40px, 5.5vw, 74px);
        font-weight: 800;
        letter-spacing: -.03em;
        line-height: 1.04;
        color: var(--t1);
        margin-bottom: 22px;
    }

    .hm-h em {
        font-style: normal;
        background: linear-gradient(120deg, var(--a), var(--am));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text
    }

    .hm-p {
        font-size: 17px;
        font-weight: 300;
        color: var(--t2);
        line-height: 1.7;
        max-width: 500px
    }

    .hm-cta {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-top: 20px;
        font-family: var(--fb);
        font-size: 14px;
        font-weight: 500;
        color: #fff;
        background: var(--a);
        border: none;
        border-radius: 8px;
        padding: 12px 28px;
        cursor: pointer;
        text-decoration: none;
        transition: transform .25s var(--expo), box-shadow .25s;
    }

    .hm-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 28px rgba(59, 124, 253, .42)
    }

    /* Slide counter bottom right */
    .hs-counter {
        position: absolute;
        bottom: 44px;
        right: 6%;
        z-index: 20;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 12px;
    }

    .hs-cnum {
        font-family: var(--fd);
        font-size: 12px;
        font-weight: 600;
        color: var(--t3);
        letter-spacing: .1em
    }

    .hs-cnum strong {
        color: var(--t1);
        font-size: 20px;
        display: block;
        line-height: 1
    }

    .hs-dots {
        display: flex;
        gap: 6px
    }

    .hs-dot {
        width: 18px;
        height: 2px;
        border-radius: 2px;
        background: var(--ln);
        transition: background .3s, width .3s
    }

    .hs-dot.on {
        background: var(--a);
        width: 32px
    }

    .hs-hint {
        position: absolute;
        bottom: 44px;
        left: 8%;
        z-index: 20;
        display: flex;
        align-items: center;
        gap: 14px;
        font-size: 11px;
        letter-spacing: .2em;
        text-transform: uppercase;
        color: var(--t3)
    }

    .hs-line {
        width: 44px;
        height: 1px;
        background: var(--t3);
        overflow: hidden;
        position: relative
    }

    .hs-line::after {
        content: '';
        position: absolute;
        inset: 0;
        background: var(--a);
        animation: scan 1.8s ease infinite
    }

    @keyframes scan {
        0% {
            transform: translateX(-100%)
        }

        100% {
            transform: translateX(100%)
        }
    }

    /* ═══════════════════════════════════════════════════════════════
   SHARED UTILITIES
   ═══════════════════════════════════════════════════════════════ */
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 5%
    }

    .sl {
        font-size: 11px;
        font-weight: 500;
        letter-spacing: .3em;
        text-transform: uppercase;
        color: var(--a);
        display: block;
        margin-bottom: 14px
    }

    .sh {
        font-family: var(--fd);
        font-size: clamp(34px, 4.2vw, 56px);
        font-weight: 800;
        letter-spacing: -.03em;
        line-height: 1.06;
        color: var(--t1);
        margin-bottom: 18px
    }

    .ss {
        font-size: 17px;
        font-weight: 300;
        color: var(--t1);
        line-height: 1.7;
        max-width: 500px
    }

    .sd,
    .f-desc,
    .tc-t,
    .sde,
    .hm-p {
        color: var(--t1);
    }

    .rev {
        opacity: 0;
        transform: translateY(36px)
    }

    /* ═══════════════════════════════════════════════════════════════
   MARQUEE
   ═══════════════════════════════════════════════════════════════ */
    #mq {
        padding: 20px 0;
        overflow: hidden;
        border-top: 1px solid var(--ln);
        border-bottom: 1px solid var(--ln);
        background: var(--bg1)
    }

    .mqt {
        display: flex;
        gap: 52px;
        width: max-content;
        animation: mqm 22s linear infinite
    }

    .mqt:hover {
        animation-play-state: paused
    }

    @keyframes mqm {
        to {
            transform: translateX(-50%)
        }
    }

    .mqi {
        font-family: var(--fd);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .22em;
        text-transform: uppercase;
        color: var(--t1);
        display: flex;
        align-items: center;
        gap: 10px;
        white-space: nowrap;
        transition: color .25s
    }

    .mqi:hover {
        color: var(--a)
    }

    .mqi span {
        background: var(--a);
        color: #fff;
        border-radius: 50%;
        width: 8px;
        height: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .mqd {
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: var(--a);
        opacity: .5
    }

    /* ═══════════════════════════════════════════════════════════════
   SERVICES
   ═══════════════════════════════════════════════════════════════ */
    #services {
        padding: 140px 0;
        background: var(--bg1)
    }

    .svc-hd {
        text-align: center;
        margin-bottom: 72px
    }

    .svc-hd .ss {
        margin: 0 auto
    }

    .svc-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px
    }

    .svc-card {
        background: var(--card);
        border: 1px solid var(--ln);
        border-radius: 18px;
        padding: 42px 36px;
        position: relative;
        overflow: hidden;
        cursor: default;
        transition: border-color .3s, transform .45s var(--expo), box-shadow .45s;
    }

    .svc-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 25% 25%, rgba(59, 124, 253, .08), transparent 60%);
        opacity: 0;
        transition: opacity .4s
    }

    .svc-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--a), transparent);
        opacity: 0;
        transition: opacity .4s
    }

    .svc-card:hover {
        border-color: rgba(59, 124, 253, .28);
        transform: translateY(-9px);
        box-shadow: 0 28px 64px rgba(0, 0, 0, .44)
    }

    .svc-card:hover::before,
    .svc-card:hover::after {
        opacity: 1
    }

    .si {
        width: 54px;
        height: 54px;
        border-radius: 14px;
        background: rgba(59, 124, 253, .1);
        border: 1px solid rgba(59, 124, 253, .18);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin-bottom: 24px;
        transition: transform .4s var(--snap), background .3s
    }

    .si i {
        color: var(--a);
        font-size: 24px
    }

    .svc-card:hover .si {
        transform: rotate(-8deg) scale(1.1);
        background: rgba(59, 124, 253, .18)
    }

    .st {
        font-family: var(--fd);
        font-size: 18px;
        font-weight: 700;
        letter-spacing: -.01em;
        color: var(--t1);
        margin-bottom: 12px
    }

    .sd {
        font-size: 14px;
        font-weight: 300;
        color: var(--t2);
        line-height: 1.65
    }

    .sa {
        position: absolute;
        bottom: 30px;
        right: 30px;
        font-size: 18px;
        color: var(--t3);
        transition: transform .3s var(--expo), color .3s
    }

    .svc-card:hover .sa {
        transform: translate(4px, -4px);
        color: var(--a)
    }

    /* ═══════════════════════════════════════════════════════════════
   HOW IT WORKS
   ═══════════════════════════════════════════════════════════════ */
    #hiw {
        padding: 140px 0;
        background: var(--bg0)
    }

    .hiw-l {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 88px;
        align-items: start
    }

    .hiw-h .ss {
        margin-top: 16px
    }

    .steps {
        display: flex;
        flex-direction: column
    }

    .step {
        display: flex;
        gap: 24px;
        padding: 30px 0;
        border-bottom: 1px solid var(--ln)
    }

    .step:last-child {
        border-bottom: none
    }

    .strk {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 44px
    }

    .snum {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: var(--card);
        border: 1px solid var(--ln);
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: var(--fd);
        font-size: 13px;
        font-weight: 700;
        color: var(--t3);
        flex-shrink: 0;
        transition: background .3s, border-color .3s, color .3s;
        position: relative;
        z-index: 1
    }

    .step.on .snum {
        background: rgba(59, 124, 253, .12);
        border-color: rgba(59, 124, 253, .4);
        color: var(--a);
        box-shadow: 0 0 16px rgba(59, 124, 253, .25)
    }

    .scon {
        flex: 1;
        width: 1px;
        background: var(--ln);
        margin-top: 6px;
        position: relative;
        overflow: hidden;
        min-height: 18px
    }

    .scon-fill {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 0%;
        background: linear-gradient(180deg, var(--a), transparent)
    }

    .sbod {
        padding-top: 8px
    }

    .sico {
        font-size: 22px;
        margin-bottom: 10px
    }

    .stt {
        font-family: var(--fd);
        font-size: 17px;
        font-weight: 700;
        letter-spacing: -.01em;
        color: var(--t1);
        margin-bottom: 8px
    }

    .sde {
        font-size: 14px;
        color: var(--t2);
        line-height: 1.65;
        font-weight: 300
    }

    /* ═══════════════════════════════════════════════════════════════
   STATS
   ═══════════════════════════════════════════════════════════════ */
    #stats {
        padding: 100px 0;
        background: var(--bg1);
        border-top: 1px solid var(--ln);
        border-bottom: 1px solid var(--ln)
    }

    .stats-r {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1px;
        background: var(--ln);
        border: 1px solid var(--ln);
        border-radius: 18px;
        overflow: hidden
    }

    .stat-c {
        background: var(--card);
        padding: 52px 40px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        position: relative;
        overflow: hidden;
        transition: background .3s
    }

    .stat-c::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--a), transparent);
        transform: scaleX(0);
        transition: transform .5s var(--expo)
    }

    .stat-c:hover {
        background: rgba(18, 28, 48, .95)
    }

    .stat-c:hover::before {
        transform: scaleX(1)
    }

    .sico2 {
        font-size: 26px;
        margin-bottom: 10px
    }

    .snum2 {
        font-family: var(--fd);
        font-size: clamp(36px, 4vw, 52px);
        font-weight: 800;
        letter-spacing: -.03em;
        color: var(--t1);
        line-height: 1
    }

    .snum2 sup {
        font-size: .42em;
        color: var(--a);
        vertical-align: super
    }

    .slbl {
        font-size: 13.5px;
        color: var(--t2);
        font-weight: 300
    }

    /* ═══════════════════════════════════════════════════════════════
   FEATURES
   ═══════════════════════════════════════════════════════════════ */
    #features {
        padding: 140px 0;
        background: var(--bg0)
    }

    .feat-hd {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: end;
        margin-bottom: 72px
    }

    .feat-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px
    }

    .fc {
        background: var(--card);
        border: 1px solid var(--ln);
        border-radius: 18px;
        padding: 36px 32px;
        position: relative;
        overflow: hidden;
        transition: transform .4s var(--expo), border-color .3s, box-shadow .4s
    }

    .fc::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 50% 0%, rgba(99, 102, 241, .08), transparent 55%);
        opacity: 0;
        transition: opacity .4s
    }

    .fc:hover {
        transform: translateY(-7px);
        border-color: rgba(99, 102, 241, .22);
        box-shadow: 0 22px 54px rgba(0, 0, 0, .4)
    }

    .fc:hover::after {
        opacity: 1
    }

    .fc.w2 {
        grid-column: span 2
    }

    .fi {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: rgba(99, 102, 241, .1);
        border: 1px solid rgba(99, 102, 241, .18);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 21px;
        margin-bottom: 20px;
        transition: transform .35s var(--snap), background .3s
    }

    .fc:hover .fi {
        transform: rotate(8deg) scale(1.1);
        background: rgba(99, 102, 241, .18)
    }

    .fbadge {
        display: inline-block;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: var(--aw);
        background: rgba(245, 158, 11, .1);
        border: 1px solid rgba(245, 158, 11, .2);
        border-radius: 4px;
        padding: 3px 8px;
        margin-bottom: 12px
    }

    .ft {
        font-family: var(--fd);
        font-size: 17px;
        font-weight: 700;
        letter-spacing: -.01em;
        color: var(--t1);
        margin-bottom: 10px
    }

    .fd2 {
        font-size: 14px;
        color: var(--t2);
        line-height: 1.65;
        font-weight: 300
    }

    /* ═══════════════════════════════════════════════════════════════
   MASK REVEAL
   ═══════════════════════════════════════════════════════════════ */
    #mask-sec {
        padding: 140px 0;
        background: var(--bg1);
        overflow: hidden
    }

    .mask-hd {
        text-align: center;
        margin-bottom: 64px
    }

    .mask-hd .ss {
        margin: 16px auto 0
    }

    .mask-wrap {
        max-width: 1020px;
        margin: 0 auto;
        border-radius: 22px;
        overflow: hidden;
        aspect-ratio: 16/7;
        position: relative;
        /* Start fully masked; reveal begins once the section pins */
        clip-path: inset(0 100% 0 0 round 22px);
        will-change: clip-path
    }

    .mask-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scale(1.14);
        will-change: transform;
        display: block
    }

    .mask-tint {
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(59, 124, 253, .2), rgba(99, 102, 241, .12));
        mix-blend-mode: overlay;
        pointer-events: none
    }

    /* ═══════════════════════════════════════════════════════════════
   GALLERY SECTION  (3 rows × 6 columns = 18 images)
   ═══════════════════════════════════════════════════════════════ */
    #gallery {
        padding: 140px 0;
        background: var(--bg0);
        overflow: hidden;
    }

    .gal-hd {
        text-align: center;
        margin-bottom: 72px
    }

    .gal-hd .ss {
        margin: 0 auto
    }

    /* The grid itself */
    .gal-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        grid-template-rows: repeat(3, 240px);
        gap: 12px;
    }

    /* Base image item */
    .gal-item {
        position: relative;
        border-radius: 14px;
        overflow: hidden;
        cursor: pointer;
        opacity: 0;
        transform-origin: center;
        backface-visibility: hidden;
        transform: translateY(48px) scale(.96);
        /* GPU hint */
        will-change: transform, opacity;
        /* Clip content tightly */
        isolation: isolate;
    }

    /* Some featured cells span 2 columns for visual rhythm */
    .gal-item.wide {
        grid-column: span 2
    }

    /* Image fills the box */
    .gal-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transform: scale(1.08);
        transition: transform .7s var(--expo);
        will-change: transform;
    }

    .gal-item:hover img {
        transform: scale(1.15)
    }

    /* Dark base overlay always present */
    .gal-ov {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, transparent 30%, rgba(5, 8, 14, .75) 100%);
        transition: opacity .4s;
    }

    /* Hover overlay — the "premium" glass layer */
    .gal-hover {
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(59, 124, 253, 0.658) 0%, rgba(99, 101, 241, 0.63) 100%);
        opacity: 0;
        transition: opacity .4s;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .gal-item:hover .gal-hover {
        opacity: 1
    }

    /* Content inside hover */
    .gal-icon {
        font-size: 28px;
        transform: scale(.7) translateY(10px);
        transition: transform .4s var(--snap), opacity .4s;
        opacity: 0
    }

    .gal-item:hover .gal-icon {
        transform: scale(1) translateY(0);
        opacity: 1
    }

    .gal-label {
        font-family: var(--fd);
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .06em;
        color: #fff;
        text-align: center;
        transform: translateY(10px);
        opacity: 0;
        transition: transform .35s var(--expo) .05s, opacity .35s .05s;
    }

    .gal-item:hover .gal-label {
        transform: translateY(0);
        opacity: 1
    }

    /* Bottom info that's always slightly visible */
    .gal-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 16px 20px 18px;
        z-index: 2;
    }

    .gal-cat {
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .2em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .5);
        display: block;
        margin-bottom: 4px;
        transition: color .3s;
    }

    .gal-item:hover .gal-cat {
        color: rgba(255, 255, 255, .8)
    }

    .gal-name {
        font-family: var(--fd);
        font-size: 14px;
        font-weight: 700;
        color: rgba(255, 255, 255, .85);
        transition: color .3s;
    }

    .gal-item:hover .gal-name {
        color: #fff
    }

    /* Glowing border on hover */
    .gal-item::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 14px;
        box-shadow: inset 0 0 0 1px rgba(59, 124, 253, 0);
        transition: box-shadow .4s;
    }

    .gal-item:hover::after {
        box-shadow: inset 0 0 0 1px rgba(59, 124, 253, .5), 0 0 30px rgba(59, 124, 253, .2)
    }

    /* ═══════════════════════════════════════════════════════════════
   TESTIMONIALS
   ═══════════════════════════════════════════════════════════════ */
    #testi {
        padding: 140px 0;
        background: var(--bg1)
    }

    .testi-hd {
        text-align: center;
        margin-bottom: 72px
    }

    .testi-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px
    }

    .tc {
        background: var(--card);
        border: 1px solid var(--ln);
        border-radius: 18px;
        padding: 40px 36px;
        position: relative;
        overflow: hidden;
        transition: transform .4s var(--expo), border-color .3s
    }

    .tc:hover {
        transform: translateY(-7px);
        border-color: var(--lnh)
    }

    .tc-stars {
        display: flex;
        gap: 3px;
        margin-bottom: 14px
    }

    .tc-s {
        color: var(--aw);
        font-size: 13px
    }

    .tc-q {
        font-family: Georgia, serif;
        font-size: 56px;
        line-height: .6;
        color: var(--a);
        opacity: .28;
        margin-bottom: 18px;
        display: block
    }

    .tc-t {
        font-size: 15px;
        font-style: italic;
        font-weight: 300;
        color: var(--t2);
        line-height: 1.75;
        margin-bottom: 28px
    }

    .tc-a {
        display: flex;
        align-items: center;
        gap: 14px
    }

    .tc-av {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--a), var(--am));
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: var(--fd);
        font-size: 13px;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0
    }

    .tc-name {
        font-family: var(--fd);
        font-size: 14px;
        font-weight: 700;
        color: var(--t1)
    }

    .tc-role {
        font-size: 12px;
        color: var(--t3)
    }

    /* ═══════════════════════════════════════════════════════════════
   CTA
   ═══════════════════════════════════════════════════════════════ */
    #cta {
        padding: 140px 0;
        background: var(--bg1);
        text-align: center;
        position: relative;
        overflow: hidden
    }

    .cta-gl {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 900px;
        height: 500px;
        background: radial-gradient(ellipse, rgba(59, 124, 253, .1), transparent 68%);
        pointer-events: none
    }

    .cta-gr {
        position: absolute;
        inset: 0;
        background-image: linear-gradient(var(--ln) 1px, transparent 1px), linear-gradient(90deg, var(--ln) 1px, transparent 1px);
        background-size: 60px 60px;
        mask-image: radial-gradient(ellipse 70% 65% at 50% 50%, black, transparent);
        -webkit-mask-image: radial-gradient(ellipse 70% 65% at 50% 50%, black, transparent);
        pointer-events: none
    }

    .cta-h {
        font-family: var(--fd);
        font-size: clamp(38px, 5.5vw, 70px);
        font-weight: 800;
        letter-spacing: -.035em;
        color: var(--t1);
        line-height: 1.05;
        margin-bottom: 22px;
        position: relative;
        z-index: 2
    }

    .cta-h em {
        font-style: normal;
        background: linear-gradient(120deg, var(--a), var(--am));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text
    }

    .cta-s {
        font-size: 17px;
        font-weight: 300;
        color: var(--t2);
        max-width: 460px;
        margin: 0 auto 52px;
        line-height: 1.7;
        position: relative;
        z-index: 2
    }

    .cta-b {
        display: flex;
        justify-content: center;
        gap: 16px;
        position: relative;
        z-index: 2
    }

    .btn-cta {
        font-family: var(--fb);
        font-size: 15px;
        font-weight: 500;
        color: #fff;
        background: var(--a);
        border: none;
        border-radius: 10px;
        padding: 16px 42px;
        cursor: pointer;
        text-decoration: none;
        position: relative;
        overflow: hidden;
        transition: transform .3s var(--expo), box-shadow .3s
    }

    .btn-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 48px rgba(59, 124, 253, .44)
    }

    .btn-ctao {
        font-family: var(--fb);
        font-size: 15px;
        font-weight: 400;
        color: var(--t1);
        background: transparent;
        border: 1px solid var(--lnh);
        border-radius: 10px;
        padding: 16px 42px;
        cursor: pointer;
        text-decoration: none;
        transition: border-color .25s, background .25s, transform .25s var(--expo)
    }

    .btn-ctao:hover {
        border-color: rgba(255, 255, 255, .28);
        background: rgba(255, 255, 255, .04);
        transform: translateY(-2px)
    }

    /* ═══════════════════════════════════════════════════════════════
   FOOTER
   ═══════════════════════════════════════════════════════════════ */

    .footer {
        padding: 120px 48px 60px;
        border-top: 1px solid var(--bdr)
    }

    .f-huge {
        font-family: var(--head);
        font-size: clamp(3rem, 14vw, 14rem);
        font-weight: 850;
        letter-spacing: -8px;
        line-height: 1;
        text-align: center;
        margin-top: 60px;
        margin-bottom: 0;
        padding: 0 20px;
        background: linear-gradient(180deg, #fff, rgba(255, 255, 255, 0.05));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        opacity: 0;
        transform: translateY(100px);
        will-change: opacity, transform
    }

    .st-l {
        font-size: .7rem;
        letter-spacing: 3px;
        text-transform: uppercase;
        color: var(--muted);
        font-weight: 700;
        margin-top: 15px
    }

    .n-brand {
        font-family: var(--head);
        font-weight: 700;
        font-size: 1.4rem;
        letter-spacing: -1px;
        color: var(--white);
        text-decoration: none
    }

    .n-links {
        display: flex;
        align-items: center;
        gap: 32px
    }

    .n-link {
        color: var(--dim);
        text-decoration: none;
        font-size: .875rem;
        font-weight: 500;
        transition: color .3s
    }

    .n-link:hover {
        color: var(--white)
    }

    .n-cta {
        padding: 10px 28px;
        background: var(--white);
        color: var(--bg);
        border-radius: 100px;
        text-decoration: none;
        font-size: .875rem;
        font-weight: 600;
        transition: all .3s
    }

    .n-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(255, 255, 255, .1);
        color: var(--bg)
    }

    /* REVEALS */
    .reveal-box {
        opacity: 0;
        transform: translateY(50px);
        transition: opacity 1s cubic-bezier(.16, 1, .3, 1), transform 1s cubic-bezier(.16, 1, .3, 1);
        will-change: opacity, transform
    }

    .reveal-box.reveal-active {
        opacity: 1;
        transform: translateY(0)
    }

    #footer {
        background: var(--bg0);
        border-top: 1px solid var(--ln);
        overflow: hidden;
        opacity: 0;
        will-change: opacity
    }

    #footer.footer-visible {
        opacity: 1
    }

    .f-cin {
        padding: 88px 5% 64px;
        overflow: hidden;
        border-bottom: 1px solid var(--ln);
        position: relative
    }

    .f-cin::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse 80% 50% at 50% 100%, rgba(59, 124, 253, .05), transparent);
        pointer-events: none
    }

    .f-big {
        font-family: var(--fd);
        font-size: clamp(38px, 7vw, 104px);
        font-weight: 800;
        letter-spacing: -.04em;
        color: transparent;
        -webkit-text-stroke: 1px rgba(255, 255, 255, 0.822);
        line-height: 1;
        position: relative;
        z-index: 1
    }

    .fw {
        display: inline-block;
        overflow: hidden;
        vertical-align: bottom;
        margin-right: .2em
    }

    .fwi {
        display: inline-block;
        transform: translateY(108%);
        opacity: 0;
        will-change: opacity, transform
    }

    .fwi.word-visible {
        opacity: 1
    }

    .f-grid {
        padding: 60px 5%;
        display: grid;
        grid-template-columns: 1.6fr 1fr 1fr 1fr;
        gap: 48px
    }

    .f-logo {
        font-family: var(--fd);
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -.02em;
        color: var(--t1);
        text-decoration: none;
        display: inline-block;
        margin-bottom: 16px
    }

    .f-logo span {
        color: var(--a)
    }

    .f-desc {
        font-size: 14px;
        font-weight: 300;
        color: var(--t1);
        line-height: 1.7;
        max-width: 260px;
        margin-bottom: 28px
    }

    .f-soc {
        display: flex;
        gap: 10px
    }

    .f-sb {
        color: #fff;
    }

    .f-sb {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: var(--card);
        border: 1px solid var(--ln);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--t3);
        font-size: 13px;
        text-decoration: none;
        transition: border-color .25s, color .25s, background .25s, transform .25s var(--expo)
    }

    .f-sb:hover {
        border-color: var(--a);
        color: var(--a);
        background: rgba(59, 124, 253, .08);
        transform: translateY(-3px)
    }

    .f-ch {
        font-family: var(--fd);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--t1);
        margin-bottom: 20px
    }

    .fl {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 11px
    }

    .fl a {
        font-size: 14px;
        font-weight: 300;
        color: var(--t1);
        text-decoration: none;
        display: inline-block;
        transition: color .25s, padding-left .25s var(--expo)
    }

    .fl a:hover {
        color: var(--a);
        padding-left: 5px
    }

    .f-btm {
        padding: 22px 5%;
        border-top: 1px solid var(--ln);
        display: flex;
        align-items: center;
        justify-content: space-between
    }

    .f-copy {
        font-size: 13px;
        font-weight: 300;
        color: var(--t3)
    }

    .f-copy span {
        color: var(--a)
    }

    .f-stat {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--t3)
    }

    .s-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #22c55e;
        animation: spulse 2s ease infinite
    }

    @keyframes spulse {

        0%,
        100% {
            opacity: 1;
            transform: scale(1)
        }

        50% {
            opacity: .5;
            transform: scale(.7)
        }
    }

    /* ═══════════════════════════════════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════════════════════════════════ */
    @media(max-width:1100px) {
        .gal-grid {
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(6, 200px)
        }

        .gal-item.wide {
            grid-column: span 1
        }
    }

    @media(max-width:1024px) {

        .svc-grid,
        .testi-grid {
            grid-template-columns: repeat(2, 1fr)
        }

        .feat-grid {
            grid-template-columns: repeat(2, 1fr)
        }

        .fc.w2 {
            grid-column: span 2
        }

        .stats-r {
            grid-template-columns: repeat(2, 1fr)
        }

        .f-grid {
            grid-template-columns: 1fr 1fr;
            gap: 32px
        }

        .hiw-l,
        .feat-hd {
            grid-template-columns: 1fr;
            gap: 48px
        }

        .hero-title {
            font-size: clamp(48px, 8vw, 64px);
        }

        .hero-desc {
            font-size: 16px;
            max-width: 500px;
        }

        .hero-content {
            left: 6%;
            max-width: 600px;
        }
    }

    @media(max-width:768px) {

        .svc-grid,
        .feat-grid,
        .testi-grid {
            grid-template-columns: 1fr
        }

        .fc.w2 {
            grid-column: span 1
        }

        .stats-r {
            grid-template-columns: 1fr 1fr
        }

        .f-grid {
            grid-template-columns: 1fr
        }

        .nlinks {
            display: none;
            position: absolute;
            right: 5%;
            top: 100%;
            background: rgba(5, 8, 14, .96);
            border: 1px solid var(--ln);
            border-radius: 14px;
            flex-direction: column;
            width: min(240px, 85vw);
            padding: 10px;
            gap: 8px;
            max-height: 65vh;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 920;
            pointer-events: auto;
        }

        .nlinks li {
            width: 100%;
        }

        .nlinks a {
            display: block;
            width: 100%;
            color: var(--t1);
            padding: 10px 12px;
            border-radius: 8px;
            opacity: 1;
        }

        .nlinks a:hover {
            background: rgba(59, 124, 253, .2);
            color: #fff;
        }

        #nav-toggle {
            display: inline-flex;
        }

        .nr {
            display: none;
            position: absolute;
            right: 5%;
            top: calc(100% + 260px);
            flex-direction: column;
            width: min(240px, 85vw);
            background: rgba(5, 8, 14, .96);
            border: 1px solid var(--ln);
            border-radius: 14px;
            padding: 12px;
            gap: 10px;
        }

        #nav.open .nr {
            display: flex;
        }

        .nr a {
            display: block;
            width: 100%;
            padding: 12px 16px;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all .25s;
        }

        .nr .btn-g {
            background: rgba(59, 124, 253, .16);
            color: #fff;
            border: 1px solid rgba(59, 124, 253, .4);
            padding: 12px 16px;
            border-radius: 8px;
            display: block;
            width: 100%;
            text-align: center;
        }

        .nr .btn-g:hover {
            background: rgba(59, 124, 253, .28);
            border-color: var(--a);
        }

        .nr .btn-p {
            background: var(--a);
            color: #fff;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            display: block;
            width: 100%;
            text-align: center;
        }

        .nr .btn-p:hover {
            background: #2966e3;
            box-shadow: 0 4px 16px rgba(59, 124, 253, .5);
        }

        .nlinks li {
            width: 100%;
        }

        .nlinks li:last-child {
            margin-bottom: 0;
        }

        .hero-content {
            left: 50%;
            transform: translate(-50%, -50%) scale(.9);
            text-align: center;
            max-width: 90vw;
            padding: 0 20px;
        }

        .hero-title {
            font-size: clamp(36px, 10vw, 48px);
            line-height: 1.1;
        }

        .hero-desc {
            font-size: 15px;
            margin-bottom: 24px;
        }

        .hero-actions {
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary,
        .btn-secondary {
            width: 100%;
            min-width: 0;
            padding: 12px 16px;
            font-size: 15px;
        }

        .sh {
            font-size: clamp(28px, 4vw, 56px);
        }

        .ss {
            font-size: 16px;
        }

        .cta-h {
            font-size: clamp(32px, 6vw, 70px);
        }

        .cta-s {
            font-size: 16px;
        }

        .f-big {
            font-size: clamp(32px, 8vw, 104px);
        }

        .hs-prog {
            display: none
        }

        .gal-grid {
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(9, 180px)
        }
    }

    @media(max-width:480px) {
        .hero-content {
            padding: 0 16px;
        }

        .hero-title {
            font-size: clamp(24px, 14vw, 34px);
            line-height: 1.1;
        }

        .hero-desc {
            font-size: 14px;
            margin-bottom: 16px;
        }

        .hero-badge {
            font-size: 11px;
            padding: 6px 10px;
            margin-bottom: 14px;
        }

        .btn-primary,
        .btn-secondary {
            padding: 10px 16px;
            font-size: 14px;
        }

        .btn-primary,
        .btn-secondary {
            width: 100%;
            text-align: center;
        }

        .hero-actions {
            width: 100%;
        }

        .nlinks {
            width: min(230px, 93vw);
            right: 50%;
            transform: translateX(50%);
        }

        .nr {
            width: min(230px, 93vw);
            right: 50%;
            transform: translateX(50%);
        }

        #bzt {
            font-size: clamp(48px, 22vw, 110px);
            white-space: normal;
            line-height: 1;
            left: 50%;
            transform: translate(-50%, -50%) scale(1);
        }

        .sh {
            font-size: clamp(24px, 5vw, 28px);
        }

        .ss {
            font-size: 15px;
        }

        .cta-h {
            font-size: clamp(28px, 7vw, 32px);
        }

        .cta-s {
            font-size: 15px;
        }

        .f-big {
            font-size: clamp(28px, 10vw, 32px);
        }

        .gal-grid {
            grid-template-columns: 1fr;
            grid-template-rows: repeat(18, 160px);
        }

        .stats-r {
            grid-template-columns: 1fr;
        }

        .f-btm {
            flex-direction: column;
            gap: 12px;
            text-align: center;
        }

        .f-stat {
            justify-content: center;
        }
    }
    
    .sico i,
    .sico2 i,
    .fi i,
    .gal-icon i {
        color: var(--a);
        display: inline-flex;
        align-items: center;
        justify-content: center
    }

    .sico i {
        font-size: 32px
    }

    .sico2 i {
        font-size: 32px
    }

    .fi i {
        font-size: 28px
    }

    .gal-icon i {
        font-size: 24px
    }</style>
</head>

<body>

    <!-- ═══════════════════════════════════════════════════════════
     LOADER
     ═══════════════════════════════════════════════════════════ -->
    <div id="loader">
        <div class="ld-dust" id="ldust"></div>
        <div class="ld-inner">
            <div class="ld-brand"><em>Consign</em>X — Courier Management</div>
            <div id="ld-pct">0</div>
            <div class="ld-road-wrap">
                <div class="ld-road">
                    <div class="ld-fill" id="lfill"></div>
                    <div class="ld-dashes"></div>
                    <div id="ld-truck">
                        <svg viewBox="0 0 62 36" fill="none" xmlns="http://www.w3.org/2000/svg"
                            style="width:100%;height:100%">
                            <rect x="1" y="7" width="36" height="20" rx="2" fill="#1a2744" stroke="#3b7cfd"
                                stroke-width=".8" />
                            <rect x="1" y="10" width="36" height="2" fill="#3b7cfd" opacity=".25" />
                            <text x="10" y="21" font-family="sans-serif" font-size="7" fill="#3b7cfd"
                                font-weight="700">CX</text>
                            <rect x="37" y="11" width="18" height="16" rx="2.5" fill="#1e3566" stroke="#3b7cfd"
                                stroke-width=".8" />
                            <rect x="39" y="13" width="9" height="7" rx="1.5" fill="#3b7cfd" opacity=".45" />
                            <rect x="48" y="13" width="5" height="7" rx="1" fill="#6366f1" opacity=".3" />
                            <rect x="54" y="16" width="5" height="4" rx="1" fill="#fbbf24" opacity=".95" />
                            <rect x="38" y="5" width="2.5" height="6" rx="1" fill="#64748b" />
                            <circle cx="11" cy="28" r="5" fill="#0c1526" stroke="#475569" stroke-width="1" />
                            <circle cx="11" cy="28" r="2" fill="#3b7cfd" />
                            <circle cx="28" cy="28" r="5" fill="#0c1526" stroke="#475569" stroke-width="1" />
                            <circle cx="28" cy="28" r="2" fill="#3b7cfd" />
                            <circle cx="46" cy="28" r="5" fill="#0c1526" stroke="#475569" stroke-width="1" />
                            <circle cx="46" cy="28" r="2" fill="#3b7cfd" />
                        </svg>
                    </div>
                </div>
                <div class="ld-lbl">Initialising ConsignX Platform&hellip;</div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
     NAVBAR
     ═══════════════════════════════════════════════════════════ -->
    <nav id="nav">
        <a class="nlogo" href="#"><span>Consign</span>X</a>
        <button id="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
            <span>☰</span>
        </button>
        <ul class="nlinks">
            <li><a href="#services">Services</a></li>
            <li><a href="#hiw">Process</a></li>
            <li><a href="#features">Platform</a></li>
            <li><a href="#gallery">Gallery</a></li>
            <li><a href="#testi">Reviews</a></li>
        </ul>
        <div class="nr">
            <a href="auth/login.php" class="btn-g">Sign In</a>
            <a href="auth/register.php" class="btn-p">Get Started</a>
        </div>
    </nav>

    <!-- ═══════════════════════════════════════════════════════════
     BRAND ZOOM SCENE  — GSAP pin target
     ═══════════════════════════════════════════════════════════ -->
    <div id="brand-scene">
        <div class="bs-grid"></div>
        <div class="bs-glow"></div>
        <div id="hero-bg">
            <img src="https://png.pngtree.com/thumb_back/fh260/background/20250415/pngtree-freight-container-ship-with-world-logistics-map-in-the-background-illustrating-image_17184380.jpg"
                alt="" loading="eager">
            <div class="hb-ov"></div>
            <div class="hero-content">
                <div class="hero-badge">ConsignX Platform</div>

                <h1 class="hero-title">
                    Powering the Future <br>
                    of <span>Smart Logistics</span>
                </h1>

                <p class="hero-desc">
                    Manage shipments, automate dispatch and track deliveries
                    in real-time with the most advanced courier management platform.
                </p>

                <div class="hero-actions">
                    <a href="auth/register.php" class="btn-primary">Register Company</a>
                    <a href="#hiw" class="btn-secondary">See How It Works</a>
                </div>
            </div>
        </div>
        <canvas id="b-canvas"></canvas>
        <div id="bzt" aria-label="CONSIGNX"></div>
        <div id="b-tag">Courier Management &nbsp;<span>·</span>&nbsp; Built for Scale</div>
        <div id="b-sh">
            <div class="smou">
                <div class="smou-d"></div>
            </div>
            <span class="smou-l">Scroll to enter</span>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
     HERO STORYTELLING SCENE — GSAP pin target
     ═══════════════════════════════════════════════════════════ -->
    <div id="hs-scene">
        <div class="hs-bg" id="hsbg"></div>
        <div class="hs-ov"></div>
        <canvas id="h-canvas"></canvas>
        <div class="hs-prog" id="hsprog">
            <div class="hpd on"></div>
            <div class="hpd"></div>
            <div class="hpd"></div>
            <div class="hpd"></div>
        </div>
        <div class="hs-msgs">
            <div class="hs-msg" id="hm0">
                <div class="hm-eye">The Platform</div>
                <h1 class="hm-h">Powering <em>Modern</em><br>Logistics</h1>
                <p class="hm-p">A unified operations hub for couriers, dispatchers and logistics directors who demand
                    precision and speed at every step.</p>
                <a href="#services" class="hm-cta">Explore Services →</a>
            </div>
            <div class="hs-msg" id="hm1">
                <div class="hm-eye">Intelligence</div>
                <h2 class="hm-h">Smart Courier<br><em>Management</em></h2>
                <p class="hm-p">AI-powered dispatch, route optimisation and anomaly detection — turning raw logistics
                    data into competitive advantage.</p>
                <a href="#features" class="hm-cta">See Platform →</a>
            </div>
            <div class="hs-msg" id="hm2">
                <div class="hm-eye">Visibility</div>
                <h2 class="hm-h">Real-Time<br><em>Shipment</em> Visibility</h2>
                <p class="hm-p">Live GPS tracking, automated milestone notifications and digital proof-of-delivery —
                    customers always know where their package is.</p>
                <a href="#hiw" class="hm-cta">How It Works →</a>
            </div>
            <div class="hs-msg" id="hm3">
                <div class="hm-eye">Infrastructure</div>
                <h2 class="hm-h"><em>Scalable</em><br>Delivery Networks</h2>
                <p class="hm-p">From 50 shipments a day to 50,000 — ConsignX scales effortlessly without adding
                    operational overhead.</p>
                <a href="#cta" class="hm-cta">Start Free Trial →</a>
            </div>
        </div>
        <div class="hs-counter">
            <div class="hs-cnum"><strong id="hscnum">01</strong>/ 04</div>
            <div class="hs-dots">
                <div class="hs-dot on"></div>
                <div class="hs-dot"></div>
                <div class="hs-dot"></div>
                <div class="hs-dot"></div>
            </div>
        </div>
        <!-- <div class="hs-hint">
            <div class="hs-line"></div>Scroll to explore
        </div> -->
    </div>

    <!-- ═══════════════════════════════════════════════════════════
     MARQUEE
     ═══════════════════════════════════════════════════════════ -->
    <div id="mq">
        <div class="mqt" id="mqt"></div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
     SERVICES
     ═══════════════════════════════════════════════════════════ -->
    <section id="services">
        <div class="container">
            <div class="svc-hd">
                <span class="sl rev">What We Offer</span>
                <h2 class="sh rev">Complete Logistics Coverage</h2>
                <p class="ss rev">From domestic last-mile to international freight — one platform handles every courier
                    need with precision.</p>
            </div>
            <div class="svc-grid">
                <div class="svc-card rev">
                    <div class="si"><i class="bi bi-box2"></i></div>
                    <div class="st">Domestic Shipping</div>
                    <div class="sd">Next-day and same-day delivery across all major cities. Optimised routing ensures
                        your packages arrive on time, every time.</div>
                    <div class="sa">↗</div>
                </div>
                <div class="svc-card rev">
                    <div class="si"><i class="bi bi-airplane"></i></div>
                    <div class="st">International Delivery</div>
                    <div class="sd">Seamless cross-border logistics with customs clearance support, real-time tracking
                        and door-to-door delivery worldwide.</div>
                    <div class="sa">↗</div>
                </div>
                <div class="svc-card rev">
                    <div class="si"><i class="bi bi-lightning-charge"></i></div>
                    <div class="st">Express Courier</div>
                    <div class="sd">Priority pickup and delivery for urgent shipments. Guaranteed 2-hour windows with
                        live agent updates along the journey.</div>
                    <div class="sa">↗</div>
                </div>
                <div class="svc-card rev">
                    <div class="si"><i class="bi bi-building"></i></div>
                    <div class="st">Warehouse Logistics</div>
                    <div class="sd">Full-service fulfilment centres with inventory management, pick-and-pack and returns
                        processing under one roof.</div>
                    <div class="sa">↗</div>
                </div>
                <div class="svc-card rev">
                    <div class="si"><i class="bi bi-satellite"></i></div>
                    <div class="st">Fleet Tracking</div>
                    <div class="sd">GPS-powered real-time fleet management with route optimisation, driver scoring and
                        fuel efficiency analytics.</div>
                    <div class="sa">↗</div>
                </div>
                <div class="svc-card rev">
                    <div class="si"><i class="bi bi-graph-up"></i></div>
                    <div class="st">Delivery Analytics</div>
                    <div class="sd">Comprehensive performance dashboards covering SLA compliance, customer satisfaction
                        and operational cost intelligence.</div>
                    <div class="sa">↗</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
     HOW IT WORKS
     ═══════════════════════════════════════════════════════════ -->
    <section id="hiw">
        <div class="container">
            <div class="hiw-l">
                <div class="hiw-h">
                    <span class="sl rev">The Process</span>
                    <h2 class="sh rev">Five Steps to<br>Perfect Delivery</h2>
                    <p class="ss rev">Our proven end-to-end process ensures every shipment is handled with precision and
                        care — from creation to confirmation.</p>
                </div>
                <div class="steps">
                    <div class="step rev">
                        <div class="strk">
                            <div class="snum">01</div>
                            <div class="scon">
                                <div class="scon-fill"></div>
                            </div>
                        </div>
                        <div class="sbod">
                            <div class="sico"><i class="bi bi-pencil-square"></i></div>
                            <div class="stt">Create Shipment</div>
                            <div class="sde">Generate labels, schedule pickups and notify recipients — all in under 60
                                seconds from the dashboard.</div>
                        </div>
                    </div>
                    <div class="step rev">
                        <div class="strk">
                            <div class="snum">02</div>
                            <div class="scon">
                                <div class="scon-fill"></div>
                            </div>
                        </div>
                        <div class="sbod">
                            <div class="sico"><i class="bi bi-door-open"></i></div>
                            <div class="stt">Pickup Package</div>
                            <div class="sde">Assign the nearest available courier automatically. Real-time ETA updates
                                keep senders informed from the moment of pickup.</div>
                        </div>
                    </div>
                    <div class="step rev">
                        <div class="strk">
                            <div class="snum">03</div>
                            <div class="scon">
                                <div class="scon-fill"></div>
                            </div>
                        </div>
                        <div class="sbod">
                            <div class="sico"><i class="bi bi-truck"></i></div>
                            <div class="stt">In Transit</div>
                            <div class="sde">Live GPS tracking with automated checkpoints. Smart rerouting avoids delays
                                from traffic, weather or road conditions.</div>
                        </div>
                    </div>
                    <div class="step rev">
                        <div class="strk">
                            <div class="snum">04</div>
                            <div class="scon">
                                <div class="scon-fill"></div>
                            </div>
                        </div>
                        <div class="sbod">
                            <div class="sico"><i class="bi bi-geo-alt"></i></div>
                            <div class="stt">Out for Delivery</div>
                            <div class="sde">Recipients receive live tracking links with accurate delivery windows.
                                Digital proof-of-delivery captures signatures instantly.</div>
                        </div>
                    </div>
                    <div class="step rev">
                        <div class="strk">
                            <div class="snum">05</div>
                        </div>
                        <div class="sbod">
                            <div class="sico"><i class="bi bi-check-circle"></i></div>
                            <div class="stt">Delivered</div>
                            <div class="sde">Confirmation notification sent to all parties. Delivery report archived for
                                billing and compliance automatically.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
     STATS
     ═══════════════════════════════════════════════════════════ -->
    <section id="stats">
        <div class="container">
            <div class="stats-r">
                <div class="stat-c rev">
                    <div class="sico2"><i class="bi bi-box2"></i></div>
                    <div class="snum2" data-t="10" data-s="K+">0</div>
                    <div class="slbl">Shipments Delivered</div>
                </div>
                <div class="stat-c rev">
                    <div class="sico2"><i class="bi bi-handshake"></i></div>
                    <div class="snum2" data-t="200" data-s="+">0</div>
                    <div class="slbl">Courier Partners</div>
                </div>
                <div class="stat-c rev">
                    <div class="sico2"><i class="bi bi-buildings"></i></div>
                    <div class="snum2" data-t="50" data-s="+">0</div>
                    <div class="slbl">Cities Covered</div>
                </div>
                <div class="stat-c rev">
                    <div class="sico2"><i class="bi bi-star-fill"></i></div>
                    <div class="snum2" data-t="99" data-s="%">0</div>
                    <div class="slbl">Delivery Success Rate</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
     FEATURES
     ═══════════════════════════════════════════════════════════ -->
    <section id="features">
        <div class="container">
            <div class="feat-hd">
                <div><span class="sl rev">Platform Power</span>
                    <h2 class="sh rev">Built for Scale,<br>Designed for Speed</h2>
                </div>
                <p class="ss rev">Every feature engineered to reduce friction, improve efficiency and delight both
                    operators and recipients.</p>
            </div>
            <div class="feat-grid">
                <div class="fc w2 rev">
                    <div class="fbadge">Most Popular</div>
                    <div class="fi"><i class="bi bi-map"></i></div>
                    <div class="ft">Smart Route Optimisation</div>
                    <div class="fd2">AI-powered routing analyses real-time traffic, package priorities and driver
                        capacity to build the most efficient delivery sequences — reducing fuel costs by up to 35% while
                        improving on-time performance across your entire fleet.</div>
                </div>
                <div class="fc rev">
                    <div class="fi"><i class="bi bi-bell"></i></div>
                    <div class="ft">Proactive Notifications</div>
                    <div class="fd2">Automated SMS, email and WhatsApp updates at every shipment milestone.</div>
                </div>
                <div class="fc rev">
                    <div class="fi"><i class="bi bi-shield-lock"></i></div>
                    <div class="ft">Proof of Delivery</div>
                    <div class="fd2">Digital signatures, photo capture and OTP verification for secure delivery
                        confirmation.</div>
                </div>
                <div class="fc rev">
                    <div class="fi"><i class="bi bi-credit-card"></i></div>
                    <div class="ft">COD Management</div>
                    <div class="fd2">Automated cash-on-delivery reconciliation with instant remittance reports and
                        settlement tracking.</div>
                </div>
                <div class="fc rev">
                    <div class="fi"><i class="bi bi-puzzle"></i></div>
                    <div class="ft">API &amp; Integrations</div>
                    <div class="fd2">REST API and pre-built connectors for Shopify, WooCommerce, Magento and 50+
                        platforms.</div>
                </div>
                <div class="fc rev">
                    <div class="fi"><i class="bi bi-graph-up"></i></div>
                    <div class="ft">Business Intelligence</div>
                    <div class="fd2">Custom dashboards, automated reports and predictive analytics for smarter
                        operational decisions.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
     MASK REVEAL
     ═══════════════════════════════════════════════════════════ -->
    <section id="mask-sec">
        <div class="container">
            <div class="mask-hd">
                <span class="sl rev">The ConsignX Network</span>
                <h2 class="sh rev">Every Route. Every City.<br>One Platform.</h2>
                <p class="ss rev">A nationwide logistics network connecting thousands of couriers, warehouses and
                    businesses in real time.</p>
            </div>
            <div class="mask-wrap" id="maskWrap">
                <img class="mask-img" id="maskImg"
                    src="https://images.unsplash.com/photo-1494412574643-ff11b0a5c1c3?w=1800&q=80"
                    alt="Logistics network" loading="lazy">
                <div class="mask-tint"></div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
     GALLERY  (3 rows × 6 cols)
     ═══════════════════════════════════════════════════════════ -->
    <section id="gallery">
        <div class="container">
            <div class="gal-hd">
                <span class="sl rev">Visual Journey</span>
                <h2 class="sh rev">Logistics in Motion</h2>
                <p class="ss rev">A window into the world ConsignX powers — every package, every mile, every delivery.
                </p>
            </div>
            <!-- 18 cells: row1 has 2 wide + 4 normal = 8 cols worth → use 4 normal + 1 wide = 6 units. Rhythm: wide, normal, normal, normal, normal, wide across rows -->
            <div class="gal-grid" id="gal-grid"></div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
     TESTIMONIALS
     ═══════════════════════════════════════════════════════════ -->
    <section id="testi">
        <div class="container">
            <div class="testi-hd">
                <span class="sl rev">Customer Stories</span>
                <h2 class="sh rev">Trusted by Logistics Leaders Worldwide</h2>
            </div>
            <div class="testi-grid">
                <div class="tc rev">
                    <div class="tc-stars"><span class="tc-s">★</span><span class="tc-s">★</span><span
                            class="tc-s">★</span><span class="tc-s">★</span><span class="tc-s">★</span></div><span
                        class="tc-q">"</span>
                    <p class="tc-t">ConsignX transformed our entire operation. We went from 200 shipments a day manually
                        to 2,000 automatically. Real-time visibility alone paid for the platform in the first month.</p>
                    <div class="tc-a">
                        <div class="tc-av">AK</div>
                        <div>
                            <div class="tc-name">Ahmed Karimi</div>
                            <div class="tc-role">COO, FastShip Logistics</div>
                        </div>
                    </div>
                </div>
                <div class="tc rev">
                    <div class="tc-stars"><span class="tc-s">★</span><span class="tc-s">★</span><span
                            class="tc-s">★</span><span class="tc-s">★</span><span class="tc-s">★</span></div><span
                        class="tc-q">"</span>
                    <p class="tc-t">Route optimisation cut our fuel costs by 28% in the first quarter. The driver app is
                        intuitive — our team adopted it within days. Best logistics investment we've made in a decade.
                    </p>
                    <div class="tc-a">
                        <div class="tc-av">SP</div>
                        <div>
                            <div class="tc-name">Sara Patel</div>
                            <div class="tc-role">Head of Operations, QuickDrop</div>
                        </div>
                    </div>
                </div>
                <div class="tc rev">
                    <div class="tc-stars"><span class="tc-s">★</span><span class="tc-s">★</span><span
                            class="tc-s">★</span><span class="tc-s">★</span><span class="tc-s">★</span></div><span
                        class="tc-q">"</span>
                    <p class="tc-t">Customer complaints dropped 60% after deploying ConsignX. Proactive notifications
                        mean our support team handles exceptions, not routine tracking queries. A genuine game-changer.
                    </p>
                    <div class="tc-a">
                        <div class="tc-av">MR</div>
                        <div>
                            <div class="tc-name">Michael Reyes</div>
                            <div class="tc-role">VP Customer Success, UrbanCourier</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
     CTA
     ═══════════════════════════════════════════════════════════ -->
    <section id="cta">
        <div class="cta-gl"></div>
        <div class="cta-gr"></div>
        <div class="container" style="position:relative;z-index:2">
            <span class="sl rev" style="display:block;text-align:center;margin-bottom:18px">Get Started Today</span>
            <h2 class="cta-h rev">Ready to <em>Transform</em><br>Your Logistics?</h2>
            <p class="cta-s rev">Join thousands of logistics companies already using ConsignX to deliver faster, smarter
                and at lower cost.</p>
            <div class="cta-b rev">
                <a href="auth/register.php" class="btn-cta">Register Now</a>
                <!-- <a href="#" class="btn-ctao">Schedule a Demo</a> -->
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
     FOOTER
     ═══════════════════════════════════════════════════════════ -->
    <footer id="footer">
        <div class="f-cin">
            <div class="f-big" id="fbig"></div>
        </div>
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-start reveal-box rev">
                <div>
                    <h3 class="n-brand" style="font-size:2rem">CONSIGNX</h3>
                    <p style="color:var(--muted);max-width:300px;margin-top:10px">The operating system for physical
                        world connectivity.</p>
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
            <h1 class="f-huge rev" id="fHuge">CONSIGNX</h1>
        </div>
        <div class="f-btm">
            <p class="f-copy">© 2026 <span>ConsignX</span>. All rights reserved.</p>
            <div class="f-stat">
                <div class="s-dot"></div>All systems operational
            </div>
        </div>
    </footer>

    <!-- ═══════════════════════════════════════════════════════════
     JAVASCRIPT
     ═══════════════════════════════════════════════════════════ -->
    <script>
    // 8. FOOTER REVEAL - Premium bottom-to-top entrance
    gsap.timeline({
        scrollTrigger: {
            trigger: '#footer',
            start: 'top 85%',
            end: 'top 40%',
            scrub: 0.8,
            once: false
        }
    })
    .to('#footer', {
        opacity: 1,
        duration: 1,
        ease: 'power2.inOut'
    }, 0)
    .to('.fwi', {
        opacity: 1,
        y: 0,
        duration: 0.9,
        ease: 'power3.out',
        stagger: 0.12
    }, 0.2)
    .to('#fHuge', {
        opacity: 1,
        y: 0,
        duration: 1.1,
        ease: 'power3.out'
    }, 0.3)
    .to('.reveal-box', {
        opacity: 1,
        y: 0,
        duration: 0.95,
        ease: 'power2.out'
    }, 0.1);

    // 4. SECTION REVEALS (Intersection Observer)
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('reveal-active');
                obs.unobserve(e.target);
            }
        });
    }, {
        threshold: 0.1
    });
    document.querySelectorAll('.reveal-box').forEach(el => obs.observe(el));



    (function() {
        'use strict';

        /* ── Register plugins ── */
        gsap.registerPlugin(ScrollTrigger);
        /* CRITICAL: always use window as scroller */
        ScrollTrigger.defaults({
            scroller: window,
            invalidateOnRefresh: true
        });

        var clamp = function(v, a, b) {
            return Math.min(Math.max(v, a), b);
        };

        /* ════════════════════════════════════════════════════════════
           BOOTSTRAP: inject DOM content before anything else
           ════════════════════════════════════════════════════════════ */

        /* Loader dust */
        (function() {
            var c = document.getElementById('ldust');
            for (var i = 0; i < 36; i++) {
                var p = document.createElement('div');
                p.className = 'dp';
                p.style.cssText = 'left:' + (Math.random() * 100) + '%;bottom:' + (Math.random() * 20) +
                    '%;--d:' + (1.5 + Math.random() * 2.2) + 's;--dl:' + (Math.random() * 3) + 's;';
                c.appendChild(p);
            }
        })();

        /* Marquee */
        (function() {
            var items = ['Domestic Shipping', 'Express Delivery', 'Fleet Tracking', 'Warehouse Mgmt',
                'Live Analytics', 'COD Settlement', 'Route Optimisation', 'API Integration',
                'Real-Time Alerts', 'Proof of Delivery', 'International Freight', 'Smart Dispatch'
            ];
            var t = document.getElementById('mqt');
            items.concat(items).forEach(function(lbl) {
                var d = document.createElement('div');
                d.className = 'mqi';
                d.innerHTML = '<span class="mqd"></span>' + lbl;
                t.appendChild(d);
            });
        })();

        /* Brand zoom letters */
        (function() {
            var el = document.getElementById('bzt');
            el.innerHTML = 'CONSIGNX'.split('').map(function(c) {
                return '<span class="bz-l"><span class="bz-i">' + c + '</span></span>';
            }).join('');
        })();

        /* Footer words */
        (function() {
            var el = document.getElementById('fbig');
            el.innerHTML = ['Delivering', 'the', 'Future', 'of', 'Logistics'].map(function(w) {
                return '<span class="fw"><span class="fwi">' + w + '</span></span>';
            }).join(' ');
        })();

        /* Gallery data */
        var GAL_DATA = [
            /* Row 1 — wide, normal, normal, normal, normal (wide takes 2 cols = 6 total) */
            {
                img: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80',
                cat: 'Express Courier',
                name: 'Last-Mile Delivery',
                ico: '<i class="bi bi-rocket"></i>',
                wide: true
            },
            {
                img: 'https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=600&q=80',
                cat: 'Fleet',
                name: 'Driver Dashboard',
                ico: '<i class="bi bi-satellite"></i>',
                wide: false
            },
            {
                img: 'https://images.unsplash.com/photo-1553413077-190dd305871c?w=600&q=80',
                cat: 'Warehouse',
                name: 'Sorting Facility',
                ico: '<i class="bi bi-building"></i>',
                wide: false
            },
            {
                img: 'https://images.unsplash.com/photo-1578575437130-527eed3abbec?w=600&q=80',
                cat: 'Analytics',
                name: 'Operations Data',
                ico: '<i class="bi bi-graph-up"></i>',
                wide: false
            },
            {
                img: 'https://images.unsplash.com/photo-1588600878108-578307a3cc9d?w=600&q=80',
                cat: 'Packaging',
                name: 'Label Generation',
                ico: '<i class="bi bi-box2"></i>',
                wide: false
            },
            /* Row 2 — normal, normal, wide, normal, normal */
            {
                img: 'https://images.unsplash.com/photo-1494412574643-ff11b0a5c1c3?w=600&q=80',
                cat: 'Network',
                name: 'City Routes',
                ico: '<i class="bi bi-map"></i>',
                wide: false
            },
            {
                img: 'https://images.unsplash.com/photo-1565440962783-b5a6b0c9f684?w=600&q=80',
                cat: 'Logistics',
                name: 'Loading Dock',
                ico: '<i class="bi bi-truck"></i>',
                wide: false
            },
            {
                img: 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=800&q=80',
                cat: 'International',
                name: 'Air Freight Terminal',
                ico: '<i class="bi bi-airplane"></i>',
                wide: true
            },
            {
                img: 'https://images.unsplash.com/photo-1586528116494-3f4d3a407e42?w=600&q=80',
                cat: 'Delivery',
                name: 'Doorstep Handoff',
                ico: '<i class="bi bi-door-open"></i>',
                wide: false
            },
            {
                img: 'https://images.unsplash.com/photo-1566576912321-d58ddd7a6088?w=600&q=80',
                cat: 'Scanning',
                name: 'Barcode Verification',
                ico: '<i class="bi bi-phone"></i>',
                wide: false
            },
            /* Row 3 — normal, wide, normal, normal, normal */
            {
                img: 'https://images.unsplash.com/photo-1530026405186-ed1f139313f8?w=600&q=80',
                cat: 'Dispatch',
                name: 'Control Centre',
                ico: '<i class="bi bi-display"></i>',
                wide: false
            },
            {
                img: 'https://images.unsplash.com/photo-1521791136064-7986c2920216?w=800&q=80',
                cat: 'Partnership',
                name: 'Courier Onboarding',
                ico: '<i class="bi bi-handshake"></i>',
                wide: true
            },
            {
                img: 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=600&q=80',
                cat: 'Tracking',
                name: 'Live Map View',
                ico: '<i class="bi bi-geo-alt"></i>',
                wide: false
            },
            {
                img: 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=600&q=80',
                cat: 'COD',
                name: 'Cash Collection',
                ico: '<i class="bi bi-cash-coin"></i>',
                wide: false
            },
            {
                img: 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=600&q=80',
                cat: 'Proof',
                name: 'Digital Signature',
                ico: '<i class="bi bi-pen"></i>',
                wide: false
            },
        ];
        (function() {
            var grid = document.getElementById('gal-grid');
            GAL_DATA.forEach(function(d) {
                var item = document.createElement('div');
                item.className = 'gal-item' + (d.wide ? ' wide' : '');
                item.innerHTML =
                    '<img src="' + d.img + '" alt="' + d.name + '" loading="lazy">' +
                    '<div class="gal-ov"></div>' +
                    '<div class="gal-hover">' +
                    '<div class="gal-icon">' + d.ico + '</div>' +
                    '<div class="gal-label">' + d.name + '</div>' +
                    '</div>' +
                    '<div class="gal-info">' +
                    '<span class="gal-cat">' + d.cat + '</span>' +
                    '<div class="gal-name">' + d.name + '</div>' +
                    '</div>';
                grid.appendChild(item);
            });
        })();

        /* ════════════════════════════════════════════════════════════
           LOADER
           ════════════════════════════════════════════════════════════ */
        var pctEl = document.getElementById('ld-pct');
        var lfill = document.getElementById('lfill');
        var truck = document.getElementById('ld-truck');
        var loader = document.getElementById('loader');
        var T0 = performance.now();
        var LDUR = 2000;

        function tickLoader(now) {
            var raw = clamp((now - T0) / LDUR, 0, 1);
            var p = 1 - Math.pow(1 - raw, 2.8);
            var pct = Math.floor(p * 100);
            pctEl.textContent = pct;
            lfill.style.width = pct + '%';
            truck.style.left = (3 + pct * .86) + '%';
            if (raw < 1) {
                requestAnimationFrame(tickLoader);
            } else {
                pctEl.textContent = 100;
                truck.style.left = '91%';
                setTimeout(loaderExit, 320);
            }
        }
        requestAnimationFrame(tickLoader);

        function loaderExit() {
            gsap.timeline({
                    onComplete: initPage
                })
                .to(loader, {
                    y: '-100%',
                    duration: 1.0,
                    ease: 'power3.inOut'
                })
                .set(loader, {
                    display: 'none'
                });
        }

        /* ════════════════════════════════════════════════════════════
           INIT PAGE
           ════════════════════════════════════════════════════════════ */
        function initPage() {
            initParticles('b-canvas');
            initParticles('h-canvas');
            initBrandZoom(); /* THE FIX: proper GSAP pin + scrub zoom */
            initHeroStory(); /* Separate GSAP pin for hero storytelling */
            initScrollReveals();
            initSvcCards();
            initSteps();
            initStats();
            initMask();
            initGallery();
            initFooter();
            initNavbar();
            initBatches();
            ScrollTrigger.refresh();
        }

        /* ════════════════════════════════════════════════════════════
           PARTICLES
           ════════════════════════════════════════════════════════════ */
        function initParticles(cid) {
            var canvas = document.getElementById(cid);
            if (!canvas) return;
            var ctx = canvas.getContext('2d'),
                W, H, pts = [];

            function resize() {
                W = canvas.width = canvas.offsetWidth || window.innerWidth;
                H = canvas.height = canvas.offsetHeight || window.innerHeight;
            }
            resize();
            window.addEventListener('resize', resize, {
                passive: true
            });
            for (var i = 0; i < 50; i++) pts.push({
                x: Math.random(),
                y: Math.random(),
                r: .5 + Math.random() * 1.3,
                vx: (Math.random() - .5) * .12,
                vy: -.04 - Math.random() * .1,
                a: .1 + Math.random() * .4,
                ph: Math.random() * Math.PI * 2
            });

            function draw() {
                ctx.clearRect(0, 0, W, H);
                pts.forEach(function(p) {
                    p.x += p.vx / W * .4;
                    p.y += p.vy / H * .4;
                    p.ph += .011;
                    if (p.y < -.01) p.y = 1.01;
                    if (p.x < -.01) p.x = 1.01;
                    if (p.x > 1.01) p.x = -.01;
                    var a = p.a * (0.5 + 0.5 * Math.sin(p.ph));
                    ctx.beginPath();
                    ctx.arc(p.x * W, p.y * H, p.r, 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(80,130,255,' + a + ')';
                    ctx.fill();
                });
                requestAnimationFrame(draw);
            }
            draw();
        }

        /* ════════════════════════════════════════════════════════════
               BRAND ZOOM — THE DEFINITIVE FIX
               ════════════════════════════════════════════════════════════
    
               PROBLEM: Using CSS position:sticky means the sticky element
               scrolls with the page layout — child transforms are in the
               normal flow, so text zooms AND translates with the scroll
               position, creating the "scrolling instead of zooming" bug.
    
               SOLUTION: Use GSAP ScrollTrigger pin:true on the SCENE div.
               ScrollTrigger converts the element to position:fixed internally
               while keeping window scrolling intact. The scene is locked to
               the viewport for the entire pin duration. During that time, a
               scrub timeline drives ONLY the zoom/opacity transform — nothing
               else moves. Native scroll is never blocked.
               ════════════════════════════════════════════════════════════ */
        function initBrandZoom() {
            var scene = document.getElementById('brand-scene');
            var letters = gsap.utils.toArray('#bzt .bz-i');
            var bzt = document.getElementById('bzt');
            var btag = document.getElementById('b-tag');
            var bsh = document.getElementById('b-sh');
            var heroBg = document.getElementById('hero-bg');

            /* 1. Entry animation after loader (not scroll-driven) */
            gsap.timeline({
                    delay: .1
                })
                .to(letters, {
                    y: 0,
                    duration: 1.0,
                    ease: 'power3.out',
                    stagger: .055
                })
                .to(btag, {
                    opacity: 1,
                    duration: .7,
                    ease: 'power2.out'
                }, '-=.25')
                .to(bsh, {
                    opacity: 1,
                    duration: .5
                }, '-=.1');

            /* 2. Scrub timeline pinned to the scene.
               pinnedContainer is NOT used — we pin the scene itself.
               The entire scene is 100vh.  We give it 4× that as scroll
               distance (pinSpacing adds that automatically). */
            var tl = gsap.timeline({
                scrollTrigger: {
                    trigger: scene,
                    start: 'top top',
                    end: '+=300%',
                    /* 3 viewport heights of scroll distance */
                    pin: true,
                    /* GSAP takes over, fixes the element    */
                    pinSpacing: true,
                    /* Adds spacer div below so page still scrolls */
                    scrub: 1.2,
                    /* Smooth lag on scrub                   */
                    anticipatePin: 1,
                }
            });

            /* HERO PARALLAX EFFECT */

            // gsap.to('#hero-bg', {
            //     y: -120,
            //     scale: 1.2,
            //     ease: 'none',
            //     scrollTrigger: {
            //         trigger: '#brand-scene',
            //         start: 'top 10%',
            //         end: 'bottom top',
            //         scrub: 1.5,
            //         // markers: true
            //     }
            // });

            // gsap.to('#b-canvas', {
            //     y: -200,
            //     ease: 'none',
            //     scrollTrigger: {
            //         trigger: '#brand-scene',
            //         start: 'top 10%',
            //         end: 'bottom top',
            //         scrub: 1.2
            //     }
            // });

            // gsap.to('#bzt', {
            //     y: -80,
            //     ease: 'none',
            //     scrollTrigger: {
            //         trigger: '#brand-scene',
            //         start: 'top top',
            //         end: 'bottom top',
            //         scrub: 1
            //     }
            // });

            gsap.fromTo(".hero-content", {
                opacity: 1,
                scale: .8,
                y: 80
            }, {
                opacity: 0,
                scale: 1,
                y: 0,
                duration: 1.2,
                ease: "power3.out",
                scrollTrigger: {
                    trigger: "#brand-scene",
                    start: "center center",
                    end: "bottom top",
                    scrub: 1
                }
            });

            /* Phase 1 (0% → 60%): zoom text out, fade hero bg in */
            tl.to(bzt, {
                    scale: 10,
                    opacity: 0,
                    transformOrigin: 'center center',
                    ease: 'power3.in',
                    duration: 1.2
                })
                .to(heroBg, {
                    opacity: 1,
                    y: 0,
                    duration: 1,
                    ease: 'power3.out'
                })
                .to(btag, {
                    opacity: 0,
                    ease: 'power1.in'
                }, '<')
                .to(bsh, {
                    opacity: 0,
                    ease: 'power1.in'
                }, '<');
        }

        /* ════════════════════════════════════════════════════════════
           HERO STORYTELLING — GSAP pin on hs-scene
           ════════════════════════════════════════════════════════════ */
        function initHeroStory() {
            var scene = document.getElementById('hs-scene');
            var msgs = [0, 1, 2, 3].map(function(i) {
                return document.getElementById('hm' + i);
            });
            var dots = document.querySelectorAll('.hpd');
            var hdots = document.querySelectorAll('.hs-dot');
            var cnum = document.getElementById('hscnum');
            var bgEl = document.getElementById('hsbg');
            var cur = -1;

            // background images per message index
            var bgImages = [
                "url('https://media.licdn.com/dms/image/v2/D4E10AQE1YuaUj4pYiQ/image-shrink_800/image-shrink_800/0/1700135536355?e=2147483647&v=beta&t=wVY9GwRNPDp8PWDEtPEx0CiJU-Bf3vKoTj8Zn-_7cMM')",
                "url('https://www.futuresol.net/assets/images/courier-managemnt.jpg')",
                "url('https://thejunctionllc.com/wp-content/uploads/2025/10/Real-Time-Tracking.jpg')",
                "url('https://urbantz.com/wp-content/uploads/2021/09/Untangle-your-delivery-network-blog-1920x1280-1.png')"
            ];

            function showMsg(idx) {
                if (idx === cur) return;

                // swap background image with a quick fade
                if (cur < 0) {
                    bgEl.style.backgroundImage = bgImages[idx];
                } else {
                    gsap.to(bgEl, {
                        opacity: 0,
                        duration: 0.3,
                        ease: 'power2.in',
                        onComplete: function() {
                            bgEl.style.backgroundImage = bgImages[idx];
                            gsap.to(bgEl, {
                                opacity: 1,
                                duration: 0.45,
                                ease: 'power2.out'
                            });
                        }
                    });
                }

                if (cur >= 0 && msgs[cur]) {
                    gsap.to(msgs[cur], {
                        opacity: 0,
                        y: -44,
                        duration: .4,
                        ease: 'power2.in',
                        overwrite: true
                    });
                    dots[cur].classList.remove('on');
                    hdots[cur].classList.remove('on');
                }
                cur = idx;
                gsap.fromTo(msgs[idx], {
                    opacity: 0,
                    y: 56
                }, {
                    opacity: 1,
                    y: 0,
                    duration: .6,
                    ease: 'power2.out',
                    overwrite: true
                });
                dots[idx].classList.add('on');
                hdots[idx].classList.add('on');
                cnum.textContent = String(idx + 1).padStart(2, '0');
            }
            showMsg(0);

            /* Pin hs-scene for 4 messages.
               We need 4 segments × some scroll height each.
               Using +=300% gives comfortable dwell per message. */
            ScrollTrigger.create({
                trigger: scene,
                start: 'top top',
                end: '+=300%',
                pin: true,
                pinSpacing: true,
                anticipatePin: 1,
                scrub: false,
                onUpdate: function(self) {
                    var idx = clamp(Math.floor(self.progress * 4), 0, 3);
                    showMsg(idx);
                    /* Subtle bg parallax */
                    gsap.set(bgEl, {
                        y: self.progress * -40,
                        immediateRender: false
                    });
                }
            });
        }

        /* ════════════════════════════════════════════════════════════
           SCROLL REVEALS
           ════════════════════════════════════════════════════════════ */
        function initScrollReveals() {
            ScrollTrigger.batch('.rev', {
                start: 'top 89%',
                once: true,
                onEnter: function(batch) {
                    gsap.fromTo(batch, {
                        opacity: 0,
                        y: 36
                    }, {
                        opacity: 1,
                        y: 0,
                        duration: .72,
                        ease: 'power2.out',
                        stagger: .09,
                        overwrite: 'auto'
                    });
                }
            });
        }

        /* ════════════════════════════════════════════════════════════
           SERVICE CARDS
           ════════════════════════════════════════════════════════════ */
        function initSvcCards() {
            ScrollTrigger.batch('.svc-card', {
                start: 'top 90%',
                once: true,
                onEnter: function(batch) {
                    gsap.fromTo(batch, {
                        opacity: 0,
                        y: 52,
                        scale: .96
                    }, {
                        opacity: 1,
                        y: 0,
                        scale: 1,
                        duration: .7,
                        ease: 'power2.out',
                        stagger: .1,
                        overwrite: 'auto'
                    });
                }
            });
        }

        /* ════════════════════════════════════════════════════════════
           STEPS
           ════════════════════════════════════════════════════════════ */
        function initSteps() {
            document.querySelectorAll('.step').forEach(function(step) {
                ScrollTrigger.create({
                    trigger: step,
                    start: 'top 62%',
                    once: true,
                    onEnter: function() {
                        step.classList.add('on');
                        var fill = step.querySelector('.scon-fill');
                        if (fill) gsap.to(fill, {
                            height: '100%',
                            duration: .9,
                            ease: 'power2.out',
                            delay: .25
                        });
                        var ico = step.querySelector('.sico');
                        if (ico) gsap.fromTo(ico, {
                            scale: 0,
                            rotate: -15
                        }, {
                            scale: 1,
                            rotate: 0,
                            duration: .5,
                            ease: 'back.out(2)',
                            delay: .1,
                            overwrite: 'auto'
                        });
                    }
                });
            });
        }

        /* ════════════════════════════════════════════════════════════
           STATS COUNT-UP
           ════════════════════════════════════════════════════════════ */
        function initStats() {
            document.querySelectorAll('.snum2[data-t]').forEach(function(el) {
                var target = parseFloat(el.getAttribute('data-t'));
                var suffix = el.getAttribute('data-s') || '';
                ScrollTrigger.create({
                    trigger: el,
                    start: 'top 85%',
                    once: true,
                    onEnter: function() {
                        var obj = {
                            v: 0
                        };
                        gsap.to(obj, {
                            v: target,
                            duration: 2.2,
                            ease: 'power2.out',
                            onUpdate: function() {
                                el.innerHTML = Math.floor(obj.v) + '<sup>' +
                                    suffix + '</sup>';
                            },
                            onComplete: function() {
                                el.innerHTML = target + '<sup>' + suffix + '</sup>';
                            }
                        });
                    }
                });
            });
        }

        /* ════════════════════════════════════════════════════════════
           MASK REVEAL
           ════════════════════════════════════════════════════════════ */
        function initMask() {
            var maskWrap = document.getElementById('maskWrap');
            var maskImg = document.getElementById('maskImg');

            var tl = gsap.timeline({
                scrollTrigger: {
                    trigger: '#mask-sec',
                    start: 'top -8%',
                    end: '+=120%',
                    pin: true,
                    pinSpacing: true,
                    anticipatePin: 1,
                    scrub: 1.4
                }
            });

            // Start fully masked; reveal animation begins once pinned.
            tl.fromTo(maskWrap, {
                    clipPath: 'inset(0 100% 0 0 round 22px)'
                }, {
                    clipPath: 'inset(0 0% 0 0% round 22px)',
                    ease: 'none'
                }, 0)
                .fromTo(maskImg, {
                    scale: 1.14
                }, {
                    scale: 1,
                    ease: 'none'
                }, 0);
        }

        /* ════════════════════════════════════════════════════════════
           GALLERY ANIMATION
           Staggered wave reveal: each image flies in with a cascading
           delay based on its position (column-driven).
           ════════════════════════════════════════════════════════════ */
        function initGallery() {
            var items = gsap.utils.toArray('.gal-item');

            /* Build a ScrollTrigger that fires once when the gallery enters */
            ScrollTrigger.create({
                trigger: '#gal-grid',
                start: 'top 85%',
                once: true,
                onEnter: function() {
                    /* Stagger by column index for a diagonal wave */
                    items.forEach(function(item, i) {
                        /* Compute visual column (0–5) from DOM order.
                           Items are laid out in row order; use i % 6 approximation.
                           We want a column-stagger so items in the same column
                           animate together — which creates a beautiful wave. */
                        var col = i % 6;
                        gsap.to(item, {
                            opacity: 1,
                            y: 0,
                            scale: 1,
                            duration: .75,
                            ease: 'power3.out',
                            delay: col * .07 + Math.floor(i / 6) * .04,
                        });
                    });
                }
            });

            /* Parallax on each image while scrolling through gallery */
            items.forEach(function(item) {
                var img = item.querySelector('img');
                gsap.to(img, {
                    y: -24,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: item,
                        start: 'top bottom',
                        end: 'bottom top',
                        scrub: 1.5,
                    }
                });
            });
        }

        /* ════════════════════════════════════════════════════════════
           FOOTER WORD REVEAL
           ════════════════════════════════════════════════════════════ */
        function initFooter() {
            gsap.to('.fwi', {
                y: 0,
                duration: 1.1,
                ease: 'power3.out',
                stagger: .11,
                scrollTrigger: {
                    trigger: '#fbig',
                    start: 'top 88%',
                    once: true
                }
            });
        }

        /* ════════════════════════════════════════════════════════════
           NAVBAR
           ════════════════════════════════════════════════════════════ */
        function initNavbar() {
            var nav = document.getElementById('nav');
            var toggle = document.getElementById('nav-toggle');

            gsap.to('#nav', {
                opacity: 1,
                duration: .6,
                ease: 'power2.out',
                delay: .2
            });
            nav.classList.add('vis');

            ScrollTrigger.create({
                start: 'top -60',
                onUpdate: function(self) {
                    nav.classList.toggle('sc', self.scroll() > 60);
                }
            });

            if (toggle) {
                toggle.addEventListener('click', function() {
                    var expanded = this.getAttribute('aria-expanded') === 'true';
                    this.setAttribute('aria-expanded', (!expanded).toString());
                    nav.classList.toggle('open');
                });
                document.addEventListener('click', function(e) {
                    if (!nav.contains(e.target) && nav.classList.contains('open')) {
                        nav.classList.remove('open');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });
            }
        }

        /* ════════════════════════════════════════════════════════════
           BATCHED STAGGER (testimonials, features)
           ════════════════════════════════════════════════════════════ */
        function initBatches() {
            ScrollTrigger.batch('.tc', {
                start: 'top 90%',
                once: true,
                onEnter: function(batch) {
                    gsap.fromTo(batch, {
                        opacity: 0,
                        y: 44
                    }, {
                        opacity: 1,
                        y: 0,
                        duration: .7,
                        ease: 'power2.out',
                        stagger: .13,
                        overwrite: 'auto'
                    });
                }
            });
            ScrollTrigger.batch('.fc', {
                start: 'top 90%',
                once: true,
                onEnter: function(batch) {
                    gsap.fromTo(batch, {
                        opacity: 0,
                        y: 40
                    }, {
                        opacity: 1,
                        y: 0,
                        duration: .68,
                        ease: 'power2.out',
                        stagger: .1,
                        overwrite: 'auto'
                    });
                }
            });
        }

    })();
    </script>
</body>

</html>

