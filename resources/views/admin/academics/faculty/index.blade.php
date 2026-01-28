<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Profile - Chandrasekar S</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #475569;
            --accent-color: #3b82f6;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
            --success-color: #10b981;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            border-radius: 16px 16px 0 0;
            padding: 2rem;
            color: white;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #60a5fa 0%, #93c5fd 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            color: white;
            border: 4px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 1.5rem;
        }
        
        .profile-content {
            background: white;
            border-radius: 0 0 16px 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 2rem;
        }
        
        .section-card {
            background: var(--light-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }
        
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--accent-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .section-title i {
            font-size: 1.5rem;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            border-left: 4px solid var(--accent-color);
        }
        
        .info-item:hover {
            background: #f1f5f9;
        }
        
        .info-label {
            font-weight: 500;
            color: var(--secondary-color);
            min-width: 180px;
            flex-shrink: 0;
        }
        
        .info-value {
            color: #0f172a;
            font-weight: 400;
        }
        
        .contact-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .contact-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }
        
        .contact-badge:hover, .contact-badge:focus {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: #f1f5f9;
            color: var(--primary-color);
            font-weight: 600;
            padding: 1rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-color: var(--border-color);
        }
        
        .table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        .upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            background: var(--accent-color);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.875rem;
            transition: background-color 0.2s ease;
        }
        
        .upload-btn:hover, .upload-btn:focus {
            background: #2563eb;
            color: white;
            text-decoration: none;
        }
        
        .expertise-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .expertise-badge {
            padding: 0.5rem 1.25rem;
            background: linear-gradient(135deg, #60a5fa 0%, #93c5fd 100%);
            color: white;
            border-radius: 20px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .expertise-badge i {
            font-size: 0.875rem;
        }
        
        /* High Contrast Mode Support */
        @media (prefers-contrast: high) {
            .section-card {
                border: 2px solid #000;
            }
            
            .info-item {
                border-left: 4px solid #000;
            }
            
            .table {
                border: 2px solid #000;
            }
            
            .table th, .table td {
                border: 1px solid #000;
            }
        }
        
        /* Reduced Motion Support */
        @media (prefers-reduced-motion: reduce) {
            .section-card,
            .info-item,
            .contact-badge,
            .upload-btn {
                transition: none;
            }
            
            .section-card:hover {
                transform: none;
            }
        }
        
        /* Focus Styles for Accessibility */
        .focus-visible:focus {
            outline: 3px solid var(--accent-color);
            outline-offset: 2px;
        }
        
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .profile-header {
                padding: 1.5rem;
            }
            
            .profile-content {
                padding: 1.5rem;
            }
            
            .info-item {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .info-label {
                min-width: 100%;
            }
            
            .section-title {
                font-size: 1.125rem;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container" role="main">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-auto text-center text-md-start">
                    <div class="profile-avatar" role="img" aria-label="Profile picture placeholder">
                        CS
                    </div>
                </div>
                <div class="col-md">
                    <h1 class="h2 mb-2">Chandrasekar S (M)</h1>
                    <p class="mb-3 opacity-90">Internal Faculty</p>
                    
                    <!-- Contact Information -->
                    <div class="contact-badges">
                        <a href="tel:+919410147761" class="contact-badge focus-visible" aria-label="Call primary number">
                            <i class="bi bi-telephone"></i>
                            <span>+91 9410147761</span>
                        </a>
                        <a href="tel:+919786125971" class="contact-badge focus-visible" aria-label="Call secondary number">
                            <i class="bi bi-phone"></i>
                            <span>+91 9786125971</span>
                        </a>
                        <a href="mailto:schand.sekar@ias.nic.in" class="contact-badge focus-visible" aria-label="Send email to primary address">
                            <i class="bi bi-envelope"></i>
                            <span>schand.sekar@ias.nic.in</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Content -->
        <div class="profile-content">
            <!-- Address Section -->
            <div class="section-card">
                <h2 class="section-title">
                    <i class="bi bi-geo-alt"></i>
                    Address
                </h2>
                
                <div class="table-responsive">
                    <table class="table table-hover" aria-label="Address details" id="dom_jq_event">
                        <thead>
                            <tr>
                                <th scope="col">Country</th>
                                <th scope="col">State</th>
                                <th scope="col">City</th>
                                <th scope="col">District</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>India</td>
                                <td>Tamil Nadu</td>
                                <td>Chennai City</td>
                                <td>Chennai</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Qualification Details -->
            <div class="section-card">
                <h2 class="section-title">
                    <i class="bi bi-mortarboard"></i>
                    Qualification Details
                </h2>
                
                <h3 class="h5 mb-3 text-secondary">Degree</h3>
                <div class="table-responsive">
                    <table class="table table-hover" aria-label="Educational qualifications">
                        <thead>
                            <tr>
                                <th scope="col">University/Institute Name</th>
                                <th scope="col">Passing Year</th>
                                <th scope="col">Percentage / CGPA</th>
                                <th scope="col">Documents</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>IIT Madras</td>
                                <td>2025</td>
                                <td>80%</td>
                                <td>
                                    <a href="#" class="upload-btn focus-visible" aria-label="Upload degree documents">
                                        <i class="bi bi-upload"></i>
                                        Upload
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>IIT Madras</td>
                                <td>2023</td>
                                <td>90%</td>
                                <td>
                                    <a href="#" class="upload-btn focus-visible" aria-label="View uploaded documents">
                                        <i class="bi bi-eye"></i>
                                        View
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Experience Details -->
            <div class="section-card">
                <h2 class="section-title">
                    <i class="bi bi-briefcase"></i>
                    Experience Details
                </h2>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="info-item">
                            <span class="info-label">Years of Experience:</span>
                            <span class="info-value">8 Years</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Area of Specialization:</span>
                            <span class="info-value">Education</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Previous Institutions:</span>
                            <span class="info-value">NgoR</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <span class="info-label">Position Held:</span>
                            <span class="info-value">Exercise</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Duration:</span>
                            <span class="info-value">1 Year</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nature of Work:</span>
                            <span class="info-value">Full Time</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bank Details -->
            <div class="section-card">
                <h2 class="section-title">
                    <i class="bi bi-bank"></i>
                    Bank Details
                </h2>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <span class="info-label">Bank Name:</span>
                            <span class="info-value">Citi Bank</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">A/c Number:</span>
                            <span class="info-value">XXXX XXXX 1234</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <span class="info-label">IFSC Code:</span>
                            <span class="info-value">IBAR0123456</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">PAN Number:</span>
                            <span class="info-value">ABCDE1234F</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Areas of Expertise -->
            <div class="section-card">
                <h2 class="section-title">
                    <i class="bi bi-stars"></i>
                    Areas of Expertise
                </h2>
                
                <div class="expertise-badges">
                    <span class="expertise-badge">
                        <i class="bi bi-book"></i>
                        Education
                    </span>
                    <span class="expertise-badge">
                        <i class="bi bi-heart-pulse"></i>
                        Health
                    </span>
                    <span class="expertise-badge">
                        <i class="bi bi-trophy"></i>
                        Sports
                    </span>
                    <span class="expertise-badge">
                        <i class="bi bi-egg-fried"></i>
                        Cooking
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Accessibility Enhancements -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add keyboard navigation to all interactive elements
            const interactiveElements = document.querySelectorAll('a, button, .upload-btn');
            
            interactiveElements.forEach(element => {
                element.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
                
                element.addEventListener('focus', function() {
                    this.classList.add('focus-visible');
                });
                
                element.addEventListener('blur', function() {
                    this.classList.remove('focus-visible');
                });
            });
            
            // Add ARIA live region for dynamic content
            const mainContent = document.querySelector('[role="main"]');
            mainContent.setAttribute('aria-live', 'polite');
            
            // Tooltip initialization for better UX
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>