<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Salesforce Test Manager — Automate. Simulate. Ship.</title>
    <meta name="description" content="A powerful internal platform to manage Salesforce test automation, simulate Vlocity CPQ flows, and keep your QA pipeline running smoothly.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --teal:   #2dd4bf;
            --teal-d: #14b8a6;
            --dark:   #0f172a;
            --dark2:  #1e293b;
            --muted:  #94a3b8;
            --light:  #f1f5f9;
            --white:  #ffffff;
            --purple: #818cf8;
            --pink:   #f472b6;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--dark);
            color: var(--white);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── NAVBAR ─────────────────────────────── */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.1rem 2.5rem;
            background: rgba(15,23,42,0.8);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .nav-logo { display: flex; align-items: center; gap: 0.6rem; text-decoration: none; }
        .nav-logo-icon {
            width: 34px; height: 34px; border-radius: 8px;
            background: linear-gradient(135deg, var(--teal), var(--purple));
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
        }
        .nav-logo-text { font-weight: 700; font-size: 1rem; color: var(--white); }
        .nav-links { display: flex; align-items: center; gap: 1.5rem; }
        .nav-links a {
            color: var(--muted); text-decoration: none; font-size: 0.875rem;
            font-weight: 500; transition: color 0.2s;
        }
        .nav-links a:hover { color: var(--white); }
        .nav-cta {
            padding: 0.5rem 1.25rem;
            background: var(--teal);
            color: var(--dark) !important;
            border-radius: 8px;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            transition: opacity 0.2s !important;
        }
        .nav-cta:hover { opacity: 0.85; color: var(--dark) !important; }

        /* ── HERO ────────────────────────────────── */
        .hero {
            min-height: 100vh;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-align: center;
            padding: 8rem 1.5rem 4rem;
            position: relative;
        }
        .hero-glow {
            position: absolute; top: 0; left: 50%; transform: translateX(-50%);
            width: 900px; height: 600px;
            background: radial-gradient(ellipse at top, rgba(45,212,191,0.18) 0%, transparent 65%);
            pointer-events: none;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(45,212,191,0.1);
            border: 1px solid rgba(45,212,191,0.3);
            border-radius: 999px;
            padding: 0.35rem 1rem;
            font-size: 0.78rem; font-weight: 600;
            color: var(--teal);
            text-transform: uppercase; letter-spacing: 0.05em;
            margin-bottom: 1.75rem;
            animation: fadeUp 0.6s ease both;
        }
        .badge-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--teal);
            animation: pulse 2s ease infinite;
        }
        @keyframes pulse {
            0%,100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.4); }
        }

        h1 {
            font-size: clamp(2.6rem, 6vw, 4.5rem);
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: -0.03em;
            max-width: 820px;
            animation: fadeUp 0.7s 0.1s ease both;
        }
        .gradient-text {
            background: linear-gradient(90deg, var(--teal) 0%, var(--purple) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-sub {
            margin-top: 1.5rem;
            color: var(--muted);
            font-size: 1.125rem;
            line-height: 1.7;
            max-width: 560px;
            animation: fadeUp 0.7s 0.2s ease both;
        }
        .hero-buttons {
            margin-top: 2.5rem;
            display: flex; align-items: center; gap: 1rem;
            flex-wrap: wrap; justify-content: center;
            animation: fadeUp 0.7s 0.3s ease both;
        }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.8rem 2rem;
            background: linear-gradient(135deg, var(--teal), var(--teal-d));
            color: var(--dark);
            font-weight: 700; font-size: 0.95rem;
            border-radius: 10px;
            text-decoration: none;
            box-shadow: 0 4px 24px rgba(45,212,191,0.35);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(45,212,191,0.45);
        }
        .btn-secondary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.8rem 1.75rem;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            color: var(--white);
            font-weight: 600; font-size: 0.95rem;
            border-radius: 10px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); }

        .hero-stats {
            display: flex; gap: 3rem; margin-top: 4rem; flex-wrap: wrap; justify-content: center;
            animation: fadeUp 0.7s 0.4s ease both;
        }
        .stat { text-align: center; }
        .stat-val { font-size: 1.75rem; font-weight: 800; color: var(--teal); }
        .stat-label { font-size: 0.78rem; color: var(--muted); margin-top: 0.2rem; text-transform: uppercase; letter-spacing: 0.04em; }

        /* ── FEATURES ────────────────────────────── */
        section { padding: 6rem 1.5rem; }
        .container { max-width: 1100px; margin: 0 auto; }
        .section-label {
            text-align: center;
            font-size: 0.78rem; font-weight: 700;
            color: var(--teal); text-transform: uppercase; letter-spacing: 0.1em;
            margin-bottom: 1rem;
        }
        .section-title {
            text-align: center;
            font-size: clamp(1.8rem, 4vw, 2.75rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.15;
            max-width: 700px;
            margin: 0 auto 1rem;
        }
        .section-sub {
            text-align: center;
            color: var(--muted);
            font-size: 1rem;
            max-width: 520px;
            margin: 0 auto 3.5rem;
            line-height: 1.7;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .feature-card {
            background: var(--dark2);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px;
            padding: 1.75rem;
            transition: transform 0.25s, border-color 0.25s, box-shadow 0.25s;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            border-color: rgba(45,212,191,0.3);
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }
        .feature-icon {
            width: 46px; height: 46px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem;
            margin-bottom: 1rem;
        }
        .icon-teal   { background: rgba(45,212,191,0.15); }
        .icon-purple { background: rgba(129,140,248,0.15); }
        .icon-pink   { background: rgba(244,114,182,0.15); }
        .icon-amber  { background: rgba(251,191,36,0.15); }
        .icon-sky    { background: rgba(56,189,248,0.15); }
        .icon-green  { background: rgba(74,222,128,0.15); }

        .feature-title { font-weight: 700; font-size: 1.05rem; margin-bottom: 0.5rem; }
        .feature-desc { color: var(--muted); font-size: 0.9rem; line-height: 1.65; }

        /* ── CPQ HIGHLIGHT ───────────────────────── */
        .cpq-section {
            background: linear-gradient(135deg, rgba(45,212,191,0.08) 0%, rgba(129,140,248,0.08) 100%);
            border-top: 1px solid rgba(45,212,191,0.12);
            border-bottom: 1px solid rgba(45,212,191,0.12);
        }
        .cpq-inner {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }
        @media(max-width:768px) { .cpq-inner { grid-template-columns: 1fr; gap: 2.5rem; } }
        .cpq-text .tag {
            display: inline-block;
            background: rgba(129,140,248,0.15);
            color: var(--purple);
            border: 1px solid rgba(129,140,248,0.3);
            border-radius: 6px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.06em;
            margin-bottom: 1.25rem;
        }
        .cpq-text h2 { font-size: 2.2rem; font-weight: 800; line-height: 1.2; letter-spacing: -0.02em; }
        .cpq-text p { color: var(--muted); margin-top: 1rem; line-height: 1.75; font-size: 0.95rem; }
        .cpq-list { margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem; }
        .cpq-item { display: flex; align-items: flex-start; gap: 0.75rem; font-size: 0.9rem; color: var(--muted); }
        .cpq-item-check {
            width: 20px; height: 20px; border-radius: 50%;
            background: rgba(45,212,191,0.15);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 0.7rem; color: var(--teal); margin-top: 1px;
        }
        .cpq-link {
            display: inline-flex; align-items: center; gap: 0.5rem;
            margin-top: 2rem;
            color: var(--teal); font-weight: 600; font-size: 0.9rem;
            text-decoration: none; transition: gap 0.2s;
        }
        .cpq-link:hover { gap: 0.75rem; }

        .cpq-visual {
            background: var(--dark);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 1.5rem;
            font-size: 0.8rem;
            box-shadow: 0 24px 60px rgba(0,0,0,0.4);
        }
        .visual-bar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .visual-dots { display: flex; gap: 5px; }
        .dot { width: 10px; height: 10px; border-radius: 50%; }
        .dot-r { background: #ff5f57; }
        .dot-y { background: #febc2e; }
        .dot-g { background: #28c840; }
        .visual-title { color: var(--muted); font-size: 0.75rem; }
        .visual-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            color: var(--muted);
        }
        .visual-row:last-child { border-bottom: none; }
        .visual-row .name { color: var(--white); font-weight: 500; }
        .chip {
            padding: 0.2rem 0.6rem; border-radius: 999px;
            font-size: 0.7rem; font-weight: 600;
        }
        .chip-teal { background: rgba(45,212,191,0.15); color: var(--teal); }
        .chip-purple { background: rgba(129,140,248,0.15); color: var(--purple); }
        .chip-amber { background: rgba(251,191,36,0.15); color: #fbbf24; }

        /* ── CTA BOTTOM ──────────────────────────── */
        .cta-section {
            padding: 6rem 1.5rem;
            text-align: center;
            position: relative;
        }
        .cta-glow {
            position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);
            width: 700px; height: 400px;
            background: radial-gradient(ellipse at bottom, rgba(129,140,248,0.15) 0%, transparent 65%);
            pointer-events: none;
        }
        .cta-section h2 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            max-width: 600px;
            margin: 0 auto 1.25rem;
        }
        .cta-section p { color: var(--muted); font-size: 1rem; margin-bottom: 2.5rem; }

        /* ── FOOTER ──────────────────────────────── */
        footer {
            border-top: 1px solid rgba(255,255,255,0.06);
            padding: 2rem 2.5rem;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 1rem;
            color: var(--muted); font-size: 0.82rem;
        }

        /* ── ANIMATIONS ──────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- NAV -->
<nav>
    <a href="/" class="nav-logo">
        <div class="nav-logo-icon">🧪</div>
        <span class="nav-logo-text">SF Test Manager</span>
    </a>
    <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#cpq">CPQ Simulator</a>
        @if(Route::has('login'))
            @auth
                <a href="{{ url('/dashboard') }}" class="nav-cta">Open App →</a>
            @else
                <a href="{{ route('login') }}" style="color:var(--muted)">Sign in</a>
                <a href="{{ route('login') }}" class="nav-cta">Get Access →</a>
            @endauth
        @endif
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-glow"></div>

    <div class="hero-badge">
        <span class="badge-dot"></span>
        Salesforce QA Platform
    </div>

    <h1>
        Test Smarter.<br>
        <span class="gradient-text">Ship Faster.</span>
    </h1>

    <p class="hero-sub">
        A unified internal platform to manage Salesforce test automation, simulate
        Vlocity CPQ quote flows, and keep your QA pipeline in full control.
    </p>

    <div class="hero-buttons">
        @auth
            <a href="{{ url('/dashboard') }}" class="btn-primary">
                Open Dashboard →
            </a>
            <a href="{{ route('cpq-simulator.index') }}" class="btn-secondary">
                🛒 CPQ Simulator
            </a>
        @else
            <a href="{{ route('login') }}" class="btn-primary">
                Sign In →
            </a>
        @endauth
    </div>

    <div class="hero-stats">
        <div class="stat">
            <div class="stat-val">100%</div>
            <div class="stat-label">API Driven</div>
        </div>
        <div class="stat">
            <div class="stat-val">3</div>
            <div class="stat-label">Core Modules</div>
        </div>
        <div class="stat">
            <div class="stat-val">Zero</div>
            <div class="stat-label">CORS Issues</div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section id="features" style="background: rgba(30,41,59,0.4); border-top: 1px solid rgba(255,255,255,0.05);">
    <div class="container">
        <p class="section-label">What's Inside</p>
        <h2 class="section-title">Everything your Salesforce QA team needs</h2>
        <p class="section-sub">Built to streamline the full test lifecycle — from writing test cases to simulating complex CPQ flows.</p>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon icon-teal">🗂️</div>
                <div class="feature-title">Module &amp; Test Case Manager</div>
                <div class="feature-desc">Organise test cases by module, assign types (API, Apex, UI), run them individually, and track pass/fail history over time.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon icon-purple">🔄</div>
                <div class="feature-title">Object Sync Manager</div>
                <div class="feature-desc">Pull Salesforce object metadata and field dictionaries directly into the app. Keep your object schema up-to-date with a single click.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon icon-pink">🛒</div>
                <div class="feature-title">Vlocity CPQ Simulator</div>
                <div class="feature-desc">Simulate the full CPQ quote flow — select opportunities, manage quotes, add products, configure attributes, and apply pricing — all via API.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon icon-amber">🔑</div>
                <div class="feature-title">Multi-persona Execution</div>
                <div class="feature-desc">Run tests as different Salesforce users with persisted OAuth tokens. Perfect for validating permission sets and profile-specific behaviour.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon icon-sky">🌐</div>
                <div class="feature-title">CORS-Free API Proxy</div>
                <div class="feature-desc">All Salesforce API calls are proxied server-side — no browser CORS blocks, automatic token refresh, and full request logging.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon icon-green">📊</div>
                <div class="feature-title">Allure Report Integration</div>
                <div class="feature-desc">Pull test run results from your CI/CD pipeline and surface them in-app alongside your manually executed test cases.</div>
            </div>
        </div>
    </div>
</section>

<!-- CPQ HIGHLIGHT -->
<section id="cpq" class="cpq-section">
    <div class="container">
        <div class="cpq-inner">
            <div class="cpq-text">
                <span class="tag">🛒 CPQ Simulator</span>
                <h2>Simulate the entire<br>quote lifecycle</h2>
                <p>No more manually clicking through Salesforce UI to test CPQ flows. Drive the entire Vlocity CPQ API from one interactive page — quotes, products, attributes, pricing.</p>
                <ul class="cpq-list">
                    <li class="cpq-item">
                        <span class="cpq-item-check">✓</span>
                        Select opportunities and manage existing quotes
                    </li>
                    <li class="cpq-item">
                        <span class="cpq-item-check">✓</span>
                        Add root products from the quote's pricelist
                    </li>
                    <li class="cpq-item">
                        <span class="cpq-item-check">✓</span>
                        View and configure child item attributes (dropdowns, text inputs, required fields)
                    </li>
                    <li class="cpq-item">
                        <span class="cpq-item-check">✓</span>
                        Override OTC/RC pricing and trigger instant recalculation
                    </li>
                </ul>
                @auth
                    <a href="{{ route('cpq-simulator.index') }}" class="cpq-link">Open CPQ Simulator →</a>
                @else
                    <a href="{{ route('login') }}" class="cpq-link">Sign in to try it →</a>
                @endauth
            </div>

            <div class="cpq-visual">
                <div class="visual-bar">
                    <div class="visual-dots">
                        <div class="dot dot-r"></div>
                        <div class="dot dot-y"></div>
                        <div class="dot dot-g"></div>
                    </div>
                    <span class="visual-title">CPQ Simulator — Quote View</span>
                </div>

                <div class="visual-row">
                    <span class="name">OPP-0042 · Telco Enterprise</span>
                    <span class="chip chip-teal">Selected</span>
                </div>
                <div class="visual-row" style="margin-top:0.75rem; font-size:0.75rem; color:var(--muted);">Cart Line Items</div>
                <div class="visual-row">
                    <span class="name">Internet Dedicated — 500 Mbps</span>
                    <span class="chip chip-purple">Root</span>
                </div>
                <div class="visual-row" style="padding-left:1.25rem;">
                    └ Bandwidth Addon
                    <span class="chip chip-amber">Configuring</span>
                </div>
                <div class="visual-row" style="padding-left:1.25rem;">
                    └ SLA Management
                    <span class="chip chip-teal">Done</span>
                </div>
                <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid rgba(255,255,255,0.06); display:flex; justify-content:space-between; color:var(--muted); font-size:0.78rem;">
                    <span>OTC Override</span>
                    <span style="color:var(--white); font-weight:600;">Rp 12,500,000</span>
                </div>
                <div style="display:flex; justify-content:space-between; color:var(--muted); font-size:0.78rem; margin-top:0.4rem;">
                    <span>RC Override</span>
                    <span style="color:var(--white); font-weight:600;">Rp 3,200,000 / mo</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- BOTTOM CTA -->
<section class="cta-section">
    <div class="cta-glow"></div>
    <div class="container">
        <h2>Ready to accelerate your<br><span class="gradient-text">Salesforce QA?</span></h2>
        <p>Sign in and start running tests, simulating CPQ flows, and keeping your pipeline healthy.</p>
        @auth
            <a href="{{ url('/dashboard') }}" class="btn-primary" style="display:inline-flex;">
                Go to Dashboard →
            </a>
        @else
            <a href="{{ route('login') }}" class="btn-primary" style="display:inline-flex;">
                Sign In →
            </a>
        @endauth
    </div>
</section>

<!-- FOOTER -->
<footer>
    <span>© {{ date('Y') }} Salesforce Test Manager. Internal tooling.</span>
    <span>Built with Laravel &amp; Alpine.js</span>
</footer>

</body>
</html>
