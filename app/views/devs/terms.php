<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Simplified Terms of Service</title>
  <style>
    :root{
      --page-green: #2f7a4a;    /* page background */
      --panel-bg: rgba(255,255,255,0.85);
      --text-color: #17202a;
      --accent: #111;
    }

    html,body{
      height:100%;
      margin:0;
      font-family: "Georgia", "Times New Roman", serif;
      background: linear-gradient(180deg, var(--page-green) 0%, #79b48a 100%);
      color:var(--text-color);
    }

    .wrap{
      min-height:100%;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:40px 20px;
      box-sizing:border-box;
    }

    /* outer pale panel */
    .panel{
      width:100%;
      max-width:760px;
      background: var(--panel-bg);
      padding: 28px;
      box-sizing:border-box;
      border-radius:6px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.18);
      position:relative;
      /* inner purple border simulated by pseudo-element */
    }

    .panel::before{
      content:"";
      position:absolute;
      inset:12px;
      border:4px solid var(--inner-border);
      pointer-events:none;
      border-radius:3px;
      box-sizing:border-box;
    }

    /* content sits above the pseudo-border */
    .content{
      position:relative;
      z-index:1;
      padding: 8px 18px;
    }

    h1{
      font-size:20px;
      margin:0 0 14px 0;
      text-align:center;
      font-weight:700;
      letter-spacing:0.6px;
    }

    ol{
      margin:0;
      padding-left:1.05rem;
    }

    li{
      margin: 10px 0;
      line-height:1.3;
      font-weight:700;
      font-size:16px;
      text-align:center;
    }

    /* smaller sub-lines (e.g., explanations) */
    li span{
      display:block;
      font-weight:400;
      font-size:14px;
      margin-top:6px;
    }

    .note{
      margin-top:18px;
      font-size:13px;
      font-weight:600;
      text-align:center;
      line-height:1.25;
    }

    /* responsive adjustments */
    @media (max-width:480px){
      .panel{ padding:18px; }
      .panel::before{ inset:10px; border-width:3px; }
      h1{ font-size:18px; }
      li{ font-size:15px; }
      li span{ font-size:13px; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <section class="panel" aria-labelledby="terms-heading">
      <div class="content">
        <h1 id="terms-heading">Simplified Terms of Service</h1>

        <ol>
          <li><strong>Agreement</strong>
            <span>By using the service, you agree to these rules.</span>
          </li>

          <li><strong>Your Account</strong>
            <span>You are responsible for your account and its activity. We can suspend accounts that break the rules.</span>
          </li>

          <li><strong>What You Can't Do</strong>
            <span>Use the service for anything illegal or harmful; harass others, spam, or upload viruses; steal our content, code, or trademarks.</span>
          </li>

          <li><strong>Your Content</strong>
            <span>You own your content, but you give us a license to host and display it. We can remove any content that violates these terms.</span>
          </li>

          <li><strong>Our Rights</strong>
            <span>We own the service and its core content. We can change these terms or the service at any time.</span>
          </li>

          <li><strong>Disclaimer</strong>
            <span>The service is provided "as is." We are not liable for issues or damages from its use.</span>
          </li>

          <li><strong>Privacy</strong>
            <span>Your data is handled as described in our separate Privacy Policy.</span>
          </li>

          <li><strong>Governing Law</strong>
            <span>These terms are governed by the laws of the Republic of the Philippines.</span>
          </li>
        </ol>

        <p class="note">
          Note: This is a simplified checklist. For a legally enforceable Terms of Service (especially for a business in the Philippines), consulting a lawyer is strongly advised.
        </p>
      </div>
    </section>
  </div>
</body>
</html>