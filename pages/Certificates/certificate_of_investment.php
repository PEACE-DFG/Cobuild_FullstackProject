<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investment Certificate - Cobuild</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Montserrat:wght@400;500;600&display=swap');
        
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            box-sizing:border-box;
            font-family: 'Montserrat', sans-serif;
        }

        .certificate {
            width: 800px;
            margin: 20px auto;
            padding: 20px;
            position: relative;
            background: #fff;
            border: 5px solid #1a365d;
        }

        .inner-border {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 2px solid #daa520;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .logo-placeholder {
            margin: 0 auto 5px;
            width: 40px;
            height: 40px;
            background:gold;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color:  #1a365d;
            font-size: 30px;
            font-weight: bold;
        }

        .title {
            color: #1a365d;
            font-size: 35px;
            margin: 0;
            font-family: 'Cormorant Garamond', serif;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .subtitle {
            color: #666;
            font-size: 14px;
            margin: 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .recipient {
            text-align: center;
            margin: 20px 0;
        }

        .recipient-name {
            color: #1a365d;
            font-size: 24px;
            font-family: 'Cormorant Garamond', serif;
            border-bottom: 2px solid #daa520;
            display: inline-block;
            padding: 0 10px 5px;
            margin-bottom: 3px;
        }

        .details-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            max-width:470px;
            margin:  auto;

        }

        .detail-item {
            flex: 1 1 calc(11.333% - 15px);
            min-width: 130px;
            background: white;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .detail-label {
            color: #666;
            font-size: 19px;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .detail-value {
            color: #1a365d;
            font-size: 24px;
            font-weight: 500;
        }

        .signatures {
            display: flex;
            justify-content: space-around;
            padding-top: 10px;
            max-width:420px;
            margin:  auto;
            text-align: center;

        }

        .signature {
            text-align: center;
            width: 200px;
        }

        .sign-line {
            width: 100%;
            height: 1px;
            background: #1a365d;
            margin-bottom: 5px;
        }

        .signature-name {
            font-weight: 500;
            color: #1a365d;
            margin: 5px 0;
        }

        .signature-title {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 12px;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            color: rgba(40, 82, 141, 0.22);
            font-size: 100px;
            font-weight: bold;
            white-space: nowrap;
            pointer-events: none;
        }
        .seal {
            position: absolute;
            right: 40px;
            top: 20px;
            width: 120px;
            height: 120px;
            z-index: 2;
            font-weight:900;
        }

        /* .seal-inner {
            width: 100%;
            height: 100%;
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        } */
    </style>
</head>
<body>
<div class="certificate">
    <div class="inner-border"></div>
    <div class="watermark">COBUILD</div>
    
    <div class="header">
        <div class="logo-placeholder">CB</div>
        <h1 class="title">Certificate of Intention</h1>
        <div class="subtitle">This document certifies that</div>
    </div>

    <div class="recipient">
        <div class="recipient-name">{{INVESTOR_NAME}}</div>
        <div>Has an intention to invest in the project below</div>
    </div>

    <div class="details-grid">
        <div class="detail-item">
            <div class="detail-label">Project Name</div>
            <div class="detail-value">{{PROJECT_NAME}}</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Investment Type</div>
            <div class="detail-value">{{INVESTMENT_TYPE}}</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Investment Amount</div>
            <div class="detail-value">${{INVESTMENT_AMOUNT}}</div>
        </div>
        <!-- Uncomment if needed -->
        <!-- <div class="detail-item">
            <div class="detail-label">Skills/Services</div>
            <div class="detail-value">{{SKILLS_SERVICES}}</div>
        </div> -->
        <!-- <div class="detail-item">
            <div class="detail-label">Additional Details</div>
            <div class="detail-value">{{INVESTMENT_DETAILS}}</div>
        </div> -->
    </div>

    <div class="signatures" style="display:flex; justify-content:space-between;">
        <div class="signature">
            <div class="signature-name">Segun Oke</div>
            <div class="signature-title">Trustee</div>
        </div>
        <div class="signature">
            <div class="signature-name">Adenike Akanji</div>
            <div class="signature-title">Trustee / Secretary</div>
        </div>
    </div>

    <div class="footer">
        Issue Date: {{DATE}} | Certificate ID: {{CERTIFICATE_ID}}
    </div>
</div>




    </body>
</html>