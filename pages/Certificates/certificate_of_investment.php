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
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 20px;
        }

        .certificate-container {
            background: white;
            width: 100%;
            max-width: 800px;
            padding: 30px;
            border: 2px solid #ffd700;
            margin: 0 auto;
            position: relative;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            page-break-before: always;
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
            padding: 30px;
        }

        .award-seal {
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 80px;
            background: #ffd700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid white;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            color: #333;
            text-align: center;
            margin-top: 60px;
            letter-spacing: 1px;
        }

        .subtitle {
            text-align: center;
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .recipient-name {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            text-align: center;
            margin: 30px 0;
            color: #333;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(8, 14, 109, 0.42);
            font-family: 'Playfair Display', serif;
            pointer-events: none;
            z-index: 0;
        }

        .details {
            text-align: center;
            margin-top: 40px;
        }

        .details p {
            font-size: 18px;
            color: #333;
            margin: 5px 0;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            padding: 0 50px;
        }

        .signature {
            text-align: center;
            flex: 1;
        }

        .signature-line {
            width: 150px;
            border-bottom: 2px solid #333;
            margin: 10px 0;
        }

        .date-section {
            text-align: left;
            margin-top: 30px;
            font-size: 16px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .certificate-container {
                padding: 20px;
            }

            .award-seal {
                width: 60px;
                height: 60px;
            }

            .title {
                font-size: 28px;
            }

            .recipient-name {
                font-size: 24px;
            }

            .details p {
                font-size: 16px;
            }

            .signature-section {
                flex-direction: column;
                align-items: center;
                padding: 0;
            }

            .signature {
                margin-bottom: 20px;
            }
        }

        /* For Print */
        @media print {
            body {
                padding: 0;
                margin: 0;
            }

            .certificate-container {
                max-width: 800px;
                margin: 0 auto;
                page-break-before: always;
                border: 2px solid #ffd700;
            }
            
            .watermark {
                font-size: 120px;
            }

            .signature-section {
                flex-direction: row;
                padding: 0 50px;
            }

            .signature {
                margin-bottom: 0;
            }

            .details p {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="diagonal-pattern"></div>
        <div class="watermark">COBUILD</div>
        <div class="certificate-content">
            <div class="award-seal">
                <img src="../../images/seal.jpg" alt="Seal" class="img-fluid">
            </div>
            
            <h1 class="title">Certificate of Investment</h1>
            <p class="subtitle">This is to certify that</p>
            
            <div class="recipient-name" id="investor-name"><strong>{{INVESTOR_NAME}}</strong></div>
            
            <p class="text-center mb-4" style="text-align:center">
            Has expressed an intention to invest in the project titled "<strong>{{PROJECT_NAME}}</strong>".
            </p>
            
            <div class="details">
                
                <p><strong>Investment Details:</strong> <span id="investment-amount"><strong>{{INVESTMENT_DETAILS}}</strong></span></p>
                <p>Investment Amount: <strong>{{AMOUNT}}</strong></p>
                </div>
            
            <div class="signature-section" style="display:flex; justift-content space-around">
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
