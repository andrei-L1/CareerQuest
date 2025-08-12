<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog | SkillMatch</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: 'Inter', Arial, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            box-sizing: border-box;
        }
        .blog-header {
            background: linear-gradient(90deg, #6366f1 0%, #818cf8 100%);
            color: #fff;
            padding: 48px 0 32px 0;
            text-align: center;
            
        }
        .blog-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .blog-header p {
            font-size: 1.2rem;
            opacity: 0.95;
        }
        .featured-post {
            background: #fff;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.10);
            border-radius: 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            margin: 24px auto 40px auto;
            max-width: 900px;
            overflow: hidden;
        }
        .featured-img {
            flex: 1 1 300px;
            min-width: 260px;
            height: 260px;
            background: url('https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?auto=format&fit=crop&w=600&q=80') center/cover no-repeat;
        }
        .featured-content {
            flex: 2 1 400px;
            padding: 32px 28px;
        }
        .featured-content h2 {
            font-size: 2rem;
            font-weight: 600;
            color: #3730a3;
        }
        .featured-content p {
            font-size: 1.1rem;
            margin: 18px 0 24px 0;
            color: #444;
        }
        .featured-content .btn {
            background: linear-gradient(90deg, #6366f1 0%, #818cf8 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 28px;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .featured-content .btn:hover {
            background: linear-gradient(90deg, #3730a3 0%, #6366f1 100%);
            box-shadow: 0 4px 16px rgba(99,102,241,0.15);
        }
        .blog-list-section {
            max-width: 1100px;
            margin: 0 auto 60px auto;
            padding: 0 16px;
        }
        .blog-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px 0 rgba(31, 38, 135, 0.08);
            overflow: hidden;
            margin-bottom: 32px;
            transition: transform 0.15s, box-shadow 0.15s;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .blog-card:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 8px 32px 0 rgba(99,102,241,0.13);
        }
        .blog-card-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .blog-card-body {
            padding: 24px 20px 20px 20px;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
        }
        .blog-card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #3730a3;
            margin-bottom: 10px;
        }
        .blog-card-excerpt {
            color: #555;
            font-size: 1rem;
            margin-bottom: 18px;
            flex: 1 1 auto;
        }
        .blog-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .blog-card-footer .btn {
            background: linear-gradient(90deg, #6366f1 0%, #818cf8 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
            font-size: 0.98rem;
            font-weight: 600;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .blog-card-footer .btn:hover {
            background: linear-gradient(90deg, #3730a3 0%, #6366f1 100%);
            box-shadow: 0 4px 16px rgba(99,102,241,0.15);
        }
        .blog-card-footer .meta {
            color: #888;
            font-size: 0.95rem;
        }
        @media (max-width: 900px) {
            .featured-post {
                flex-direction: column;
                margin: 16px 0 32px 0;
            }
            .featured-img {
                width: 100%;
                height: 180px;
                min-width: 0;
            }
            .featured-content {
                padding: 24px 16px;
            }
        }
        @media (max-width: 600px) {
            .blog-header {
                padding: 32px 0 18px 0;
            }
            .blog-header h1 {
                font-size: 1.6rem;
            }
            .blog-header p {
                font-size: 1rem;
            }
            .featured-post {
                margin: 12px 0 24px 0;
                border-radius: 12px;
            }
            .featured-img {
                height: 120px;
            }
            .featured-content h2 {
                font-size: 1.1rem;
            }
            .featured-content p {
                font-size: 0.98rem;
            }
            .featured-content .btn {
                padding: 8px 16px;
                font-size: 0.98rem;
            }
            .blog-list-section {
                padding: 0 4px;
            }
            .blog-card {
                border-radius: 10px;
            }
            .blog-card-img {
                height: 110px;
            }
            .blog-card-body {
                padding: 14px 8px 12px 8px;
            }
            .blog-card-title {
                font-size: 1.05rem;
            }
            .blog-card-excerpt {
                font-size: 0.95rem;
            }
            .blog-card-footer .btn {
                padding: 6px 12px;
                font-size: 0.93rem;
            }
            .blog-card-footer .meta {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php' ?>
    <div class="blog-header w-100">
        <h1>SkillMatch Blog</h1>
        <p>Insights, tips, and stories to help you grow your career and skills.</p>
    </div>
    <div class="container px-2 px-md-3">
        <div class="featured-post mb-5 mt-n5 mx-auto">
            <div class="featured-img"></div>
            <div class="featured-content">
                <h2>How to Stand Out in a Competitive Job Market</h2>
                <p>Discover proven strategies to make your application shine and land your dream job, even when competition is fierce. Learn from industry experts and real success stories.</p>
                <a href="#" class="btn">Read Featured</a>
            </div>
        </div>
        <div class="blog-list-section">
            <div class="row g-4">
                <!-- Blog Card 1 -->
                <div class="col-md-6 col-lg-4 d-flex align-items-stretch">
                    <div class="blog-card d-flex flex-column h-100 w-100">
                        <img src="https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=400&q=80" class="blog-card-img" alt="Blog 1">
                        <div class="blog-card-body d-flex flex-column flex-grow-1">
                            <div class="blog-card-title">Mastering Remote Work</div>
                            <div class="blog-card-excerpt flex-grow-1">Tips and tools to boost your productivity and well-being while working from home or anywhere in the world.</div>
                            <div class="blog-card-footer mt-auto">
                                <span class="meta"><i class="fa-regular fa-calendar"></i> May 2024</span>
                                <a href="#" class="btn btn-sm">Read More</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Blog Card 2 -->
                <div class="col-md-6 col-lg-4 d-flex align-items-stretch">
                    <div class="blog-card d-flex flex-column h-100 w-100">
                        <img src="https://images.unsplash.com/photo-1503676382389-4809596d5290?auto=format&fit=crop&w=400&q=80" class="blog-card-img" alt="Blog 2">
                        <div class="blog-card-body d-flex flex-column flex-grow-1">
                            <div class="blog-card-title">Top Skills Employers Want</div>
                            <div class="blog-card-excerpt flex-grow-1">Stay ahead of the curve by learning the most in-demand skills for 2024 and beyond, from tech to soft skills.</div>
                            <div class="blog-card-footer mt-auto">
                                <span class="meta"><i class="fa-regular fa-calendar"></i> April 2024</span>
                                <a href="#" class="btn btn-sm">Read More</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Blog Card 3 -->
                <div class="col-md-6 col-lg-4 d-flex align-items-stretch">
                    <div class="blog-card d-flex flex-column h-100 w-100">
                        <img src="https://images.unsplash.com/photo-1519125323398-675f0ddb6308?auto=format&fit=crop&w=400&q=80" class="blog-card-img" alt="Blog 3">
                        <div class="blog-card-body d-flex flex-column flex-grow-1">
                            <div class="blog-card-title">Networking for Success</div>
                            <div class="blog-card-excerpt flex-grow-1">Unlock the power of professional networking—online and offline—to open doors and accelerate your career growth.</div>
                            <div class="blog-card-footer mt-auto">
                                <span class="meta"><i class="fa-regular fa-calendar"></i> March 2024</span>
                                <a href="#" class="btn btn-sm">Read More</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Add more blog cards as needed -->
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php' ?>
    <!-- Bootstrap JS (for navbar functionality) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>