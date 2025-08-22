<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MACTA Framework - High Tech Talents | Professional Process Management Solutions</title>
    <meta name="description" content="HTT's comprehensive MACTA Framework provides systematic process optimization through modeling, analysis, customization, training, and assessment for superior business results.">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Enhanced HTT Brand Colors */
            --htt-primary: #2C7DC7;
            --htt-primary-dark: #1B5B9E;
            --htt-primary-light: #4A9FE8;
            --htt-secondary: #5A5A5A;
            --htt-accent: #F8FAFC;
            
            /* MACTA Module Colors - Enhanced */
            --modeling-primary: #FF7B2B;
            --modeling-light: #FFB366;
            --analysis-primary: #E53E3E;
            --analysis-light: #FC8181;
            --customization-primary: #38B2AC;
            --customization-light: #81E6D9;
            --training-primary: #F6E05E;
            --training-light: #F6E05E;
            --assessment-primary: #68D391;
            --assessment-light: #9AE6B4;
            
            /* Modern UI Variables */
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --card-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --card-shadow-hover: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --text-primary: #1A202C;
            --text-secondary: #4A5568;
            --text-muted: #718096;
            --border-radius-sm: 8px;
            --border-radius-md: 16px;
            --border-radius-lg: 24px;
            --border-radius-xl: 32px;
            
            /* Animation Variables */
            --transition-fast: 0.15s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;
            --bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--htt-primary) 0%, var(--htt-primary-dark) 50%, #0F172A 100%);
            background-attachment: fixed;
            min-height: 100vh;
            line-height: 1.6;
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* Enhanced Background Pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Enhanced Header with Glass Morphism */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            padding: 20px 0;
            box-shadow: 0 8px 32px rgba(44, 125, 199, 0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--htt-primary), var(--modeling-primary), var(--analysis-primary), var(--customization-primary), var(--training-primary), var(--assessment-primary));
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform var(--transition-normal);
        }

        .logo-section:hover {
            transform: translateY(-2px);
        }

        /* Enhanced Logo with Professional Styling */
        .logo-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--htt-primary), var(--htt-primary-light));
            border-radius: var(--border-radius-md);
            box-shadow: var(--card-shadow);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: 800;
        }

        .logo-icon::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.3) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .logo-section:hover .logo-icon::before {
            transform: translateX(100%);
        }

        .company-info h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 32px;
            color: var(--htt-primary);
            font-weight: 800;
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }

        .company-tagline {
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .header-actions {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        /* Enhanced Button Styles */
        .btn {
            padding: 12px 24px;
            border-radius: var(--border-radius-sm);
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition-normal);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            position: relative;
            overflow: hidden;
            border: none;
            cursor: pointer;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-outline {
            border: 2px solid var(--htt-primary);
            color: var(--htt-primary);
            background: transparent;
        }

        .btn-outline:hover {
            background: var(--htt-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--card-shadow);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--htt-primary), var(--htt-primary-dark));
            color: white;
            box-shadow: var(--card-shadow);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-shadow-hover);
        }

        /* Enhanced Main Content */
        .main-content {
            padding: 60px 0;
        }

        /* Hero Section with Enhanced Typography */
        .hero-section {
            text-align: center;
            margin-bottom: 80px;
            position: relative;
        }

        .framework-title {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(48px, 8vw, 72px);
            color: white;
            font-weight: 800;
            margin-bottom: 24px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            letter-spacing: -1px;
            position: relative;
        }

        .framework-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--modeling-primary), var(--assessment-primary));
            border-radius: 2px;
        }

        .framework-subtitle {
            font-size: 24px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 32px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .framework-description {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.8);
            max-width: 900px;
            margin: 0 auto 40px;
            line-height: 1.8;
        }

        .hero-cta {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 40px;
        }

        /* Trust Indicators Section */
        .trust-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius-lg);
            padding: 40px;
            margin-bottom: 60px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .trust-title {
            color: white;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 24px;
            opacity: 0.9;
        }

        .trust-badges {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
            flex-wrap: wrap;
        }

        .trust-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            font-weight: 500;
        }

        .trust-badge i {
            font-size: 24px;
            color: var(--assessment-primary);
        }

        /* Enhanced MACTA Framework Card */
        .macta-framework {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-radius: var(--border-radius-xl);
            padding: 60px 40px;
            box-shadow: var(--card-shadow-hover);
            margin-bottom: 60px;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .macta-framework::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, var(--modeling-primary), var(--analysis-primary), var(--customization-primary), var(--training-primary), var(--assessment-primary));
            border-radius: var(--border-radius-xl) var(--border-radius-xl) 0 0;
        }

        .framework-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .framework-header h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 40px;
            color: var(--text-primary);
            margin-bottom: 16px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .methodology-subtitle {
            font-size: 18px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Enhanced MACTA Flow with Modern Design */
        .macta-flow-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
            gap: 24px;
        }

        .macta-flow {
            display: flex;
            align-items: center;
            gap: 0;
            position: relative;
        }

        .module-card {
            background: white;
            border-radius: var(--border-radius-md);
            padding: 24px 20px;
            text-align: center;
            text-decoration: none;
            color: white;
            font-weight: 700;
            font-size: 14px;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-normal) var(--bounce);
            position: relative;
            overflow: hidden;
            width: 140px;
            height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 2;
            border: 2px solid transparent;
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: inherit;
            border-radius: inherit;
            transition: all var(--transition-normal);
        }

        .module-card:hover {
            transform: translateY(-8px) scale(1.05);
            box-shadow: var(--card-shadow-hover);
            z-index: 10;
            border-color: rgba(255, 255, 255, 0.3);
        }

        .module-card:hover::before {
            filter: brightness(1.1);
        }

        .module-icon {
            font-size: 32px;
            margin-bottom: 12px;
            display: block;
            transition: transform var(--transition-normal);
            z-index: 3;
            position: relative;
        }

        .module-card:hover .module-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .module-title {
            font-size: 13px;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 3;
            position: relative;
        }

        /* Enhanced Module Colors with Gradients */
        .modeling {
            background: linear-gradient(135deg, var(--modeling-primary) 0%, var(--modeling-light) 100%);
        }

        .analysis {
            background: linear-gradient(135deg, var(--analysis-primary) 0%, var(--analysis-light) 100%);
        }

        .customization {
            background: linear-gradient(135deg, var(--customization-primary) 0%, var(--customization-light) 100%);
        }

        .training {
            background: linear-gradient(135deg, var(--training-primary) 0%, var(--training-light) 100%);
            color: var(--text-primary) !important;
        }

        .assessment {
            background: linear-gradient(135deg, var(--assessment-primary) 0%, var(--assessment-light) 100%);
        }

        /* Enhanced Connection Lines */
        .connection-line {
            width: 50px;
            height: 4px;
            background: linear-gradient(90deg, var(--htt-accent), var(--htt-secondary));
            position: relative;
            z-index: 1;
            border-radius: 2px;
        }

        .connection-line::after {
            content: '';
            position: absolute;
            right: -6px;
            top: -4px;
            width: 0;
            height: 0;
            border-left: 12px solid var(--htt-secondary);
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
        }

        /* Enhanced Status Indicators */
        .module-status {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 12px;
            height: 12px;
            background: var(--assessment-primary);
            border: 2px solid white;
            border-radius: 50%;
            z-index: 3;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .module-status.coming-soon {
            background: var(--training-primary);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Enhanced Slider with Professional Content */
        .slider-container {
            margin-top: 40px;
            position: relative;
            height: 500px;
            overflow: hidden;
            border-radius: var(--border-radius-lg);
            background: white;
            box-shadow: var(--card-shadow-hover);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .slider-wrapper {
            display: flex;
            width: 500%;
            height: 100%;
            transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .slide {
            width: 20%;
            height: 100%;
            display: flex;
            align-items: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }

        .slide-content {
            flex: 1;
            z-index: 2;
            position: relative;
            max-width: 50%;
        }

        .slide-icon {
            font-size: 56px;
            margin-bottom: 24px;
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .slide-title {
            font-family: 'Poppins', sans-serif;
            font-size: 36px;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .slide-description {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.95);
            line-height: 1.7;
            margin-bottom: 24px;
        }

        .slide-features {
            list-style: none;
        }

        .slide-features li {
            padding: 8px 0;
            color: rgba(255, 255, 255, 0.9);
            position: relative;
            padding-left: 24px;
            font-size: 16px;
        }

        .slide-features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        /* Specific text color overrides for better readability */
        .slide-modeling .slide-title,
        .slide-modeling .slide-description,
        .slide-modeling .slide-features li,
        .slide-modeling .slide-features li::before {
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .slide-training .slide-title {
            color: var(--text-primary);
            text-shadow: 0 2px 10px rgba(255, 255, 255, 0.3);
        }

        .slide-training .slide-description {
            color: rgba(26, 32, 44, 0.9);
        }

        .slide-training .slide-features li {
            color: rgba(26, 32, 44, 0.85);
        }

        .slide-training .slide-features li::before {
            color: var(--text-primary);
        }

        .slide-customization .slide-title,
        .slide-customization .slide-description,
        .slide-customization .slide-features li,
        .slide-customization .slide-features li::before {
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
        }

        /* Enhanced Slide Background Images */
        .slide-image {
            position: absolute;
            right: 0;
            top: 0;
            width: 60%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: 1;
            opacity: 0.9;
        }

        /* Slide backgrounds with professional images */
        .slide-modeling {
            background: linear-gradient(135deg, var(--modeling-primary), var(--modeling-light));
        }

        .slide-modeling .slide-image {
            background-image: url('assets/images/modeling-illustration.png');
        }

        .slide-analysis {
            background: linear-gradient(135deg, var(--analysis-primary), var(--analysis-light));
        }

        .slide-analysis .slide-image {
            background-image: url('assets/images/analysis-illustration.png');
        }

        .slide-customization {
            background: linear-gradient(135deg, var(--customization-primary), var(--customization-light));
        }

        .slide-customization .slide-image {
            background-image: url('assets/images/customization-illustration.png');
        }

        .slide-training {
            background: linear-gradient(135deg, var(--training-primary), var(--training-light));
        }

        .slide-training .slide-image {
            background-image: url('assets/images/training-illustration.png');
        }

        .slide-assessment {
            background: linear-gradient(135deg, var(--assessment-primary), var(--assessment-light));
        }

        .slide-assessment .slide-image {
            background-image: url('assets/images/assessment-illustration.png');
        }

        /* Enhanced Slider Indicators */
        .slider-indicators {
            position: absolute;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 10;
        }

        .indicator {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all var(--transition-normal);
            border: 2px solid rgba(255, 255, 255, 0.6);
        }

        .indicator.active {
            background: white;
            transform: scale(1.2);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        /* Testimonials Section */
        .testimonials-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius-xl);
            padding: 80px 60px;
            margin: 80px 0;
            box-shadow: var(--card-shadow-hover);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .testimonials-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .testimonials-header h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 40px;
            color: var(--text-primary);
            margin-bottom: 16px;
            font-weight: 700;
        }

        .testimonials-subtitle {
            font-size: 18px;
            color: var(--text-secondary);
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .testimonial-card {
            background: white;
            border-radius: var(--border-radius-md);
            padding: 32px;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-normal);
            position: relative;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .testimonial-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-shadow-hover);
        }

        .testimonial-quote {
            font-size: 16px;
            line-height: 1.7;
            color: var(--text-secondary);
            margin-bottom: 24px;
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .author-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--htt-primary), var(--htt-primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        .author-info h4 {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .author-info p {
            font-size: 14px;
            color: var(--text-muted);
        }

        /* Contact Section */
        .contact-section {
            background: linear-gradient(135deg, var(--htt-primary-dark), var(--htt-primary));
            border-radius: var(--border-radius-xl);
            padding: 80px 60px;
            margin: 80px 0;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .contact-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: rotate 40s linear infinite;
        }

        .contact-content {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .contact-info h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 40px;
            font-weight: 700;
            margin-bottom: 24px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .contact-info p {
            font-size: 18px;
            line-height: 1.7;
            margin-bottom: 32px;
            opacity: 0.9;
        }

        .contact-details {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 16px;
        }

        .contact-item i {
            font-size: 20px;
            width: 24px;
            text-align: center;
            opacity: 0.8;
        }

        .contact-form {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius-md);
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--border-radius-sm);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            transition: all var(--transition-normal);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-control:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.15);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .btn-submit {
            width: 100%;
            background: white;
            color: var(--htt-primary);
            font-weight: 700;
            padding: 16px;
            border: none;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: all var(--transition-normal);
        }

        .btn-submit:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
        }

        /* Enhanced Customer Satisfaction Section */
        .satisfaction-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: var(--border-radius-xl);
            padding: 80px 60px;
            margin-top: 100px;
            position: relative;
            overflow: hidden;
            text-align: center;
            box-shadow: var(--card-shadow-hover);
        }

        .satisfaction-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .satisfaction-content {
            position: relative;
            z-index: 2;
        }

        .satisfaction-title {
            font-family: 'Poppins', sans-serif;
            font-size: 48px;
            font-weight: 800;
            color: white;
            margin-bottom: 24px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .satisfaction-subtitle {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 50px;
        }

        .satisfaction-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .metric-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius-md);
            padding: 32px 28px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all var(--transition-normal);
            text-align: center;
        }

        .metric-card:hover {
            transform: translateY(-8px);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: var(--card-shadow);
        }

        .metric-number {
            font-family: 'Poppins', sans-serif;
            font-size: 48px;
            font-weight: 800;
            color: white;
            margin-bottom: 12px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .metric-label {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Enhanced Footer */
        .footer {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 60px 0;
            text-align: center;
            margin-top: 80px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-content {
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            transition: all var(--transition-normal);
            padding: 8px 16px;
            border-radius: var(--border-radius-sm);
        }

        .footer-links a:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .footer-social {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 32px;
        }

        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all var(--transition-normal);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .social-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateY(-2px);
        }

        /* Enhanced Responsive Design */
        @media (max-width: 1024px) {
            .slide {
                padding: 40px;
            }
            
            .slide-content {
                max-width: 60%;
            }
            
            .slide-image {
                width: 50%;
            }
            
            .contact-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .testimonials-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 16px;
            }
            
            .framework-title {
                font-size: 48px;
            }
            
            .framework-subtitle {
                font-size: 20px;
            }
            
            .macta-flow {
                flex-direction: column;
                gap: 24px;
            }
            
            .connection-line {
                width: 4px;
                height: 50px;
                transform: rotate(90deg);
            }
            
            .connection-line::after {
                right: -4px;
                top: 44px;
                transform: rotate(90deg);
            }
            
            .module-card {
                width: 160px;
                height: 140px;
            }
            
            .slider-container {
                height: 400px;
                border-radius: var(--border-radius-md);
            }
            
            .slide {
                padding: 32px 24px;
                flex-direction: column;
                text-align: center;
            }
            
            .slide-content {
                max-width: 100%;
                margin-bottom: 24px;
            }
            
            .slide-image {
                position: static;
                width: 100%;
                height: 200px;
                border-radius: var(--border-radius-sm);
            }
            
            .satisfaction-metrics {
                grid-template-columns: repeat(2, 1fr);
                gap: 24px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .hero-cta {
                flex-direction: column;
                align-items: center;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 16px;
            }
            
            .testimonials-section,
            .contact-section,
            .satisfaction-section {
                padding: 40px 24px;
            }
            
            .trust-badges {
                flex-direction: column;
                gap: 20px;
            }
        }

        /* Enhanced Animation Classes */
        .fade-in {
            animation: fadeInUp 1s ease-out;
        }

        .fade-in-delay {
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--htt-primary), var(--htt-primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .loading-overlay.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Scroll Progress Bar */
        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 4px;
            background: linear-gradient(90deg, var(--modeling-primary), var(--assessment-primary));
            z-index: 9999;
            transition: width 0.1s ease;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Scroll Progress Bar -->
    <div class="scroll-progress" id="scrollProgress"></div>

    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo-icon">HTT</div>
                    <div class="company-info">
                        <h1>High Tech Talents</h1>
                        <div class="company-tagline">Managed Services Excellence</div>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="#contact" class="btn btn-outline">
                        <i class="fas fa-envelope"></i>
                        Contact Us
                    </a>
                    <a href="admin/login.php" class="btn btn-primary">
                        <i class="fas fa-user-shield"></i>
                        Admin Login
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <section class="hero-section fade-in">
                <h1 class="framework-title">MACTA Framework</h1>
                <p class="framework-subtitle">Modeling — Analysis — Customization — Training — Assessment</p>
                <p class="framework-description">
                    Transform your business processes with HTT's comprehensive MACTA Framework. Our systematic approach 
                    combines visual process modeling, data-driven analysis, customized solutions, targeted training programs, 
                    and continuous performance assessment to deliver measurable results and superior customer satisfaction.
                </p>
                <div class="hero-cta">
                    <a href="#framework" class="btn btn-primary">
                        <i class="fas fa-rocket"></i>
                        Explore Framework
                    </a>
                    <a href="#contact" class="btn btn-outline">
                        <i class="fas fa-calendar"></i>
                        Schedule Demo
                    </a>
                </div>
            </section>

            <!-- Trust Indicators -->
            <section class="trust-section fade-in-delay">
                <h3 class="trust-title">Trusted by Industry Leaders</h3>
                <div class="trust-badges">
                    <div class="trust-badge">
                        <i class="fas fa-shield-alt"></i>
                        <span>ISO 27001 Certified</span>
                    </div>
                    <div class="trust-badge">
                        <i class="fas fa-award"></i>
                        <span>Industry Excellence Award</span>
                    </div>
                    <div class="trust-badge">
                        <i class="fas fa-users"></i>
                        <span>500+ Satisfied Clients</span>
                    </div>
                    <div class="trust-badge">
                        <i class="fas fa-clock"></i>
                        <span>24/7 Support</span>
                    </div>
                </div>
            </section>

            <section class="macta-framework fade-in-delay" id="framework">
                <div class="framework-header">
                    <h2>Strategic Process Optimization</h2>
                    <p class="methodology-subtitle">A proven methodology for business process excellence and digital transformation</p>
                </div>

                <div class="macta-flow-container">
                    <div class="macta-flow">
                        <a href="modules/M/enhanced_macta_modeling.php" class="module-card modeling" data-module="modeling">
                            <div class="module-status"></div>
                            <i class="module-icon fas fa-project-diagram"></i>
                            <div class="module-title">Modeling</div>
                        </a>

                        <div class="connection-line"></div>

                        <a href="modules/A/process_viewer_page.php" class="module-card analysis" data-module="analysis">
                            <div class="module-status"></div>
                            <i class="module-icon fas fa-chart-line"></i>
                            <div class="module-title">Analysis</div>
                        </a>

                        <div class="connection-line"></div>

                        <a href="#" class="module-card customization" data-module="customization" onclick="showComingSoon('Customization')">
                            <div class="module-status coming-soon"></div>
                            <i class="module-icon fas fa-cogs"></i>
                            <div class="module-title">Customization</div>
                        </a>

                        <div class="connection-line"></div>

                        <a href="#" class="module-card training" data-module="training" onclick="showComingSoon('Training')">
                            <div class="module-status coming-soon"></div>
                            <i class="module-icon fas fa-graduation-cap"></i>
                            <div class="module-title">Training</div>
                        </a>

                        <div class="connection-line"></div>

                        <a href="#" class="module-card assessment" data-module="assessment" onclick="showComingSoon('Assessment')">
                            <div class="module-status coming-soon"></div>
                            <i class="module-icon fas fa-chart-bar"></i>
                            <div class="module-title">Assessment</div>
                        </a>
                    </div>
                </div>

                <!-- Enhanced Auto-Rotating Slider -->
                <div class="slider-container">
                    <div class="slider-wrapper" id="sliderWrapper">
                        <!-- Modeling Slide -->
                        <div class="slide slide-modeling">
                            <div class="slide-content">
                                <i class="slide-icon fas fa-project-diagram"></i>
                                <h3 class="slide-title">Process Modeling</h3>
                                <p class="slide-description">
                                    Advanced visual process builder with intuitive drag-and-drop functionality, comprehensive process simulation, and intelligent bottleneck analysis using industry-standard BPMN notation.
                                </p>
                                <ul class="slide-features">
                                    <li>Interactive visual process mapping and documentation</li>
                                    <li>Real-time bottleneck identification and optimization</li>
                                    <li>Process Workflows and Path simulation</li>
                                    <li>BPMN 2.0 compliant modeling standards</li>
                                </ul>
                            </div>
                            <div class="slide-image"></div>
                        </div>

                        <!-- Analysis Slide -->
                        <div class="slide slide-analysis">
                            <div class="slide-content">
                                <i class="slide-icon fas fa-chart-line"></i>
                                <h3 class="slide-title">Workflow Analysis</h3>
                                <p class="slide-description">
                                    Comprehensive analytics platform with advanced optimization capabilities, intelligent trend analysis, and machine learning-powered pattern recognition from multiple integrated data sources.
                                </p>
                                <ul class="slide-features">        
                                    <li>AI-powered insights and predictive trend analysis</li>
                                    <li>Custom dashboard creation with real-time updates</li>
                                    <li>Automated monitoring with intelligent alerts</li>
                                    <li>Multi-source data integration and visualization</li>
                                    <li>Process simulation with predictive analytics</li>
                                </ul>
                            </div>
                            <div class="slide-image"></div>
                        </div>

                        <!-- Customization Slide -->
                        <div class="slide slide-customization">
                            <div class="slide-content">
                                <i class="slide-icon fas fa-cogs"></i>
                                <h3 class="slide-title">Innovative Customization</h3>
                                <p class="slide-description">
                                    Tailored business solutions featuring dynamic job description generation, customizable client portals, and enterprise-grade role-based access control systems.
                                </p>
                                <ul class="slide-features">
                                    <li>AI-generated customized job descriptions</li>
                                    <li>White-label client portal management</li>
                                    <li>Enterprise role-based access control</li>
                                    <li>Flexible workflow customization engine</li>
                                </ul>
                            </div>
                            <div class="slide-image"></div>
                        </div>

                        <!-- Training Slide -->
                        <div class="slide slide-training">
                            <div class="slide-content">
                                <i class="slide-icon fas fa-graduation-cap"></i>
                                <h3 class="slide-title">Tailored Training</h3>
                                <p class="slide-description">
                                    Comprehensive training ecosystem with personalized learning paths, real client case studies, and immersive scenario-based practice environments for optimal skill development.
                                </p>
                                <ul class="slide-features">
                                    <li>Real-world scenario-based training modules</li>
                                    <li>Interactive learning with gamification</li>
                                    <li>Advanced progress tracking and assessment</li>
                                    <li>Personalized learning path recommendations</li>
                                </ul>
                            </div>
                            <div class="slide-image"></div>
                        </div>

                        <!-- Assessment Slide -->
                        <div class="slide slide-assessment">
                            <div class="slide-content">
                                <i class="slide-icon fas fa-chart-bar"></i>
                                <h3 class="slide-title">Performance Assessment</h3>
                                <p class="slide-description">
                                    Enterprise-grade performance measurement suite with comprehensive KPI tracking, real-time executive dashboards, and intelligent automated reporting systems.
                                </p>
                                <ul class="slide-features">
                                    <li>Executive-level performance dashboards</li>
                                    <li>Comprehensive KPI tracking and benchmarking</li>
                                    <li>Intelligent automated reporting systems</li>
                                    <li>Predictive performance analytics</li>
                                </ul>
                            </div>
                            <div class="slide-image"></div>
                        </div>
                    </div>

                    <!-- Enhanced Slider Indicators -->
                    <div class="slider-indicators">
                        <span class="indicator active" data-slide="0"></span>
                        <span class="indicator" data-slide="1"></span>
                        <span class="indicator" data-slide="2"></span>
                        <span class="indicator" data-slide="3"></span>
                        <span class="indicator" data-slide="4"></span>
                    </div>
                </div>
            </section>

            <!-- Testimonials Section -->
            <section class="testimonials-section fade-in" id="testimonials">
                <div class="testimonials-header">
                    <h3>What Our Clients Say</h3>
                    <p class="testimonials-subtitle">Real feedback from companies that have transformed their processes with MACTA</p>
                </div>
                <div class="testimonials-grid">
                    <div class="testimonial-card">
                        <p class="testimonial-quote">
                            "The MACTA Framework revolutionized our operations. We saw a 45% improvement in process efficiency within the first quarter. The visual modeling tools made it easy for our team to understand and optimize complex workflows."
                        </p>
                        <div class="testimonial-author">
                            <div class="author-avatar">SM</div>
                            <div class="author-info">
                                <h4>Sarah Mitchell</h4>
                                <p>Operations Director, TechCorp Solutions</p>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <p class="testimonial-quote">
                            "HTT's analytical capabilities are outstanding. The real-time dashboards and predictive insights have given us unprecedented visibility into our business processes. Customer satisfaction has never been higher."
                        </p>
                        <div class="testimonial-author">
                            <div class="author-avatar">MJ</div>
                            <div class="author-info">
                                <h4>Michael Johnson</h4>
                                <p>CEO, Innovation Dynamics</p>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <p class="testimonial-quote">
                            "The training modules are exceptional. Our team was up and running quickly, and the ongoing support has been fantastic. The ROI was evident within weeks of implementation."
                        </p>
                        <div class="testimonial-author">
                            <div class="author-avatar">ER</div>
                            <div class="author-info">
                                <h4>Emily Rodriguez</h4>
                                <p>Process Manager, Global Manufacturing Inc.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Contact Section -->
            <section class="contact-section" id="contact">
                <div class="contact-content">
                    <div class="contact-info">
                        <h3>Ready to Transform Your Business?</h3>
                        <p>
                            Get in touch with our experts to learn how the MACTA Framework can optimize your processes, 
                            improve efficiency, and drive measurable results for your organization.
                        </p>
                        <div class="contact-details">
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <span>info@hightechtalents.com</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <span>+1 (555) 123-4567</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>123 Innovation Drive, Tech City, TC 12345</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-clock"></i>
                                <span>24/7 Support Available</span>
                            </div>
                        </div>
                    </div>
                    <div class="contact-form">
                        <form id="contactForm" onsubmit="handleFormSubmit(event)">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Your full name" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="your.email@company.com" required>
                            </div>
                            <div class="form-group">
                                <label for="company">Company</label>
                                <input type="text" id="company" name="company" class="form-control" placeholder="Your company name">
                            </div>
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" class="form-control" placeholder="Tell us about your process optimization needs..." required></textarea>
                            </div>
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane"></i>
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Enhanced Customer Satisfaction -->
            <div class="satisfaction-section">
                <div class="satisfaction-content">
                    <h3 class="satisfaction-title">Delivering Excellence</h3>
                    <p class="satisfaction-subtitle">Our commitment to customer satisfaction drives measurable business results</p>
                    
                    <div class="satisfaction-metrics">
                        <div class="metric-card">
                            <div class="metric-number">98%</div>
                            <div class="metric-label">Client Satisfaction</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-number">45%</div>
                            <div class="metric-label">Process Efficiency</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-number">24/7</div>
                            <div class="metric-label">Support Available</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-number">500+</div>
                            <div class="metric-label">Projects Delivered</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <p>&copy; 2024 High Tech Talents. Transforming businesses through innovative process management solutions.</p>
            </div>
            <div class="footer-links">
                <a href="#framework">Framework</a>
                <a href="#testimonials">Testimonials</a>
                <a href="#contact">Contact</a>
                <a href="#privacy">Privacy Policy</a>
                <a href="#terms">Terms of Service</a>
                <a href="admin/login.php">Admin Portal</a>
            </div>
            <div class="footer-social">
                <a href="#" class="social-link" title="LinkedIn">
                    <i class="fab fa-linkedin-in"></i>
                </a>
                <a href="#" class="social-link" title="Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="social-link" title="Email">
                    <i class="fas fa-envelope"></i>
                </a>
            </div>
        </div>
    </footer>

    <script>
        // Enhanced JavaScript with Modern Features
        let currentSlide = 0;
        let sliderInterval;
        const totalSlides = 5;

        // Loading Animation
        window.addEventListener('load', function() {
            setTimeout(() => {
                document.getElementById('loadingOverlay').classList.add('hidden');
            }, 1000);
        });

        // Scroll Progress Bar
        window.addEventListener('scroll', function() {
            const scrollProgress = document.getElementById('scrollProgress');
            const scrollTop = window.pageYOffset;
            const docHeight = document.body.scrollHeight - window.innerHeight;
            const scrollPercent = (scrollTop / docHeight) * 100;
            scrollProgress.style.width = scrollPercent + '%';
        });

        // Enhanced Slider Functions
        function moveToSlide(slideIndex) {
            currentSlide = slideIndex;
            const sliderWrapper = document.getElementById('sliderWrapper');
            const translateX = -slideIndex * 20;
            sliderWrapper.style.transform = `translateX(${translateX}%)`;
            
            // Update indicators
            document.querySelectorAll('.indicator').forEach((indicator, index) => {
                indicator.classList.toggle('active', index === slideIndex);
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            moveToSlide(currentSlide);
        }

        function startSlider() {
            sliderInterval = setInterval(nextSlide, 4000);
        }

        function stopSlider() {
            clearInterval(sliderInterval);
        }

        // Enhanced Coming Soon Notification
        function showComingSoon(moduleName) {
            // Remove existing notifications
            document.querySelectorAll('.coming-soon-notification').forEach(n => n.remove());
            
            const notification = document.createElement('div');
            notification.className = 'coming-soon-notification';
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 24px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                padding: 20px 24px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                z-index: 1000;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 12px;
                animation: slideInRight 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                border: 1px solid rgba(255, 255, 255, 0.2);
                backdrop-filter: blur(10px);
                max-width: 300px;
            `;
            
            notification.innerHTML = `
                <i class="fas fa-rocket" style="font-size: 20px;"></i>
                <div>
                    <div style="font-size: 16px; margin-bottom: 4px;">${moduleName} Module</div>
                    <div style="font-size: 14px; opacity: 0.9;">Coming soon with advanced features!</div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Add animation styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideInRight {
                    from { 
                        transform: translateX(100%); 
                        opacity: 0; 
                    }
                    to { 
                        transform: translateX(0); 
                        opacity: 1; 
                    }
                }
                
                @keyframes slideOutRight {
                    from { 
                        transform: translateX(0); 
                        opacity: 1; 
                    }
                    to { 
                        transform: translateX(100%); 
                        opacity: 0; 
                    }
                }
            `;
            document.head.appendChild(style);
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.4s ease-in-out';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                    if (document.head.contains(style)) {
                        document.head.removeChild(style);
                    }
                }, 400);
            }, 4000);
        }

        // Contact Form Handler
        function handleFormSubmit(event) {
            event.preventDefault();
            
            // Get form data
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData);
            
            // Show success message
            const submitBtn = event.target.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Message Sent!';
            submitBtn.style.background = 'var(--assessment-primary)';
            
            // Reset form after 2 seconds
            setTimeout(() => {
                event.target.reset();
                submitBtn.innerHTML = originalText;
                submitBtn.style.background = '';
            }, 2000);
            
            // In a real application, you would send this data to your server
            console.log('Form submitted:', data);
        }

        // Smooth Scrolling for Anchor Links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Enhanced Intersection Observer for Animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Start auto-slider
            startSlider();
            
            // Enhanced indicator click events
            document.querySelectorAll('.indicator').forEach((indicator, index) => {
                indicator.addEventListener('click', () => {
                    stopSlider();
                    moveToSlide(index);
                    // Restart timer after manual selection
                    setTimeout(startSlider, 3000);
                });
            });
            
            // Enhanced slider pause/resume on hover
            const sliderContainer = document.querySelector('.slider-container');
            sliderContainer.addEventListener('mouseenter', stopSlider);
            sliderContainer.addEventListener('mouseleave', startSlider);
            
            // Enhanced module card interactions
            document.querySelectorAll('.module-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    if (this.href && this.href !== '#' && !this.href.includes('javascript:')) {
                        const moduleName = this.querySelector('.module-title').textContent;
                        console.log(`Navigating to ${moduleName} module`);
                        
                        // Enhanced loading effect
                        this.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 150);
                    }
                });
                
                // Add hover sound effect (optional)
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.05)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });

            // Initialize fade-in animations
            const fadeElements = document.querySelectorAll('.fade-in, .fade-in-delay');
            fadeElements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(40px)';
                el.style.transition = 'opacity 1s ease, transform 1s ease';
                observer.observe(el);
            });

            // Enhanced button interactions
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    // Create ripple effect
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(255, 255, 255, 0.3);
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.6s ease-out;
                        pointer-events: none;
                    `;
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Add ripple animation
            const rippleStyle = document.createElement('style');
            rippleStyle.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(2);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(rippleStyle);

            // Animate metrics on scroll
            const metricsObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const number = entry.target.querySelector('.metric-number');
                        const finalValue = number.textContent;
                        let currentValue = 0;
                        const increment = finalValue.includes('%') ? 1 : 10;
                        const target = parseInt(finalValue);
                        
                        const timer = setInterval(() => {
                            currentValue += increment;
                            if (currentValue >= target) {
                                currentValue = target;
                                clearInterval(timer);
                            }
                            number.textContent = currentValue + (finalValue.includes('%') ? '%' : finalValue.includes('+') ? '+' : finalValue.includes('/') ? '/7' : '');
                        }, 50);
                    }
                });
            }, { threshold: 0.5 });

            document.querySelectorAll('.metric-card').forEach(card => {
                metricsObserver.observe(card);
            });
        });

        // Enhanced Performance Monitoring
        if ('performance' in window) {
            window.addEventListener('load', function() {
                setTimeout(() => {
                    const perfData = performance.timing;
                    const loadTime = perfData.loadEventEnd - perfData.navigationStart;
                    console.log(`Page load time: ${loadTime}ms`);
                }, 0);
            });
        }
    </script>
</body>
</html>

