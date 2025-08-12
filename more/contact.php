<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | SkillMatch</title>
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
        }
        .contact-page-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 90vh;
        }
        .contact-container {
            background: rgba(255,255,255,0.95);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            border-radius: 24px;
            max-width: 900px;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            overflow: hidden;
            margin: 40px 0;
        }
        .contact-info {
            flex: 1 1 300px;
            background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
            color: #fff;
            padding: 48px 32px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .contact-info h2 {
            margin: 0 0 24px 0;
            font-size: 2rem;
            font-weight: 600;
        }
        .contact-info p {
            margin: 0 0 16px 0;
            font-size: 1.1rem;
            opacity: 0.95;
        }
        .contact-info .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 18px;
        }
        .contact-info .info-item svg {
            margin-right: 12px;
            flex-shrink: 0;
        }
        .contact-form-section {
            flex: 2 1 400px;
            padding: 48px 32px;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .contact-form-section h3 {
            margin-bottom: 24px;
            font-size: 1.5rem;
            font-weight: 600;
            color: #3730a3;
        }
        .contact-form-section .form-group {
            position: relative;
            margin-bottom: 28px;
        }
        .contact-form-section .form-group input,
        .contact-form-section .form-group textarea {
            width: 100%;
            padding: 16px 12px 16px 12px;
            font-size: 1rem;
            border: 1.5px solid #c7d2fe;
            border-radius: 8px;
            background: transparent;
            outline: none;
            transition: border 0.2s;
            resize: none;
        }
        .contact-form-section .form-group input:focus,
        .contact-form-section .form-group textarea:focus {
            border-color: #6366f1;
        }
        .contact-form-section .form-group label {
            position: absolute;
            left: 14px;
            top: 16px;
            background: #fff;
            color: #6366f1;
            font-size: 1rem;
            padding: 0 4px;
            pointer-events: none;
            transition: 0.2s;
        }
        .contact-form-section .form-group input:focus + label,
        .contact-form-section .form-group input:not(:placeholder-shown) + label,
        .contact-form-section .form-group textarea:focus + label,
        .contact-form-section .form-group textarea:not(:placeholder-shown) + label {
            top: -12px;
            left: 10px;
            font-size: 0.92rem;
            background: #fff;
            color: #3730a3;
        }
        .contact-form-section button {
            background: linear-gradient(90deg, #6366f1 0%, #818cf8 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 14px 0;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(99,102,241,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .contact-form-section button:hover {
            background: linear-gradient(90deg, #3730a3 0%, #6366f1 100%);
            box-shadow: 0 4px 16px rgba(99,102,241,0.15);
        }
        @media (max-width: 900px) {
            .contact-container {
                flex-direction: column;
                max-width: 98vw;
            }
            .contact-info, .contact-form-section {
                padding: 32px 16px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php' ?>
    <div class="contact-page-wrapper">
        <div class="contact-container">
            <div class="contact-info">
                <h2>Contact Us</h2>
                <p>We'd love to hear from you! Reach out with any questions, feedback, or partnership opportunities.</p>
                <div class="info-item">
                    <svg width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.657 16.243a8 8 0 1 0-11.314 0A8 8 0 0 0 17.657 16.243Z" stroke="#fff" stroke-width="2"/><path d="M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="#fff" stroke-width="2"/></svg>
                    <span>123 Main Street, City, Country</span>
                </div>
                <div class="info-item">
                    <svg width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 6.5v9A2.5 2.5 0 0 0 4.5 18h13a2.5 2.5 0 0 0 2.5-2.5v-9A2.5 2.5 0 0 0 17.5 4h-13A2.5 2.5 0 0 0 2 6.5Z" stroke="#fff" stroke-width="2"/><path d="m3 7 8 6 8-6" stroke="#fff" stroke-width="2"/></svg>
                    <span>careerquest93@gmail.com</span>
                </div>
                <div class="info-item">
                    <svg width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 2H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2Z" stroke="#fff" stroke-width="2"/><path d="M12 18v-2" stroke="#fff" stroke-width="2"/></svg>
                    <span>+1 (555) 123-4567</span>
                </div>
            </div>
            <form class="contact-form-section" autocomplete="off">
                <h3>Send us a message</h3>
                <div class="form-group">
                    <input type="text" id="name" name="name" required placeholder=" " />
                    <label for="name">Your Name</label>
                </div>
                <div class="form-group">
                    <input type="email" id="email" name="email" required placeholder=" " />
                    <label for="email">Your Email</label>
                </div>
                <div class="form-group">
                    <textarea id="message" name="message" rows="5" required placeholder=" "></textarea>
                    <label for="message">Your Message</label>
                </div>
                <button type="submit">Send Message</button>
            </form>
        </div>
    </div>
    <!-- Bootstrap JS (for navbar functionality) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../includes/footer.php' ?>
</body>
</html>