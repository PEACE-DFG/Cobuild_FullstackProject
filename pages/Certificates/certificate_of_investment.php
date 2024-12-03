<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investment Certificate - Cobuild</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Montserrat:wght@400;500;600&display=swap');
        
        body {
            background: #f0f0f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Montserrat', sans-serif;
        }
        
        .certificate-container {
            background: white;
            width: 1000px;
            position: relative;
            padding: 50px;
            border: 2px solid #ffd700;
        }
        
        .diagonal-pattern {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(
                -45deg,
                #f9f9f9,
                #f9f9f9 10px,
                #ffffff 10px,
                #ffffff 20px
            );
            z-index: 0;
        }
        
        .certificate-content {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid #ffd700;
            padding: 40px;
        }
        
        .corner {
            position: absolute;
            width: 100px;
            height: 100px;
            border: 2px solid #ffd700;
        }
        
        .top-left {
            top: -2px;
            left: -2px;
            border-right: none;
            border-bottom: none;
        }
        
        .top-right {
            top: -2px;
            right: -2px;
            border-left: none;
            border-bottom: none;
        }
        
        .bottom-left {
            bottom: -2px;
            left: -2px;
            border-right: none;
            border-top: none;
        }
        
        .bottom-right {
            bottom: -2px;
            right: -2px;
            border-left: none;
            border-top: none;
        }
        
        .award-seal {
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 100px;
            background: #ffd700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid white;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .title {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            color: #333;
            text-align: center;
            margin-top: 40px;
            letter-spacing: 2px;
        }
        
        .subtitle {
            text-align: center;
            font-size: 18px;
            color: #666;
            margin-bottom: 40px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        
        .recipient-name {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            text-align: center;
            margin: 30px 0;
            color: #333;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(0,0,0,0.9);
            white-space: nowrap;
            font-family: 'Playfair Display', serif;
            pointer-events: none;
            z-index: 0;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            padding: 0 100px;
        }
        
        .signature {
            text-align: center;
        }
        
        .signature-line {
            width: 200px;
            border-bottom: 2px solid #333;
            margin: 10px 0;
        }
        
        .date-section {
            text-align: left;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="diagonal-pattern"></div>
        <div class="watermark">COBUILD</div>
        <div class="certificate-content">
            <div class="corner top-left"></div>
            <div class="corner top-right"></div>
            <div class="corner bottom-left"></div>
            <div class="corner bottom-right"></div>
            
            <div class="award-seal">
                <img src="https://t3.ftcdn.net/jpg/00/95/85/54/360_F_95855459_krAkLf2eLiIpuY4yKniEiDmXykjnC2pn.jpg" alt="Seal" class="img-fluid">
            </div>
            
            <h1 class="title">Certificate of Investment</h1>
            <p class="subtitle">This is to certify that</p>
            
            <div class="recipient-name">
                <span id="investor-name">JOHN DOE</span>
            </div>
            
            <p class="text-center mb-4">
                Has invested in the following project through Cobuild Investment Platform
            </p>
            
            <div class="details text-center">
                <p><strong>Project Name:</strong> <span id="project-name">Sample Project</span></p>
                <p><strong>Investment Amount:</strong> <span id="investment-amount">₦1,000,000</span></p>
                <p><strong>Expected Returns:</strong> <span id="expected-returns">15%</span></p>
                <p><strong>Certificate Number:</strong> <span id="certificate-number">INV-2024-001</span></p>
            </div>
            
            <div class="signature-section">
                <div class="signature">
                    <div class="signature-line"></div>
                    <p class="mb-0">Segun Oke</p>
                    <small>Trustee</small>
                </div>
                <div class="signature">
                    <div class="signature-line"></div>
                    <p class="mb-0">Adenike Akanji</p>
                    <small>Trustee / Secretary</small>
                </div>
            </div>
            
            <div class="date-section">
                <p><strong>Date:</strong> <span id="certificate-date">October 6, 2024</span></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to update certificate details
        function updateCertificate(data) {
            document.getElementById('investor-name').textContent = data.investorName;
            document.getElementById('project-name').textContent = data.projectName;
            document.getElementById('investment-amount').textContent = formatCurrency(data.amount);
            document.getElementById('expected-returns').textContent = data.returns + '%';
            document.getElementById('certificate-number').textContent = data.certificateNumber;
            document.getElementById('certificate-date').textContent = formatDate(data.date);
        }

        // Helper function to format currency
        function formatCurrency(amount) {
            return '₦' + new Intl.NumberFormat().format(amount);
        }

        // Helper function to format date
        function formatDate(date) {
            return new Date(date).toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            });
        }
    </script>
</body>
</html>